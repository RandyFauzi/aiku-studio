<?php

namespace Tests\Feature;

use App\Models\Investor;
use App\Models\StudioRecording;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_studio_targets_endpoint(): void
    {
        $this->getJson('/api/studio-targets')->assertUnauthorized();
    }

    public function test_all_user_roles_can_access_studio_targets_and_see_target_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributor = User::factory()->create(['role' => 'contributor']);
        $investorUser = User::factory()->create(['role' => 'investor']);
        Investor::create([
            'user_id' => $investorUser->id,
            'code' => 'INV-01',
            'name' => 'Investor Target',
        ]);

        $this->actingAs($admin)->getJson('/api/studio-targets')->assertOk()
            ->assertJsonPath('monthly.target_hours', 2150);
        $this->actingAs($contributor)->getJson('/api/studio-targets')->assertOk()
            ->assertJsonPath('monthly.target_hours', 2150);
        $this->actingAs($investorUser)->getJson('/api/studio-targets')->assertOk()
            ->assertJsonPath('monthly.target_hours', 2150);

        $this->actingAs($admin)->get('/')->assertOk()
            ->assertSee('Target Harian Studio')
            ->assertSee('Target Bulanan Studio');
        $this->actingAs($contributor)->get('/me')->assertOk()
            ->assertSee('Target Harian Studio')
            ->assertSee('Target Bulanan Studio');
        $this->actingAs($investorUser)->get('/investor')->assertOk()
            ->assertSee('Target Harian Studio')
            ->assertSee('Target Bulanan Studio');
    }

    public function test_studio_targets_calculate_16_to_15_cutoff_and_daily_target_accurately(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-26 11:00:00', 'Asia/Makassar'));
        try {
            $user = User::factory()->create(['role' => 'contributor']);

            // Hari ini (26 Nov 2026 WITA -> masuk siklus 16 Nov - 15 Des 2026, 30 hari): 120 menit billable = 2 jam
            StudioRecording::create([
                'record_id' => 'rec_today_1',
                'participant_email' => 'a@kamerakitaid.site',
                'recorded_at' => CarbonImmutable::parse('2026-11-26 10:00:00', 'Asia/Makassar')->setTimezone('UTC'),
                'duration_sec' => 7200,
                'duration_min' => 120,
                'billable_min' => 120,
            ]);

            // Hari lain masih di dalam siklus 16 Nov - 15 Des 2026: 180 menit billable = 3 jam
            StudioRecording::create([
                'record_id' => 'rec_cycle_2',
                'participant_email' => 'b@kamerakitaid.site',
                'recorded_at' => CarbonImmutable::parse('2026-11-18 10:00:00', 'Asia/Makassar')->setTimezone('UTC'),
                'duration_sec' => 10800,
                'duration_min' => 180,
                'billable_min' => 180,
            ]);

            // Di luar siklus (15 Nov 2026): tidak boleh ikut hitungan siklus aktif
            StudioRecording::create([
                'record_id' => 'rec_old_3',
                'participant_email' => 'c@kamerakitaid.site',
                'recorded_at' => CarbonImmutable::parse('2026-11-15 10:00:00', 'Asia/Makassar')->setTimezone('UTC'),
                'duration_sec' => 36000,
                'duration_min' => 600,
                'billable_min' => 600,
            ]);

            $res = $this->actingAs($user)->getJson('/api/studio-targets');
            $res->assertOk()
                ->assertJsonPath('period.id', '2026-11-16_2026-12-15')
                ->assertJsonPath('period.total_days', 30)
                ->assertJsonPath('daily.target_hours', 80)
                ->assertJsonPath('daily.actual_hours', 2)
                ->assertJsonPath('monthly.target_hours', 2150)
                ->assertJsonPath('monthly.actual_hours', 5);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
