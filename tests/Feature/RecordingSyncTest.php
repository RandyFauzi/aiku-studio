<?php
namespace Tests\Feature;

use App\Models\ShiftSetting;
use App\Services\RecordingSync;
use App\Services\Robotics1618Sync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordingSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_failure_runs_other_source_and_reports_error(): void
    {
        config(['payroll.atlas_cookie' => '', 'payroll.robotics_1618_username' => 'test', 'payroll.robotics_1618_password' => 'test']);
        $robot = $this->mock(Robotics1618Sync::class);
        $robot->shouldReceive('sync')->once()->andReturn(['status' => 'success']);
        $robot->shouldReceive('state')->andReturn(['isRunning' => false, 'stage' => 'completed', 'percent' => 100, 'message' => '1618 selesai']);
        $sync = app(RecordingSync::class);
        $sync->request(); $sync->tick();
        $state = $sync->state();
        $this->assertFalse($state['isRunning']);
        $this->assertSame('error', $state['stage']);
        $this->assertSame('completed', $state['sources']['robotics']['stage']);
        $this->assertStringContainsString('Atlas', $state['error']);
    }

    public function test_disabled_schedule_does_not_start_either_source(): void
    {
        $this->mock(Robotics1618Sync::class)->shouldNotReceive('sync');
        app(RecordingSync::class)->tick();
        $this->assertFalse(app(RecordingSync::class)->state()['isRunning']);
    }

    public function test_due_shared_schedule_queues_both_sources(): void
    {
        config(['payroll.atlas_cookie' => '', 'payroll.robotics_1618_username' => 'test', 'payroll.robotics_1618_password' => 'test']);
        ShiftSetting::create(['setting_key' => 'atlas_auto_sync', 'setting_value' => ['enabled' => true, 'intervalMinutes' => 30, 'nextRun' => null]]);
        $robot = $this->mock(Robotics1618Sync::class);
        $robot->shouldReceive('sync')->once()->andReturn([]);
        $robot->shouldReceive('state')->andReturn(['stage' => 'completed', 'percent' => 100, 'message' => 'Selesai']);
        app(RecordingSync::class)->tick();
        $this->assertCount(2, app(RecordingSync::class)->state()['sources']);
        $this->assertNotNull(ShiftSetting::where('setting_key', 'atlas_auto_sync')->value('setting_value')['nextRun']);
    }
    public function test_atlas_continues_past_three_pages_until_finished_in_one_tick(): void
    {
        $pages = 0;
        $atlas = $this->mock(\App\Services\AtlasSync::class);
        $atlas->shouldReceive('auto')->andReturn(['enabled' => false]);
        $atlas->shouldReceive('tick')->times(8)->andReturnUsing(function () use (&$pages) { $pages++; });
        // Capture by reference because each processed page changes the remaining work.
        $atlas->shouldReceive('state')->andReturnUsing(function () use (&$pages) {
            return ['isRunning' => $pages < 8, 'stage' => $pages < 8 ? 'scraping' : 'completed', 'percent' => $pages < 8 ? 0 : 100, 'message' => 'Atlas selesai'];
        });
        ShiftSetting::create(['setting_key' => 'recording_sync_state', 'setting_value' => [
            'isRunning' => true, 'stage' => 'queued', 'message' => 'Menunggu',
            'sources' => ['atlas' => ['stage' => 'queued'], 'robotics' => ['stage' => 'completed', 'percent' => 100]],
        ]]);
        app(RecordingSync::class)->tick();
        $this->assertSame(8, $pages);
        $this->assertSame('completed', app(RecordingSync::class)->state()['stage']);
    }

    public function test_manual_sync_can_target_only_1618(): void
    {
        config(['payroll.robotics_1618_username' => 'test', 'payroll.robotics_1618_password' => 'test']);
        $robot = $this->mock(Robotics1618Sync::class);
        $robot->shouldReceive('sync')->once()->andReturn(['status' => 'success']);
        $robot->shouldReceive('state')->andReturn(['isRunning' => false, 'stage' => 'completed', 'percent' => 100, 'message' => '1618 selesai']);

        $atlas = $this->mock(\App\Services\AtlasSync::class);
        $atlas->shouldReceive('state')->andReturn(['isRunning' => false]);
        $atlas->shouldReceive('auto')->andReturn(['enabled' => false, 'intervalMinutes' => 30]);
        $atlas->shouldNotReceive('request');
        $atlas->shouldNotReceive('tick');

        $sync = app(RecordingSync::class);
        $sync->request('1618');

        $state = $sync->state();
        $this->assertTrue($state['isRunning']);
        $this->assertSame(['robotics'], array_keys($state['sources']));

        $sync->tick();
        $finalState = $sync->state();
        $this->assertFalse($finalState['isRunning']);
        $this->assertSame('completed', $finalState['stage']);
    }

    public function test_manual_sync_can_target_only_verbos(): void
    {
        $this->mock(Robotics1618Sync::class)->shouldNotReceive('sync');

        $atlas = $this->mock(\App\Services\AtlasSync::class);
        $atlas->shouldReceive('state')->andReturn(['isRunning' => false, 'stage' => 'completed', 'percent' => 100, 'message' => 'Verbos selesai']);
        $atlas->shouldReceive('auto')->andReturn(['enabled' => false, 'intervalMinutes' => 30]);
        $atlas->shouldReceive('request')->once();
        $atlas->shouldReceive('tick')->once();

        $sync = app(RecordingSync::class);
        $sync->request('verbos');

        $state = $sync->state();
        $this->assertTrue($state['isRunning']);
        $this->assertSame(['atlas'], array_keys($state['sources']));

        $sync->tick();
        $finalState = $sync->state();
        $this->assertFalse($finalState['isRunning']);
        $this->assertSame('completed', $finalState['stage']);
    }
}
