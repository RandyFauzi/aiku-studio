<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\ShiftDay;
use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\AtlasSync;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use App\Services\RecordingMatching;
use App\Services\ShiftPatterns;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordingMatchingTest extends TestCase
{
    use RefreshDatabase;

    private function report(string $phone = 'putih', ?string $email = 'shared@example.test'): DailyWorkReport
    {
        $person = Contributor::create(['nama' => 'Budi', 'email' => 'budi@example.test']);
        return DailyWorkReport::create(['user_id' => User::factory()->create()->id, 'contributor_id' => $person->id,
            'contributor_name' => 'Budi', 'work_date' => '2026-11-05', 'phones' => [$phone],
            'phone_emails' => $email ? [$phone => $email] : [], 'phone_durations' => [$phone => 60],
            'total_duration_minutes' => 60, 'revision' => 1]);
    }

    private function recording(string $id, string $time = '2026-11-06 02:00:00', string $email = 'shared@example.test'): StudioRecording
    {
        return StudioRecording::create(['record_id' => $id, 'participant_email' => $email, 'recorded_at' => $time,
            'duration_min' => 60, 'billable_min' => 30, 'pending_min' => 30, 'notes' => 'Existing note']);
    }

    private function confirmation(DailyWorkReport $report, array $ids): array
    {
        return ['report_id' => $report->id, 'expected_revision' => 1, 'phone_key' => $report->phones[0],
            'record_ids' => $ids, 'reason' => 'Upload terlambat; sudah diperiksa sesuai laporan.'];
    }

    public function test_group_boundaries_use_daily_settings_and_overnight_is_previous_workday(): void
    {
        $cfg = config('shifts');
        $cfg['start_time'] = '08:00'; $cfg['sessions_count'] = 2; $cfg['session_durations'] = [300, 240]; $cfg['charging_duration_min'] = 0;
        ShiftDay::create(['work_date' => '2026-11-06', 'config' => $cfg, 'revision' => 1]);
        $this->recording('before', '2026-11-05 23:59:59');
        $this->recording('one', '2026-11-06 04:59:59'); // 12:59 WITA
        $this->recording('two', '2026-11-06 05:00:00'); // 13:00 WITA
        $this->recording('night', '2026-11-06 23:59:59'); // next day 07:59
        $this->recording('next', '2026-11-07 00:00:00');
        $this->actingAs(User::factory()->create())->getJson('/api/recording-matching?recording_date=2026-11-06&report_date=2026-11-05')
            ->assertOk()->assertJsonPath('total', 3)->assertJsonPath('groups.0.recordings.0.record_id', 'one')
            ->assertJsonPath('groups.1.recordings.0.record_id', 'two')->assertJsonPath('groups.2.recordings.0.record_id', 'night');
    }

    public function test_period_settings_breaks_and_next_days_changed_start_are_respected(): void
    {
        $cfg = config('shifts'); $cfg['start_time'] = '20:00'; $cfg['sessions_count'] = 2;
        $cfg['session_durations'] = [240, 180]; $cfg['charging_duration_min'] = 60;
        $patterns = app(ShiftPatterns::class);
        $period = app(PayrollPeriod::class)->cutoff(CarbonImmutable::parse('2026-11-06'));
        ShiftSetting::create(['setting_key' => $patterns->key($period), 'setting_value' => ['config' => $cfg, 'revision' => 1]]);
        $next = $cfg; $next['start_time'] = '02:00';
        ShiftDay::create(['work_date' => '2026-11-07', 'config' => $next, 'revision' => 1]);
        $windows = app(RecordingMatching::class)->windows('2026-11-06');
        $this->assertSame('break-1', $windows[1]['id']);
        $this->assertSame('2026-11-07T02:00:00+08:00', end($windows)['end_at']);
    }

    public function test_delayed_record_can_be_confirmed_to_previous_report_with_audit_and_sync_preserves_owner(): void
    {
        $report = $this->report(); $this->recording('delayed');
        $admin = User::factory()->create();
        $this->actingAs($admin)->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['delayed']))->assertOk()->assertJsonPath('updated', 1);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'delayed', 'assigned_contributor_id' => $report->contributor_id, 'notes' => 'Existing note']);
        $this->assertDatabaseHas('recording_owner_confirmations', ['record_id' => 'delayed', 'report_id' => $report->id, 'admin_id' => $admin->id, 'work_date' => '2026-11-05']);
        app(AtlasSync::class)->ingest(['userTaskId' => 'delayed', 'recordedAt' => '2026-11-06T02:00:00Z',
            'durationSeconds' => 3600, 'tileBillableHours' => 0.5], 'shared@example.test');
        $this->assertSame($report->contributor_id, StudioRecording::find('delayed')->assigned_contributor_id);
        $period = app(PayrollPeriod::class)->cutoff(CarbonImmutable::parse('2026-11-06'));
        $evidence = app(PayrollCalculator::class)->evidence($period, 'Budi');
        $this->assertEquals(30, $evidence[0]['approved_mins']);
        $this->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['delayed']))->assertConflict();
        $this->assertDatabaseCount('recording_owner_confirmations', 1);
    }

    public function test_batch_conflict_and_email_mismatch_roll_back_everything(): void
    {
        $report = $this->report(); $this->recording('free');
        $taken = $this->recording('taken'); $taken->update(['assigned_worker_name' => 'Andi']);
        $this->actingAs(User::factory()->create())->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['free', 'taken']))->assertConflict();
        $this->assertNull(StudioRecording::find('free')->assigned_contributor_id);
        $this->recording('other-email', email: 'other@example.test');
        $this->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['free', 'other-email']))->assertUnprocessable();
        $this->assertDatabaseCount('recording_owner_confirmations', 0);
    }

    public function test_stale_report_missing_record_and_paid_record_are_rejected(): void
    {
        $report = $this->report(); $paid = $this->recording('paid'); $paid->update(['manual_payment_status' => 'Paid']);
        $this->actingAs(User::factory()->create())->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['paid']))->assertConflict();
        $this->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['missing']))->assertConflict();
        $report->update(['revision' => 2]);
        $this->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['paid']))->assertConflict();
        $this->assertDatabaseCount('recording_owner_confirmations', 0);
    }

    public function test_personal_phone_does_not_use_master_and_master_email_is_only_a_candidate(): void
    {
        $report = $this->report('sendiri'); $this->recording('personal');
        $this->actingAs(User::factory()->create())->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['personal']))->assertOk();
        $this->assertDatabaseCount('device_accounts', 0);
        $report->update(['phone_emails' => []]);
        $this->recording('no-email');
        $this->postJson('/api/recording-matching/confirm', $this->confirmation($report, ['no-email']))->assertUnprocessable();
        $studio = $this->report('putih', null);
        DeviceAccount::create(['phone_key' => 'putih', 'email' => 'shared@example.test', 'is_active' => false]);
        $this->assertSame('master_candidates', app(RecordingMatching::class)->phones($studio)[0]['email_source']);
        $this->postJson('/api/recording-matching/confirm', $this->confirmation($studio, ['no-email']))->assertOk();
    }

    public function test_worker_and_guest_cannot_read_or_confirm(): void
    {
        $url = '/api/recording-matching?recording_date=2026-11-06&report_date=2026-11-05';
        $this->getJson($url)->assertUnauthorized();
        $this->postJson('/api/recording-matching/confirm', [])->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'contributor']))->getJson($url)->assertForbidden();
        $this->postJson('/api/recording-matching/confirm', [])->assertForbidden();
    }

    public function test_report_emails_and_paginated_group_totals_are_returned_without_automatic_assignment(): void
    {
        $report = $this->report();
        for ($i = 0; $i < 101; $i++) $this->recording('r'.$i);
        $this->actingAs(User::factory()->create())->getJson('/api/recording-matching?recording_date=2026-11-06&report_date=2026-11-05&page=2&email=SHARED@example.test')
            ->assertOk()->assertJsonPath('total', 101)->assertJsonPath('last_page', 2)
            ->assertJsonPath('reports.0.phones.0.emails.0', 'shared@example.test')->assertJsonPath('reports.0.phones.0.email_source', 'report');
        $this->assertSame(0, StudioRecording::whereNotNull('assigned_contributor_id')->count());
    }

    public function test_main_recording_list_includes_shift_dividers_metadata_and_submission_timestamps(): void
    {
        $report = $this->report();
        $report->forceFill(['created_at' => '2026-11-05 04:15:00', 'updated_at' => '2026-11-05 05:30:00', 'revision' => 2])->save();
        $cfg = config('shifts'); $cfg['start_time'] = '08:00'; $cfg['sessions_count'] = 2;
        $cfg['session_durations'] = [300, 240]; $cfg['charging_duration_min'] = 0;
        ShiftDay::create(['work_date' => '2026-11-06', 'config' => $cfg, 'revision' => 1]);
        $this->recording('shift-one', '2026-11-06 04:59:59');
        $this->recording('shift-two', '2026-11-06 05:00:00');
        $this->recording('overnight', '2026-11-06 23:59:00');
        $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings?limit=2')
            ->assertOk()->assertJsonPath('total', 3)->assertJsonPath('recordings.0.shift_group.key', '2026-11-06:outside')
            ->assertJsonPath('recordings.1.shift_group.key', '2026-11-06:shift-2');
        $this->getJson('/api/studio-recordings?limit=2&page=2')->assertOk()
            ->assertJsonPath('recordings.0.shift_group.label', '6 November 2026 · Shift 1');
        $this->getJson('/api/recording-matching?recording_date=2026-11-06&report_date=2026-11-05')->assertOk()
            ->assertJsonPath('reports.0.submitted_at', '2026-11-05T04:15:00+00:00')->assertJsonPath('reports.0.reported_at', '2026-11-05T05:30:00+00:00');
        $this->get('/recordings')->assertOk()->assertDontSee('Cocokkan recording per shift')->assertSee('data-shift-divider', false);
        $this->get('/work-reports')->assertOk()->assertSee('Dikirim:')->assertSee('Diperbarui:');
    }
}
