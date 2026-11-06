<?php

namespace App\Services;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\ShiftDay;
use App\Models\StudioRecording;
use Carbon\CarbonImmutable;

class RecordingOwnerRecommendations
{
    public function __construct(private RecordingMatching $matching, private TaskLanguage $language) {}

    /** Evidence ranking only. This method never assigns a recording or changes payroll. */
    public function recommend(StudioRecording $recording, int $lookbackDays = 2): array
    {
        $group = $this->matching->groupFor($recording->getRawOriginal('recorded_at'));
        if (empty($group['work_date'])) {
            return ['group' => $group, 'candidates' => [], 'ambiguous' => false, 'message' => 'Waktu recording belum tersedia. Pilih pekerja secara manual.'];
        }
        $date = $group['work_date'];
        $start = CarbonImmutable::parse($date)->subDays(max(0, min(7, $lookbackDays)))->toDateString();
        $reports = DailyWorkReport::whereBetween('work_date', [$start, $date])->orderByDesc('work_date')->get();
        $people = Contributor::with('user')->whereIn('id', $reports->pluck('contributor_id'))->get()->keyBy('id');
        $accounts = DeviceAccount::all()->groupBy('phone_key');
        $customDates = ShiftDay::whereBetween('work_date', [$start, $date])->pluck('work_date')->all();
        $email = mb_strtolower(trim($recording->participant_email ?? ''));
        $task = ReportTasks::key($recording->task_title ?? '');
        $candidates = [];
        foreach ($reports as $report) {
            $person = $people->get($report->contributor_id);
            if (! $person || $person->role === 'admin' || ($person->user && ! $person->user->is_active)) continue;
            $shift = $report->work_date === $date && $this->shiftMatches($person, $report->work_date, $group['key'], $customDates, $people);
            foreach ($report->phones ?? [] as $phone) {
                $override = mb_strtolower(trim($report->phone_emails[$phone] ?? ''));
                $emails = $override !== '' ? [$override] : ($phone === 'sendiri' ? [] : ($accounts->get($phone)?->pluck('email')->map(fn ($value) => mb_strtolower(trim($value)))->all() ?? []));
                $emailMatch = $email !== '' && in_array($email, $emails, true);
                $tasks = $report->phone_tasks[$phone] ?? [];
                $taskMatch = trim($recording->task_title ?? '') !== '' && in_array($task, array_map([ReportTasks::class, 'key'], $tasks), true);
                // Do not combine a matching email on one phone with a task on a different phone.
                if (($emails && ! $emailMatch) || (! $emailMatch && ! $taskMatch)) continue;
                $emailSource = $override !== '' ? 'report' : ($phone === 'sendiri' ? 'missing' : 'master');
                $score = ($emailMatch ? ($emailSource === 'report' ? 40 : 20) : 0) + ($taskMatch ? 35 : 0) + ($shift ? 15 : 0) + ($report->work_date === $date ? 5 : 0);
                $warnings = [];
                if (! $emailMatch) $warnings[] = 'Email pada laporan belum tersedia; cocokkan manual.';
                elseif ($emailSource === 'master') $warnings[] = 'Email dari referensi master HP, bukan bukti akun yang dipakai saat itu.';
                if (! $tasks) $warnings[] = 'Task belum dicatat pada laporan.';
                elseif (! $taskMatch) $warnings[] = 'Task recording tidak tercantum pada HP ini.';
                if ($report->work_date !== $date) $warnings[] = 'Laporan hari sebelumnya; periksa kemungkinan upload terlambat.';
                if ($shift === false) $warnings[] = 'Jadwal belum terkonfirmasi untuk kelompok upload ini; waktu Atlas bisa mengalami delay.';
                $minutes = $report->phone_durations[$phone] ?? null;
                if ($minutes !== null && $recording->duration_min > $minutes) $warnings[] = 'Durasi recording melebihi durasi HP dalam laporan.';
                $candidate = ['contributor_id' => $person->id, 'name' => $person->nama, 'report_id' => $report->id,
                    'report_revision' => $report->revision, 'work_date' => $report->work_date, 'reported_at' => $report->created_at?->toIso8601String(),
                    'phone_key' => $phone, 'phone_label' => 'HP '.(DailyWorkReport::REPORT_PHONES[$phone] ?? $phone),
                    'reported_minutes' => $minutes, 'total_reported_minutes' => $report->total_duration_minutes,
                    'reported_tasks' => $tasks, 'score' => $score, 'signal_count' => (int) $emailMatch + (int) $taskMatch + (int) $shift,
                    'signals' => ['email' => ['matched' => $emailMatch, 'source' => $emailSource, 'label' => $emailMatch ? ($emailSource === 'report' ? 'Email laporan sesuai' : 'Email referensi HP sesuai') : 'Email belum tersedia'],
                        'task' => ['matched' => $taskMatch, 'label' => $taskMatch ? 'Task pada HP ini sesuai' : ($tasks ? 'Task berbeda' : 'Task belum dicatat')],
                        'shift' => ['matched' => $shift, 'label' => $shift ? 'Jadwal sesuai kelompok upload' : 'Jadwal belum terkonfirmasi']],
                    'warnings' => $warnings, 'can_confirm' => $emailMatch && ! $recording->assigned_contributor_id && ! trim($recording->assigned_worker_name ?? '') && $recording->manual_payment_status !== 'Paid'];
                if (! isset($candidates[$person->id]) || $candidate['score'] > $candidates[$person->id]['score']) $candidates[$person->id] = $candidate;
            }
        }
        $candidates = array_values($candidates);
        usort($candidates, fn ($a, $b) => ($b['score'] <=> $a['score']) ?: strcmp($a['name'], $b['name']));
        $ambiguous = count($candidates) > 1 && $candidates[0]['score'] - $candidates[1]['score'] <= 10;
        return ['group' => $group, 'candidates' => array_slice($candidates, 0, 5), 'ambiguous' => $ambiguous,
            'task_label' => $this->language->describe($recording->task_title ?? '')['label'],
            'report_window' => ['start' => $start, 'end' => $date],
            'message' => $ambiguous ? 'Beberapa kandidat memiliki bukti serupa. Periksa laporan sebelum menandai.' : ($candidates ? 'Urutan berdasarkan bukti yang tersedia, bukan kepastian pemilik.' : 'Belum ada laporan yang cocok. Daftar pekerja manual tetap tersedia.')];
    }

