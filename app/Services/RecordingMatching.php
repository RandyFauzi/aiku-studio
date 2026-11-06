<?php

namespace App\Services;

use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\ShiftDay;
use Carbon\CarbonImmutable;

class RecordingMatching
{
    private array $configs = [];

    private array $dayWindows = [];

    public function config(string $date): array
    {
        return $this->configs[$date] ??= ShiftDay::where('work_date', $date)->value('config')
            ?? app(ShiftPatterns::class)->read(app(PayrollPeriod::class)->cutoff(CarbonImmutable::parse($date, 'Asia/Makassar')))['config'];
    }

    public function windows(string $date): array
    {
        if (isset($this->dayWindows[$date])) {
            return $this->dayWindows[$date];
        }
        $config = $this->config($date);
        $start = CarbonImmutable::parse($date.' '.$config['start_time'], 'Asia/Makassar');
        $nextDate = CarbonImmutable::parse($date, 'Asia/Makassar')->addDay()->toDateString();
        $next = CarbonImmutable::parse($nextDate.' '.$this->config($nextDate)['start_time'], 'Asia/Makassar');
        $durations = $config['session_durations'] ?? array_fill(0, $config['sessions_count'], $config['session_duration_min']);
        $windows = [];
        foreach ($durations as $index => $duration) {
            if ($start->gte($next)) {
                break;
            }
            $end = $start->addMinutes($duration)->min($next);
            $windows[] = $this->window('shift-'.($index + 1), 'Shift '.($index + 1), $start, $end);
            $start = $end;
            if ($end->lt($next) && $index < count($durations) - 1 && $config['charging_duration_min'] > 0) {
                $start = $end->addMinutes($config['charging_duration_min'])->min($next);
                $windows[] = $this->window('break-'.($index + 1), 'Jeda setelah Shift '.($index + 1), $end, $start);
            }
        }
        // The following day's settings may have a different starting hour.
        if ($start->lt($next)) {
            $windows[] = $this->window('outside', 'Lembur / di luar shift', $start, $next);
        }

        return $this->dayWindows[$date] = $windows;
    }

    public function groupFor(?string $recordedAt): array
    {
        if (! $recordedAt) {
            return ['key' => 'unknown', 'label' => 'Tanggal belum tersedia', 'time' => ''];
        }
        $instant = CarbonImmutable::parse($recordedAt, 'UTC')->setTimezone('Asia/Makassar');
        $date = $instant->toDateString();
        $start = CarbonImmutable::parse($date.' '.$this->config($date)['start_time'], 'Asia/Makassar');
        if ($instant->lt($start)) {
            $date = $instant->subDay()->toDateString();
        }
        foreach ($this->windows($date) as $window) {
            $start = CarbonImmutable::parse($window['start_at']);
            $end = CarbonImmutable::parse($window['end_at']);
            if ($instant->gte($start) && $instant->lt($end)) {
                return ['key' => $date.':'.$window['id'], 'work_date' => $date,
                    'label' => CarbonImmutable::parse($date)->locale('id')->translatedFormat('j F Y').' · '.$window['label'],
                    'time' => $start->format('H:i').'–'.$end->format('H:i').' WITA'];
            }
        }
        return ['key' => $date.':outside', 'work_date' => $date, 'label' => $date.' · Di luar shift', 'time' => ''];
    }

    private function window(string $id, string $label, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return ['id' => $id, 'label' => $label, 'start_at' => $start->toIso8601String(), 'end_at' => $end->toIso8601String(),
            'time_label' => $start->format('d/m H:i').' – '.$end->format('d/m H:i').' WITA'];
    }

    public function phones(DailyWorkReport $report): array
    {
        $accounts = DeviceAccount::whereIn('phone_key', array_diff($report->phones ?? [], ['sendiri']))->get();
        return array_map(function ($key) use ($report, $accounts) {
            $override = mb_strtolower(trim($report->phone_emails[$key] ?? ''));
            $emails = $override !== '' ? [$override] : ($key === 'sendiri' ? [] : $accounts->where('phone_key', $key)->pluck('email')->map(fn ($email) => mb_strtolower(trim($email)))->filter()->unique()->values()->all());

            return ['key' => $key, 'label' => 'HP '.(DailyWorkReport::REPORT_PHONES[$key] ?? $key), 'emails' => $emails,
                'email_source' => $override !== '' ? 'report' : ($key === 'sendiri' ? 'missing' : 'master_candidates'),
                'duration_minutes' => $report->phone_durations[$key] ?? null];
        }, $report->phones ?? []);
    }
}
