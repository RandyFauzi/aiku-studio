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
}
