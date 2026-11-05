<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyWorkReportTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $email = 'reporter@example.com'): User
    {
        return app(ContributorAccess::class)->sync(Contributor::create(['nama' => $email, 'email' => $email]));
    }

    private function payload(): array
    {
        return ['work_date' => now('Asia/Makassar')->toDateString(), 'total_duration_minutes' => 150, 'phones' => ['putih', 'pink_st'], 'expected_revision' => 0];
    }

    public function test_employee_submits_and_updates_own_daily_duration_and_multiple_phones(): void
    {
        $user = $this->employee();
        $this->actingAs($user);
        $data = $this->payload() + ['user_id' => 999, 'contributor_id' => 999, 'contributor_name' => 'Forged name'];
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.user_id', $user->id)
            ->assertJsonPath('report.contributor_name', $user->name)->assertJsonPath('report.phones', ['putih', 'pink_st'])->assertJsonPath('report.revision', 1);
        $data['total_duration_minutes'] = 240;
        $data['expected_revision'] = 1;
        $data['phones'] = ['biru', 'hijau', 'hitam', 'pink'];
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.total_duration_minutes', 240)->assertJsonPath('report.revision', 2);
        $this->assertDatabaseCount('daily_work_reports', 1);
        $this->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertOk()->assertJsonPath('today_report.revision', 2)->assertJsonCount(1, 'reports');
    }

    public function test_validation_rejects_empty_duration_invalid_or_duplicate_phone_and_wrong_date(): void
    {
        $this->actingAs($this->employee());
        foreach ([['total_duration_minutes' => 0], ['total_duration_minutes' => -5], ['total_duration_minutes' => 8641], ['phones' => []], ['phones' => ['ungu']], ['phones' => ['pink', 'pink']], ['work_date' => now('Asia/Makassar')->subDay()->toDateString()]] as $change) {
            $this->putJson('/api/me/work-report', array_replace($this->payload(), $change))->assertUnprocessable();
        }
        $this->assertDatabaseCount('daily_work_reports', 0);
    }

    public function test_reports_are_private_and_admin_gets_recap_for_selected_date(): void
    {
        $a = $this->employee('first@example.com');
        $b = $this->employee('second@example.com');
        $this->actingAs($a)->putJson('/api/me/work-report', $this->payload())->assertOk();
        $this->actingAs($b)->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertJsonCount(0, 'reports')->assertJsonPath('today_report', null);
        $this->getJson('/api/work-reports?date='.now('Asia/Makassar')->toDateString())->assertForbidden();
        $this->get('/work-reports.html')->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/api/work-reports?date='.now('Asia/Makassar')->toDateString())->assertOk()->assertJsonCount(1, 'reports')->assertJsonPath('total_duration_minutes', 150);
        $this->putJson('/api/me/work-report', $this->payload())->assertForbidden();
    }

    public function test_date_is_wita_and_stale_revision_does_not_overwrite_report(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(16, 0));
        $this->actingAs($this->employee());
        $this->putJson('/api/me/work-report', $this->payload())->assertOk()->assertJsonPath('report.work_date', '2026-11-04');
        $this->putJson('/api/me/work-report', array_replace($this->payload(), ['total_duration_minutes' => 700]))->assertConflict();
        $this->assertDatabaseHas('daily_work_reports', ['total_duration_minutes' => 150, 'revision' => 1]);
        $this->travelBack();
    }

    public function test_guest_cannot_submit_or_read_reports(): void
    {
        $this->putJson('/api/me/work-report', $this->payload())->assertUnauthorized();
        $this->getJson('/api/me/work-reports?month=2026-11')->assertUnauthorized();
        $this->getJson('/api/work-reports?date=2026-11-03')->assertUnauthorized();
    }

    public function test_employee_can_submit_per_phone_durations_in_minutes(): void
    {
        $user = $this->employee('durations_user@example.com');
        $this->actingAs($user);

        $today = now('Asia/Makassar')->toDateString();

        $res = $this->putJson('/api/me/work-report', [
            'work_date' => $today,
            'expected_revision' => 0,
            'phone_durations' => [
                'putih' => 120,
                'pink' => 180,
            ],
        ]);

        $res->assertOk()
            ->assertJsonPath('report.total_duration_minutes', 300)
            ->assertJsonPath('report.phones', ['putih', 'pink'])
            ->assertJsonPath('report.phone_durations.putih', 120)
            ->assertJsonPath('report.phone_durations.pink', 180);

        $this->assertDatabaseHas('daily_work_reports', [
            'user_id' => $user->id,
            'work_date' => $today,
            'total_duration_minutes' => 300,
        ]);
    }

    public function test_admin_can_get_assist_matches_and_assign_candidate_recordings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $worker = $this->employee('afif_assist@example.com');
        $today = now('Asia/Makassar')->toDateString();

        // Worker submits report: HP Putih 120m, HP Pink 180m
        $this->actingAs($worker)->putJson('/api/me/work-report', [
            'work_date' => $today,
            'expected_revision' => 0,
            'phone_durations' => [
                'putih' => 120,
                'pink' => 180,
            ],
        ])->assertOk();

        // Register HP Putih to account email
        \App\Models\DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'device_putih@studio.test',
            'is_active' => true,
        ]);

        // Create 2 candidate recordings on HP Putih on today's WITA date
        $nowWita = \Carbon\CarbonImmutable::now('Asia/Makassar')->setTime(10, 0);
        \App\Models\StudioRecording::create([
            'record_id' => 'rec_putih_001',
            'participant_email' => 'device_putih@studio.test',
            'task_title' => 'Task Putih A',
            'recorded_at' => $nowWita->utc()->format('Y-m-d H:i:s'),
            'duration_min' => 60,
            'billable_min' => 60,
            'manual_payment_status' => 'Belum Rekap',
        ]);
        \App\Models\StudioRecording::create([
            'record_id' => 'rec_putih_002',
            'participant_email' => 'device_putih@studio.test',
            'task_title' => 'Task Putih B',
            'recorded_at' => $nowWita->addMinutes(70)->utc()->format('Y-m-d H:i:s'),
            'duration_min' => 58,
            'billable_min' => 58,
            'manual_payment_status' => 'Belum Rekap',
        ]);

        // 1. Admin gets assist matches
        $res = $this->actingAs($admin)->getJson("/api/work-reports/assist-matches?date={$today}");
        $res->assertOk()
            ->assertJsonStructure(['date', 'assist_data'])
            ->assertJsonPath('assist_data.0.contributor_name', $worker->name);

        $putihMatch = collect($res->json('assist_data.0.phones'))->firstWhere('phone_key', 'putih');
        $this->assertNotNull($putihMatch);
        $this->assertEquals(120, $putihMatch['reported_minutes']);
        $this->assertEquals(2, $putihMatch['unassigned_count']);
        $this->assertEquals(118.0, $putihMatch['unassigned_minutes']);
        $this->assertEquals('exact_match', $putihMatch['status']);

        // 2. Admin 1-click assigns the matched recordings to Afif
        $assignRes = $this->actingAs($admin)->postJson('/api/work-reports/assist-assign', [
            'contributor_id' => $worker->contributor_id,
            'worker_name' => $worker->name,
            'record_ids' => ['rec_putih_001', 'rec_putih_002'],
        ]);

        $assignRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('updated_count', 2);

        $this->assertDatabaseHas('studio_recordings', [
            'record_id' => 'rec_putih_001',
            'assigned_contributor_id' => $worker->contributor_id,
            'assigned_worker_name' => $worker->name,
        ]);

        // 3. Admin can unassign if needed
        $unassignRes = $this->actingAs($admin)->postJson('/api/work-reports/assist-unassign', [
            'record_ids' => ['rec_putih_001'],
        ]);

        $unassignRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('updated_count', 1);

        $this->assertDatabaseHas('studio_recordings', [
            'record_id' => 'rec_putih_001',
            'assigned_worker_name' => null,
        ]);
    }

    public function test_admin_can_edit_and_delete_daily_work_report(): void
    {
        $employee = $this->employee('contributor@example.com');
        $this->actingAs($employee)->putJson('/api/me/work-report', [
            'work_date' => now('Asia/Makassar')->toDateString(),
            'expected_revision' => 0,
            'phones' => ['putih'],
            'phone_durations' => ['putih' => 120],
        ])->assertOk();

        $report = \App\Models\DailyWorkReport::first();
        $this->assertNotNull($report);
        $this->assertEquals(120, $report->total_duration_minutes);

        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Admin edits report (changes phones to putih + pink_st and total to 180)
        $this->actingAs($admin)->putJson('/api/work-reports/'.$report->id, [
            'phones' => ['putih', 'pink_st'],
            'phone_durations' => ['putih' => 100, 'pink_st' => 80],
            'total_duration_minutes' => 180,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('report.total_duration_minutes', 180)
            ->assertJsonPath('report.phones', ['putih', 'pink_st']);

        $report->refresh();
        $this->assertEquals(180, $report->total_duration_minutes);
        $this->assertEquals(['putih' => 100, 'pink_st' => 80], $report->phone_durations);

        // 2. Admin deletes report
        $this->actingAs($admin)->deleteJson('/api/work-reports/'.$report->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('daily_work_reports', ['id' => $report->id]);
    }
}
