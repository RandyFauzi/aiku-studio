<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\RecordingOwnerRecommendations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordingOwnerRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    private function report(string $name, array $tasks = ['Wrap a gift'], ?string $email = 'shared@example.test', string $date = '2026-11-06'): DailyWorkReport
    {
        $person = Contributor::create(['nama' => $name, 'email' => strtolower($name).'@example.test']);
        return DailyWorkReport::create(['user_id' => User::factory()->create()->id, 'contributor_id' => $person->id,
            'contributor_name' => $name, 'work_date' => $date, 'phones' => ['putih'], 'phone_durations' => ['putih' => 120],
            'phone_emails' => $email === null ? [] : ['putih' => $email], 'phone_tasks' => ['putih' => $tasks], 'total_duration_minutes' => 120, 'revision' => 1]);
    }

    private function recording(): StudioRecording
    {
        return StudioRecording::create(['record_id' => 'recommend-me', 'participant_email' => 'shared@example.test',
            'task_title' => 'Wrap a gift', 'recorded_at' => '2026-11-06 04:00:00', 'duration_min' => 30]);
    }

    public function test_ranks_report_email_task_and_actual_schedule_groups_without_assigning_anything(): void
    {
        $best = $this->report('Budi'); $this->report('Andi', ['Other task']); $this->report('WrongEmail', email: 'other@example.test');
        $cfg = config('shifts'); $cfg['start_time'] = '09:00'; $cfg['sessions_count'] = 1; $cfg['session_durations'] = [240]; $cfg['charging_duration_min'] = 0; $cfg['day_off'] = 'none';
        $cfg['karyawan'] = [['contributor_id' => $best->contributor_id, 'name' => 'Budi', 'sessions' => [1], 'alat' => 'Alat 1']];
        ShiftSetting::create(['setting_key' => 'main_shift_config', 'setting_value' => $cfg]);
        $record = $this->recording();
        $result = $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings/recommend-me/recommendations')->assertOk()
            ->assertJsonPath('candidates.0.name', 'Budi')->assertJsonPath('candidates.0.signal_count', 3)
            ->assertJsonPath('candidates.0.report_id', $best->id)->assertJsonPath('candidates.0.can_confirm', true)->assertJsonCount(2, 'candidates');
        $this->assertNull($record->fresh()->assigned_contributor_id);
        $this->assertDatabaseCount('recording_owner_confirmations', 0);
    }

    public function test_same_evidence_is_marked_ambiguous_and_previous_day_reports_remain_candidates(): void
    {
        $this->report('Budi'); $this->report('Andi'); $this->report('Delayed', date: '2026-11-05'); $this->report('TooOld', date: '2026-11-01');
        $this->recording();
        $data = $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings/recommend-me/recommendations')->assertOk()
            ->assertJsonPath('ambiguous', true)->assertJsonCount(3, 'candidates')->json();
        $delayed = collect($data['candidates'])->firstWhere('name', 'Delayed');
        $this->assertSame('2026-11-05', $delayed['work_date']);
        $this->assertContains('Laporan hari sebelumnya; periksa kemungkinan upload terlambat.', $delayed['warnings']);
    }

    public function test_does_not_combine_email_on_one_phone_with_task_on_another(): void
    {
        $report = $this->report('Budi', ['Other task']);
        $report->update(['phones' => ['putih', 'pink'], 'phone_emails' => ['putih' => 'shared@example.test', 'pink' => 'wrong@example.test'],
            'phone_tasks' => ['putih' => ['Other task'], 'pink' => ['Wrap a gift']]]);
        $this->recording();
        $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings/recommend-me/recommendations')->assertOk()
            ->assertJsonPath('candidates.0.phone_key', 'putih')->assertJsonPath('candidates.0.signals.task.matched', false);
    }

    public function test_master_is_weaker_than_report_email_and_missing_email_cannot_be_confirmed(): void
    {
        $this->report('Explicit'); $this->report('Master', email: null);
        DeviceAccount::create(['phone_key' => 'putih', 'email' => 'shared@example.test', 'is_active' => false]);
        $unknown = $this->report('Missing', email: null); $unknown->update(['phones' => ['sendiri'], 'phone_tasks' => ['sendiri' => ['Wrap a gift']]]);
        $this->recording();
        $data = $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings/recommend-me/recommendations')->assertOk()->assertJsonPath('candidates.0.name', 'Explicit')->json();
        $this->assertSame('master', collect($data['candidates'])->firstWhere('name', 'Master')['signals']['email']['source']);
        $this->assertFalse(collect($data['candidates'])->firstWhere('name', 'Missing')['can_confirm']);
    }

    public function test_overnight_uses_previous_work_date_and_owned_records_are_not_reassigned(): void
    {
        $report = $this->report('Budi'); $record = $this->recording();
        $record->update(['recorded_at' => '2026-11-06 23:30:00', 'assigned_worker_name' => 'Other', 'assigned_contributor_id' => 999]);
        $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings/recommend-me/recommendations')->assertOk()
            ->assertJsonPath('group.work_date', '2026-11-06')->assertJsonPath('candidates.0.work_date', $report->work_date)->assertJsonPath('candidates.0.can_confirm', false);
        $this->assertSame('Other', $record->fresh()->assigned_worker_name);
    }

    public function test_shared_service_handles_missing_time_and_endpoint_requires_admin(): void
    {
        $record = $this->recording(); $record->update(['recorded_at' => null]);
        $this->assertSame([], app(RecordingOwnerRecommendations::class)->recommend($record)['candidates']);
        $url = '/api/studio-recordings/recommend-me/recommendations';
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'contributor']))->getJson($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/api/studio-recordings/missing/recommendations')->assertNotFound();
    }
}
