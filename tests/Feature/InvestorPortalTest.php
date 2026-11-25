<?php

namespace Tests\Feature;

use App\Models\DeviceAccount;
use App\Models\Investor;
use App\Models\InvestorDeviceContract;
use App\Models\InvestorPayout;
use App\Models\StudioDevice;
use App\Models\StudioRecording;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvestorPortalTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin AIKu',
            'email' => 'admin@aiku.test',
        ]);
    }

    public function test_admin_can_provision_investor_login_and_investor_redirects_to_portal(): void
    {
        $admin = $this->adminUser();
        $investor = Investor::create([
            'code' => 'INV-01',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'bank_name' => 'BCA',
            'no_rek' => '123456789',
            'account_holder' => 'Budi Santoso',
            'is_active' => true,
        ]);

        $res = $this->actingAs($admin)->postJson("/api/investors/{$investor->id}/account", [
            'email' => 'budi.login@aiku.test',
            'username' => 'Budi Investor',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('login_account.email', 'budi.login@aiku.test')
            ->assertJsonPath('login_account.is_active', true);

        $this->post('/logout');

        $loginRes = $this->post('/login', [
            'email' => 'budi.login@aiku.test',
            'password' => 'rahasia123',
        ]);

        $loginRes->assertRedirect('/investor');
        $this->get('/')->assertRedirect('/investor');
        $this->get('/investor')->assertOk()->assertSee('Portal Investor');
    }

    public function test_investor_dashboard_is_isolated_to_own_devices_and_payouts(): void
    {
        $user = User::factory()->create([
            'role' => 'investor',
            'name' => 'Investor Satu',
            'email' => 'inv1@aiku.test',
            'password' => Hash::make('secret123'),
        ]);
        $investorA = Investor::create([
            'user_id' => $user->id,
            'code' => 'INV-01',
            'name' => 'Investor Satu',
            'is_active' => true,
        ]);
        $investorB = Investor::create([
            'code' => 'INV-02',
            'name' => 'Investor Rahasia Lain',
            'is_active' => true,
        ]);

        StudioDevice::create(['key' => 'hp_inv_a', 'label' => 'INV-01-HP1', 'sort_order' => 1]);
        StudioDevice::create(['key' => 'hp_inv_b', 'label' => 'INV-02-HP1', 'sort_order' => 2]);

        InvestorDeviceContract::create([
            'investor_id' => $investorA->id,
            'device_key' => 'hp_inv_a',
            'device_code' => 'INV-01-HP1',
            'rate_per_hour_idr' => 10000,
            'rate_basis' => 'billable',
            'is_active' => true,
        ]);
        InvestorDeviceContract::create([
            'investor_id' => $investorB->id,
            'device_key' => 'hp_inv_b',
            'device_code' => 'INV-02-HP1',
            'rate_per_hour_idr' => 10000,
            'rate_basis' => 'billable',
            'is_active' => true,
        ]);

        DeviceAccount::create([
            'phone_key' => 'hp_inv_a',
            'email' => 'hp_a@kamerakitaid.site',
            'account_name' => 'HP A',
            'is_active' => true,
        ]);
        DeviceAccount::create([
            'phone_key' => 'hp_inv_b',
            'email' => 'hp_b@kamerakitaid.site',
            'account_name' => 'HP B',
            'is_active' => true,
        ]);

        StudioRecording::create([
            'record_id' => 'rec_a_1',
            'participant_email' => 'hp_a@kamerakitaid.site',
            'recorded_at' => CarbonImmutable::now('UTC')->subHours(2),
            'duration_sec' => 3600,
            'duration_min' => 60,
            'billable_min' => 60,
            'rejected_min' => 0,
            'pending_min' => 0,
        ]);
        StudioRecording::create([
            'record_id' => 'rec_b_1',
            'participant_email' => 'hp_b@kamerakitaid.site',
            'recorded_at' => CarbonImmutable::now('UTC')->subHours(2),
            'duration_sec' => 7200,
            'duration_min' => 120,
            'billable_min' => 120,
            'rejected_min' => 0,
            'pending_min' => 0,
        ]);

        InvestorPayout::create([
            'investor_id' => $investorA->id,
            'period_id' => '2026-11-16_2026-12-15',
            'period_name' => '16 Nov - 15 Des 2026',
            'total_billable_hours' => 1,
            'total_amount_idr' => 10000,
            'status' => 'paid',
        ]);
        InvestorPayout::create([
            'investor_id' => $investorB->id,
            'period_id' => '2026-11-16_2026-12-15',
            'period_name' => '16 Nov - 15 Des 2026',
            'total_billable_hours' => 2,
            'total_amount_idr' => 20000,
            'status' => 'paid',
        ]);

        $res = $this->actingAs($user)->getJson('/api/investor/dashboard');
        $res->assertOk()
            ->assertJsonPath('investor.code', 'INV-01')
            ->assertJsonPath('summary.billable_hours', 1)
            ->assertJsonPath('summary.profit_idr', 10000)
            ->assertJsonCount(1, 'summary.contracts')
            ->assertJsonPath('summary.contracts.0.device_code', 'INV-01-HP1')
            ->assertJsonCount(1, 'payout_history');

        $res->assertDontSee('Investor Rahasia Lain');
        $res->assertDontSee('INV-02-HP1');
    }

    public function test_role_isolation_between_investor_contributor_and_admin(): void
    {
        $admin = $this->adminUser();
        $contributor = User::factory()->create(['role' => 'contributor']);
        $investorUser = User::factory()->create(['role' => 'investor']);
        Investor::create([
            'user_id' => $investorUser->id,
            'code' => 'INV-09',
            'name' => 'Investor Sembilan',
        ]);

        // Investor tidak boleh masuk portal pekerja maupun panel admin
        $this->actingAs($investorUser)->get('/me')->assertForbidden();
        $this->actingAs($investorUser)->getJson('/api/me/recordings')->assertForbidden();
        $this->actingAs($investorUser)->get('/investors')->assertForbidden();
        $this->actingAs($investorUser)->getJson('/api/investors/analytics')->assertForbidden();

        // Contributor & Admin tidak boleh masuk endpoint portal investor
        $this->actingAs($contributor)->get('/investor')->assertForbidden();
        $this->actingAs($contributor)->getJson('/api/investor/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/investor')->assertForbidden();
    }

    public function test_admin_can_toggle_investor_login_and_investor_can_change_password(): void
    {
        $admin = $this->adminUser();
        $investorUser = User::factory()->create([
            'role' => 'investor',
            'email' => 'toggle@aiku.test',
            'password' => Hash::make('oldpass123'),
            'is_active' => true,
        ]);
        $investor = Investor::create([
            'user_id' => $investorUser->id,
            'code' => 'INV-10',
            'name' => 'Investor Sepuluh',
        ]);

        // Investor ganti password sendiri
        $this->actingAs($investorUser)->putJson('/api/investor/password', [
            'current_password' => 'oldpass123',
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ])->assertOk()->assertJsonPath('success', true);

        // Admin nonaktifkan akses login investor
        $this->actingAs($admin)->patchJson("/api/investors/{$investor->id}/account/toggle")
            ->assertOk()
            ->assertJsonPath('login_account.is_active', false);

        $this->post('/logout');

        // Login ditolak saat nonaktif
        $this->post('/login', [
            'email' => 'toggle@aiku.test',
            'password' => 'newpass123',
        ])->assertSessionHasErrors('email');
    }
}
