<?php

namespace Tests\Feature;

use App\Models\DeviceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KameraKitaPartnerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER_EMAIL = 'owner.studio@example.com';
    private const BASE_URL = 'https://kamerakitaid.site';
    private const TOKEN = 'test-studio-token-aiku-2026';

    private User $owner;
    private User $admin;
    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.kamerakita.url', self::BASE_URL);
        Config::set('services.kamerakita.token', self::TOKEN);
        Config::set('services.kamerakita.owner_email', self::OWNER_EMAIL);

        $this->owner = User::factory()->create([
            'email' => self::OWNER_EMAIL,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'regular.admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->worker = User::factory()->create([
            'email' => 'contributor@example.com',
            'role' => 'contributor',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_forbidden_from_device_api(): void
    {
        $this->actingAs($this->worker)
            ->getJson('/api/devices')
            ->assertForbidden();

        $this->actingAs($this->worker)
            ->postJson('/api/devices/accounts', ['phone_key' => 'putih', 'email' => 'a@b.com'])
            ->assertForbidden();
    }

    public function test_admin_non_owner_does_not_see_sensitive_fields_or_owner_access(): void
    {
        DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'mitra.linked@example.com',
            'account_name' => 'Mitra Linked',
            'is_active' => true,
            'kamerakita_partner_id' => '11111111-1111-1111-1111-111111111111',
            'kamerakita_partner_code' => 'KMK-001',
            'kamerakita_status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/devices')->assertOk();

        $this->assertFalse($response->json('kamerakita.owner_access'));
        $this->assertEmpty($response->json('kamerakita.parents'));

        $accounts = $response->json('accounts');
        $this->assertCount(1, $accounts);
        $this->assertArrayNotHasKey('kamerakita_partner_id', $accounts[0]);
        $this->assertArrayNotHasKey('kamerakita_partner_code', $accounts[0]);
        $this->assertArrayNotHasKey('kamerakita_status', $accounts[0]);
    }

    public function test_owner_sees_kamerakita_status_and_parents(): void
    {
        Http::fake([
            self::BASE_URL.'/api/private/studio/parents' => Http::response([
                'data' => [
                    ['id' => '99999999-9999-9999-9999-999999999999', 'mitra_id' => 'KMK-999', 'full_name' => 'Mitra Lain Luar Studio'],
                    ['id' => '21000000-0000-0000-0000-000000000210', 'mitra_id' => 'KMK-210', 'full_name' => 'AI KU Studio'],
                    ['id' => '84700000-0000-0000-0000-000000000847', 'mitra_id' => 'KMK-847', 'full_name' => 'AI KU STUDIO 2', 'whatsapp_number' => '+62895366583095'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/devices')->assertOk();

        $this->assertTrue($response->json('kamerakita.owner_access'));
        $this->assertTrue($response->json('kamerakita.configured'));
        $parents = $response->json('kamerakita.parents');
        // Hanya KMK-847 dan KMK-210 yang diizinkan untuk studio
        $this->assertCount(2, $parents);
        $this->assertEquals('KMK-847', $parents[0]['mitra_id']);
        $this->assertEquals('KMK-210', $parents[1]['mitra_id']);
    }

    public function test_non_owner_cannot_create_device_account_with_partner(): void
    {
        $this->actingAs($this->admin)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'hack@example.com',
            'create_kamerakita_partner' => true,
            'full_name' => 'Hacker',
            'whatsapp_number' => '08123456789',
            'mitra_parent_id' => '22222222-2222-2222-2222-222222222222',
        ])->assertForbidden();
    }

    public function test_owner_creates_device_account_and_kamerakita_partner(): void
    {
        Http::fake([
            self::BASE_URL.'/api/private/studio/partners*' => function (\Illuminate\Http\Client\Request $request) {
                // If it's a GET search by email
                if ($request->method() === 'GET') {
                    return Http::response(['data' => null], 200);
                }
                // POST create
                return Http::response([
                    'data' => [
                        'id' => '33333333-3333-3333-3333-333333333333',
                        'mitra_id' => 'KMK-123',
                        'email' => 'streamer.mitra@example.com',
                        'status' => 'active',
                        'partner_role' => 'mitra',
                    ],
                ], 201);
            },
        ]);

        $response = $this->actingAs($this->owner)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'streamer.mitra@example.com',
            'account_name' => 'Streamer HP Putih',
            'create_kamerakita_partner' => true,
            'full_name' => 'Mitra Budi',
            'whatsapp_number' => '081234567890',
            'mitra_parent_id' => '22222222-2222-2222-2222-222222222222',
        ]);
        $response->assertCreated();

        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_accounts', [
            'phone_key' => 'putih',
            'email' => 'streamer.mitra@example.com',
            'is_active' => true,
            'kamerakita_partner_id' => '33333333-3333-3333-3333-333333333333',
            'kamerakita_partner_code' => 'KMK-123',
            'kamerakita_status' => 'active',
        ]);
    }

    public function test_partial_failure_when_kamerakita_api_fails_keeps_device_account(): void
    {
        Http::fake([
            self::BASE_URL.'/api/private/studio/partners*' => Http::response([
                'message' => 'KameraKita server timeout.',
            ], 500),
        ]);

        $response = $this->actingAs($this->owner)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'partial.fail@example.com',
            'account_name' => 'Partial Fail Test',
            'create_kamerakita_partner' => true,
            'full_name' => 'Partial Person',
            'whatsapp_number' => '081234567890',
            'mitra_parent_id' => '22222222-2222-2222-2222-222222222222',
        ])->assertStatus(502);

        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('Akun HP tersimpan, tetapi pembuatan mitra KameraKita gagal', $response->json('message'));

        // Device account is saved locally
        $this->assertDatabaseHas('device_accounts', [
            'phone_key' => 'putih',
            'email' => 'partial.fail@example.com',
            'is_active' => true,
            'kamerakita_partner_id' => null,
        ]);
    }

    public function test_owner_can_link_existing_device_account_to_kamerakita(): void
    {
        $account = DeviceAccount::create([
            'phone_key' => 'hitam',
            'email' => 'unlinked@example.com',
            'account_name' => 'Unlinked Akun',
            'is_active' => true,
        ]);

        Http::fake([
            self::BASE_URL.'/api/private/studio/partners*' => function (\Illuminate\Http\Client\Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response(['data' => null], 200);
                }
                return Http::response([
                    'data' => [
                        'id' => '44444444-4444-4444-4444-444444444444',
                        'mitra_id' => 'KMK-200',
                        'status' => 'active',
                    ],
                ], 201);
            },
        ]);

        $response = $this->actingAs($this->owner)->postJson("/api/devices/accounts/{$account->id}/kamerakita", [
            'full_name' => 'Linked Later',
            'whatsapp_number' => '08123459999',
            'mitra_parent_id' => '22222222-2222-2222-2222-222222222222',
        ])->assertOk();

        $this->assertTrue($response->json('success'));

        $account->refresh();
        $this->assertEquals('44444444-4444-4444-4444-444444444444', $account->kamerakita_partner_id);
        $this->assertEquals('KMK-200', $account->kamerakita_partner_code);
        $this->assertEquals('active', $account->kamerakita_status);
    }

    public function test_owner_can_toggle_kamerakita_partner_status(): void
    {
        $account = DeviceAccount::create([
            'phone_key' => 'hitam',
            'email' => 'toggle.status@example.com',
            'is_active' => true,
            'kamerakita_partner_id' => '55555555-5555-5555-5555-555555555555',
            'kamerakita_status' => 'active',
        ]);

        Http::fake([
            self::BASE_URL.'/api/private/studio/partners/55555555-5555-5555-5555-555555555555/status' => Http::response([
                'data' => [
                    'id' => '55555555-5555-5555-5555-555555555555',
                    'status' => 'suspended',
                ],
            ], 200),
        ]);

        $this->actingAs($this->owner)
            ->patchJson("/api/devices/accounts/{$account->id}/kamerakita-status", ['status' => 'suspended'])
            ->assertOk();

        $this->assertEquals('suspended', $account->fresh()->kamerakita_status);
    }

    public function test_owner_can_view_and_update_kamerakita_partner_profile(): void
    {
        $account = DeviceAccount::create([
            'phone_key' => 'hitam',
            'email' => 'profile.edit@example.com',
            'account_name' => 'Old Name',
            'is_active' => true,
            'kamerakita_partner_id' => '66666666-6666-6666-6666-666666666666',
        ]);

        Http::fake([
            self::BASE_URL.'/api/private/studio/partners/66666666-6666-6666-6666-666666666666' => function (\Illuminate\Http\Client\Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'data' => [
                            'id' => '66666666-6666-6666-6666-666666666666',
                            'full_name' => 'Old Name',
                            'whatsapp_number' => '08123450000',
                            'mitra_parent_id' => '22222222-2222-2222-2222-222222222222',
                        ],
                    ], 200);
                }
                // PUT update
                return Http::response([
                    'data' => [
                        'id' => '66666666-6666-6666-6666-666666666666',
                        'full_name' => 'Brand New Name',
                        'whatsapp_number' => '08123451111',
                        'mitra_parent_id' => '99999999-9999-9999-9999-999999999999',
                    ],
                ], 200);
            },
        ]);

        // GET profile
        $this->actingAs($this->owner)
            ->getJson("/api/devices/accounts/{$account->id}/kamerakita")
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Old Name');

        // PUT update profile
        $this->actingAs($this->owner)
            ->putJson("/api/devices/accounts/{$account->id}/kamerakita", [
                'full_name' => 'Brand New Name',
                'whatsapp_number' => '08123451111',
                'mitra_parent_id' => '99999999-9999-9999-9999-999999999999',
            ])
            ->assertOk();

        $account->refresh();
        $this->assertEquals('Brand New Name', $account->account_name);
    }

    public function test_cannot_delete_or_change_email_of_linked_kamerakita_account(): void
    {
        $account = DeviceAccount::create([
            'phone_key' => 'hitam',
            'email' => 'protected.mitra@example.com',
            'account_name' => 'Protected Mitra',
            'is_active' => false,
            'kamerakita_partner_id' => '77777777-7777-7777-7777-777777777777',
        ]);

        // Cannot delete
        $this->actingAs($this->owner)
            ->deleteJson("/api/devices/accounts/{$account->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('device_accounts', ['id' => $account->id]);

        // Cannot rename email
        $this->actingAs($this->owner)
            ->putJson("/api/devices/accounts/{$account->id}", [
                'phone_key' => 'hitam',
                'email' => 'renamed@example.com',
                'account_name' => 'Protected Mitra',
            ])
            ->assertStatus(422);

        $this->assertEquals('protected.mitra@example.com', $account->fresh()->email);
    }

    public function test_legacy_device_accounts_without_kamerakita_work_normally(): void
    {
        $res = $this->actingAs($this->admin)->postJson('/api/devices/accounts', [
            'phone_key' => 'putih',
            'email' => 'legacy.worker@example.com',
            'account_name' => 'Legacy Worker',
        ])->assertCreated();

        $id = $res->json('account.id');
        $this->assertDatabaseHas('device_accounts', [
            'id' => $id,
            'email' => 'legacy.worker@example.com',
            'kamerakita_partner_id' => null,
        ]);

        // Can update note or email
        $this->putJson("/api/devices/accounts/{$id}", [
            'email' => 'legacy.updated@example.com',
            'account_name' => 'Updated Note',
        ])->assertOk();

        $this->assertDatabaseHas('device_accounts', [
            'id' => $id,
            'email' => 'legacy.updated@example.com',
            'account_name' => 'Updated Note',
        ]);
    }
}
