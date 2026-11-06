<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorAccess;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

    public function test_legacy_unique_index_is_repaired_without_losing_first_session(): void
    {
        Schema::table('daily_work_reports', fn (Blueprint $table) => $table->unique(['user_id', 'work_date']));
        $this->actingAs($this->employee());
        $data = $this->payload() + ['is_new' => true, 'phone_tasks' => ['putih' => ['Wrap a gift'], 'pink_st' => ['Break down cardboard']]];
        $this->putJson('/api/me/work-report', $data)->assertOk();
        $first = DailyWorkReport::first()->toArray();
        $this->putJson('/api/me/work-report', $data)->assertStatus(503)
            ->assertJsonPath('message', 'Sesi tambahan belum bisa disimpan karena database server belum diperbarui. Laporan sebelumnya tetap aman. Hubungi admin untuk pembaruan database, lalu coba lagi.');
        $this->assertDatabaseCount('daily_work_reports', 1);

        $repair = require database_path('migrations/2026_11_06_000005_repair_daily_work_report_session_index.php');
        $repair->up();
        $repair->up(); // Retrying after partial MySQL DDL must also be safe.
        $this->putJson('/api/me/work-report', $data + ['shift_label' => 'Shift 2'])->assertOk()
            ->assertJsonPath('report.revision', 1)->assertJsonPath('report.shift_label', 'Shift 2');
        $this->assertDatabaseCount('daily_work_reports', 2);
        $this->assertSame($first, DailyWorkReport::findOrFail($first['id'])->toArray());
        $this->assertTrue(Schema::hasIndex('daily_work_reports', ['user_id', 'work_date']));
        $this->assertFalse(Schema::hasIndex('daily_work_reports', ['user_id', 'work_date'], 'unique'));
    }

    public function test_admin_second_session_on_legacy_schema_returns_actionable_error(): void
    {
        Schema::table('daily_work_reports', fn (Blueprint $table) => $table->unique(['user_id', 'work_date']));
        $employee = $this->employee();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->payload() + ['contributor_id' => $employee->contributor_id, 'is_new' => true];
        $this->postJson('/api/work-reports', $data)->assertOk();
        $this->postJson('/api/work-reports', $data)->assertStatus(503);
        $this->assertDatabaseCount('daily_work_reports', 1);
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
        foreach ([['total_duration_minutes' => 0], ['total_duration_minutes' => -5], ['total_duration_minutes' => 10081], ['phones' => []], ['phones' => ['ungu']], ['phones' => ['pink', 'pink']], ['work_date' => now('Asia/Makassar')->subDay()->toDateString()]] as $change) {
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
        DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'device_putih@studio.test',
            'is_active' => true,
        ]);

        // Create 2 candidate recordings on HP Putih on today's WITA date
        $nowWita = CarbonImmutable::now('Asia/Makassar')->setTime(10, 0);
        StudioRecording::create([
            'record_id' => 'rec_putih_001',
            'participant_email' => 'device_putih@studio.test',
            'task_title' => 'Task Putih A',
            'recorded_at' => $nowWita->utc()->format('Y-m-d H:i:s'),
            'duration_min' => 60,
            'billable_min' => 60,
            'manual_payment_status' => 'Belum Rekap',
        ]);
        StudioRecording::create([
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

        $report = DailyWorkReport::first();
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

    public function test_admin_can_create_daily_work_report_for_any_contributor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributor = Contributor::create([
            'nama' => 'Budi Santoso',
            'email' => 'budi_create@example.com',
            'role' => 'contributor',
        ]);

        $today = now('Asia/Makassar')->toDateString();

        // 1. Admin creates report for Budi Santoso
        $response = $this->actingAs($admin)->postJson('/api/work-reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'phones' => ['putih', 'biru'],
            'phone_durations' => ['putih' => 120, 'biru' => 60],
            'total_duration_minutes' => 180,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('report.contributor_name', 'Budi Santoso')
            ->assertJsonPath('report.contributor_id', $contributor->id)
            ->assertJsonPath('report.total_duration_minutes', 180)
            ->assertJsonPath('report.phones', ['putih', 'biru']);

        $this->assertDatabaseHas('daily_work_reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'total_duration_minutes' => 180,
        ]);

        // 2. Creating again for same contributor and date updates the report
        $updateResponse = $this->actingAs($admin)->postJson('/api/work-reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'phones' => ['putih', 'biru', 'hijau'],
            'phone_durations' => ['putih' => 120, 'biru' => 60, 'hijau' => 60],
            'total_duration_minutes' => 240,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('report.total_duration_minutes', 240)
            ->assertJsonPath('report.revision', 2);

        $this->assertDatabaseHas('daily_work_reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'total_duration_minutes' => 240,
        ]);

        // 3. Non-admin (contributor) cannot access admin endpoint
        $worker = $this->employee('other_worker@example.com');
        $this->actingAs($worker)->postJson('/api/work-reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'phones' => ['putih'],
            'phone_durations' => ['putih' => 60],
            'total_duration_minutes' => 60,
        ])->assertForbidden();
    }

    public function test_report_scoped_phone_emails_does_not_modify_master_device_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributor = Contributor::create([
            'nama' => 'Ghina Mawaddah',
            'email' => 'ghina@example.com',
            'role' => 'contributor',
        ]);

        $masterDevice = DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'master_putih@gmail.com',
            'account_name' => 'aikuputih_master',
            'is_active' => true,
        ]);

        $today = now('Asia/Makassar')->toDateString();

        // Admin creates work report with custom overridden email
        $res = $this->actingAs($admin)->postJson('/api/work-reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'phones' => ['putih'],
            'phone_durations' => ['putih' => 130],
            'phone_emails' => ['putih' => 'override_khusus_laporan@gmail.com'],
            'total_duration_minutes' => 130,
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('report.phone_emails.putih', 'override_khusus_laporan@gmail.com');

        $reportId = $res->json('report.id');

        // Verify DeviceAccount in database remains completely untouched!
        $this->assertDatabaseHas('device_accounts', [
            'id' => $masterDevice->id,
            'phone_key' => 'putih',
            'email' => 'master_putih@gmail.com',
            'account_name' => 'aikuputih_master',
            'is_active' => true,
        ]);

        // Verify report in database has the overridden email
        $report = DailyWorkReport::find($reportId);
        $this->assertEquals('override_khusus_laporan@gmail.com', $report->phone_emails['putih'] ?? null);

        // Admin updates report with another email
        $updateRes = $this->actingAs($admin)->putJson('/api/work-reports/'.$reportId, [
            'phones' => ['putih'],
            'phone_durations' => ['putih' => 130],
            'phone_emails' => ['putih' => 'updated_override@gmail.com'],
            'total_duration_minutes' => 130,
        ]);

        $updateRes->assertOk()
            ->assertJsonPath('report.phone_emails.putih', 'updated_override@gmail.com');

        // Master device account is STILL untouched!
        $this->assertEquals('master_putih@gmail.com', $masterDevice->fresh()->email);

        // Assist matches checks the overridden email
        $assistRes = $this->actingAs($admin)->getJson('/api/work-reports/assist-matches?date='.$today);
        $assistRes->assertOk();
        $phoneData = $assistRes->json('assist_data.0.phones.0');
        $this->assertEquals('updated_override@gmail.com', $phoneData['active_email']);
        $this->assertTrue($phoneData['is_email_overridden']);
    }

    public function test_personal_phone_optional_email_persists_for_worker_and_can_be_cleared(): void
    {
        $user = $this->employee();
        $this->actingAs($user);
        $data = ['work_date' => now('Asia/Makassar')->toDateString(), 'expected_revision' => 0, 'phone_durations' => ['sendiri' => 90]];
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.phones', ['sendiri'])->assertJsonPath('report.phone_emails', null);
        $data['expected_revision'] = 1;
        $data['phone_emails'] = ['sendiri' => 'pribadi@example.test'];
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.phone_emails.sendiri', 'pribadi@example.test');
        $this->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertOk()
            ->assertJsonPath('phone_options.sendiri', 'Sendiri')->assertJsonPath('today_report.phone_emails.sendiri', 'pribadi@example.test');
        $data['expected_revision'] = 2;
        $data['phone_emails']['sendiri'] = 'bukan-email';
        $this->putJson('/api/me/work-report', $data)->assertUnprocessable();
        $data['phone_emails']['sendiri'] = '';
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.phone_emails', null);
        $this->assertDatabaseCount('device_accounts', 0);
        $this->assertDatabaseCount('studio_accounts', 0);
    }

    public function test_admin_can_create_and_edit_personal_phone_without_studio_account_integration(): void
    {
        $worker = $this->employee();
        $this->actingAs(User::factory()->create());
        $date = now('Asia/Makassar')->toDateString();
        $response = $this->postJson('/api/work-reports', ['contributor_id' => $worker->contributor_id, 'work_date' => $date,
            'phone_durations' => ['sendiri' => 120], 'phone_emails' => ['sendiri' => 'manual@example.test']])->assertOk();
        $id = $response->json('report.id');
        $this->putJson('/api/work-reports/'.$id, ['phone_durations' => ['sendiri' => 180], 'phone_emails' => ['sendiri' => 'edit@example.test']])
            ->assertOk()->assertJsonPath('report.phone_emails.sendiri', 'edit@example.test');
        $this->getJson('/api/work-reports/assist-matches?date='.$date)->assertOk()
            ->assertJsonPath('assist_data.0.phones.0.status', 'no_bot_data')
            ->assertJsonPath('assist_data.0.phones.0.emails', ['edit@example.test'])
            ->assertJsonPath('assist_data.0.phones.0.candidate_record_ids', []);
        $this->getJson('/api/devices')->assertOk()->assertJsonCount(6, 'phones');
        $this->postJson('/api/devices/accounts', ['phone_key' => 'sendiri', 'email' => 'manual@example.test'])->assertUnprocessable();
        $this->assertDatabaseCount('device_accounts', 0);
    }

    public function test_personal_phone_email_matches_only_same_wita_day_without_touching_master_accounts(): void
    {
        $worker = $this->employee();
        $date = now('Asia/Makassar')->toDateString();
        $start = CarbonImmutable::parse($date, 'Asia/Makassar')->startOfDay();
        $master = DeviceAccount::create(['phone_key' => 'putih', 'email' => 'personal@example.test', 'account_name' => 'Studio Master', 'is_active' => true]);
        $masterBefore = $master->fresh()->getAttributes();
        $other = Contributor::create(['nama' => 'Other Worker']);

        $recording = ['participant_email' => ' PERSONAL@EXAMPLE.TEST ', 'duration_min' => 60, 'billable_min' => 60,
            'recorded_at' => $start->addMinutes(5)->utc()->format('Y-m-d H:i:s'), 'manual_payment_status' => 'Belum Rekap'];
        StudioRecording::create(['record_id' => 'personal-match'] + $recording);
        StudioRecording::create(array_replace($recording, ['record_id' => 'previous-day', 'recorded_at' => $start->subMinute()->utc()->format('Y-m-d H:i:s')]));
        StudioRecording::create(array_replace($recording, ['record_id' => 'next-day', 'recorded_at' => $start->addDay()->utc()->format('Y-m-d H:i:s')]));
        StudioRecording::create(array_replace($recording, ['record_id' => 'other-email', 'participant_email' => 'different@example.test']));
        StudioRecording::create(array_replace($recording, ['record_id' => 'assigned-elsewhere', 'assigned_worker_name' => $other->nama, 'assigned_contributor_id' => $other->id]));

        $payload = ['work_date' => $date, 'expected_revision' => 0, 'phone_durations' => ['sendiri' => 60], 'phone_emails' => ['sendiri' => 'Personal@example.test']];
        $this->actingAs($worker)->putJson('/api/me/work-report', $payload)->assertOk();
        $admin = User::factory()->create();
        $this->actingAs($admin)->getJson('/api/work-reports/assist-matches?date='.$date)->assertOk()
            ->assertJsonPath('assist_data.0.phones.0.status', 'exact_match')
            ->assertJsonPath('assist_data.0.phones.0.emails', ['personal@example.test'])
            ->assertJsonPath('assist_data.0.phones.0.active_account_name', null)
            ->assertJsonPath('assist_data.0.phones.0.candidate_record_ids', ['personal-match'])
            ->assertJsonPath('assist_data.0.phones.0.other_assigned_count', 1)
            ->assertJsonPath('assist_data.0.phones.0.unassigned_minutes', 60);
        $this->assertSame($masterBefore, $master->fresh()->getAttributes());
        $this->assertDatabaseCount('device_accounts', 1);
        $this->assertDatabaseCount('studio_accounts', 0);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'personal-match', 'assigned_worker_name' => null]);

        $payload['expected_revision'] = 1;
        $payload['phone_emails']['sendiri'] = '';
        $this->actingAs($worker)->putJson('/api/me/work-report', $payload)->assertOk();
        $this->actingAs($admin)->getJson('/api/work-reports/assist-matches?date='.$date)->assertOk()
            ->assertJsonPath('assist_data.0.phones.0.status', 'personal_phone')
            ->assertJsonPath('assist_data.0.phones.0.emails', [])
            ->assertJsonPath('assist_data.0.phones.0.candidate_record_ids', []);
    }

    public function test_all_seven_report_phone_options_are_accepted_together(): void
    {
        $this->actingAs($this->employee());
        $this->putJson('/api/me/work-report', ['work_date' => now('Asia/Makassar')->toDateString(), 'expected_revision' => 0,
            'phone_durations' => array_fill_keys(array_keys(DailyWorkReport::REPORT_PHONES), 1440)])
            ->assertOk()->assertJsonCount(7, 'report.phones')->assertJsonPath('report.total_duration_minutes', 10080);
    }

    public function test_employee_can_submit_multiple_work_reports_per_day_for_multiple_shifts(): void
    {
        $user = $this->employee('multi_shift_worker@example.com');
        $this->actingAs($user);
        $today = now('Asia/Makassar')->toDateString();

        // 1. Submit Shift 1 (Pagi) report
        $res1 = $this->putJson('/api/me/work-report', [
            'work_date' => $today,
            'shift_label' => 'Shift 1 (Pagi)',
            'is_new' => true,
            'expected_revision' => 0,
            'phone_durations' => ['putih' => 120],
        ])->assertOk();

        $res1->assertJsonPath('success', true)
            ->assertJsonPath('report.shift_label', 'Shift 1 (Pagi)')
            ->assertJsonPath('report.total_duration_minutes', 120);
        $reportId1 = $res1->json('report.id');

        // 2. Submit Shift 2 (Malam) report on same day
        $res2 = $this->putJson('/api/me/work-report', [
            'work_date' => $today,
            'shift_label' => 'Shift 2 (Malam)',
            'is_new' => true,
            'expected_revision' => 0,
            'phone_durations' => ['pink' => 180],
        ])->assertOk();

        $res2->assertJsonPath('success', true)
            ->assertJsonPath('report.shift_label', 'Shift 2 (Malam)')
            ->assertJsonPath('report.total_duration_minutes', 180);
        $reportId2 = $res2->json('report.id');

        $this->assertNotEquals($reportId1, $reportId2);
        $this->assertDatabaseCount('daily_work_reports', 2);

        // 3. Check mine endpoint returns both reports and accurate total minutes
        $mine = $this->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertOk();
        $mine->assertJsonCount(2, 'today_reports')
            ->assertJsonPath('today_total_minutes', 300)
            ->assertJsonCount(2, 'reports');

        // 4. Update specific report (Shift 1) using report_id
        $this->putJson('/api/me/work-report', [
            'report_id' => $reportId1,
            'work_date' => $today,
            'shift_label' => 'Shift 1 (Pagi Diperbarui)',
            'expected_revision' => 1,
            'phone_durations' => ['putih' => 150],
        ])->assertOk()->assertJsonPath('report.total_duration_minutes', 150)->assertJsonPath('report.revision', 2);

        $this->assertDatabaseHas('daily_work_reports', [
            'id' => $reportId1,
            'shift_label' => 'Shift 1 (Pagi Diperbarui)',
            'total_duration_minutes' => 150,
            'revision' => 2,
        ]);

        // 5. Worker can delete their own report for today
        $this->deleteJson('/api/me/work-reports/'.$reportId2)->assertOk();
        $this->assertDatabaseMissing('daily_work_reports', ['id' => $reportId2]);
        $this->assertDatabaseCount('daily_work_reports', 1);
    }

    public function test_admin_can_create_multiple_reports_for_same_worker_with_allow_multiple(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributor = Contributor::create([
            'nama' => 'Rina Shift',
            'email' => 'rina_shift@example.com',
            'role' => 'contributor',
        ]);
        $today = now('Asia/Makassar')->toDateString();

        // Admin creates Shift 1
        $res1 = $this->actingAs($admin)->postJson('/api/work-reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'shift_label' => 'Shift 1 (Pagi)',
            'allow_multiple' => true,
            'phones' => ['putih'],
            'phone_durations' => ['putih' => 120],
            'total_duration_minutes' => 120,
        ])->assertOk();

        // Admin creates Shift 2 on same day
        $res2 = $this->actingAs($admin)->postJson('/api/work-reports', [
            'contributor_id' => $contributor->id,
            'work_date' => $today,
            'shift_label' => 'Shift 2 (Malam)',
            'allow_multiple' => true,
            'phones' => ['pink'],
            'phone_durations' => ['pink' => 180],
            'total_duration_minutes' => 180,
        ])->assertOk();

        $this->assertNotEquals($res1->json('report.id'), $res2->json('report.id'));
        $this->assertDatabaseCount('daily_work_reports', 2);

        $index = $this->actingAs($admin)->getJson('/api/work-reports?date='.$today)->assertOk();
        $index->assertJsonCount(2, 'reports')
            ->assertJsonPath('total_duration_minutes', 300);
    }
}
