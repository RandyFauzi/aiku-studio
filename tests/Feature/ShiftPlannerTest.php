<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use App\Services\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function configuration(int $count = 1): array
    {
        $config = config('shifts');
        $config['planner_version'] = 2;
        $config['sessions_count'] = 2;
        $config['session_durations'] = [90, 180];
        $config['day_off'] = 'none';
        $config['karyawan'] = [];
        for ($i = 0; $i < $count; $i++) {
            $person = Contributor::create(['nama' => 'Worker '.$i, 'email' => 'worker'.$i.'@example.test']);
            app(ContributorAccess::class)->sync($person);
            $config['karyawan'][] = ['name' => $person->nama, 'contributor_id' => $person->id, 'alat' => 'Alat 1', 'sessions' => [1, 2], 'status' => 'scheduled', 'note' => ''];
        }

        return $config;
    }

    private function savePattern(array $config, string $period = '2026-10-22_2026-11-21', int $revision = 0)
    {
        return $this->postJson('/api/shifts/config', ['periodId' => $period, 'expected_revision' => $revision, 'config' => $config]);
    }

    public function test_period_boundaries_persistence_and_stale_revision(): void
    {
        $resolver = app(PayrollPeriod::class);
        $this->assertSame('2026-10-22_2026-11-21', $resolver->cutoff(CarbonImmutable::parse('2026-11-21 23:59', 'Asia/Makassar'))['id']);
        $this->assertSame('2026-11-22_2026-12-21', $resolver->cutoff(CarbonImmutable::parse('2026-11-22 00:00', 'Asia/Makassar'))['id']);
        $this->actingAs(User::factory()->create());
        $config = $this->configuration();
        $this->savePattern($config)->assertOk()->assertJsonPath('revision', 1)->assertJsonPath('config.karyawan.0.daily_work_hrs', 4.5);
        $this->getJson('/api/shifts/config?periodId=2026-10-22_2026-11-21')->assertOk()->assertJsonPath('inherited', false)->assertJsonPath('config.session_durations', [90, 180]);
        $this->getJson('/api/shifts/config?periodId=2026-11-22_2026-12-21')->assertOk()->assertJsonPath('revision', 0)->assertJsonPath('inherited', true);
        $this->savePattern($config)->assertConflict();
        $this->savePattern($config, '2026-11-01_2026-11-30')->assertUnprocessable();
        $this->savePattern($config, '2026-11-22_2026-12-21')->assertOk();
        $this->assertDatabaseMissing('shift_settings', ['setting_key' => 'main_shift_config']);
    }

    public function test_capacity_duration_and_identity_are_enforced_on_server(): void
    {
        $this->actingAs(User::factory()->create());
        $config = $this->configuration(4);
        $this->savePattern($config)->assertUnprocessable();
        array_pop($config['karyawan']);
        $this->savePattern($config)->assertOk(); // Three people; tools assigned per shift automatically.
        $invalid = $config;
        $invalid['session_durations'] = [90];
        $this->savePattern($invalid, revision: 1)->assertUnprocessable();
        $invalid['session_durations'] = [900, 900];
        $this->savePattern($invalid, revision: 1)->assertUnprocessable();
        $invalid = $config;
        $invalid['karyawan'][0]['contributor_id'] = 999999;
        $this->savePattern($invalid, revision: 1)->assertUnprocessable();
        $invalid['karyawan'][0]['contributor_id'] = null;
        $invalid['karyawan'][0]['name'] = 'Unknown worker';
        $this->savePattern($invalid, revision: 1)->assertUnprocessable();
    }

    public function test_workers_receive_only_own_durations_tools_and_daily_leave(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $config = $this->configuration(3);
        $this->savePattern($config)->assertOk();
        $worker = User::where('contributor_id', $config['karyawan'][1]['contributor_id'])->firstOrFail();
        $person = Contributor::findOrFail($worker->contributor_id);
        $person->update(['nama' => 'Renamed Worker']);
        $this->actingAs($worker)->getJson('/api/me/attendance?month=2026-11')->assertOk()
            ->assertJsonPath('schedule.0.sessions.0.tool', 'Alat 2')
            ->assertJsonPath('schedule.0.sessions.0.start', '08:00')
            ->assertJsonPath('schedule.0.sessions.0.end', '09:30')
            ->assertJsonPath('schedule.0.sessions.1.start', '10:30')
            ->assertJsonPath('schedule.0.sessions.1.end', '13:30')
            ->assertJsonPath('schedule.0.work_hours', 4.5);
        $daily = $config;
        $daily['karyawan'][1]['status'] = 'sakit';
        $daily['karyawan'][1]['note'] = 'Istirahat hari ini';
        $this->actingAs($admin)->putJson('/api/shifts/days/2026-11-05', ['config' => $daily, 'expected_revision' => 0])->assertOk()
            ->assertJsonPath('day.config.karyawan.1.daily_work_hrs', 0);
        $this->getJson('/api/shifts/days?startDate=2026-10-22&endDate=2026-11-21')->assertOk()->assertJsonPath('days.2026-11-05.config.karyawan.1.status', 'sakit');
        $this->actingAs($worker)->getJson('/api/me/attendance?month=2026-11')->assertOk()
            ->assertJsonPath('schedule.4.status', 'sakit')->assertJsonPath('schedule.4.note', 'Istirahat hari ini')
            ->assertJsonPath('schedule.4.sessions', [])->assertJsonPath('schedule.5.work_hours', 4.5);
        $other = User::where('contributor_id', $config['karyawan'][0]['contributor_id'])->firstOrFail();
        $this->actingAs($other)->getJson('/api/me/attendance?month=2026-11')->assertOk()
            ->assertJsonPath('schedule.4.status', 'scheduled')->assertJsonPath('schedule.4.note', '');
        $this->getJson('/api/shifts/config?periodId=current')->assertForbidden();
        $this->savePattern($config)->assertForbidden();
        $this->actingAs($admin)->deleteJson('/api/shifts/days/2026-11-05', ['expected_revision' => 1])->assertOk();
        $this->actingAs($worker)->getJson('/api/me/attendance?month=2026-11')->assertJsonPath('schedule.4.status', 'scheduled');
    }

    public function test_date_overrides_survive_period_changes_and_overnight_times_are_correct(): void
    {
        $this->actingAs(User::factory()->create());
        $config = $this->configuration();
        $config['start_time'] = '23:00';
        $config['charging_duration_min'] = 30;
        $this->savePattern($config)->assertOk();
        $daily = $config;
        $daily['karyawan'][0]['status'] = 'izin';
        $this->putJson('/api/shifts/days/2026-11-06', ['config' => $daily, 'expected_revision' => 0])->assertOk();
        $config['session_durations'] = [60, 120];
        $this->savePattern($config, revision: 1)->assertOk();
        $next = $config;
        $next['session_durations'] = [30, 45];
        $this->savePattern($next, '2026-11-22_2026-12-21')->assertOk();
        $worker = User::where('contributor_id', $config['karyawan'][0]['contributor_id'])->firstOrFail();
        $response = $this->actingAs($worker)->getJson('/api/me/attendance?month=2026-11')->assertOk()
            ->assertJsonPath('schedule.5.status', 'izin')->assertJsonPath('schedule.5.sessions', [])
            ->assertJsonPath('schedule.20.work_hours', 3)->assertJsonPath('schedule.21.work_hours', 1.25);
        $this->assertSame('2026-11-02T00:00:00+08:00', $response->json('schedule.0.sessions.0.end_at'));
        $this->assertSame('2026-11-02T00:30:00+08:00', $response->json('schedule.0.sessions.1.start_at'));
    }

    public function test_same_display_names_are_linked_by_distinct_ids_and_views_render(): void
    {
        $this->actingAs(User::factory()->create());
        $config = $this->configuration(2);
        Contributor::whereIn('id', array_column($config['karyawan'], 'contributor_id'))->update(['nama' => 'Same Name']);
        foreach ($config['karyawan'] as &$worker) {
            $worker['name'] = 'Same Name';
        }
        unset($worker);
        $this->savePattern($config)->assertOk();
        $this->get('/shifts')->assertOk()->assertSee('Susun shift, semudah pindah kartu.');
        $config['karyawan'][1]['contributor_id'] = $config['karyawan'][0]['contributor_id'];
        $this->savePattern($config, revision: 1)->assertUnprocessable();
        $this->getJson('/api/shifts/days?startDate=2026-01-01&endDate=2026-12-31')->assertUnprocessable();
    }
}
