<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\ShiftSetting;
use App\Models\User;
use App\Services\ContributorAccess;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceQrWifiTest extends TestCase
{
    use RefreshDatabase;

    private function contributorUser(string $email = 'worker@example.com'): User
    {
        $person = Contributor::create(['nama' => 'Test Worker', 'email' => $email]);

        return app(ContributorAccess::class)->sync($person);
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@aiku.test']);
    }

    public function test_admin_can_get_and_update_wifi_barcode_config(): void
    {
        $admin = $this->adminUser();

        // 1. Get default config
        $res = $this->actingAs($admin)->getJson('/api/attendance/wifi-config');
        $res->assertOk()
            ->assertJsonStructure([
                'config' => ['wifi_ips', 'wifi_check_enabled', 'qr_secret', 'studio_name'],
                'current_ip',
                'qr_url',
            ]);

        $defaultToken = $res->json('config.qr_secret');
        $this->assertNotEmpty($defaultToken);

        // 2. Save new WiFi IP and studio name
        $postRes = $this->actingAs($admin)->postJson('/api/attendance/wifi-config', [
            'wifi_ips' => ['114.122.50.10', '180.252.10.5'],
            'wifi_check_enabled' => true,
            'studio_name' => 'AIKu Creative Studio',
            'regenerate_qr' => false,
        ]);

        $postRes->assertOk()
            ->assertJsonPath('config.studio_name', 'AIKu Creative Studio')
            ->assertJsonPath('config.wifi_ips.0', '114.122.50.10')
            ->assertJsonPath('config.wifi_ips.1', '180.252.10.5')
            ->assertJsonPath('config.qr_secret', $defaultToken);

        // 3. Regenerate QR secret
        $regenRes = $this->actingAs($admin)->postJson('/api/attendance/wifi-config', [
            'wifi_ips' => ['114.122.50.10'],
            'wifi_check_enabled' => true,
            'studio_name' => 'AIKu Creative Studio',
            'regenerate_qr' => true,
        ]);

        $regenRes->assertOk();
        $this->assertNotEquals($defaultToken, $regenRes->json('config.qr_secret'));
    }

    public function test_barcode_poster_page_renders_cleanly_for_admin(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/attendance/barcode/poster')
            ->assertOk()
            ->assertSee('Barcode Absensi Masuk')
            ->assertSee('Khusus Jaringan WiFi Studio');
    }

    public function test_contributor_can_checkin_with_valid_qr_token_on_whitelisted_wifi_ip(): void
    {
        $admin = $this->adminUser();
        $worker = $this->contributorUser();

        // Configure WiFi IP whitelist
        $config = [
            'wifi_ips' => ['114.122.99.88'],
            'wifi_check_enabled' => true,
            'qr_secret' => 'valid_secret_qr_token_123',
            'studio_name' => 'AIKu Studio',
        ];
        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => $config]
        );

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

        // Check in from matching IP
        $response = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '114.122.99.88'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'valid_secret_qr_token_123',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_checked_in', false)
            ->assertJsonPath('attendance.work_date', $today)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi')
            ->assertJsonPath('attendance.client_ip', '114.122.99.88')
            ->assertJsonPath('attendance.wifi_verified', true);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'work_date' => $today,
            'checkin_method' => 'qr_wifi',
            'client_ip' => '114.122.99.88',
        ]);

        // Subsequent check in on same day is idempotent
        $idempotentRes = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '114.122.99.88'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'valid_secret_qr_token_123',
            ]);

        $idempotentRes->assertOk()->assertJsonPath('already_checked_in', true);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_checkin_succeeds_on_explicitly_configured_subnet_wildcard(): void
    {
        $worker = $this->contributorUser();

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.216.*'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'token_subnet_test',
            ]]
        );

        // Worker connects on same /24 router gateway pool: 140.213.216.25
        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.216.25'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'token_subnet_test',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi');
    }

    public function test_contributor_without_prelinked_contributor_id_recovers_gracefully(): void
    {
        // Contributor user exists with role contributor, but contributor_id is null
        $user = User::factory()->create([
            'role' => 'contributor',
            'name' => 'Afif Anshari',
            'email' => 'afif@example.com',
            'contributor_id' => null,
        ]);

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.216.5'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'test_afif_token',
            ]]
        );

        $res = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.216.5'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'test_afif_token',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.contributor_name', 'Afif Anshari');

        $this->assertNotNull($user->fresh()->contributor_id);
    }

    public function test_checkin_is_rejected_if_qr_token_is_invalid(): void
    {
        $worker = $this->contributorUser();

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['114.122.99.88'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'valid_token_xyz',
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '114.122.99.88'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'wrong_or_expired_token',
            ]);

        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['qr_token']);
    }

    public function test_checkin_is_rejected_if_ip_does_not_match_office_wifi(): void
    {
        $worker = $this->contributorUser();

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['114.122.99.88'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'valid_token_xyz',
            ]]
        );

        // Try from mobile data cellular IP
        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '36.80.120.45'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'valid_token_xyz',
            ]);

        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['wifi']);
    }

    public function test_checkin_succeeds_without_ip_restriction_when_wifi_check_is_disabled(): void
    {
        $worker = $this->contributorUser();

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['114.122.99.88'],
                'wifi_check_enabled' => false,
                'qr_secret' => 'valid_token_xyz',
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '36.80.120.45'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'valid_token_xyz',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi');
    }

    public function test_admin_attendance_list_returns_checkin_method_and_ip(): void
    {
        $admin = $this->adminUser();
        $worker = $this->contributorUser();
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

        Attendance::create([
            'user_id' => $worker->id,
            'contributor_id' => $worker->contributor_id,
            'contributor_name' => 'Test Worker',
            'work_date' => $today,
            'checked_in_at' => now(),
            'checkin_method' => 'qr_wifi',
            'client_ip' => '114.122.99.88',
            'wifi_verified' => true,
        ]);

        $res = $this->actingAs($admin)->getJson("/api/attendance?date={$today}");
        $res->assertOk()
            ->assertJsonPath('attendances.0.checkin_method', 'qr_wifi')
            ->assertJsonPath('attendances.0.client_ip', '114.122.99.88');
    }

    public function test_contributor_can_checkin_with_qr_and_gps_coordinates_stored(): void
    {
        $worker = $this->contributorUser('gps_worker@example.com');
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.216.5'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'qr_gps_token_secret',
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.216.5'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'qr_gps_token_secret',
                'latitude' => -8.5123456,
                'longitude' => 115.1234567,
                'accuracy_m' => 12.4,
                'captured_at' => now()->toISOString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi')
            ->assertJsonPath('attendance.client_ip', '140.213.216.5')
            ->assertJsonPath('attendance.latitude', -8.5123456)
            ->assertJsonPath('attendance.longitude', 115.1234567);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'work_date' => $today,
            'checkin_method' => 'qr_wifi',
            'latitude' => -8.5123456,
            'longitude' => 115.1234567,
        ]);
    }

    public function test_checkin_validates_geofence_distance_when_studio_coordinates_configured(): void
    {
        $worker = $this->contributorUser('geofence_worker@example.com');

        // Studio is at Monas, Jakarta: -6.175392, 106.827153, radius 100m
        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.216.5'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'geofence_token_123',
                'studio_lat' => -6.175392,
                'studio_lng' => 106.827153,
                'geofence_radius_m' => 100,
            ]]
        );

        // 1. Worker is far away in Bandung (-6.917464, 107.619123) -> Should fail
        $farRes = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.216.5'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'geofence_token_123',
                'latitude' => -6.917464,
                'longitude' => 107.619123,
                'accuracy_m' => 10.0,
                'captured_at' => now()->toISOString(),
            ]);

        $farRes->assertUnprocessable()
            ->assertJsonValidationErrors(['location']);

        // 2. Worker is right at the studio (-6.175400, 106.827160 ~ 1 meter away) -> Should succeed
        $nearRes = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.216.5'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'geofence_token_123',
                'latitude' => -6.175400,
                'longitude' => 106.827160,
                'accuracy_m' => 10.0,
                'captured_at' => now()->toISOString(),
            ]);

        $nearRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi');
    }

    public function test_admin_can_save_studio_gps_and_geofence_settings(): void
    {
        $admin = $this->adminUser();

        $res = $this->actingAs($admin)->postJson('/api/attendance/wifi-config', [
            'wifi_ips' => ['140.213.216.5'],
            'wifi_check_enabled' => true,
            'studio_name' => 'AIKu Studio Bali',
            'studio_lat' => -8.512345,
            'studio_lng' => 115.123456,
            'geofence_radius_m' => 200,
        ]);

        $res->assertOk()
            ->assertJsonPath('config.studio_lat', -8.512345)
            ->assertJsonPath('config.studio_lng', 115.123456)
            ->assertJsonPath('config.geofence_radius_m', 200);
    }

    public function test_checkin_succeeds_with_official_printed_poster_token_permanently(): void
    {
        $worker = $this->contributorUser();

        // Even if DB has a completely different or freshly regenerated token
        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['114.122.99.88'],
                'wifi_check_enabled' => false,
                'qr_secret' => 'some_other_random_secret_999',
            ]]
        );

        // Official printed poster token always succeeds
        $res = $this->actingAs($worker)
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'aiku_qr_oAA9kgzn0xNmnq9W2gBATjCm',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi');
    }

    public function test_admin_role_can_also_checkin_with_qr_barcode_successfully(): void
    {
        $admin = $this->adminUser();

        $res = $this->actingAs($admin)
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'aiku_qr_oAA9kgzn0xNmnq9W2gBATjCm',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $admin->id,
            'checkin_method' => 'qr_wifi',
        ]);
    }

    public function test_contributor_can_checkin_with_cidr_subnet(): void
    {
        $worker = $this->contributorUser('cidr_worker@example.com');

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.0.0/16'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'cidr_token_123',
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.10.103'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'cidr_token_123',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.client_ip', '140.213.10.103')
            ->assertJsonPath('attendance.wifi_verified', true);
    }

    public function test_contributor_can_checkin_with_multi_wildcard(): void
    {
        $worker = $this->contributorUser('wildcard_worker@example.com');

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.*.*'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'wildcard_token_123',
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.10.103'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'wildcard_token_123',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.client_ip', '140.213.10.103')
            ->assertJsonPath('attendance.wifi_verified', true);
    }

    public function test_contributor_can_checkin_with_dynamic_isp_subnet_tolerance(): void
    {
        $worker = $this->contributorUser('dynamic_worker@example.com');

        // Admin registered 140.213.216.5, client connects from rotated IP 140.213.10.103 on same ISP
        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.216.5'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'dynamic_token_123',
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '140.213.10.103'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'dynamic_token_123',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.client_ip', '140.213.10.103')
            ->assertJsonPath('attendance.wifi_verified', true);
    }

    public function test_admin_can_save_cidr_and_wildcard_wifi_ips(): void
    {
        $admin = $this->adminUser();

        $res = $this->actingAs($admin)->postJson('/api/attendance/wifi-config', [
            'wifi_ips' => ['140.213.0.0/16', '140.213.*.*', '180.252.10.5'],
            'wifi_check_enabled' => true,
            'studio_name' => 'AIKu Dynamic Studio',
        ]);

        $res->assertOk()
            ->assertJsonPath('config.wifi_ips.0', '140.213.0.0/16')
            ->assertJsonPath('config.wifi_ips.1', '140.213.*.*')
            ->assertJsonPath('config.wifi_ips.2', '180.252.10.5');
    }

    public function test_contributor_can_checkin_via_gps_geofence_fallback_when_wifi_ip_differs(): void
    {
        $worker = $this->contributorUser('fallback_worker@example.com');

        // Studio is configured at Monas, Jakarta: -6.175392, 106.827153
        // Whitelisted WiFi is 114.122.99.88, but worker connects on completely different cellular IP 180.244.1.2
        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['114.122.99.88'],
                'wifi_check_enabled' => true,
                'qr_secret' => 'geofence_fallback_token',
                'studio_lat' => -6.175392,
                'studio_lng' => 106.827153,
                'geofence_radius_m' => 150,
            ]]
        );

        $res = $this->actingAs($worker)
            ->withServerVariables(['REMOTE_ADDR' => '180.244.1.2'])
            ->postJson('/api/me/attendance/qr', [
                'qr_token' => 'geofence_fallback_token',
                'latitude' => -6.175400,
                'longitude' => 106.827160, // ~1 meter away inside studio
                'accuracy_m' => 8.0,
                'captured_at' => now()->toISOString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendance.checkin_method', 'qr_wifi')
            ->assertJsonPath('attendance.client_ip', '180.244.1.2');
    }
}
