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

    public function test_checkin_succeeds_on_same_subnet_cgnat(): void
    {
        $worker = $this->contributorUser();

        ShiftSetting::updateOrCreate(
            ['setting_key' => AttendanceController::SETTING_KEY],
            ['setting_value' => [
                'wifi_ips' => ['140.213.216.5'],
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
}
