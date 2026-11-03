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
        $pattern = ShiftSetting::where('setting_key', 'main_shift_config')->value('setting_value') ?? config('shifts');
        $custom = ShiftDay::whereBetween('work_date', [$first->toDateString(), $first->endOfMonth()->toDateString()])->get()->keyBy('work_date');
        $result = [];
        for ($date = $first; $date->month === $first->month; $date = $date->addDay()) {
            $key = $date->toDateString();
            $day = $custom->get($key);
            $cfg = $day?->config ?? $pattern;
            $worker = collect($cfg['karyawan'])->first(fn ($worker) => ! empty($worker['contributor_id']) ? (int) $worker['contributor_id'] === $person->id : mb_strtolower($worker['name']) === mb_strtolower($person->nama));
            $off = ! $day && strtolower($date->englishDayOfWeek) === ($pattern['day_off'] ?? 'none');
            $sessions = [];
            foreach (($off ? [] : ($worker['sessions'] ?? [])) as $session) {
                [$h, $m] = array_map('intval', explode(':', $cfg['start_time']));
                $start = $date->startOfDay()->addMinutes($h * 60 + $m + ($session - 1) * ($cfg['session_duration_min'] + $cfg['charging_duration_min']));
                $sessions[] = ['id' => $session, 'start' => $start->format('H:i'), 'end' => $start->addMinutes($cfg['session_duration_min'])->format('H:i'), 'tool' => $worker['alat'], 'start_at' => $start->toIso8601String()];
            }
            $result[] = ['date' => $key, 'custom' => (bool) $day, 'sessions' => $sessions];
        }

        return $result;
    }
}
