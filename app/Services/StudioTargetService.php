<?php

namespace App\Services;

use App\Models\StudioRecording;
use Carbon\CarbonImmutable;

class StudioTargetService
{
    public const MONTHLY_TARGET_HOURS = 2150.0;

    public function __construct(private readonly PayrollPeriod $periodResolver) {}

    public function summary(?string $periodId = null): array
    {
        $nowWita = CarbonImmutable::now('Asia/Makassar');
        $currentCutoff = $this->periodResolver->cutoff($nowWita);

        $period = ($periodId && $periodId !== '' && $periodId !== 'training_2026')
            ? $this->periodResolver->resolve(['periodId' => $periodId])
            : $currentCutoff;

        $startWita = CarbonImmutable::parse($period['startDate'], 'Asia/Makassar')->startOfDay();
        $endWita = CarbonImmutable::parse($period['endDate'], 'Asia/Makassar')->endOfDay();

        $totalDays = max(1, (int) $startWita->diffInDays($endWita->startOfDay()) + 1);
        $dailyTargetHours = round(self::MONTHLY_TARGET_HOURS / $totalDays, 1);

        $isCurrentCycle = $nowWita->betweenIncluded($startWita, $endWita);
        $daysElapsed = $isCurrentCycle
            ? min($totalDays, max(1, (int) $startWita->diffInDays($nowWita->startOfDay()) + 1))
            : ($nowWita->greaterThan($endWita) ? $totalDays : 0);
        $daysRemaining = max(0, $totalDays - $daysElapsed);

        $monthlyAgg = StudioRecording::query()
            ->whereBetween('recorded_at', [$period['startUtc'], $period['endUtc']])
            ->selectRaw('COALESCE(SUM(billable_min), 0) as billable_min, COALESCE(SUM(duration_min), 0) as duration_min')
            ->first();

        $todayStartUtc = $nowWita->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $todayEndUtc = $nowWita->endOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s');

        $dailyAgg = StudioRecording::query()
            ->whereBetween('recorded_at', [$todayStartUtc, $todayEndUtc])
            ->selectRaw('COALESCE(SUM(billable_min), 0) as billable_min, COALESCE(SUM(duration_min), 0) as duration_min')
            ->first();

        $monthlyBillableHours = round(((float) ($monthlyAgg->billable_min ?? 0)) / 60, 1);
        $monthlyLoggedHours = round(((float) ($monthlyAgg->duration_min ?? 0)) / 60, 1);
        $dailyBillableHours = round(((float) ($dailyAgg->billable_min ?? 0)) / 60, 1);
        $dailyLoggedHours = round(((float) ($dailyAgg->duration_min ?? 0)) / 60, 1);

        $monthlyPercent = self::MONTHLY_TARGET_HOURS > 0
            ? round(($monthlyBillableHours / self::MONTHLY_TARGET_HOURS) * 100, 1)
            : 0.0;
        $dailyPercent = $dailyTargetHours > 0
            ? round(($dailyBillableHours / $dailyTargetHours) * 100, 1)
            : 0.0;

        $monthlyRemainingHours = max(0.0, round(self::MONTHLY_TARGET_HOURS - $monthlyBillableHours, 1));
        $dailyRemainingHours = max(0.0, round($dailyTargetHours - $dailyBillableHours, 1));

        $expectedProgressHours = round($dailyTargetHours * $daysElapsed, 1);
        $recommendedDailyPace = $daysRemaining > 0
            ? round($monthlyRemainingHours / $daysRemaining, 1)
            : $monthlyRemainingHours;

        return [
            'period' => [
                'id' => $period['id'],
                'name' => $period['name'],
                'start_date' => $period['startDate'],
                'end_date' => $period['endDate'],
                'total_days' => $totalDays,
                'days_elapsed' => $daysElapsed,
                'days_remaining' => $daysRemaining,
                'is_current' => $isCurrentCycle,
            ],
            'daily' => [
                'date' => $nowWita->toDateString(),
                'target_hours' => $dailyTargetHours,
                'actual_hours' => $dailyBillableHours,
                'logged_hours' => $dailyLoggedHours,
                'remaining_hours' => $dailyRemainingHours,
                'percent' => $dailyPercent,
                'bar_percent' => min(100.0, max(0.0, $dailyPercent)),
                'achieved' => $dailyBillableHours >= $dailyTargetHours,
            ],
            'monthly' => [
                'target_hours' => self::MONTHLY_TARGET_HOURS,
                'actual_hours' => $monthlyBillableHours,
                'logged_hours' => $monthlyLoggedHours,
                'remaining_hours' => $monthlyRemainingHours,
                'expected_hours_to_date' => $expectedProgressHours,
                'recommended_daily_pace' => $recommendedDailyPace,
                'percent' => $monthlyPercent,
                'bar_percent' => min(100.0, max(0.0, $monthlyPercent)),
                'achieved' => $monthlyBillableHours >= self::MONTHLY_TARGET_HOURS,
                'on_track' => $monthlyBillableHours >= $expectedProgressHours,
            ],
        ];
    }
}
