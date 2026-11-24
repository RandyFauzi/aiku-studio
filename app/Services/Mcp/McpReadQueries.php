<?php

namespace App\Services\Mcp;

use App\Models\AdminNote;
use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\McpToolCall;
use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Models\RecordStreakDay;
use App\Models\ShiftDay;
use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorPerformanceService;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use App\Services\ShiftConfiguration;
use App\Services\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class McpReadQueries
{
    public function __construct(
        private readonly McpAccess $access,
        private readonly PayrollPeriod $payrollPeriod,
        private readonly PayrollCalculator $payrollCalculator,
        private readonly ShiftSchedule $shiftSchedule,
        private readonly ShiftConfiguration $shiftConfig,
        private readonly ContributorPerformanceService $performanceService,
    ) {}

    public function timezone(): string
    {
        return (string) config('aiku_mcp.timezone', 'Asia/Makassar');
    }

    /**
     * @return array{start: string, end: string}
     */
    public function resolveDateRange(?string $startDate, ?string $endDate, int $defaultDays = 14): array
    {
        $tz = $this->timezone();
        $today = CarbonImmutable::now($tz)->startOfDay();

        $end = $endDate !== null && $endDate !== ''
            ? $this->parseDate($endDate, 'end_date')
            : $today;

        $start = $startDate !== null && $startDate !== ''
            ? $this->parseDate($startDate, 'start_date')
            : $end->subDays(max(0, $defaultDays - 1));

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => 'start_date tidak boleh lebih besar dari end_date.',
            ]);
        }

        $maxDays = (int) config('aiku_mcp.max_date_range_days', 90);
        if ($start->diffInDays($end) + 1 > $maxDays) {
            throw ValidationException::withMessages([
                'end_date' => "Rentang tanggal maksimal {$maxDays} hari.",
            ]);
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    public function clampLimit(?int $limit): int
    {
        $default = (int) config('aiku_mcp.default_limit', 25);
        $max = (int) config('aiku_mcp.max_limit', 100);

        return max(1, min($limit ?? $default, $max));
    }

    private function parseDate(string $value, string $field): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $this->timezone());
        if (! $date || $date->toDateString() !== $value) {
            throw ValidationException::withMessages([
                $field => "Format {$field} harus YYYY-MM-DD.",
            ]);
        }

        return $date;
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toDateString();
        }

        return (string) $value;
    }

    private function formatDateTimeWita(mixed $value, string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->setTimezone($this->timezone())->format($format);
        }

        return CarbonImmutable::parse((string) $value)->setTimezone($this->timezone())->format($format);
    }

    private function maskString(?string $value, int $keepStart = 2, int $keepEnd = 2): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $len = mb_strlen($value);
        if ($len <= $keepStart + $keepEnd) {
            return str_repeat('*', $len);
        }

        return mb_substr($value, 0, $keepStart)
            .str_repeat('*', max(3, $len - $keepStart - $keepEnd))
            .mb_substr($value, -$keepEnd);
    }

    public function context(User $user): array
    {
        $tz = $this->timezone();
        $now = CarbonImmutable::now($tz);
        $period = $this->payrollPeriod->cutoff($now);

        $contributor = $user->contributor_id !== null
            ? Contributor::find($user->contributor_id)
            : null;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'contributor_id' => $contributor?->id ?? $user->contributor_id,
                'contributor_name' => $contributor?->nama,
            ],
            'abilities' => $this->access->abilities($user),
            'timezone' => $tz,
            'current_time_wita' => $now->toIso8601String(),
            'active_payroll_period' => $period,
        ];
    }

    public function payrollPeriodInfo(?string $referenceDate = null, string $direction = 'current'): array
    {
        $ref = $referenceDate !== null && $referenceDate !== ''
            ? $this->parseDate($referenceDate, 'date')
            : CarbonImmutable::now($this->timezone());

        $period = $this->payrollPeriod->cutoff($ref);
        if ($direction === 'previous') {
            $period = $this->payrollPeriod->previous($period);
        } elseif ($direction === 'next') {
            $period = $this->payrollPeriod->next($period);
        }

        return [
            'period' => $period,
            'direction' => $direction,
            'reference_date' => $ref->toDateString(),
        ];
    }

    public function listAttendances(
        User $user,
        ?string $startDate,
        ?string $endDate,
        ?int $contributorId,
        ?string $method,
        ?int $limit,
    ): array {
        $range = $this->resolveDateRange($startDate, $endDate, 14);
        $limit = $this->clampLimit($limit);
        $isAdmin = $this->access->isAdmin($user);

        $query = Attendance::query()
            ->whereBetween('work_date', [$range['start'], $range['end']])
            ->orderByDesc('work_date')
            ->orderByDesc('checked_in_at');

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($contributorId !== null) {
            $query->where('contributor_id', $contributorId);
        }

        if ($method !== null && $method !== '') {
            $query->where('checkin_method', $method);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->get();

        // Never expose raw GPS coordinates (latitude/longitude/accuracy_m/client_ip).
        $items = $rows->map(fn (Attendance $row) => [
            'id' => $row->id,
            'work_date' => $this->formatDate($row->work_date),
            'contributor_id' => $row->contributor_id,
            'contributor_name' => $row->contributor_name,
            'checked_in_at_wita' => $this->formatDateTimeWita($row->checked_in_at),
            'checkin_method' => $row->checkin_method,
            'wifi_verified' => (bool) $row->wifi_verified,
        ])->values()->all();

        return [
            'range' => $range,
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function attendanceSummary(User $user, ?string $startDate, ?string $endDate, ?int $contributorId): array
    {
        $range = $this->resolveDateRange($startDate, $endDate, 30);
        $isAdmin = $this->access->isAdmin($user);

        $query = Attendance::query()
            ->whereBetween('work_date', [$range['start'], $range['end']]);

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($contributorId !== null) {
            $query->where('contributor_id', $contributorId);
        }

        $rows = $query->get(['contributor_id', 'contributor_name', 'work_date', 'checkin_method', 'wifi_verified']);

        $byMethod = [];
        $byContributor = [];
        foreach ($rows as $row) {
            $m = (string) ($row->checkin_method ?: 'unknown');
            $byMethod[$m] = ($byMethod[$m] ?? 0) + 1;

            $cKey = (string) $row->contributor_id;
            if (! isset($byContributor[$cKey])) {
                $byContributor[$cKey] = [
                    'contributor_id' => $row->contributor_id,
                    'contributor_name' => $row->contributor_name,
                    'days_present' => 0,
                ];
            }
            $byContributor[$cKey]['days_present']++;
        }

        return [
            'range' => $range,
            'total_checkins' => $rows->count(),
            'unique_days' => $rows->pluck('work_date')->unique()->count(),
            'by_method' => $byMethod,
            'by_contributor' => array_values($byContributor),
        ];
    }

    public function shiftSchedule(User $user, ?string $month, ?int $contributorId): array
    {
        $tz = $this->timezone();
        $targetMonth = $month !== null && $month !== ''
            ? $month
            : CarbonImmutable::now($tz)->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            throw ValidationException::withMessages(['month' => 'Format month harus YYYY-MM.']);
        }

        $isAdmin = $this->access->isAdmin($user);

        if (! $isAdmin) {
            $person = $this->access->resolveContributor($user);
            if (! $person) {
                return ['month' => $targetMonth, 'schedules' => []];
            }

            return [
                'month' => $targetMonth,
                'contributor' => ['id' => $person->id, 'nama' => $person->nama],
                'schedule' => $this->shiftSchedule->forContributor($person, $targetMonth),
            ];
        }

        if ($contributorId !== null) {
            $person = Contributor::findOrFail($contributorId);

            return [
                'month' => $targetMonth,
                'contributor' => ['id' => $person->id, 'nama' => $person->nama],
                'schedule' => $this->shiftSchedule->forContributor($person, $targetMonth),
            ];
        }

        $start = CarbonImmutable::createFromFormat('!Y-m', $targetMonth, $tz)->startOfMonth();
        $end = $start->endOfMonth();

        $overrides = ShiftDay::whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->map(fn (ShiftDay $day) => [
                'work_date' => $day->work_date,
                'revision' => $day->revision,
                'config' => $day->config,
            ])
            ->values()
            ->all();

        $routineRaw = ShiftSetting::where('setting_key', 'routine_shift')->value('setting_value');

        return [
            'month' => $targetMonth,
            'routine_config' => $routineRaw ? json_decode($routineRaw, true) : null,
            'date_overrides_count' => count($overrides),
            'date_overrides' => array_slice($overrides, 0, 31),
        ];
    }

    public function listDailyReports(
        User $user,
        ?string $startDate,
        ?string $endDate,
        ?int $contributorId,
        ?bool $validatedOnly,
        ?int $limit,
    ): array {
        $range = $this->resolveDateRange($startDate, $endDate, 14);
        $limit = $this->clampLimit($limit);
        $isAdmin = $this->access->isAdmin($user);

        $query = DailyWorkReport::query()
            ->whereBetween('work_date', [$range['start'], $range['end']])
            ->orderByDesc('work_date')
            ->orderByDesc('id');

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($contributorId !== null) {
            $query->where('contributor_id', $contributorId);
        }

        if ($validatedOnly !== null) {
            $query->where('is_validated', $validatedOnly);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->get();

        $items = $rows->map(fn (DailyWorkReport $r) => [
            'id' => $r->id,
            'work_date' => (string) $r->work_date,
            'contributor_id' => $r->contributor_id,
            'contributor_name' => $r->contributor_name,
            'shift_label' => $r->shift_label,
            'total_duration_minutes' => (int) $r->total_duration_minutes,
            'phones' => $r->phones ?? [],
            'phone_durations' => $r->phone_durations ?? [],
            'is_validated' => (bool) $r->is_validated,
            'revision' => (int) $r->revision,
            'notes' => $r->notes ? Str::limit($r->notes, 160) : null,
        ])->values()->all();

        return [
            'range' => $range,
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function dailyReportSummary(User $user, ?string $startDate, ?string $endDate, ?int $contributorId): array
    {
        $range = $this->resolveDateRange($startDate, $endDate, 30);
        $isAdmin = $this->access->isAdmin($user);

        $query = DailyWorkReport::query()
            ->whereBetween('work_date', [$range['start'], $range['end']]);

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($contributorId !== null) {
            $query->where('contributor_id', $contributorId);
        }

        $rows = $query->get(['total_duration_minutes', 'phone_durations', 'is_validated', 'contributor_id', 'contributor_name']);

        $totalMinutes = 0;
        $validatedCount = 0;
        $byPhone = [];

        foreach ($rows as $row) {
            $totalMinutes += (int) $row->total_duration_minutes;
            if ($row->is_validated) {
                $validatedCount++;
            }
            foreach (($row->phone_durations ?? []) as $phone => $mins) {
                $byPhone[$phone] = ($byPhone[$phone] ?? 0) + (int) $mins;
            }
        }

        return [
            'range' => $range,
            'reports_count' => $rows->count(),
            'validated_count' => $validatedCount,
            'total_logged_minutes' => $totalMinutes,
            'total_logged_hours' => round($totalMinutes / 60, 2),
            'minutes_by_phone' => $byPhone,
        ];
    }

    public function listRecordings(
        User $user,
        ?string $startDate,
        ?string $endDate,
        ?int $contributorId,
        ?string $billableStatus,
        ?int $limit,
    ): array {
        $range = $this->resolveDateRange($startDate, $endDate, 14);
        $limit = $this->clampLimit($limit);
        $isAdmin = $this->access->isAdmin($user);

        $startUtc = CarbonImmutable::createFromFormat('!Y-m-d', $range['start'], $this->timezone())->startOfDay()->utc();
        $endUtc = CarbonImmutable::createFromFormat('!Y-m-d', $range['end'], $this->timezone())->endOfDay()->utc();

        $query = StudioRecording::query()
            ->whereBetween('recorded_at', [$startUtc, $endUtc])
            ->orderByDesc('recorded_at');

        if (! $isAdmin) {
            $person = $this->access->resolveContributor($user);
            if (! $person) {
                return ['range' => $range, 'total_matched' => 0, 'returned' => 0, 'items' => []];
            }
            $query->where('assigned_contributor_id', $person->id);
        } elseif ($contributorId !== null) {
            $query->where('assigned_contributor_id', $contributorId);
        }

        if ($billableStatus !== null && $billableStatus !== '') {
            $query->where('billable_status', $billableStatus);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->get();

        $items = $rows->map(fn (StudioRecording $rec) => [
            'record_id' => $rec->record_id,
            'task_title' => $rec->task_title,
            'recorded_at_wita' => $rec->recorded_at?->setTimezone($this->timezone())->format('Y-m-d H:i:s'),
            'duration_min' => round((float) $rec->duration_min, 2),
            'billable_min' => round((float) $rec->billable_min, 2),
            'rejected_min' => round((float) $rec->rejected_min, 2),
            'pending_min' => round((float) $rec->pending_min, 2),
            'processing_status' => $rec->processing_status,
            'billable_status' => $rec->billable_status,
            'assigned_contributor_id' => $rec->assigned_contributor_id,
            'assigned_worker_name' => $rec->assigned_worker_name,
            'manual_payment_status' => $rec->manual_payment_status,
        ])->values()->all();

        return [
            'range' => $range,
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function recordingSummary(User $user, ?string $startDate, ?string $endDate, ?int $contributorId): array
    {
        $range = $this->resolveDateRange($startDate, $endDate, 30);
        $isAdmin = $this->access->isAdmin($user);

        $startUtc = CarbonImmutable::createFromFormat('!Y-m-d', $range['start'], $this->timezone())->startOfDay()->utc();
        $endUtc = CarbonImmutable::createFromFormat('!Y-m-d', $range['end'], $this->timezone())->endOfDay()->utc();

        $query = StudioRecording::query()
            ->whereBetween('recorded_at', [$startUtc, $endUtc]);

        if (! $isAdmin) {
            $person = $this->access->resolveContributor($user);
            if (! $person) {
                return ['range' => $range, 'count' => 0];
            }
            $query->where('assigned_contributor_id', $person->id);
        } elseif ($contributorId !== null) {
            $query->where('assigned_contributor_id', $contributorId);
        }

        $agg = $query->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(duration_min), 0) as duration_min,
            COALESCE(SUM(billable_min), 0) as billable_min,
            COALESCE(SUM(rejected_min), 0) as rejected_min,
            COALESCE(SUM(pending_min), 0) as pending_min,
            COALESCE(SUM(deferred_min), 0) as deferred_min
        ')->first();

        return [
            'range' => $range,
            'total_recordings' => (int) ($agg->total_count ?? 0),
            'duration_min' => round((float) ($agg->duration_min ?? 0), 2),
            'billable_min' => round((float) ($agg->billable_min ?? 0), 2),
            'rejected_min' => round((float) ($agg->rejected_min ?? 0), 2),
            'pending_min' => round((float) ($agg->pending_min ?? 0), 2),
            'deferred_min' => round((float) ($agg->deferred_min ?? 0), 2),
        ];
    }

    public function streakSummary(User $user, ?string $startDate, ?string $endDate, ?int $contributorId): array
    {
        $range = $this->resolveDateRange($startDate, $endDate, 14);
        $isAdmin = $this->access->isAdmin($user);

        $query = RecordStreakDay::query()
            ->whereBetween('work_date', [$range['start'], $range['end']])
            ->orderByDesc('work_date');

        if (! $isAdmin) {
            $person = $this->access->resolveContributor($user);
            if (! $person) {
                return ['range' => $range, 'items' => []];
            }
            $query->where('contributor_id', $person->id);
        } elseif ($contributorId !== null) {
            $query->where('contributor_id', $contributorId);
        }

        $rows = $query->limit(50)->get();

        $byStatus = [];
        $items = $rows->map(function (RecordStreakDay $row) use (&$byStatus) {
            $st = (string) $row->status;
            $byStatus[$st] = ($byStatus[$st] ?? 0) + 1;

            return [
                'id' => $row->id,
                'contributor_id' => $row->contributor_id,
                'work_date' => (string) $row->work_date,
                'target_minutes' => (int) $row->target_minutes,
                'verified_minutes' => round((float) $row->verified_minutes, 2),
                'status' => $st,
                'reason' => $row->reason,
                'deadline_at_wita' => $row->deadline_at?->setTimezone($this->timezone())->format('Y-m-d H:i:s'),
            ];
        })->values()->all();

        return [
            'range' => $range,
            'by_status' => $byStatus,
            'items' => $items,
        ];
    }

    public function studioDevices(User $user): array
    {
        $isAdmin = $this->access->isAdmin($user);
        $phones = DailyWorkReport::phoneOptions();
        if ($phones === []) {
            $phones = DailyWorkReport::PHONES;
        }

        $accounts = DeviceAccount::query()->orderBy('phone_key')->get();
        $grouped = $accounts->groupBy('phone_key');

        $items = [];
        foreach ($phones as $key => $label) {
            $list = $grouped->get($key, collect());
            $entry = [
                'phone_key' => $key,
                'label' => $label,
                'total_accounts' => $list->count(),
                'active_accounts' => $list->where('is_active', true)->count(),
            ];

            if ($isAdmin) {
                $entry['accounts'] = $list->map(fn (DeviceAccount $acc) => [
                    'id' => $acc->id,
                    'account_name' => $acc->account_name,
                    'email_masked' => $this->maskString($acc->email, 3, 6),
                    'is_active' => (bool) $acc->is_active,
                    'kamerakita_status' => $acc->kamerakita_status,
                ])->values()->all();
            }

            $items[] = $entry;
        }

        return [
            'total_devices' => count($items),
            'devices' => $items,
        ];
    }

    public function getMyPayslip(User $user, ?string $periodLabel, ?string $workerName): array
    {
        $isAdmin = $this->access->isAdmin($user);
        $targetName = $isAdmin && $workerName !== null && $workerName !== ''
            ? $workerName
            : $this->access->resolvePayrollName($user);

        $query = Payslip::query()
            ->where('employee_name', $targetName)
            ->whereNull('superseded_by_id')
            ->orderByDesc('id');

        if ($periodLabel !== null && $periodLabel !== '') {
            $query->where('period', $periodLabel);
        }

        $slip = $query->first();
        if (! $slip) {
            return [
                'found' => false,
                'employee_name' => $targetName,
                'period' => $periodLabel,
                'payslip' => null,
            ];
        }

        return [
            'found' => true,
            'employee_name' => $targetName,
            'payslip' => $this->formatPayslip($slip),
        ];
    }

    public function listPayslips(User $user, ?string $periodLabel, ?string $workerName, ?int $limit): array
    {
        $limit = $this->clampLimit($limit);
        $isAdmin = $this->access->isAdmin($user);

        $query = Payslip::query()
            ->whereNull('superseded_by_id')
            ->orderByDesc('id');

        if (! $isAdmin) {
            $targetName = $this->access->resolvePayrollName($user);
            $query->where('employee_name', $targetName);
        } elseif ($workerName !== null && $workerName !== '') {
            $query->where('employee_name', $workerName);
        }

        if ($periodLabel !== null && $periodLabel !== '') {
            $query->where('period', $periodLabel);
        }

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get()->map(fn (Payslip $slip) => $this->formatPayslip($slip))->values()->all();

        return [
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    private function formatPayslip(Payslip $slip): array
    {
        return [
            'id' => $slip->id,
            'employee_name' => $slip->employee_name,
            'period' => $slip->period,
            'total_logged_hours' => round((float) $slip->total_logged_hours, 2),
            'total_approved_hours' => round((float) $slip->total_approved_hours, 2),
            'total_thp' => (int) $slip->total_thp,
            'bonus' => (int) $slip->bonus,
            'pending_carry_over_amount' => (int) $slip->pending_carry_over_amount,
            'pending_carry_over_hours' => round((float) $slip->pending_carry_over_hours, 2),
            'status' => $slip->status,
            'is_locked' => (bool) $slip->is_locked,
            'created_at' => $slip->created_at?->toIso8601String(),
        ];
    }

    public function listPayrollStatuses(User $user, ?string $periodLabel, ?string $status, ?int $limit): array
    {
        $limit = $this->clampLimit($limit);
        $isAdmin = $this->access->isAdmin($user);

        $period = $periodLabel !== null && $periodLabel !== ''
            ? $periodLabel
            : $this->payrollPeriod->cutoff()['label'];

        $query = PayrollStatus::query()->where('period', $period)->orderBy('worker_name');

        if (! $isAdmin) {
            $query->where('worker_name', $this->access->resolvePayrollName($user));
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get()->map(fn (PayrollStatus $row) => [
            'id' => $row->id,
            'worker_name' => $row->worker_name,
            'period' => $row->period,
            'status' => $row->status,
            'notes' => $row->notes,
            'updated_at' => $row->updated_at?->toIso8601String(),
        ])->values()->all();

        return [
            'period' => $period,
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function calculateLivePayroll(?string $referenceDate, ?string $workerName): array
    {
        $ref = $referenceDate !== null && $referenceDate !== ''
            ? $this->parseDate($referenceDate, 'date')
            : CarbonImmutable::now($this->timezone());

        $period = $this->payrollPeriod->cutoff($ref);

        if ($workerName !== null && $workerName !== '') {
            $live = $this->payrollCalculator->liveSlip($period, $workerName);

            return [
                'period' => $period,
                'mode' => 'single_worker',
                'worker' => $workerName,
                'result' => $live,
            ];
        }

        $summary = $this->payrollCalculator->summary($period);

        return [
            'period' => $period,
            'mode' => 'studio_summary',
            'summary' => $summary,
        ];
    }

    public function listContributors(?string $role, ?bool $activeOnly, ?int $limit): array
    {
        $limit = $this->clampLimit($limit);

        $query = Contributor::query()
            ->leftJoin('users', 'users.contributor_id', '=', 'contributors.id')
            ->select([
                'contributors.id',
                'contributors.nama',
                'contributors.domisili',
                'contributors.shift',
                'contributors.hp',
                'contributors.role',
                'contributors.no_wa',
                'contributors.no_rek',
                'users.id as user_id',
                'users.is_active as user_is_active',
            ])
            ->orderBy('contributors.nama');

        if ($role !== null && $role !== '') {
            $query->where('contributors.role', $role);
        }

        if ($activeOnly === true) {
            $query->where('users.is_active', true);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->get();

        // Redact sensitive personal fields (no_wa, no_rek).
        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama' => $row->nama,
            'role' => $row->role,
            'shift' => $row->shift,
            'hp' => $row->hp,
            'domisili' => $row->domisili,
            'no_wa_masked' => $this->maskString($row->no_wa, 3, 3),
            'no_rek_masked' => $this->maskString($row->no_rek, 2, 3),
            'user_id' => $row->user_id ? (int) $row->user_id : null,
            'user_is_active' => $row->user_is_active !== null ? (bool) $row->user_is_active : null,
        ])->values()->all();

        return [
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function contributorPerformance(?string $startDate, ?string $endDate, ?int $contributorId): array
    {
        $range = $this->resolveDateRange($startDate, $endDate, 30);

        $query = Contributor::query()->orderBy('nama');
        if ($contributorId !== null) {
            $query->whereKey($contributorId);
        }
        $contributors = $query->limit(50)->get();

        $startUtc = CarbonImmutable::createFromFormat('!Y-m-d', $range['start'], $this->timezone())->startOfDay()->utc();
        $endUtc = CarbonImmutable::createFromFormat('!Y-m-d', $range['end'], $this->timezone())->endOfDay()->utc();

        $attendanceCounts = Attendance::whereBetween('work_date', [$range['start'], $range['end']])
            ->selectRaw('contributor_id, COUNT(*) as days_present')
            ->groupBy('contributor_id')
            ->pluck('days_present', 'contributor_id');

        $reportMinutes = DailyWorkReport::whereBetween('work_date', [$range['start'], $range['end']])
            ->selectRaw('contributor_id, SUM(total_duration_minutes) as total_mins, COUNT(*) as reports_count')
            ->groupBy('contributor_id')
            ->get()
            ->keyBy('contributor_id');

        $recStats = StudioRecording::whereBetween('recorded_at', [$startUtc, $endUtc])
            ->whereNotNull('assigned_contributor_id')
            ->selectRaw('assigned_contributor_id, SUM(billable_min) as billable_min, SUM(rejected_min) as rejected_min, COUNT(*) as rec_count')
            ->groupBy('assigned_contributor_id')
            ->get()
            ->keyBy('assigned_contributor_id');

        $items = $contributors->map(function (Contributor $c) use ($attendanceCounts, $reportMinutes, $recStats) {
            $rep = $reportMinutes->get($c->id);
            $rec = $recStats->get($c->id);

            return [
                'contributor_id' => $c->id,
                'nama' => $c->nama,
                'days_present' => (int) ($attendanceCounts[$c->id] ?? 0),
                'reports_count' => (int) ($rep->reports_count ?? 0),
                'reported_minutes' => (int) ($rep->total_mins ?? 0),
                'recordings_count' => (int) ($rec->rec_count ?? 0),
                'billable_min' => round((float) ($rec->billable_min ?? 0), 2),
                'rejected_min' => round((float) ($rec->rejected_min ?? 0), 2),
            ];
        })->values()->all();

        return [
            'range' => $range,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function listAdminNotes(?string $noteDate, ?bool $pinnedOnly, ?bool $completed, ?int $limit): array
    {
        $limit = $this->clampLimit($limit);

        $query = AdminNote::query()
            ->orderByDesc('is_pinned')
            ->orderByDesc('note_date')
            ->orderByDesc('id');

        if ($noteDate !== null && $noteDate !== '') {
            $this->parseDate($noteDate, 'note_date');
            $query->whereDate('note_date', $noteDate);
        }
        if ($pinnedOnly !== null) {
            $query->where('is_pinned', $pinnedOnly);
        }
        if ($completed !== null) {
            $query->where('is_completed', $completed);
        }

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get()->map(fn (AdminNote $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'content' => $n->content ? Str::limit($n->content, 300) : null,
            'type' => $n->type,
            'priority' => $n->priority,
            'note_date' => $n->note_date?->toDateString() ?? (string) $n->note_date,
            'is_pinned' => (bool) $n->is_pinned,
            'is_completed' => (bool) $n->is_completed,
            'checklist_count' => is_array($n->checklist_items) ? count($n->checklist_items) : 0,
        ])->values()->all();

        return [
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function listTaskTranslations(?string $search, ?int $limit): array
    {
        $limit = $this->clampLimit($limit);

        $query = DB::table('report_task_translations')->orderBy('label');
        if ($search !== null && $search !== '') {
            $like = '%'.strtolower(trim($search)).'%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(source_title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(label) LIKE ?', [$like]);
            });
        }

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get()->map(fn ($row) => [
            'normalized_key' => $row->normalized_key,
            'source_title' => $row->source_title,
            'label' => $row->label,
            'keywords' => json_decode((string) $row->keywords, true) ?? [],
        ])->values()->all();

        return [
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }

    public function syncHealth(): array
    {
        $lastAtlas = StudioRecording::max('atlas_synced_at');
        $lastRobotics = StudioRecording::max('robotics_1618_synced_at');
        $lastGeneral = StudioRecording::max('last_synced_at');

        $accounts = StudioAccount::query()->get(['id', 'name', 'active', 'updated_at']);

        return [
            'last_atlas_synced_at' => $lastAtlas,
            'last_robotics_1618_synced_at' => $lastRobotics,
            'last_general_synced_at' => $lastGeneral,
            'total_recordings' => StudioRecording::count(),
            'unassigned_recordings' => StudioRecording::whereNull('assigned_contributor_id')->count(),
            'studio_accounts' => [
                'total' => $accounts->count(),
                'active' => $accounts->where('active', true)->count(),
                'inactive' => $accounts->where('active', false)->count(),
            ],
        ];
    }

    public function dataAuditSummary(): array
    {
        $duplicatePayrollNames = Contributor::query()
            ->selectRaw('LOWER(TRIM(nama)) as norm_name, COUNT(*) as cnt')
            ->whereNotNull('nama')
            ->groupBy('norm_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cnt', 'norm_name')
            ->all();

        $usersWithoutContributor = User::where('role', 'contributor')
            ->whereNull('contributor_id')
            ->count();

        $inactiveUsers = User::where('is_active', false)->count();

        $unassignedRecordings = StudioRecording::whereNull('assigned_contributor_id')->count();

        $unvalidatedReports = DailyWorkReport::where('is_validated', false)->count();

        return [
            'duplicate_payroll_names_count' => count($duplicatePayrollNames),
            'duplicate_payroll_names' => array_keys($duplicatePayrollNames),
            'contributor_users_without_link' => $usersWithoutContributor,
            'inactive_users_count' => $inactiveUsers,
            'unassigned_recordings_count' => $unassignedRecordings,
            'unvalidated_daily_reports_count' => $unvalidatedReports,
        ];
    }

    public function listMcpAuditLogs(?string $toolName, ?string $outcome, ?int $limit): array
    {
        $limit = $this->clampLimit($limit);

        $query = McpToolCall::query()->orderByDesc('id');
        if ($toolName !== null && $toolName !== '') {
            $query->where('tool_name', $toolName);
        }
        if ($outcome !== null && $outcome !== '') {
            $query->where('outcome', $outcome);
        }

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get()->map(fn (McpToolCall $log) => [
            'id' => $log->id,
            'trace_id' => $log->trace_id,
            'user_id' => $log->user_id,
            'role' => $log->role,
            'method' => $log->method,
            'tool_name' => $log->tool_name,
            'outcome' => $log->outcome,
            'http_status' => $log->http_status,
            'latency_ms' => $log->latency_ms,
            'created_at' => $log->created_at?->setTimezone($this->timezone())->format('Y-m-d H:i:s'),
        ])->values()->all();

        return [
            'total_matched' => $total,
            'returned' => count($items),
            'items' => $items,
        ];
    }
}
