<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Payslip;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\PayrollPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PayslipSyncTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_live_summary_calculates_current_estimate_without_overwriting_saved_draft(): void
    {
        $admin = $this->adminUser();
        $worker = 'Muhammad Rio Hadi';
        Contributor::create(['nama' => $worker]);

        $resolver = app(PayrollPeriod::class);
        $period = $resolver->cutoff(); // current period

        // 1. Create live recordings in this period: 2 tasks of 60 mins each = 120 mins (2 hours = Rp 100.000)
        StudioRecording::create([
            'record_id' => 'RIO_REC_01',
            'participant_email' => 'rio@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Task Live 1',
            'recorded_at' => $period['startUtc'],
            'duration_min' => 60,
            'billable_min' => 60,
            'pending_min' => 0,
            'rejected_min' => 0,
        ]);
        StudioRecording::create([
            'record_id' => 'RIO_REC_02',
            'participant_email' => 'rio@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Task Live 2',
            'recorded_at' => $period['startUtc'],
            'duration_min' => 60,
            'billable_min' => 60,
            'pending_min' => 0,
            'rejected_min' => 0,
        ]);

        // 2. Existing draft slip saved earlier with stale 1 hour (Rp 50.000)
        $slip = Payslip::create([
            'employee_name' => $worker,
            'period' => $period['name'],
            'total_logged_hours' => 1.0,
            'total_approved_hours' => 1.0,
            'total_thp' => 50000,
            'bonus' => 0,
            'status' => 'belum',
            'is_locked' => false,
            'evidence_data' => [
                [
                    'date' => '2026-11-01',
                    'approved_mins' => 60,
                    'rejected_mins' => 0,
                    'pending_mins' => 0,
                    'total_video_mins' => 60,
                    'tasks' => [
                        [
                            'task_name' => 'Old Stale Task',
                            'approved_mins' => 60,
                            'rejected_mins' => 0,
                            'pending_mins' => 0,
                            'notes' => 'OLD_01',
                        ],
                    ],
                ],
            ],
        ]);

        // 3. Fetch live summary
        $response = $this->actingAs($admin)->getJson('/api/payroll/live-summary?periodId='.$period['id']);
        $response->assertOk();

        $workerData = collect($response->json('workers'))->firstWhere('worker_name', $worker);
        $this->assertNotNull($workerData);
        // Approved hours must be 2.0 hours (120 mins)
        $this->assertEquals(2.0, $workerData['approved_hours']);
        // Take Home Pay must be Rp 100.000 (NOT frozen to stale Rp 50.000)
        $this->assertEquals(100000, $workerData['thp']);
        $this->assertFalse($workerData['is_locked']);

        // 4. Reading a live estimate must not overwrite the saved document
        $freshSlip = $slip->fresh();
        $this->assertEquals(1.0, $freshSlip->total_approved_hours);
        $this->assertEquals(50000, $freshSlip->total_thp);
    }

    public function test_paid_or_locked_slip_remains_immutable_in_live_summary(): void
    {
        $admin = $this->adminUser();
        $worker = 'Paid Worker';
        Contributor::create(['nama' => $worker]);

        $resolver = app(PayrollPeriod::class);
        $period = $resolver->cutoff();

        // Live recording has 3 hours approved = Rp 150.000
        StudioRecording::create([
            'record_id' => 'PAID_REC_01',
            'participant_email' => 'paid@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Task Live 1',
            'recorded_at' => $period['startUtc'],
            'duration_min' => 180,
            'billable_min' => 180,
            'pending_min' => 0,
            'rejected_min' => 0,
        ]);

        // Slip was already paid ('sudah') and locked with 1 hour = Rp 50.000
        $slip = Payslip::create([
            'employee_name' => $worker,
            'period' => $period['name'],
            'total_logged_hours' => 1.0,
            'total_approved_hours' => 1.0,
            'total_thp' => 50000,
            'bonus' => 0,
            'status' => 'sudah',
            'is_locked' => true,
            'evidence_data' => [
                [
                    'date' => '2026-11-01',
                    'approved_mins' => 60,
                    'rejected_mins' => 0,
                    'pending_mins' => 0,
                    'total_video_mins' => 60,
                    'tasks' => [],
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/api/payroll/live-summary?periodId='.$period['id']);
        $response->assertOk();

        $workerData = collect($response->json('workers'))->firstWhere('worker_name', $worker);
        $this->assertNotNull($workerData);
        // Locked / already paid slip retains its paid THP of Rp 50.000
        $this->assertEquals(50000, $workerData['thp']);
        $this->assertTrue($workerData['is_locked']);
    }

    public function test_worker_evidence_returns_live_evidence_for_unpaid_slip_and_respects_refresh_param(): void
    {
        $admin = $this->adminUser();
        $worker = 'Muhammad Rio Hadi';
        Contributor::create(['nama' => $worker]);

        $resolver = app(PayrollPeriod::class);
        $period = $resolver->cutoff();

        StudioRecording::create([
            'record_id' => 'REC_EVID_01',
            'participant_email' => 'rio@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Fresh Live Recording',
            'recorded_at' => $period['startUtc'],
            'duration_min' => 60,
            'billable_min' => 60,
            'pending_min' => 0,
            'rejected_min' => 0,
        ]);

        // Unpaid draft slip with old evidence
        Payslip::create([
            'employee_name' => $worker,
            'period' => $period['name'],
            'total_logged_hours' => 1.0,
            'total_approved_hours' => 1.0,
            'total_thp' => 50000,
            'status' => 'belum',
            'is_locked' => false,
            'evidence_data' => [
                [
                    'date' => '2026-11-01',
                    'approved_mins' => 60,
                    'rejected_mins' => 0,
                    'pending_mins' => 0,
                    'total_video_mins' => 60,
                    'tasks' => [
                        [
                            'task_name' => 'Stale Evidence Task',
                            'approved_mins' => 60,
                            'rejected_mins' => 0,
                            'pending_mins' => 0,
                            'notes' => 'STALE_REC',
                        ],
                    ],
                ],
            ],
        ]);

        // A saved draft is preserved unless refresh is explicitly requested
        $res = $this->actingAs($admin)->getJson('/api/payroll/worker-evidence?worker='.urlencode($worker).'&periodId='.$period['id']);
        $res->assertOk();
        $evidence = $res->json('evidence');
        $this->assertNotEmpty($evidence);
        $this->assertEquals('STALE_REC', $evidence[0]['tasks'][0]['notes']);

        // Explicit refresh=1 forces fresh live evidence
        $resRefresh = $this->actingAs($admin)->getJson('/api/payroll/worker-evidence?worker='.urlencode($worker).'&periodId='.$period['id'].'&refresh=1');
        $resRefresh->assertOk();
        $this->assertEquals('REC_EVID_01', $resRefresh->json('evidence.0.tasks.0.notes'));
    }

    public function test_sync_draft_payslips_command_updates_unpaid_draft_slips(): void
    {
        $worker = 'Muhammad Rio Hadi';
        Contributor::create(['nama' => $worker]);

        $resolver = app(PayrollPeriod::class);
        $period = $resolver->cutoff();

        StudioRecording::create([
            'record_id' => 'REC_CMD_01',
            'participant_email' => 'rio@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Cmd Live Recording',
            'recorded_at' => $period['startUtc'],
            'duration_min' => 120,
            'billable_min' => 120,
            'pending_min' => 0,
            'rejected_min' => 0,
        ]);

        $slip = Payslip::create([
            'employee_name' => $worker,
            'period' => $period['name'],
            'total_logged_hours' => 1.0,
            'total_approved_hours' => 1.0,
            'total_thp' => 50000,
            'status' => 'belum',
            'is_locked' => false,
            'evidence_data' => [],
        ]);

        $exitCode = Artisan::call('payroll:sync-drafts');
        $this->assertSame(0, $exitCode);

        $fresh = $slip->fresh();
        $this->assertEquals(2.0, $fresh->total_approved_hours);
        $this->assertEquals(100000, $fresh->total_thp);
    }
}
