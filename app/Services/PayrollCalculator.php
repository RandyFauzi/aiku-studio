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
        $slip = app(PayslipWriter::class)->unique($worker, $period['name']);
        if ($slip && ($slip->is_locked || $slip->status === 'sudah')) return $this->displaySlip($slip);
        return array_merge($this->calculate($this->evidence($period, $worker), $slip?->bonus ?? 0,
            $slip?->pending_carry_over_amount ?? 0, $slip?->pending_carry_over_data), [
            'id' => $slip?->id ?? 'LIVE-'.preg_replace('/[^a-zA-Z0-9]/', '', $worker),
            'employee_name' => $worker, 'period' => $period['name'],
            'status' => PayrollStatus::where('worker_name', $worker)->where('period', $period['name'])->value('status') ?? $slip?->status ?? 'belum',
            'editUrl' => $slip ? '/create.html?id='.$slip->id : '/create.html?'.http_build_query(['worker' => $worker, 'periodId' => $period['id']]),
        ]);
    }

    public function displaySlip(Payslip $slip): array
    {
        // A document ID always identifies its saved snapshot, including draft carry-over.
        return array_merge($slip->toArray(), ['editUrl' => '/create.html?id='.$slip->id]);
    }

    public function summary(array $period): array
    {
        $groups = $this->recordings($period)->whereNotNull('assigned_worker_name')->whereRaw("TRIM(assigned_worker_name) != ''")
            ->whereNotIn('assigned_worker_name', ['???', '?', '-', 'Unknown', 'null', 'undefined'])
            ->selectRaw('assigned_worker_name, COUNT(*) as total_tasks, SUM(duration_min) as duration, SUM(billable_min) as approved, SUM(rejected_min) as rejected, SUM(pending_min) as pending')
            ->groupBy('assigned_worker_name')->orderByDesc('approved')->get();
        $writer = app(PayslipWriter::class);
        $slips = Payslip::whereNull('superseded_by_id')->get()->filter(fn ($slip) => $writer->period($slip->period) === $period['name'])->groupBy(fn ($slip) => $writer->workerKey($slip->employee_name));
        $statuses = PayrollStatus::where('period', $period['name'])->pluck('status', 'worker_name');
        $workers = [];
        foreach ($groups as $group) {
            $name = $group->assigned_worker_name;
            $evidence = $this->evidence($period, $name);
            $daily = [];
            foreach ($evidence as $day) {
                $a = $day['approved_mins'];
                $r = $day['rejected_mins'];
                $p = $day['pending_mins'];
                $daily[] = ['date' => $day['date'], 'formattedDate' => CarbonImmutable::parse($day['date'])->locale('id')->translatedFormat('l, j M Y'),
                    'tasks_count' => count($day['tasks']), 'approved_min' => $a, 'approved_hours' => round($a / 60, 2),
                    'rejected_min' => $r, 'rejected_hours' => round($r / 60, 2), 'pending_min' => $p, 'pending_hours' => round($p / 60, 2),
                    'total_min' => $day['total_video_mins'], 'total_hours' => round($day['total_video_mins'] / 60, 2),
                    'status' => $a > 0 && $r > 0 ? 'Disetujui sebagian' : ($a == 0 && $r > 0 ? 'Ditolak' : ($a == 0 && $p > 0 ? 'Sedang Ditinjau' : 'Disetujui penuh'))];
            }
            $versions = $slips->get($writer->workerKey($name), collect());
            $conflict = $versions->count() > 1;
            $slip = $versions->count() === 1 ? $versions->first() : null;
            $carryOver = (int) ($slip?->pending_carry_over_amount ?? 0);
            $effectiveStatus = $statuses->get($name) ?? $slip?->status ?? 'belum';
            $isLocked = (bool) ($slip && ($slip->is_locked || $slip->status === 'sudah') && $effectiveStatus === 'sudah');

            // The overview shows only unallocated credit; the form may exclude its own reservation when editing.
            $pendingBank = $this->detectPendingCarryOver($name, $period);
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
                'available_pending_mins' => $pendingBank['total_mins'],
                'duplicate_count' => $versions->count(), 'pending_balance_warnings' => $pendingBank['warnings'],
                'reserved_pending_balance' => $pendingBank['reserved_amount'],
                'slip_id' => $slip?->id, 'has_slip' => (bool) $slip, 'is_locked' => $isLocked,
                'status' => $statuses->get($name) ?? $slip?->status ?? 'belum', 'thp' => $conflict ? null : $thp,
                'work_days_count' => count($daily), 'daily_breakdown' => $daily];
        }
        $c = collect($workers);

        return ['period' => ['id' => $period['id'], 'name' => $period['name'], 'startUtc' => $period['startUtc'], 'endUtc' => $period['endUtc']],
            'stats' => ['totalWorkers' => $c->count(), 'totalSudah' => $c->where('status', 'sudah')->count(), 'totalBelum' => $c->where('status', 'belum')->count(),
                'totalThp' => $c->sum('thp'), 'conflictWorkers' => $c->where('duplicate_count', '>', 1)->count(), 'totalApprovedHours' => round($c->sum('approved_hours'), 2),
                'totalRejectedHours' => round($c->sum('rejected_hours'), 2), 'totalLoggedHours' => round($c->sum('logged_hours'), 2)], 'workers' => $workers];
    }

    public function detectPendingCarryOver(string $worker, string|array $targetPeriod, ?int $excludeSlipId = null): array
    {
        $periodResolver = app(PayrollPeriod::class);
        $period = is_array($targetPeriod) ? $targetPeriod : $periodResolver->resolve(['period' => $targetPeriod]);

        $writer = app(PayslipWriter::class);
        $all = $writer->forWorker($worker);
        if ($excludeSlipId !== null) {
            $excluded = $all->firstWhere('id', $excludeSlipId);
            abort_unless($excluded && $writer->period($excluded->period) === $period['name'], 422, 'Slip yang dikecualikan harus milik pekerja dan periode tujuan.');
        }
        $groups = $all->groupBy(fn ($slip) => $writer->period($slip->period));
        $claimed = [];
        $ambiguous = [];
        $warnings = [];
        foreach ($groups as $label => $versions) {
            foreach ($versions as $saved) {
                if ($saved->id === $excludeSlipId) continue;
                foreach ($saved->pending_carry_over_data ?? [] as $claim) {
                    $source = $writer->period($claim['source_period'] ?? '');
                    if ($versions->count() > 1) $ambiguous[$source] = true;
                    $claimed[$source] = ($claimed[$source] ?? 0) + max(0, (float) ($claim['delta_min'] ?? 0));
                }
                if ($saved->pending_carry_over_amount > 0 && empty($saved->pending_carry_over_data)) {
                    $warnings[] = 'Slip #'.$saved->id.' memiliki carry-over lama tanpa rincian sumber; perlu rekonsiliasi sebelum klaim baru.';
                    $ambiguous['*'] = true;
                }
            }
        }
        $tasks = [];
        $reservedMins = 0;
        foreach ($groups as $label => $versions) {
            try {
                $previous = $periodResolver->resolve(['period' => $label]);
            } catch (ValidationException) {
                continue;
            }
            if ($previous['endDate'] >= $period['startDate']) continue;
            if ($versions->count() > 1 || isset($ambiguous[$label]) || isset($ambiguous['*'])) {
                $warnings[] = 'Saldo dari '.$label.' perlu rekonsiliasi slip ganda/rincian klaim.';
                continue;
            }
            $source = $versions->first();
            if (! $source->is_locked && $source->status !== 'sudah') {
                $warnings[] = 'Periode '.$label.' belum final; saldo tambahannya belum dapat dicairkan.';
                continue;
            }
            $lockedMins = (float) $source->total_approved_hours * 60;
            $actual = (float) $this->recordings($previous, $worker)->sum('billable_min');
            $used = $claimed[$label] ?? 0;
            $reservedMins += $used;
            $delta = round($actual - $lockedMins - $used, 2);
            if ($delta <= 0) continue;
            $tasks[] = [
                'source_period' => $label, 'source_slip_id' => $source->id,
                'record_id' => $this->recordings($previous, $worker)->where('billable_min', '>', 0)->orderBy('record_id')->value('record_id') ?? $previous['id'],
                'locked_hours' => round($lockedMins / 60, 2), 'actual_hours' => round($actual / 60, 2),
                'locked_mins' => round($lockedMins, 2), 'actual_mins' => round($actual, 2),
                'delta_min' => $delta, 'delta_hours' => round($delta / 60, 2),
                'amount' => (int) round($delta / 60 * config('payroll.hourly_rate')),
                'task_name' => 'Sisa Pending '.$label,
            ];
        }
        $minutes = array_sum(array_column($tasks, 'delta_min'));
        $amount = (int) round($minutes / 60 * config('payroll.hourly_rate'));
        return [
            'available' => $amount > 0, 'worker_name' => $worker, 'target_period' => $period['name'],
            'total_mins' => round($minutes, 2), 'total_hours' => round($minutes / 60, 2), 'total_amount' => $amount,
            'reserved_amount' => (int) round($reservedMins / 60 * config('payroll.hourly_rate')),
            'warnings' => array_values(array_unique($warnings)), 'tasks_count' => count($tasks), 'tasks' => $tasks,
        ];
    }
}
