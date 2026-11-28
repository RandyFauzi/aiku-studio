<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_page_renders_pin_gate_without_exposing_pin_literal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $res = $this->actingAs($admin)->get('/profit');
        $res->assertOk()
            ->assertSee('Akses Halaman Profit')
            ->assertDontSee('123123');
    }

    public function test_admin_can_verify_correct_pin_and_wrong_pin_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/profit/verify-pin', ['pin' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($admin)->postJson('/api/profit/verify-pin', ['pin' => '123123'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_non_admin_cannot_verify_profit_pin(): void
    {
        $worker = User::factory()->create(['role' => 'contributor']);

        $this->actingAs($worker)->postJson('/api/profit/verify-pin', ['pin' => '123123'])
            ->assertForbidden();
    }
}
