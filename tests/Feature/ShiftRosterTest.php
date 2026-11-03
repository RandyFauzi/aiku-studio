<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_changes_persist_and_hours_are_calculated_on_the_server(): void
    {
        $this->actingAs(User::factory()->create());
        $config = config('shifts');
        $config['karyawan'] = [['name' => 'First Worker', 'short' => 'First', 'role' => 'Reguler', 'alat' => 'Alat 2', 'sessions' => [5, 3], 'daily_work_hrs' => 999, 'daily_prod_hrs' => 999]];
        $this->postJson('/api/shifts/config', $config)->assertOk()->assertJsonPath('config.karyawan.0.sessions', [3, 5])
            ->assertJsonPath('config.karyawan.0.daily_work_hrs', 4)->assertJsonPath('config.karyawan.0.daily_prod_hrs', 8);
        $this->getJson('/api/shifts/config')->assertOk()->assertJsonPath('config.karyawan.0.name', 'First Worker')
            ->assertJsonPath('config.karyawan.0.alat', 'Alat 2')->assertJsonPath('config.karyawan.0.sessions', [3, 5]);
    }

    public function test_conflicting_assignments_and_out_of_range_slots_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $config = config('shifts');
        $config['karyawan'][1]['alat'] = $config['karyawan'][0]['alat'];
        $this->postJson('/api/shifts/config', $config)->assertUnprocessable();
        $this->assertDatabaseMissing('shift_settings', ['setting_key' => 'main_shift_config']);
        $config = config('shifts');
        $config['karyawan'][0]['sessions'] = [6];
        $this->postJson('/api/shifts/config', $config)->assertUnprocessable();
        $config['karyawan'][0]['sessions'] = [1, 1];
        $this->postJson('/api/shifts/config', $config)->assertUnprocessable();
        $config['karyawan'][0]['sessions'] = [1];
        $config['karyawan'][0]['alat'] = 'Alat 4';
        $this->postJson('/api/shifts/config', $config)->assertUnprocessable();
    }

    public function test_unassigned_workers_and_empty_rosters_are_supported(): void
    {
        $this->actingAs(User::factory()->create());
        $config = config('shifts');
        $config['karyawan'][0]['sessions'] = [];
        $this->postJson('/api/shifts/config', $config)->assertOk()->assertJsonPath('config.karyawan.0.daily_prod_hrs', 0);
        $config['karyawan'] = [];
        $this->postJson('/api/shifts/config', $config)->assertOk();
        $this->getJson('/api/shifts/config')->assertJsonPath('config.karyawan', []);
    }
}
