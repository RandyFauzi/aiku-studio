<?php

namespace App\Services;

use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Models\StudioRecording;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PayrollCalculator
{
    public function recordings(array $period, ?string $worker = null): Builder
    {
        return StudioRecording::query()->whereBetween('recorded_at', [$period['startUtc'], $period['endUtc']])
            ->when($worker !== null, fn ($q) => $q->where('assigned_worker_name', $worker));
    }

    public function calculate(array $evidence, int $bonus = 0, int $carryOverAmount = 0, ?array $carryOverData = null): array
    {
        $logged = 0;
        $approved = 0;
        foreach ($evidence as &$day) {
            if (! empty($day['tasks'])) {
                foreach (['approved_mins', 'rejected_mins', 'pending_mins', 'total_video_mins'] as $key) {
                    $day[$key] = round(array_sum(array_map(function ($task) use ($key) {
                        return $key === 'total_video_mins' ? $this->duration($task) : (float) ($task[$key] ?? 0);
                    }, $day['tasks'])), 2);
                }
            }
            $logged += $this->duration($day);
            $approved += (float) ($day['approved_mins'] ?? 0);
        }
        unset($day);

        $base = (int) round($approved / 60 * config('payroll.hourly_rate'));
        $carryOverHours = 0;
        if (! empty($carryOverData) && is_array($carryOverData)) {
            $carryOverHours = round(array_sum(array_column($carryOverData, 'delta_min')) / 60, 2);
        }

        return [
            'total_logged_hours' => $logged / 60,
            'total_approved_hours' => $approved / 60,
            'base_thp' => $base,
            'bonus' => $bonus,
            'pending_carry_over_amount' => $carryOverAmount,
            'pending_carry_over_hours' => $carryOverHours,
            'pending_carry_over_data' => $carryOverData,
            'total_thp' => $base + $bonus + $carryOverAmount,
            'evidence_data' => $evidence,
        ];
    }

    private function duration(array $item): float
    {
        return (float) ($item['total_video_mins'] ?? 0) ?: (float) ($item['approved_mins'] ?? 0) + (float) ($item['rejected_mins'] ?? 0) + (float) ($item['pending_mins'] ?? 0);
    }

    public function evidence(array $period, string $worker): array
    {
        $days = [];
        foreach ($this->recordings($period, $worker)->orderByDesc('recorded_at')->get() as $r) {
            $local = CarbonImmutable::parse($r->getRawOriginal('recorded_at'), 'UTC')->setTimezone('Asia/Makassar');
            $date = $local->toDateString();
            $days[$date] ??= ['date' => $date, 'approved_mins' => 0, 'rejected_mins' => 0, 'pending_mins' => 0, 'total_video_mins' => 0, 'tasks' => []];
            $task = ['task_name' => $r->task_title ?: 'Rekaman Atlas', 'time_str' => $local->format('H:i').' WITA',
                'approved_mins' => $r->billable_min, 'rejected_mins' => $r->rejected_min, 'pending_mins' => $r->pending_min,
                'total_video_mins' => $r->duration_min ?: $r->billable_min + $r->rejected_min + $r->pending_min, 'notes' => $r->record_id];
            foreach (['approved_mins', 'rejected_mins', 'pending_mins', 'total_video_mins'] as $key) {
                $days[$date][$key] += $task[$key];
            }
            $days[$date]['tasks'][] = $task;
        }

        return array_values($days);
    }

    public function liveSlip(array $period, string $worker): array
    {
        // Never borrow a bonus or payment status from another period.
        $slip = Payslip::where('employee_name', $worker)->where('period', $period['name'])->latest('id')->first();
        $evidence = $this->evidence($period, $worker);

        return array_merge($this->calculate($evidence, $slip?->bonus ?? 0), [
            'id' => $slip?->id ?? 'LIVE-'.preg_replace('/[^a-zA-Z0-9]/', '', $worker),
            'employee_name' => $worker, 'period' => $period['name'],
            'status' => PayrollStatus::where('worker_name', $worker)->where('period', $period['name'])->value('status') ?? $slip?->status ?? 'belum',
            'editUrl' => $slip ? '/create.html?id='.$slip->id : '/create.html?'.http_build_query(['worker' => $worker, 'periodId' => $period['id']]),
        ]);
    }

    public function displaySlip(Payslip $slip): array
    {
        $data = $slip->toArray();
        $data['editUrl'] = '/create.html?id='.$slip->id;

        // If the slip is saved in database with evidence or locked/sudah, retain frozen historical snapshot!
        if (! empty($slip->evidence_data) || $slip->is_locked || $slip->status === 'sudah') {
            return $data;
        }

        if (! str_contains(strtolower($slip->period), 'training')) {
            try {
                $period = app(PayrollPeriod::class)->resolve(['period' => $slip->period]);
                if ($this->recordings($period, $slip->employee_name)->exists()) {
                    return array_merge($data, $this->liveSlip($period, $slip->employee_name));
                }
            } catch (ValidationException) {
                // Historical custom labels retain their saved evidence.
            }
        }

        return $data;
    }

    public function summary(array $period): array
    {
        $groups = $this->recordings($period)->whereNotNull('assigned_worker_name')->whereRaw("TRIM(assigned_worker_name) != ''")
            ->whereNotIn('assigned_worker_name', ['???', '?', '-', 'Unknown', 'null', 'undefined'])
            ->selectRaw('assigned_worker_name, COUNT(*) as total_tasks, SUM(duration_min) as duration, SUM(billable_min) as approved, SUM(rejected_min) as rejected, SUM(pending_min) as pending')
            ->groupBy('assigned_worker_name')->orderByDesc('approved')->get();
        $slips = Payslip::where('period', $period['name'])->orderBy('id')->get()->keyBy('employee_name');
        $statuses = PayrollStatus::where('period', $period['name'])->pluck('status', 'worker_name');
        $workers = [];
        foreach ($groups as $group) {
            $name = $group->assigned_worker_name;
            $daily = [];
            foreach ($this->evidence($period, $name) as $day) {
                $a = $day['approved_mins'];
                $r = $day['rejected_mins'];
                $p = $day['pending_mins'];
                $daily[] = ['date' => $day['date'], 'formattedDate' => CarbonImmutable::parse($day['date'])->locale('id')->translatedFormat('l, j M Y'),
                    'tasks_count' => count($day['tasks']), 'approved_min' => $a, 'approved_hours' => round($a / 60, 2),
                    'rejected_min' => $r, 'rejected_hours' => round($r / 60, 2), 'pending_min' => $p, 'pending_hours' => round($p / 60, 2),
                    'total_min' => $day['total_video_mins'], 'total_hours' => round($day['total_video_mins'] / 60, 2),
                    'status' => $a > 0 && $r > 0 ? 'Disetujui sebagian' : ($a == 0 && $r > 0 ? 'Ditolak' : ($a == 0 && $p > 0 ? 'Sedang Ditinjau' : 'Disetujui penuh'))];
            }
            $slip = $slips->get($name);
            $carryOver = (int) ($slip?->pending_carry_over_amount ?? 0);
            $isLocked = $slip && (! empty($slip->evidence_data) || $slip->is_locked || $slip->status === 'sudah');

            $pendingBank = $this->detectPendingCarryOver($name, $period, $slip?->id);
            $availablePendingBalance = $pendingBank['available'] ? $pendingBank['total_amount'] : 0;
            $availablePendingHours = $pendingBank['available'] ? $pendingBank['total_hours'] : 0;

            if ($isLocked) {
                $base = (int) round($slip->total_approved_hours * config('payroll.hourly_rate'));
                $bonus = (int) ($slip->bonus ?? 0);
                $thp = (int) $slip->total_thp;
            } else {
                $base = (int) round((float) $group->approved / 60 * config('payroll.hourly_rate'));
                $bonus = (int) ($slip?->bonus ?? 0);
                $thp = $base + $bonus + $carryOver;
            }

            $workers[] = ['worker_name' => $name, 'total_tasks' => (int) $group->total_tasks,
                'approved_min' => round($group->approved, 2), 'approved_hours' => round($group->approved / 60, 2),
                'rejected_min' => round($group->rejected, 2), 'rejected_hours' => round($group->rejected / 60, 2),
                'pending_min' => round($group->pending, 2), 'pending_hours' => round($group->pending / 60, 2),
                'logged_hours' => round($group->duration / 60, 2), 'total_duration_min' => round($group->duration, 2),
                'base_thp' => $base, 'bonus' => $bonus, 'pending_carry_over_amount' => $carryOver,
                'available_pending_balance' => $availablePendingBalance,
                'available_pending_hours' => $availablePendingHours,
                'slip_id' => $slip?->id, 'has_slip' => (bool) $slip, 'is_locked' => $isLocked,
                'status' => $statuses->get($name) ?? $slip?->status ?? 'belum', 'thp' => $thp,
                'work_days_count' => count($daily), 'daily_breakdown' => $daily];
        }
        $c = collect($workers);

        return ['period' => ['id' => $period['id'], 'name' => $period['name'], 'startUtc' => $period['startUtc'], 'endUtc' => $period['endUtc']],
            'stats' => ['totalWorkers' => $c->count(), 'totalSudah' => $c->where('status', 'sudah')->count(), 'totalBelum' => $c->where('status', 'belum')->count(),
                'totalThp' => $c->sum('thp'), 'totalApprovedHours' => round($c->sum('approved_hours'), 2),
                'totalRejectedHours' => round($c->sum('rejected_hours'), 2), 'totalLoggedHours' => round($c->sum('logged_hours'), 2)], 'workers' => $workers];
    }

    public function detectPendingCarryOver(string $worker, string|array $targetPeriod, ?int $excludeSlipId = null): array
    {
        $periodResolver = app(PayrollPeriod::class);
        $period = is_array($targetPeriod) ? $targetPeriod : $periodResolver->resolve(['period' => $targetPeriod]);

        // Gather all claimed carry-over minutes from other saved slips
        $claimedAmountsByPeriod = [];
        $existingSlips = Payslip::where('employee_name', $worker)
            ->when($excludeSlipId, fn ($q) => $q->where('id', '!=', $excludeSlipId))
            ->where('pending_carry_over_amount', '>', 0)
            ->get();

        foreach ($existingSlips as $es) {
            if (! empty($es->pending_carry_over_data) && is_array($es->pending_carry_over_data)) {
                foreach ($es->pending_carry_over_data as $co) {
                    $src = $co['source_period'] ?? null;
                    if ($src) {
                        $claimedAmountsByPeriod[$src] = ($claimedAmountsByPeriod[$src] ?? 0) + (float) ($co['delta_min'] ?? 0);
                    }
                }
            }
        }

        // Look back over prior cut-off periods (up to 3 periods)
        $cursor = $period;
        $priorPeriods = [];
        for ($i = 1; $i <= 3; $i++) {
            $cursor = $periodResolver->previous($cursor);
            $priorPeriods[] = $cursor;
        }

        $carryOverTasks = [];
        $totalDeltaMins = 0;
        $rate = config('payroll.hourly_rate');

        foreach ($priorPeriods as $prevP) {
            $prevSlip = Payslip::where('employee_name', $worker)
                ->where('period', $prevP['name'])
                ->latest('id')
                ->first();

            if (! $prevSlip) {
                continue;
            }

            // Data 1: Locked Approved Minutes in Previous Slip
            $lockedMins = (float) $prevSlip->total_approved_hours * 60;

            // Data 2: Actual Total Billable Minutes from Database Recordings for that range
            $actualBillableMins = (float) StudioRecording::where('assigned_worker_name', $worker)
                ->whereBetween('recorded_at', [$prevP['startUtc'], $prevP['endUtc']])
                ->sum('billable_min');

            $alreadyClaimedMins = $claimedAmountsByPeriod[$prevP['name']] ?? 0;
            $deltaMins = round($actualBillableMins - $lockedMins - $alreadyClaimedMins, 2);

            if ($deltaMins > 0) {
                $taskAmount = (int) round($deltaMins / 60 * $rate);
                $firstRec = StudioRecording::where('assigned_worker_name', $worker)
                    ->whereBetween('recorded_at', [$prevP['startUtc'], $prevP['endUtc']])
                    ->where('billable_min', '>', 0)
                    ->first();

                $carryOverTasks[] = [
                    'source_period' => $prevP['name'],
                    'record_id' => $firstRec?->record_id ?? $prevP['id'],
                    'locked_hours' => round($lockedMins / 60, 2),
                    'actual_hours' => round($actualBillableMins / 60, 2),
                    'delta_min' => $deltaMins,
                    'delta_hours' => round($deltaMins / 60, 2),
                    'amount' => $taskAmount,
                    'task_name' => 'Sisa Pending ' . $prevP['name'],
                ];
                $totalDeltaMins += $deltaMins;
            }
        }

        $totalAmount = (int) round($totalDeltaMins / 60 * $rate);

        return [
            'available' => count($carryOverTasks) > 0 && $totalAmount > 0,
            'worker_name' => $worker,
            'target_period' => $period['name'],
            'total_mins' => round($totalDeltaMins, 2),
            'total_hours' => round($totalDeltaMins / 60, 2),
            'total_amount' => $totalAmount,
            'tasks_count' => count($carryOverTasks),
            'tasks' => $carryOverTasks,
        ];
    }
}
