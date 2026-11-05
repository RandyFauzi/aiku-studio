<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Payslip;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingCarryOverTest extends TestCase
{
    use RefreshDatabase;

    private function contributorUser(string $name, string $email): User
    {
        $contributor = Contributor::create([
            'nama' => $name,
            'email' => $email,
            'role' => 'contributor',
        ]);

        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'contributor',
            'contributor_id' => $contributor->id,
            'is_active' => true,
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@aiku.test',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_locked_or_saved_slip_does_not_mutate_when_recordings_change_to_billable(): void
    {
        $admin = $this->adminUser();
        $worker = 'Budi Santoso';

        // 1. Create a recording for Sep-Oct cutoff (22 Sep - 21 Okt)
        StudioRecording::create([
            'record_id' => 'REC_001',
            'participant_email' => 'budi@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Task Clean Shelf',
            'recorded_at' => '2026-10-05 10:00:00',
            'duration_min' => 60,
            'billable_min' => 0, // initially 0 billable, 60 pending
            'pending_min' => 60,
            'rejected_min' => 0,
        ]);

        // 2. Payslip was generated on Oct 22 night based on approved 0 hours
        $slip = Payslip::create([
            'employee_name' => $worker,
            'period' => '22 Sep 2026 - 21 Okt 2026',
            'total_logged_hours' => 1.0,
            'total_approved_hours' => 0.0,
            'total_thp' => 0,
            'bonus' => 0,
            'status' => 'sudah',
            'is_locked' => true,
            'evidence_data' => [
                [
                    'date' => '2026-10-05',
                    'approved_mins' => 0,
                    'rejected_mins' => 0,
                    'pending_mins' => 60,
                    'total_video_mins' => 60,
                    'tasks' => [
                        [
                            'task_name' => 'Task Clean Shelf',
                            'approved_mins' => 0,
                            'rejected_mins' => 0,
                            'pending_mins' => 60,
                            'notes' => 'REC_001',
                        ],
                    ],
                ],
            ],
        ]);

        // 3. Days later, recording is approved by 1618: billable_min becomes 60, pending_min becomes 0
        StudioRecording::where('record_id', 'REC_001')->update([
            'billable_min' => 60,
            'pending_min' => 0,
        ]);

        // 4. Viewing the historical slip must NOT change its THP (must remain 0)
        $calculator = app(PayrollCalculator::class);
        $displayed = $calculator->displaySlip($slip);
        $this->assertEquals(0, $displayed['total_thp']);
        $this->assertEquals(0, $displayed['total_approved_hours']);

        // 5. In summary view for 22 Sep - 21 Okt, it must return locked slip THP (0)
        $periodResolver = app(\App\Services\PayrollPeriod::class);
        $period = $periodResolver->resolve(['period' => '22 Sep 2026 - 21 Okt 2026']);
        $summary = $calculator->summary($period);
        $workerSummary = collect($summary['workers'])->firstWhere('worker_name', $worker);
        $this->assertNotNull($workerSummary);
        $this->assertEquals(0, $workerSummary['thp']);
        $this->assertTrue($workerSummary['is_locked']);
    }

    public function test_detects_pending_carry_over_for_next_period_and_includes_in_new_slip(): void
    {
        $admin = $this->adminUser();
        $worker = 'Aufa Anugraha';

        // 1. Create recording for 22 Sep - 21 Okt period
        StudioRecording::create([
            'record_id' => 'REC_AUFA_01',
            'participant_email' => 'aufa@example.com',
            'assigned_worker_name' => $worker,
            'task_title' => 'Recording Atlas 1',
            'recorded_at' => '2026-10-10 12:00:00',
            'duration_min' => 60,
            'billable_min' => 60, // Now approved 60 mins (was pending at cutoff)
            'pending_min' => 0,
            'rejected_min' => 0,
        ]);

        // Prior slip on 22 Sep - 21 Okt only had 0 mins approved for this task
        Payslip::create([
            'employee_name' => $worker,
            'period' => '22 Sep 2026 - 21 Okt 2026',
            'total_logged_hours' => 1.0,
            'total_approved_hours' => 0.0,
            'total_thp' => 100000, // say other task had 100k
            'bonus' => 0,
            'status' => 'sudah',
            'is_locked' => true,
            'evidence_data' => [
                [
                    'date' => '2026-10-10',
                    'approved_mins' => 0,
                    'rejected_mins' => 0,
                    'pending_mins' => 60,
                    'total_video_mins' => 60,
                    'tasks' => [
                        [
                            'task_name' => 'Recording Atlas 1',
                            'approved_mins' => 0,
                            'rejected_mins' => 0,
                            'pending_mins' => 60,
                            'notes' => 'REC_AUFA_01',
                        ],
                    ],
                ],
            ],
        ]);

        // 2. Query detectPendingCarryOver for current period 22 Okt 2026 - 21 Nov 2026
        $response = $this->actingAs($admin)->getJson('/api/payroll/pending-carry-over?worker='.urlencode($worker).'&period='.urlencode('22 Okt 2026 - 21 Nov 2026'));
        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['available']);
        $this->assertEquals(60.0, $data['total_mins']);
        $this->assertEquals(1.0, $data['total_hours']);
        $this->assertEquals(50000, $data['total_amount']); // 60 mins at 50k/hr
        $this->assertCount(1, $data['tasks']);
        $this->assertEquals('REC_AUFA_01', $data['tasks'][0]['record_id']);

        // 3. Create new slip for 22 Okt 2026 - 21 Nov 2026 including this carry over
        $createResponse = $this->actingAs($admin)->postJson('/api/payslips', [
            'employee_name' => $worker,
            'period' => '22 Okt 2026 - 21 Nov 2026',
            'bonus' => 10000,
            'pending_carry_over_amount' => 50000,
            'pending_carry_over_hours' => 1.0,
            'pending_carry_over_data' => $data['tasks'],
            'evidence' => [
                [
                    'date' => '2026-10-25',
                    'approved_mins' => 120, // 2 hours = 100,000
                    'rejected_mins' => 0,
                    'pending_mins' => 0,
                    'total_video_mins' => 120,
                    'tasks' => [
                        [
                            'task_name' => 'Current task',
                            'approved_mins' => 120,
                            'rejected_mins' => 0,
                            'pending_mins' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $createResponse->assertOk();

        // 4. Verify new slip in DB has total_thp = 100,000 (base) + 10,000 (bonus) + 50,000 (carry-over) = 160,000
        $newSlip = Payslip::where('employee_name', $worker)->where('period', '22 Okt 2026 - 21 Nov 2026')->first();
        $this->assertNotNull($newSlip);
        $this->assertEquals(160000, $newSlip->total_thp);
        $this->assertEquals(50000, $newSlip->pending_carry_over_amount);
        $this->assertCount(1, $newSlip->pending_carry_over_data);

        // 5. Query carry over again for next period -> must NOT include REC_AUFA_01 (anti-double-pay)
        $checkAgain = $this->actingAs($admin)->getJson('/api/payroll/pending-carry-over?worker='.urlencode($worker).'&period='.urlencode('22 Nov 2026 - 21 Des 2026'));
        $checkAgain->assertOk();
        $dataAgain = $checkAgain->json();
        $this->assertFalse($dataAgain['available']);
        $this->assertEquals(0, $dataAgain['total_amount']);
    }
}
