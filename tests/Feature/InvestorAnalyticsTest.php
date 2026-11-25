<?php

namespace Tests\Feature;

use App\Models\DeviceAccount;
use App\Models\Investor;
use App\Models\StudioDevice;
use App\Models\StudioRecording;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorAnalyticsTest extends TestCase
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

    private function workerUser(): User
    {
        return User::factory()->create([
            'role' => 'contributor',
            'name' => 'Pekerja Biasa',
            'email' => 'worker@aiku.test',
        ]);
    }

    public function test_non_admin_cannot_access_investor_endpoints(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)->get('/investors')->assertForbidden();
        $this->actingAs($worker)->getJson('/api/investors/analytics')->assertForbidden();
        $this->actingAs($worker)->postJson('/api/investors', ['name' => 'Investor X'])->assertForbidden();
    }

    public function test_admin_can_create_investor_device_and_calculate_billable_profit_and_integrate_with_live_summary(): void
    {
        $admin = $this->adminUser();

        // 1. Tambah investor baru
        $invRes = $this->actingAs($admin)->postJson('/api/investors', [
            'code' => 'INV-01',
            'name' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'bank_name' => 'BCA',
            'no_rek' => '1234567890',
            'account_holder' => 'Budi Santoso',
        ]);
        $invRes->assertCreated()->assertJsonPath('investor.code', 'INV-01');
        $investorId = $invRes->json('investor.id');

        // 2. Daftarkan HP baru berkode khusus INV-01-HP1 dengan tarif Rp 10.000/jam billable
        $contractRes = $this->actingAs($admin)->postJson("/api/investors/{$investorId}/contracts", [
            'new_device_label' => 'INV01 A15',
            'device_code' => 'INV-01-HP1',
            'rate_per_hour_idr' => 10000,
            'rate_basis' => 'billable',
        ]);
        $contractRes->assertCreated()->assertJsonPath('contract.device_code', 'INV-01-HP1');
        $deviceKey = $contractRes->json('contract.device_key');

        $this->assertTrue(StudioDevice::where('key', $deviceKey)->exists());

        // 3. Kaitkan email akun HP ke device tersebut
        DeviceAccount::create([
            'phone_key' => $deviceKey,
            'email' => 'inv01@kamerakitaid.site',
            'account_name' => 'Akun Investor 01',
            'is_active' => true,
            'activated_at' => CarbonImmutable::parse('2026-11-16 01:00:00', 'UTC'),
        ]);

        // Email yang sama sebelumnya pernah di HP studio lain sebelum 16 Nov (menguji histori activated_at)
        DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'inv01@kamerakitaid.site',
            'account_name' => 'Akun Lama Putih',
            'is_active' => false,
            'activated_at' => CarbonImmutable::parse('2026-11-01 01:00:00', 'UTC'),
        ]);

        // 4. Masukkan rekaman pada periode 2026-11-16_2026-12-15:
        //    - 90 menit billable (1.5 jam) -> harus masuk hitungan (1.5 * 10.000 = Rp 15.000)
        //    - 60 menit duration tapi 0 billable (pending/reject) -> TIDAK masuk hitungan profit
        StudioRecording::create([
            'record_id' => 'rec_inv_01',
            'participant_email' => 'inv01@kamerakitaid.site',
            'recorded_at' => CarbonImmutable::parse('2026-11-20 04:00:00', 'UTC'),
            'duration_sec' => 7200,
            'duration_min' => 120,
            'billable_min' => 90,
            'rejected_min' => 30,
            'pending_min' => 0,
            'processing_status' => 'processed',
        ]);

        StudioRecording::create([
            'record_id' => 'rec_inv_02',
            'participant_email' => 'inv01@kamerakitaid.site',
            'recorded_at' => CarbonImmutable::parse('2026-11-21 04:00:00', 'UTC'),
            'duration_sec' => 3600,
            'duration_min' => 60,
            'billable_min' => 0,
            'rejected_min' => 0,
            'pending_min' => 60,
            'processing_status' => 'pending',
        ]);

        $periodId = '2026-11-16_2026-12-15';

        // 5. Cek analitik investor
        $analytics = $this->actingAs($admin)
            ->getJson("/api/investors/analytics?periodId={$periodId}")
            ->assertOk()
            ->json();

        $this->assertSame(1, $analytics['totals']['investor_count']);
        $this->assertSame(1, $analytics['totals']['device_count']);
        $this->assertEquals(1.5, $analytics['totals']['total_billable_hours']);
        $this->assertSame(15000, $analytics['totals']['total_investor_profit_idr']);
        $this->assertSame(15000, $analytics['totals']['total_unpaid_idr']);
        $this->assertSame(0, $analytics['totals']['total_paid_idr']);

        // 6. Cek integrasi ke /api/payroll/live-summary (untuk Estimasi Profit)
        $summaryRes = $this->actingAs($admin)
            ->getJson("/api/payroll/live-summary?periodId={$periodId}")
            ->assertOk()
            ->json();

        $this->assertSame(15000, $summaryRes['investor_share']['total_investor_profit_idr']);
        $this->assertEquals(1.5, $summaryRes['investor_share']['total_billable_hours']);

        // 7. Tandai payout Sudah Dibayar
        $this->actingAs($admin)
            ->postJson("/api/investors/{$investorId}/payout", [
                'period_id' => $periodId,
                'status' => 'paid',
                'notes' => 'Transfer BCA',
            ])->assertOk()
            ->assertJsonPath('payout.status', 'paid');

        $afterPaid = $this->actingAs($admin)
            ->getJson("/api/investors/analytics?periodId={$periodId}")
            ->assertOk()
            ->json();

        $this->assertSame(15000, $afterPaid['totals']['total_paid_idr']);
        $this->assertSame(0, $afterPaid['totals']['total_unpaid_idr']);
    }
}
