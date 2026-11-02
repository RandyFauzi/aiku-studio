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

    public function calculate(array $evidence, int $bonus = 0): array
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

        return ['total_logged_hours' => $logged / 60, 'total_approved_hours' => $approved / 60,
            'total_thp' => (int) round($approved / 60 * config('payroll.hourly_rate')) + $bonus,
            'bonus' => $bonus, 'evidence_data' => $evidence];
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
            $base = (int) round((float) $group->approved / 60 * config('payroll.hourly_rate'));
            $workers[] = ['worker_name' => $name, 'total_tasks' => (int) $group->total_tasks,
                'approved_min' => round($group->approved, 2), 'approved_hours' => round($group->approved / 60, 2),
                'rejected_min' => round($group->rejected, 2), 'rejected_hours' => round($group->rejected / 60, 2),
                'pending_min' => round($group->pending, 2), 'pending_hours' => round($group->pending / 60, 2),
                'logged_hours' => round($group->duration / 60, 2), 'total_duration_min' => round($group->duration, 2),
                'base_thp' => $base, 'bonus' => $slip?->bonus ?? 0, 'slip_id' => $slip?->id, 'has_slip' => (bool) $slip,
                'status' => $statuses->get($name) ?? $slip?->status ?? 'belum', 'thp' => $base + ($slip?->bonus ?? 0),
                'work_days_count' => count($daily), 'daily_breakdown' => $daily];
        }
        $c = collect($workers);

        return ['period' => ['id' => $period['id'], 'name' => $period['name'], 'startUtc' => $period['startUtc'], 'endUtc' => $period['endUtc']],
            'stats' => ['totalWorkers' => $c->count(), 'totalSudah' => $c->where('status', 'sudah')->count(), 'totalBelum' => $c->where('status', 'belum')->count(),
                'totalThp' => $c->sum('thp'), 'totalApprovedHours' => round($c->sum('approved_hours'), 2),
                'totalRejectedHours' => round($c->sum('rejected_hours'), 2), 'totalLoggedHours' => round($c->sum('logged_hours'), 2)], 'workers' => $workers];
    }
}