    private function shiftMatches(Contributor $person, string $date, string $groupKey, array $customDates, $people): bool
    {
        $config = $this->matching->config($date);
        $day = CarbonImmutable::parse($date, 'Asia/Makassar');
        if (! in_array($date, $customDates, true) && strtolower($day->englishDayOfWeek) === ($config['day_off'] ?? 'none')) return false;
        $sameName = $people->filter(fn ($p) => mb_strtolower($p->nama) === mb_strtolower($person->nama))->count();
        $worker = collect($config['karyawan'])->first(fn ($row) => ! empty($row['contributor_id'])
            ? (int) $row['contributor_id'] === $person->id
            : $sameName === 1 && mb_strtolower($row['name']) === mb_strtolower($person->nama));
        if (! $worker || ($worker['status'] ?? 'scheduled') !== 'scheduled') return false;
        $durations = $config['session_durations'] ?? array_fill(0, $config['sessions_count'], $config['session_duration_min']);
        foreach ($worker['sessions'] ?? [] as $session) {
            if (! isset($durations[$session - 1])) continue;
            $offset = array_sum(array_slice($durations, 0, $session - 1)) + ($session - 1) * $config['charging_duration_min'];
            $start = CarbonImmutable::parse($date.' '.$config['start_time'], 'Asia/Makassar')->addMinutes($offset);
            foreach ([$start->addMinutes(intdiv($durations[$session - 1], 2)), $start->addMinutes($durations[$session - 1])] as $time) {
                if ($this->matching->groupFor($time->utc()->format('Y-m-d H:i:s'))['key'] === $groupKey) return true;
            }
        }
        return false;
    }
}
