<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\RecordStreakDay;
use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordStreak
{
    public function summary(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $owner = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $person = Contributor::findOrFail($owner->contributor_id);
            $now = CarbonImmutable::now('Asia/Makassar');
            $today = $now->toDateString();
            $policy = ShiftSetting::where('setting_key', 'record_streak_policy')->value('setting_value');
            $start = max($policy['start_date'] ?? $today, $owner->created_at->setTimezone('Asia/Makassar')->toDateString());
            $existing = RecordStreakDay::where('contributor_id', $person->id)->orderBy('work_date')->get()->keyBy('work_date');
            $schedules = $this->schedules($person, min($start, $now->subDays(7)->toDateString()), $today);
            foreach ($existing as $key => $row) {
                if ($key < $today && ! ($schedules[$key]['custom'] ?? false) && ! ($row->schedule_snapshot['custom'] ?? false)) {
                    $schedules[$key] = $row->schedule_snapshot;
                }
            }
            $workDate = $this->dateForInstant($now, $schedules);
            $reports = DailyWorkReport::where('contributor_id', $person->id)->whereBetween('work_date', [$start, $today])->get()->keyBy('work_date');
            $attendances = Attendance::where('contributor_id', $person->id)->whereBetween('checked_in_at', [CarbonImmutable::parse($start, 'Asia/Makassar')->utc(), $now->utc()])->get();
            $attendanceByDate = [];
            foreach ($attendances as $attendance) {
                $date = $this->dateForInstant(CarbonImmutable::instance($attendance->checked_in_at)->setTimezone('Asia/Makassar'), $schedules);
                $attendanceByDate[$date] ??= $attendance->id;
            }

            // Only assigned evidence counts. A legacy name is accepted only when unique.
            $uniqueName = Contributor::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($person->nama))])->count() === 1;
            $recordings = StudioRecording::whereBetween('recorded_at', [CarbonImmutable::parse($start, 'Asia/Makassar')->utc(), $now->utc()])
                ->where(function ($query) use ($person, $uniqueName) {
                    $query->where('assigned_contributor_id', $person->id);
                    if ($uniqueName) {
                        $query->orWhere(fn ($legacy) => $legacy->whereNull('assigned_contributor_id')->whereRaw('LOWER(TRIM(assigned_worker_name)) = ?', [mb_strtolower(trim($person->nama))]));
                    }
                })->where('duration_min', '>', 0)->get(['record_id', 'recorded_at', 'duration_min']);
            $evidence = [];
            foreach ($recordings as $recording) {
                $instant = CarbonImmutable::parse($recording->getRawOriginal('recorded_at'), 'UTC')->setTimezone('Asia/Makassar');
                $date = $this->dateForInstant($instant, $schedules);
                $evidence[$date]['minutes'] = ($evidence[$date]['minutes'] ?? 0) + $recording->duration_min;
                $evidence[$date]['ids'][] = $recording->record_id;
            }

            for ($date = CarbonImmutable::parse($start, 'Asia/Makassar'); $date->toDateString() <= $today; $date = $date->addDay()) {
                $key = $date->toDateString();
                $live = $schedules[$key];
                $row = $existing->get($key) ?? new RecordStreakDay(['contributor_id' => $person->id, 'work_date' => $key]);
                // Freeze past routine patterns; explicit daily corrections remain effective.
                if (! $row->exists || $key >= $today || $live['custom'] || ($row->schedule_snapshot['custom'] ?? false)) {
                    $row->schedule_snapshot = $live;
                    $deadline = $date->addDay()->startOfDay();
                    foreach ($live['sessions'] as $session) {
                        $deadline = $deadline->max(CarbonImmutable::parse($session['end_at']));
                    }
                    $row->deadline_at = $deadline->utc();
                    $row->target_minutes = min((int) config('streak.minimum_minutes', 30), max(1, (int) round($live['work_hours'] * 60)));
                    $row->rule_version = config('streak.rule_version', 1);
                }
                $report = $reports->get($key);
                $row->attendance_id = $attendanceByDate[$key] ?? null;
                $row->verified_minutes = round($evidence[$key]['minutes'] ?? 0, 2);
                $row->evidence_ids = $evidence[$key]['ids'] ?? [];
                [$row->status, $row->reason] = $this->evaluate($row, $report, $now, $workDate);
                if ($row->isDirty()) {
                    $row->save();
                }
                $existing->put($key, $row);
            }

            return $this->present($existing, $workDate, $today, $start, $person->id);
        }, 3);
    }

    private function schedules(Contributor $person, string $start, string $end): array
    {
        $result = [];
        for ($month = CarbonImmutable::parse($start)->startOfMonth(); $month->format('Y-m') <= substr($end, 0, 7); $month = $month->addMonth()) {
            foreach (app(ShiftSchedule::class)->forContributor($person, $month->format('Y-m')) as $day) {
                $result[$day['date']] = $day;
            }
        }

        return $result;
    }

    private function dateForInstant(CarbonImmutable $instant, array $schedules): string
    {
        $previous = $instant->subDay()->toDateString();

        // A session crossing midnight belongs to the date on which it began.
        return ShiftSchedule::dateFromPreviousSessions($instant, $schedules[$previous]['sessions'] ?? []);
    }

    private function evaluate(RecordStreakDay $day, ?DailyWorkReport $report, CarbonImmutable $now, string $workDate): array
    {
        if ($day->work_date > $workDate) {
            return ['upcoming', 'Hari kerja berikutnya belum dimulai.'];
        }
        $schedule = $day->schedule_snapshot;
        if (in_array($schedule['status'], ['izin', 'sakit'], true)) {
            return ['broken', ucfirst($schedule['status']).' dari admin. Mulai streak baru pada hari kerja berikutnya.'];
        }
        if ($schedule['status'] === 'libur' || empty($schedule['sessions'])) {
            return ['protected', 'Hari libur atau tidak dijadwalkan. Streak tetap aman.'];
        }
        if (! $day->attendance_id) {
            return $now->gte($day->deadline_at)
                ? ['broken', 'Hari kerja terlewat tanpa absensi.']
                : ['open', 'Absen lalu kirim laporan rekaman untuk menjaga api hari ini.'];
        }
        $validReport = $report && $report->total_duration_minutes >= $day->target_minutes;
        $manual = $report && $day->verified_by && $day->manual_report_id === $report->id && $day->manual_report_revision === $report->revision;
        if ($validReport && ($day->verified_minutes >= $day->target_minutes || $manual)) {
            return ['success', 'Absensi dan rekaman hari ini sudah terverifikasi.'];
        }
        if ($validReport && $now->lte($day->deadline_at->addHours((int) config('streak.verification_grace_hours', 24)))) {
            return ['pending', 'Laporan terkirim. Menunggu penugasan rekaman bot atau verifikasi admin.'];
        }
        if ($now->lt($day->deadline_at)) {
            return ['open', 'Absen tercatat. Lanjutkan rekaman dan kirim laporan harian.'];
        }

        return ['broken', $validReport ? 'Batas verifikasi terlewat. Hubungi admin jika rekaman terlambat tersinkron.' : 'Laporan rekaman belum memenuhi target hari kerja ini.'];
    }

    private function present(Collection $rows, string $workDate, string $today, string $start, int $contributorId): array
    {
        $current = 0;
        $best = 0;
        $pending = 0;
        $queued = 0;
        foreach ($rows->sortKeys() as $date => $row) {
            if ($date > $workDate) {
                continue;
            }
            if ($row->status === 'broken') {
                $current = $pending = $queued = 0;
            } elseif ($row->status === 'pending') {
                $pending++;
            } elseif ($row->status === 'success') {
                if ($pending) {
                    $queued++;
                } else {
                    $current++;
                    $best = max($best, $current);
                }
            }
        }
        $selected = $rows->get($workDate);
        $days = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        for ($date = CarbonImmutable::parse($workDate)->subDays(6); $date->toDateString() <= $workDate; $date = $date->addDay()) {
            $key = $date->toDateString();
            $days[] = ['date' => $key, 'label' => $dayNames[$date->dayOfWeek], 'status' => $rows->get($key)?->status ?? 'not_started', 'today' => $key === $workDate];
        }

        return ['current' => $current, 'longest' => $best, 'pending_days' => $pending, 'queued_days' => $queued,
            'active' => $current > 0 && $selected?->status !== 'broken', 'work_date' => $workDate, 'today' => $today,
            'started_on' => $start, 'contributor_id' => $contributorId,
            'status' => $selected?->status ?? 'open', 'message' => $selected?->reason ?? 'Mulai kebiasaan rekaman hari ini.',
            'target_minutes' => $selected?->target_minutes ?? config('streak.minimum_minutes'),
            'verified_minutes' => $selected?->verified_minutes ?? 0,
            'attendance_id' => $selected?->attendance_id, 'celebrated' => (bool) $selected?->celebrated_at, 'days' => $days];
    }

    public function celebrate(User $user): array
    {
        $summary = $this->summary($user);
        $claimed = $summary['attendance_id'] && RecordStreakDay::where('contributor_id', $user->contributor_id)
            ->where('work_date', $summary['work_date'])->whereNull('celebrated_at')->update(['celebrated_at' => now()]) === 1;

        return ['show_popup' => (bool) $claimed, 'streak' => $summary];
    }

    public function verify(DailyWorkReport $report, User $admin, int $expectedRevision): array
    {
        $owner = User::where('contributor_id', $report->contributor_id)->firstOrFail();

        return DB::transaction(function () use ($report, $admin, $owner, $expectedRevision) {
            User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $report = DailyWorkReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_if($report->revision !== $expectedRevision, 409, 'Laporan sudah berubah. Muat ulang detail sebelum memverifikasi.');
            $this->summary($owner);
            $row = RecordStreakDay::where('contributor_id', $report->contributor_id)->where('work_date', $report->work_date)->first();
            abort_unless($row && $row->attendance_id && ! in_array($row->schedule_snapshot['status'], ['izin', 'sakit', 'libur']) && ! empty($row->schedule_snapshot['sessions']), 422, 'Verifikasi memerlukan absensi pada hari kerja aktif.');
            abort_if($report->total_duration_minutes < $row->target_minutes, 422, 'Durasi laporan belum mencapai target Daily Record.');
            $row->update(['manual_report_id' => $report->id, 'manual_report_revision' => $report->revision, 'verified_by' => $admin->id, 'verified_at' => now()]);

            return $this->summary($owner);
        }, 3);
    }
}
