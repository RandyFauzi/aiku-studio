<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\RecordStreakDay;
use App\Models\ShiftDay;
use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorAccess;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordStreakTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    private array $pattern;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock('2026-11-11 10:00');
        $person = Contributor::create(['nama' => 'Record Worker', 'email' => 'record@example.test']);
        $this->worker = app(ContributorAccess::class)->sync($person);
        $this->worker->forceFill(['created_at' => '2026-10-01 00:00:00'])->save();
        $this->pattern = array_replace(config('shifts'), ['day_off' => 'none', 'start_time' => '08:00', 'sessions_count' => 1, 'session_duration_min' => 60, 'charging_duration_min' => 0,
            'karyawan' => [['name' => $person->nama, 'contributor_id' => $person->id, 'alat' => 'Alat 1', 'sessions' => [1]]]]);
        ShiftSetting::updateOrCreate(['setting_key' => 'main_shift_config'], ['setting_value' => $this->pattern]);
        $this->start('2026-11-05');
        $this->actingAs($this->worker);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function clock(string $date): void
    {
        $instant = CarbonImmutable::parse($date, 'Asia/Makassar');
        $this->travelTo($instant);
        CarbonImmutable::setTestNow($instant);
    }

    private function start(string $date): void
    {
        ShiftSetting::updateOrCreate(['setting_key' => 'record_streak_policy'], ['setting_value' => ['start_date' => $date]]);
    }

    private function activity(string $date, bool $bot = true): DailyWorkReport
    {
        $time = CarbonImmutable::parse($date.' 08:05', 'Asia/Makassar')->utc();
        Attendance::create(['user_id' => $this->worker->id, 'contributor_id' => $this->worker->contributor_id, 'contributor_name' => $this->worker->name,
            'work_date' => $date, 'checked_in_at' => $time, 'latitude' => 0, 'longitude' => 0, 'accuracy_m' => 1, 'gps_captured_at' => $time]);
        $report = DailyWorkReport::create(['user_id' => $this->worker->id, 'contributor_id' => $this->worker->contributor_id,
            'contributor_name' => $this->worker->name, 'work_date' => $date, 'phones' => ['sendiri'], 'total_duration_minutes' => 60, 'revision' => 1]);
        if ($bot) {
            $this->recording($date);
        }

        return $report;
    }

    private function recording(string $date): void
    {
        StudioRecording::create(['record_id' => 'streak-'.$date, 'participant_email' => 'record@example.test', 'recorded_at' => CarbonImmutable::parse($date.' 08:10', 'Asia/Makassar')->utc(),
            'duration_min' => 60, 'billable_min' => 0, 'assigned_contributor_id' => $this->worker->contributor_id, 'assigned_worker_name' => $this->worker->name]);
    }

    public function test_holiday_preserves_chain_and_repeated_reads_and_popups_are_idempotent(): void
    {
        $this->clock('2026-11-09 10:00');
        $this->pattern['day_off'] = 'sunday';
        ShiftSetting::where('setting_key', 'main_shift_config')->update(['setting_value' => $this->pattern]);
        foreach (['2026-11-05', '2026-11-06', '2026-11-07', '2026-11-09'] as $date) {
            $this->activity($date);
        }
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 4)->assertJsonPath('streak.longest', 4);
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 4);
        $this->assertDatabaseCount('record_streak_days', 5);
        $this->assertDatabaseHas('record_streak_days', ['work_date' => '2026-11-08', 'status' => 'protected']);
        $this->postJson('/api/me/streak/celebrate')->assertOk()->assertJsonPath('show_popup', true);
        $this->postJson('/api/me/streak/celebrate')->assertOk()->assertJsonPath('show_popup', false);
    }

    public function test_approved_izin_and_sakit_reset_immediately_and_next_success_starts_at_one(): void
    {
        $this->clock('2026-11-10 10:00');
        foreach (['2026-11-05', '2026-11-06', '2026-11-07', '2026-11-08', '2026-11-09'] as $date) {
            $this->activity($date);
        }
        foreach (['izin', 'sakit'] as $status) {
            $config = $this->pattern;
            $config['karyawan'][0]['status'] = $status;
            ShiftDay::updateOrCreate(['work_date' => '2026-11-10'], ['config' => $config, 'revision' => 1]);
            $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 0)->assertJsonPath('streak.active', false)->assertJsonPath('streak.status', 'broken');
        }
        $this->clock('2026-11-11 10:00');
        $this->activity('2026-11-11');
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 1)->assertJsonPath('streak.longest', 5);
    }

    public function test_absence_breaks_only_after_deadline_and_check_in_alone_does_not_add_record_streak(): void
    {
        $this->clock('2026-11-06 20:00');
        $this->activity('2026-11-05');
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 1)->assertJsonPath('streak.status', 'open');
        $this->clock('2026-11-06 23:59:59.500000');
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 1)->assertJsonPath('streak.status', 'open');
        $this->clock('2026-11-07 00:00');
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 0);
        $this->assertDatabaseHas('record_streak_days', ['work_date' => '2026-11-06', 'status' => 'broken']);
        $this->clock('2026-11-07 10:00');
        $report = $this->activity('2026-11-07', false);
        $report->delete();
        $this->getJson('/api/me/streak')->assertJsonPath('streak.current', 0)->assertJsonPath('streak.status', 'open');
    }

    public function test_delayed_bot_sync_preserves_pending_days_and_recalculates_once_evidence_arrives(): void
    {
        $this->start('2026-11-10');
        $this->activity('2026-11-10', false);
        $this->activity('2026-11-11');
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 0)->assertJsonPath('streak.pending_days', 1)->assertJsonPath('streak.queued_days', 1);
        $this->recording('2026-11-10');
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 2)->assertJsonPath('streak.pending_days', 0);
    }

    public function test_manual_verification_is_admin_only_revision_bound_and_does_not_change_payroll_or_qa(): void
    {
        $this->start('2026-11-11');
        $report = $this->activity('2026-11-11', false);
        $url = '/api/work-reports/'.$report->id.'/streak-verify';
        $this->postJson($url, ['expected_revision' => 1])->assertForbidden();
        $admin = User::factory()->create();
        $this->actingAs($admin)->postJson($url, ['expected_revision' => 2])->assertConflict();
        $this->postJson($url, ['expected_revision' => 1])->assertOk()->assertJsonPath('streak.current', 1);
        $this->postJson($url, ['expected_revision' => 1])->assertOk()->assertJsonPath('streak.current', 1);
        $this->assertDatabaseHas('record_streak_days', ['work_date' => '2026-11-11', 'verified_by' => $admin->id, 'manual_report_revision' => 1]);
        $report->update(['revision' => 2]);
        $this->actingAs($this->worker)->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 0)->assertJsonPath('streak.status', 'pending');
        $this->assertDatabaseCount('payslips', 0);
        $this->assertDatabaseCount('studio_recordings', 0);
    }

    public function test_streak_survives_payroll_period_boundary_and_past_routine_changes(): void
    {
        $this->clock('2026-11-23 10:00');
        $this->start('2026-11-20');
        foreach (['2026-11-20', '2026-11-21', '2026-11-22', '2026-11-23'] as $date) {
            $this->activity($date);
        }
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 4);
        $this->pattern['karyawan'] = [];
        ShiftSetting::where('setting_key', 'main_shift_config')->update(['setting_value' => $this->pattern]);
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 3)->assertJsonPath('streak.status', 'protected');
    }

    public function test_only_own_identity_is_returned_and_pre_feature_days_are_not_penalised(): void
    {
        $this->start('2026-11-11');
        $this->activity('2026-11-11');
        $this->worker->forceFill(['created_at' => '2026-11-01 00:00:00'])->save();
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 1)->assertJsonPath('streak.started_on', '2026-11-11');
        $other = app(ContributorAccess::class)->sync(Contributor::create(['nama' => 'Another Person', 'email' => 'other@example.test']));
        $this->actingAs($other)->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.current', 0)->assertJsonPath('streak.attendance_id', null);
        $this->actingAs(User::factory()->create())->getJson('/api/me/streak')->assertForbidden();
        $this->assertSame(1, RecordStreakDay::where('contributor_id', $this->worker->contributor_id)->count());
    }

    public function test_overnight_recording_and_attendance_belong_to_session_start_date(): void
    {
        $this->clock('2026-11-12 01:00');
        $this->start('2026-11-11');
        $this->pattern['start_time'] = '23:00';
        $this->pattern['session_duration_min'] = 180;
        ShiftSetting::where('setting_key', 'main_shift_config')->update(['setting_value' => $this->pattern]);
        $report = $this->activity('2026-11-11');
        $overnight = CarbonImmutable::parse('2026-11-12 00:30', 'Asia/Makassar')->utc();
        Attendance::where('user_id', $this->worker->id)->update(['checked_in_at' => $overnight, 'work_date' => '2026-11-12']);
        StudioRecording::where('record_id', 'streak-2026-11-11')->update(['recorded_at' => $overnight]);
        $this->getJson('/api/me/streak')->assertOk()->assertJsonPath('streak.work_date', '2026-11-11')->assertJsonPath('streak.current', 1)->assertJsonPath('streak.status', 'success');
    }

    public function test_overnight_check_in_and_report_use_work_date_and_do_not_block_next_day_check_in(): void
    {
        $this->clock('2026-11-12 00:30');
        $this->start('2026-11-11');
        $this->pattern['start_time'] = '23:00';
        $this->pattern['session_duration_min'] = 180;
        ShiftSetting::where('setting_key', 'main_shift_config')->update(['setting_value' => $this->pattern]);
        $gps = ['latitude' => 0, 'longitude' => 0, 'accuracy_m' => 1, 'captured_at' => now()->toIso8601String()];
        $this->postJson('/api/me/attendance', $gps)->assertOk()->assertJsonPath('attendance.work_date', '2026-11-11')->assertJsonPath('already_checked_in', false);
        $this->getJson('/api/me/work-reports?month=2026-11')->assertOk()->assertJsonPath('today', '2026-11-11');
        $this->putJson('/api/me/work-report', ['work_date' => '2026-11-11', 'expected_revision' => 0, 'phone_durations' => ['sendiri' => 60]])->assertOk();
        $this->getJson('/api/me/attendance?month=2026-11')->assertOk()->assertJsonPath('today', '2026-11-12')
            ->assertJsonPath('attendance_work_date', '2026-11-11')->assertJsonPath('active_schedule.date', '2026-11-11')
            ->assertJsonPath('today_attendance.work_date', '2026-11-11');

        $this->clock('2026-11-12 10:00');
        $gps['captured_at'] = now()->toIso8601String();
        $this->postJson('/api/me/attendance', $gps)->assertOk()->assertJsonPath('attendance.work_date', '2026-11-12')->assertJsonPath('already_checked_in', false);
        $this->assertDatabaseCount('attendances', 2);
    }
}
