<?php

namespace App\Services;

use App\Models\Contributor;
use App\Models\ShiftDay;
use App\Models\ShiftSetting;
use Carbon\CarbonImmutable;

class ShiftSchedule
{
    public function forContributor(Contributor $person, string $month): array
    {
        $first = CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Makassar');
        $patterns = app(ShiftPatterns::class);
        $legacy = $patterns->legacy();
        $resolver = app(PayrollPeriod::class);
        $keys = [$patterns->key($resolver->cutoff($first)), $patterns->key($resolver->cutoff($first->endOfMonth()))];
        $periodPatterns = ShiftSetting::whereIn('setting_key', $keys)->get()->keyBy('setting_key');
        $custom = ShiftDay::whereBetween('work_date', [$first->toDateString(), $first->endOfMonth()->toDateString()])->get()->keyBy('work_date');
        $result = [];
        for ($date = $first; $date->month === $first->month; $date = $date->addDay()) {
            $key = $date->toDateString();
            $period = $resolver->cutoff($date);
            $pattern = $periodPatterns->get($patterns->key($period))?->setting_value['config'] ?? $legacy;
            $day = $custom->get($key);
            $cfg = $day?->config ?? $pattern;
            $worker = collect($cfg['karyawan'])->first(fn ($worker) => ! empty($worker['contributor_id']) ? (int) $worker['contributor_id'] === $person->id : mb_strtolower($worker['name']) === mb_strtolower($person->nama));
            $off = ! $day && strtolower($date->englishDayOfWeek) === ($pattern['day_off'] ?? 'none');
            $sessions = [];
            $status = $off ? 'libur' : ($worker['status'] ?? 'scheduled');
            $durations = $cfg['session_durations'] ?? array_fill(0, (int) $cfg['sessions_count'], (int) $cfg['session_duration_min']);
            foreach (($status !== 'scheduled' ? [] : ($worker['sessions'] ?? [])) as $session) {
                [$h, $m] = array_map('intval', explode(':', $cfg['start_time']));
                $start = $date->startOfDay()->addMinutes($h * 60 + $m + array_sum(array_slice($durations, 0, $session - 1)) + ($session - 1) * $cfg['charging_duration_min']);
                $tool = $worker['alat'];
                if (($cfg['planner_version'] ?? 1) === 2) {
                    $active = array_values(array_filter($cfg['karyawan'], fn ($p) => ($p['status'] ?? 'scheduled') === 'scheduled' && in_array($session, $p['sessions'])));
                    foreach ($active as $index => $p) {
                        if ((! empty($p['contributor_id']) && (int) $p['contributor_id'] === $person->id) || (empty($p['contributor_id']) && mb_strtolower($p['name']) === mb_strtolower($person->nama))) {
                            $tool = 'Alat '.($index + 1);
                            break;
                        }
                    }
                }
                $end = $start->addMinutes($durations[$session - 1]);
                $sessions[] = ['id' => $session, 'start' => $start->format('H:i'), 'end' => $end->format('H:i'), 'duration_min' => $durations[$session - 1], 'tool' => $tool, 'start_at' => $start->toIso8601String(), 'end_at' => $end->toIso8601String()];
            }
            $result[] = ['date' => $key, 'custom' => (bool) $day, 'period_id' => $period['id'], 'status' => $status,
                'note' => $worker['note'] ?? '', 'work_hours' => array_sum(array_column($sessions, 'duration_min')) / 60, 'sessions' => $sessions];
        }

        return $result;
    }
}
