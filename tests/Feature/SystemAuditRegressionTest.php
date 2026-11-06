<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Models\Contributor;
use App\Models\DeviceAccount;
use App\Models\Payslip;
use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorAccess;
use App\Services\Robotics1618Sync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function worker(string $name = 'Worker', string $email = 'worker@example.test'): User
    {
        return app(ContributorAccess::class)->sync(Contributor::create(['nama' => $name, 'email' => $email]));
    }

    private function slip(string $name, array $attributes = []): Payslip
    {
        return Payslip::create($attributes + ['employee_name' => $name, 'period' => 'Custom audit period', 'total_thp' => 0]);
    }

    private function qrConfig(array $extra = []): void
    {
        ShiftSetting::create(['setting_key' => AttendanceController::SETTING_KEY, 'setting_value' => $extra + [
            'qr_secret' => 'audit-token', 'wifi_check_enabled' => true, 'wifi_ips' => ['203.0.113.10'],
        ]]);
    }

    public function test_raw_payroll_files_require_ownership_even_when_url_is_known(): void
    {
        Storage::fake('local');
        $worker = $this->worker();
        $this->slip('Worker', ['period' => '22 Sep 2026 - 21 Okt 2026', 'status' => 'sudah', 'file_path' => '/pdf_slips/own.pdf', 'evidence_data' => [['image_urls' => ['/evidence_images/own.png']]]]);
        $this->slip('Other', ['file_path' => '/pdf_slips/other.pdf', 'evidence_data' => [['image_urls' => ['/evidence_images/other.png']]]]);
        foreach (['own', 'other'] as $key) {
            Storage::disk('local')->put('payroll/pdf_slips/'.$key.'.pdf', "%PDF-1.4\n%%EOF");
            Storage::disk('local')->put('payroll/evidence_images/'.$key.'.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        }
        $this->actingAs($worker)->get('/pdf_slips/own.pdf')->assertOk();
        $this->get('/evidence_images/own.png')->assertOk();
        $this->get('/pdf_slips/other.pdf')->assertForbidden();
        $this->get('/evidence_images/other.png')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/pdf_slips/other.pdf')->assertOk();
    }

    public function test_live_pdf_exports_are_admin_only_even_for_the_worker_owner(): void
    {
        Storage::fake('local');
        $worker = $this->worker();
        $other = $this->worker('Other', 'other@example.test');
        $this->actingAs($worker)->getJson('/api/payroll/export-live/Worker')->assertForbidden();
        $admin = User::factory()->create();
        $result = $this->actingAs($admin)->getJson('/api/payroll/export-live/Worker')->assertOk();
        $url = $result->json('url');
        $this->actingAs($worker)->get($url)->assertForbidden();
        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_stale_display_name_does_not_grant_access_to_another_workers_slip(): void
    {
        $worker = $this->worker();
        $worker->forceFill(['name' => 'Other'])->save();
        $other = $this->slip('Other');
        $this->actingAs($worker)->get('/slip/'.$other->id)->assertForbidden();
        $this->getJson('/api/me/payslips')->assertOk()->assertJsonCount(0, 'slips');
    }

    public function test_ambiguous_legacy_payroll_names_fail_closed(): void
    {
        $worker = $this->worker();
        Contributor::create(['nama' => 'Worker', 'email' => 'duplicate@example.test']);
        $this->actingAs($worker)->getJson('/api/me/payslips')->assertForbidden();
    }

    public function test_unlinked_identity_is_not_recovered_by_someone_elses_name(): void
    {
        $existing = Contributor::create(['nama' => 'Same Name', 'email' => 'someone@example.test']);
        $worker = User::factory()->create(['role' => 'contributor', 'name' => 'Same Name', 'email' => 'new@example.test']);
        $this->actingAs($worker)->getJson('/api/me/attendance?month=2026-11')->assertForbidden();
        $this->assertNull($worker->fresh()->contributor_id);
        $this->assertDatabaseCount('contributors', 1);
    }

    public function test_forwarded_ip_headers_cannot_spoof_studio_wifi(): void
    {
        $this->qrConfig();
        $this->actingAs($this->worker())->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeaders(['CF-Connecting-IP' => '203.0.113.10', 'X-Real-IP' => '203.0.113.10', 'X-Forwarded-For' => '203.0.113.10'])
            ->postJson('/api/me/attendance/qr', ['qr_token' => 'audit-token'])->assertUnprocessable()->assertJsonValidationErrors('wifi');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_exact_wifi_ip_does_not_implicitly_allow_the_entire_subnet(): void
    {
        $controller = app(AttendanceController::class);
        $this->assertFalse($controller->isIpMatched('203.0.113.20', ['203.0.113.10']));
        $this->assertTrue($controller->isIpMatched('203.0.113.20', ['203.0.113.*']));
        $this->assertFalse($controller->isIpMatched('2001:db8::20', ['2001:db8::10']));
    }

    public function test_geofence_cannot_be_bypassed_by_omitting_gps(): void
    {
        $this->qrConfig(['studio_lat' => 0, 'studio_lng' => 106, 'geofence_radius_m' => 100]);
        $this->actingAs($this->worker())->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/me/attendance/qr', ['qr_token' => 'audit-token'])->assertUnprocessable()->assertJsonValidationErrors('location');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_old_or_future_gps_timestamps_are_rejected(): void
    {
        $this->actingAs($this->worker());
        foreach ([now()->subHours(2), now()->addHours(2)] as $timestamp) {
            $this->postJson('/api/me/attendance', ['latitude' => -8, 'longitude' => 115, 'accuracy_m' => 10, 'captured_at' => $timestamp->toISOString()])
                ->assertUnprocessable()->assertJsonValidationErrors('captured_at');
        }
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_unknown_phone_duration_keys_are_not_silently_discarded(): void
    {
        $this->actingAs($this->worker())->putJson('/api/me/work-report', [
            'work_date' => now('Asia/Makassar')->toDateString(), 'expected_revision' => 0,
            'phone_durations' => ['putih' => 30, 'unknown' => 100],
        ])->assertUnprocessable()->assertJsonValidationErrors('phone_durations');
        $this->assertDatabaseCount('daily_work_reports', 0);
    }

    public function test_assisted_assignment_uses_database_name_and_does_not_overwrite_another_assignment(): void
    {
        $person = Contributor::create(['nama' => 'Correct Name']);
        StudioRecording::create(['record_id' => 'free', 'participant_email' => 'a@example.test']);
        StudioRecording::create(['record_id' => 'taken', 'participant_email' => 'a@example.test', 'assigned_contributor_id' => 999, 'assigned_worker_name' => 'Other']);
        $this->actingAs(User::factory()->create())->postJson('/api/work-reports/assist-assign', [
            'record_ids' => ['free', 'taken'], 'contributor_id' => $person->id, 'worker_name' => 'Wrong Name',
        ])->assertOk()->assertJsonPath('updated_count', 1);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'free', 'assigned_contributor_id' => $person->id, 'assigned_worker_name' => 'Correct Name']);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'taken', 'assigned_worker_name' => 'Other']);
    }

    public function test_batch_assignment_canonicalizes_id_and_name_and_clears_both(): void
    {
        $person = Contributor::create(['nama' => 'Canonical']);
        StudioRecording::create(['record_id' => 'audit', 'participant_email' => 'a@example.test']);
        $this->actingAs(User::factory()->create())->putJson('/api/studio-recordings/batch-assign', [
            'record_ids' => ['audit'], 'contributor_id' => $person->id, 'worker_name' => 'Wrong',
        ])->assertOk();
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'audit', 'assigned_worker_name' => 'Canonical']);
        $this->putJson('/api/studio-recordings/batch-assign', ['record_ids' => ['audit'], 'contributor_id' => $person->id, 'worker_name' => null])->assertOk();
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'audit', 'assigned_contributor_id' => null, 'assigned_worker_name' => null]);
    }

    public function test_moving_active_device_account_deactivates_target_and_preserves_external_email_identity(): void
    {
        $source = DeviceAccount::create(['phone_key' => 'putih', 'email' => 'old@example.test', 'is_active' => true]);
        $target = DeviceAccount::create(['phone_key' => 'pink', 'email' => 'target@example.test', 'is_active' => true]);
        StudioAccount::create(['email' => 'old@example.test', 'name' => 'Old', 'participant_id' => 'old-external']);
        StudioAccount::create(['email' => 'new@example.test', 'name' => 'New', 'participant_id' => 'new-external']);
        $this->actingAs(User::factory()->create())->putJson('/api/devices/accounts/'.$source->id, [
            'phone_key' => 'pink', 'email' => 'new@example.test',
        ])->assertOk();
        $this->assertFalse($target->fresh()->is_active);
        $this->assertDatabaseHas('studio_accounts', ['email' => 'old@example.test', 'participant_id' => 'old-external']);
        $this->assertDatabaseHas('studio_accounts', ['email' => 'new@example.test', 'participant_id' => 'new-external']);
    }

    public function test_contributor_name_history_and_own_admin_access_are_preserved(): void
    {
        $worker = $this->worker();
        $this->slip('Worker');
        $this->actingAs(User::factory()->create())->putJson('/api/contributors/'.$worker->contributor_id, [
            'nama' => 'Renamed', 'email' => $worker->email,
        ])->assertUnprocessable()->assertJsonValidationErrors('nama');
        $this->postJson('/api/contributors', ['nama' => ' worker ', 'email' => 'another@example.test'])->assertUnprocessable();
        $person = Contributor::create(['nama' => 'Admin Person', 'email' => 'adminperson@example.test', 'role' => 'admin']);
        $admin = app(ContributorAccess::class)->sync($person);
        $this->actingAs($admin)->deleteJson('/api/contributors/'.$person->id)->assertUnprocessable();
        $this->putJson('/api/contributors/'.$person->id, ['nama' => 'Admin Person', 'email' => null])->assertUnprocessable();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_invalid_csv_dates_cannot_change_existing_recording_or_enter_current_payroll(): void
    {
        StudioRecording::create(['record_id' => 'bad', 'participant_email' => 'a@example.test', 'recorded_at' => '2026-01-01 00:00:00']);
        $file = tempnam(sys_get_temp_dir(), 'aiku_audit_');
        try {
            file_put_contents($file, "recording_id,email,task,created_utc,duration_s\nbad,a@example.test,Task,invalid-date,120\nmissing,a@example.test,Task,,120\n");
            $method = new \ReflectionMethod(Robotics1618Sync::class, 'processCsvStream');
            $result = $method->invoke(app(Robotics1618Sync::class), $file, ['a@example.test' => true]);
            $this->assertSame(0, $result['matched']);
            $this->assertDatabaseHas('studio_recordings', ['record_id' => 'bad', 'recorded_at' => '2026-01-01 00:00:00']);
            $this->assertDatabaseCount('studio_recordings', 1);
        } finally {
            unlink($file);
        }
    }

    public function test_slip_status_mutations_keep_all_versions_and_payroll_status_in_sync(): void
    {
        $first = $this->slip('Worker');
        $second = $this->slip('Worker');
        $this->actingAs(User::factory()->create())->patchJson('/api/payslips/'.$first->id.'/status', ['status' => 'sudah'])->assertOk();
        $this->assertEquals('sudah', $second->fresh()->status);
        $this->putJson('/api/payslips/'.$first->id, ['employee_name' => 'Worker', 'period' => $first->period, 'evidence' => [], 'status' => 'belum'])->assertOk();
        $this->assertEquals('belum', $second->fresh()->status);
        $this->assertDatabaseHas('payroll_statuses', ['worker_name' => 'Worker', 'period' => $first->period, 'status' => 'belum']);
        $this->postJson('/api/payslips', ['employee_name' => 'Worker', 'period' => $first->period, 'evidence' => [], 'status' => 'sudah'])->assertOk();
        $this->assertEquals('sudah', $first->fresh()->status);
        $this->assertDatabaseHas('payroll_statuses', ['worker_name' => 'Worker', 'period' => $first->period, 'status' => 'sudah']);
    }

    public function test_sync_without_a_configured_source_does_not_report_success(): void
    {
        config(['payroll.atlas_cookie' => '', 'payroll.robotics_1618_username' => '', 'payroll.robotics_1618_password' => '']);
        Storage::fake('local');
        $this->actingAs(User::factory()->create())->postJson('/api/studio-recordings/sync-now')->assertUnprocessable()->assertJsonPath('status', 'failed');
    }

    public function test_malformed_period_query_is_a_validation_error_not_a_server_error(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/payroll/live-summary?period[]=invalid')->assertUnprocessable();
        $this->getJson('/api/payroll/live-summary?startDate=2026-11-01')->assertUnprocessable();
    }

    public function test_read_only_integrity_audit_reports_aggregates_without_disclosing_people(): void
    {
        $person = Contributor::create(['nama' => 'Private Name', 'email' => 'private@example.test']);
        StudioRecording::create(['record_id' => 'mismatch', 'participant_email' => 'private@example.test', 'assigned_contributor_id' => $person->id, 'assigned_worker_name' => 'Wrong']);
        $this->artisan('panel:audit-data')->assertFailed();
        $output = Artisan::output();
        $this->assertStringNotContainsString('Private Name', $output);
        $this->assertStringNotContainsString('private@example.test', $output);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'mismatch', 'assigned_worker_name' => 'Wrong']);
    }

    public function test_disabled_wifi_check_does_not_claim_verification_or_invent_gps(): void
    {
        $this->qrConfig(['wifi_check_enabled' => false]);
        $this->actingAs($this->worker())->postJson('/api/me/attendance/qr', ['qr_token' => 'audit-token'])
            ->assertOk()->assertJsonPath('attendance.wifi_verified', false)
            ->assertJsonPath('attendance.latitude', null)->assertJsonPath('attendance.longitude', null)
            ->assertJsonPath('attendance.gps_captured_at', null);
    }

    public function test_opening_wifi_settings_does_not_enroll_admins_home_network(): void
    {
        $this->qrConfig(['wifi_ips' => []]);
        $this->actingAs(User::factory()->create())->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->getJson('/api/attendance/wifi-config')->assertOk()->assertJsonPath('config.wifi_ips', []);
    }

    public function test_shift_identity_survives_name_change_and_cannot_be_duplicated_under_an_alias(): void
    {
        $person = Contributor::create(['nama' => 'Current Name']);
        $config = config('shifts');
        $worker = ['name' => 'Old Name', 'contributor_id' => $person->id, 'alat' => 'Alat 1', 'sessions' => [1]];
        $config['karyawan'] = [$worker];
        $this->actingAs(User::factory()->create())->postJson('/api/shifts/config', $config)->assertOk()
            ->assertJsonPath('config.karyawan.0.name', 'Current Name')->assertJsonPath('config.karyawan.0.contributor_id', $person->id);
        $config['karyawan'][] = array_merge($worker, ['name' => 'Alias', 'alat' => 'Alat 2']);
        $this->postJson('/api/shifts/config', $config)->assertUnprocessable();
    }
}
