<?php

namespace Tests\Feature;

use App\Models\DeviceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $worker;

    public function test_admin_can_add_phone_then_register_account_and_see_it_in_report_options(): void
    {
        $this->actingAs($this->admin)->postJson('/api/devices/phones', ['label' => 'HP Merah'])->assertCreated()
            ->assertJsonPath('phone.key', 'merah')->assertJsonPath('phone.label', 'Merah');
        $this->postJson('/api/devices/accounts', ['phone_key' => 'merah', 'email' => 'red@example.test'])->assertCreated();
        $response = $this->getJson('/api/devices')->assertOk();
        $this->assertCount(7, $response->json('phones'));
        $this->assertSame('Merah', \App\Models\DailyWorkReport::reportPhoneOptions()['merah']);
        $this->assertSame('Merah', DeviceAccount::where('phone_key', 'merah')->first()->phone_label);
        $this->assertSame('Sendiri', \App\Models\DailyWorkReport::reportPhoneOptions()['sendiri']);
        $person = \App\Models\Contributor::create(['nama' => 'Device Worker', 'email' => 'device-worker@example.test', 'role' => 'contributor']);
        $durations = array_fill_keys(array_keys(\App\Models\DailyWorkReport::reportPhoneOptions()), 15);
        $this->postJson('/api/work-reports', ['contributor_id' => $person->id, 'work_date' => now('Asia/Makassar')->toDateString(),
            'phone_durations' => $durations, 'phone_tasks' => array_fill_keys(array_keys($durations), ['Clean table'])])->assertOk()
            ->assertJsonPath('report.phone_durations.merah', 15)->assertJsonPath('report.phone_emails.merah', 'red@example.test');
        $this->getJson('/api/work-reports?date='.now('Asia/Makassar')->toDateString())->assertOk()->assertJsonPath('phone_options.merah', 'Merah');
    }

    public function test_phone_name_validation_and_permissions(): void
    {
        $this->actingAs($this->worker)->postJson('/api/devices/phones', ['label' => 'Merah'])->assertForbidden();
        $this->actingAs($this->admin);
        foreach (['', 'HP Putih', ' putih ', 'Sendiri', 'HP Sendiri', str_repeat('a', 61)] as $label) {
            $this->postJson('/api/devices/phones', ['label' => $label])->assertUnprocessable();
        }
        $this->postJson('/api/devices/phones', ['label' => 'HP Merah'])->assertCreated();
        $this->postJson('/api/devices/phones', ['label' => 'MERAH'])->assertUnprocessable();
        $this->assertDatabaseCount('studio_devices', 7);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'email' => 'admin.test@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->worker = User::factory()->create([
            'email' => 'worker.test@example.com',
            'role' => 'contributor',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_devices_page_or_api(): void
    {
        $this->actingAs($this->worker)->get('/devices')->assertForbidden();
        $this->actingAs($this->worker)->getJson('/api/devices')->assertForbidden();
        $this->actingAs($this->worker)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'test@example.com',
        ])->assertForbidden();
    }

    public function test_admin_can_access_devices_page_and_api(): void
    {
        $this->actingAs($this->admin)->get('/devices')->assertOk();
        $response = $this->actingAs($this->admin)->getJson('/api/devices');
        $response->assertOk()
            ->assertJsonStructure(['phones', 'accounts']);

        $this->assertCount(6, $response->json('phones'));
    }

    public function test_admin_can_register_new_device_account_and_it_becomes_active(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'streamer1@example.com',
            'account_name' => 'Akun Streamer 1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_accounts', [
            'phone_key' => 'putih',
            'email' => 'streamer1@example.com',
            'account_name' => 'Akun Streamer 1',
            'is_active' => true,
        ]);
    }

    public function test_registering_second_account_deactivates_first_account(): void
    {
        // First account
        $this->actingAs($this->admin)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'first@example.com',
        ])->assertCreated();

        $first = DeviceAccount::where('email', 'first@example.com')->first();
        $this->assertTrue($first->is_active);

        // Second account on the same phone
        $this->actingAs($this->admin)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'second@example.com',
        ])->assertCreated();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue(DeviceAccount::where('email', 'second@example.com')->first()->is_active);
    }

    public function test_admin_can_switch_active_account(): void
    {
        $first = DeviceAccount::create([
            'phone_key' => 'pink',
            'email' => 'pink1@example.com',
            'is_active' => true,
            'activated_at' => now()->subDay(),
        ]);
        $second = DeviceAccount::create([
            'phone_key' => 'pink',
            'email' => 'pink2@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/devices/accounts/{$second->id}/activate")
            ->assertOk();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_admin_cannot_delete_active_account_but_can_delete_inactive_account(): void
    {
        $active = DeviceAccount::create([
            'phone_key' => 'biru',
            'email' => 'active@example.com',
            'is_active' => true,
        ]);
        $inactive = DeviceAccount::create([
            'phone_key' => 'biru',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        // Attempt deleting active -> rejected 422
        $this->actingAs($this->admin)
            ->deleteJson("/api/devices/accounts/{$active->id}")
            ->assertStatus(422);

        // Attempt deleting inactive -> success
        $this->actingAs($this->admin)
            ->deleteJson("/api/devices/accounts/{$inactive->id}")
            ->assertOk();

        $this->assertDatabaseMissing('device_accounts', ['id' => $inactive->id]);
        $this->assertDatabaseHas('device_accounts', ['id' => $active->id]);
    }

    public function test_admin_can_update_device_account(): void
    {
        $account = DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'old.email@example.com',
            'account_name' => 'Old Label',
            'is_active' => true,
        ]);

        // Non admin forbidden
        $this->actingAs($this->worker)
            ->putJson("/api/devices/accounts/{$account->id}", [
                'email' => 'new.email@example.com',
                'account_name' => 'New Label',
            ])->assertForbidden();

        // Admin update
        $res = $this->actingAs($this->admin)
            ->putJson("/api/devices/accounts/{$account->id}", [
                'email' => 'revised.email@example.com',
                'account_name' => 'Revisi Batch 3',
            ]);

        $res->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_accounts', [
            'id' => $account->id,
            'email' => 'revised.email@example.com',
            'account_name' => 'Revisi Batch 3',
        ]);
        $this->assertDatabaseMissing('device_accounts', [
            'id' => $account->id,
            'email' => 'old.email@example.com',
        ]);
    }
}
