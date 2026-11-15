<?php

namespace Tests\Feature;

use App\Models\Payslip;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use App\Services\PayslipWriter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['employee_name' => 'Worker', 'period' => '22 Okt 2026 - 15 Nov 2026',
            'bonus' => 1000, 'evidence' => [['date' => '2026-11-01', 'approved_mins' => 60, 'notes' => 'manual snapshot']]], $extra);
    }

    private function slip(array $extra = []): Payslip
    {
        return Payslip::create(array_replace(['employee_name' => 'Worker', 'period' => '22 Okt 2026 - 15 Nov 2026',
            'status' => 'belum', 'is_locked' => false, 'bonus' => 1000, 'total_approved_hours' => 1,
            'total_logged_hours' => 1, 'total_thp' => 51000, 'evidence_data' => $this->payload()['evidence']], $extra));
    }

    private function recording(string $date = '2026-11-01 04:00:00'): void
    {
        StudioRecording::create(['record_id' => 'rec-'.$date, 'participant_email' => 'worker@example.test',
            'assigned_worker_name' => 'Worker', 'recorded_at' => $date, 'duration_min' => 120, 'billable_min' => 120]);
    }

    public function test_repeated_post_reuses_id_without_overwriting_a_snapshot(): void
    {
        $id = $this->postJson('/api/payslips', $this->payload())->assertOk()->json('data.id');
        $this->postJson('/api/payslips', $this->payload(['bonus' => 90000, 'period' => '22 Oct 2026 - 15 Nov 2026']))
            ->assertOk()->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('payslips', 1);
        $this->assertDatabaseHas('payslips', ['id' => $id, 'total_thp' => 51000]);
    }

    public function test_active_key_is_unique_at_database_level(): void
    {
        $key = app(PayslipWriter::class)->key('Worker', $this->payload()['period']);
        $this->slip(['active_key' => $key]);
        $this->expectException(QueryException::class);
        $this->slip(['active_key' => $key]);
    }

    public function test_reading_list_summary_detail_and_live_never_mutates_saved_carry_or_identity(): void
    {
        $slip = $this->slip(['pending_carry_over_amount' => 25000,
            'pending_carry_over_data' => [['source_period' => '22 Sep 2026 - 21 Okt 2026', 'delta_min' => 30]], 'total_thp' => 76000]);
        $this->recording();
        $before = $slip->fresh()->getAttributes();
        $this->getJson('/api/slips')->assertOk()->assertJsonPath('0.total_thp', 76000);
        $this->getJson('/api/payroll/live-summary?periodId=2026-10-22_2026-11-15')->assertOk()->assertJsonPath('workers.0.thp', 126000);
        $calculator = app(PayrollCalculator::class);
        $display = $calculator->displaySlip($slip);
        $this->assertSame($slip->id, $display['id']);
        $this->assertSame(76000, $display['total_thp']);
        $live = $calculator->liveSlip(app(PayrollPeriod::class)->resolve(['period' => $slip->period]), 'Worker');
        $this->assertSame(25000, $live['pending_carry_over_amount']);
        $this->assertSame(126000, $live['total_thp']);
        $this->assertEquals($before, $slip->fresh()->getAttributes());
    }

    public function test_duplicates_are_visible_and_never_silently_selected(): void
    {
        $first = $this->slip(['total_thp' => 76000, 'pending_carry_over_amount' => 25000]);
        $second = $this->slip();
        $this->recording();
        $this->getJson('/api/slips')->assertOk()->assertJsonPath('0.duplicate_count', 2);
        $this->postJson('/api/payslips', $this->payload())->assertConflict();
        $this->putJson('/api/payslips/'.$first->id, $this->payload())->assertConflict();
        $this->patchJson('/api/payslips/'.$first->id.'/status', ['status' => 'sudah'])->assertConflict();
        $this->getJson('/api/payroll/worker-evidence?worker=Worker&periodId=2026-10-22_2026-11-15')->assertConflict();
        $this->getJson('/api/payroll/live-summary?periodId=2026-10-22_2026-11-15')->assertOk()
            ->assertJsonPath('workers.0.thp', null)->assertJsonPath('stats.conflictWorkers', 1);
        $this->assertSame(76000, app(PayrollCalculator::class)->displaySlip($first)['total_thp']);
        $this->assertSame($second->id, app(PayrollCalculator::class)->displaySlip($second)['id']);
    }

    public function test_resolution_preserves_both_documents_and_selected_carry(): void
    {
        $chosen = $this->slip(['pending_carry_over_amount' => 25000, 'total_thp' => 76000]);
        $other = $this->slip();
        $this->postJson('/api/payslips/'.$chosen->id.'/resolve-duplicates')->assertOk();
        $this->assertDatabaseCount('payslips', 2);
        $this->assertSame($chosen->id, $other->fresh()->superseded_by_id);
        $this->assertNotNull($other->fresh()->superseded_at);
        $this->assertSame(auth()->id(), $other->fresh()->superseded_by_user_id);
        $this->assertSame(76000, $chosen->fresh()->total_thp);
        $this->assertSame(25000, $chosen->fresh()->pending_carry_over_amount);
        $this->postJson('/api/payslips', $this->payload())->assertOk()->assertJsonPath('data.id', $chosen->id);
        $this->putJson('/api/payslips/'.$other->id, $this->payload())->assertConflict();
        $this->deleteJson('/api/payslips/'.$chosen->id)->assertConflict();
    }

    public function test_paid_or_locked_slip_cannot_be_edited_deleted_or_unlocked(): void
    {
        foreach ([['status' => 'sudah'], ['is_locked' => true]] as $extra) {
            $slip = $this->slip($extra);
            $before = $slip->fresh()->getAttributes();
            $this->putJson('/api/payslips/'.$slip->id, $this->payload(['is_locked' => false, 'bonus' => 999]))->assertConflict();
            $this->deleteJson('/api/payslips/'.$slip->id)->assertConflict();
            $this->assertEquals($before, $slip->fresh()->getAttributes());
            $slip->delete(); // Test fixture cleanup only.
        }
    }

    public function test_returning_payment_to_unpaid_does_not_unlock_snapshot(): void
    {
        $slip = $this->slip();
        $this->patchJson('/api/payslips/'.$slip->id.'/status', ['status' => 'sudah'])->assertOk();
        $this->postJson('/api/payroll/status', ['worker_name' => 'Worker', 'period' => $slip->period, 'status' => 'belum'])->assertOk();
        $this->assertTrue($slip->fresh()->is_locked);
        $this->putJson('/api/payslips/'.$slip->id, $this->payload())->assertConflict();
    }

    public function test_paid_live_worker_gets_frozen_document_and_refresh_cannot_replace_it(): void
    {
        $this->recording();
        $this->postJson('/api/payroll/status', ['worker_name' => 'Worker', 'period' => $this->payload()['period'], 'status' => 'sudah', 'expected_thp' => 100000])->assertOk();
        $slip = Payslip::first();
        $this->assertTrue($slip->is_locked);
        StudioRecording::query()->update(['billable_min' => 180]);
        $this->getJson('/api/payroll/worker-evidence?worker=Worker&periodId=2026-10-22_2026-11-15&refresh=1')->assertOk()
            ->assertJsonPath('evidence.0.approved_mins', 120);
    }

    public function test_payment_from_live_freezes_estimate_but_saved_payment_keeps_snapshot(): void
    {
        $slip = $this->slip(['pending_carry_over_amount' => 25000, 'total_thp' => 76000]);
        $this->recording();
        $this->postJson('/api/payroll/status', ['worker_name' => 'Worker', 'period' => $slip->period, 'status' => 'sudah', 'expected_thp' => 126000])->assertOk();
        $this->assertSame(126000, $slip->fresh()->total_thp);
        $this->assertTrue($slip->fresh()->is_locked);
    }

    public function test_stale_payment_estimate_rolls_back_snapshot_changes(): void
    {
        $slip = $this->slip();
        $this->recording();
        $before = $slip->fresh()->getAttributes();
        $this->postJson('/api/payroll/status', ['worker_name' => 'Worker', 'period' => $slip->period,
            'status' => 'sudah', 'expected_thp' => 51000])->assertConflict();
        $this->assertEquals($before, $slip->fresh()->getAttributes());
    }

    public function test_carry_is_available_from_old_final_period_and_server_prevents_second_claim(): void
    {
        $source = $this->slip(['period' => '22 Sep 2026 - 21 Okt 2026', 'status' => 'sudah', 'is_locked' => true, 'total_approved_hours' => 1]);
        $this->recording('2026-10-01 04:00:00');
        $calculator = app(PayrollCalculator::class);
        // More than three cutoff periods later, the balance is still available.
        $bank = $calculator->detectPendingCarryOver('Worker', '16 Mar 2027 - 15 Apr 2027');
        $this->assertSame(50000, $bank['total_amount']);
        $claim = ['pending_carry_over_amount' => $bank['total_amount'], 'pending_carry_over_data' => $bank['tasks']];
        $this->postJson('/api/payslips', $this->payload($claim))->assertOk();
        $this->postJson('/api/payslips', $this->payload($claim + ['period' => '16 Nov 2026 - 15 Des 2026']))->assertUnprocessable();
        $this->assertSame(0, $calculator->detectPendingCarryOver('Worker', '16 Nov 2026 - 15 Des 2026')['total_amount']);
        $this->assertSame(50000, $calculator->detectPendingCarryOver('Worker', '16 Nov 2026 - 15 Des 2026')['reserved_amount']);
    }

    public function test_unfinalized_source_has_explanation_and_cannot_be_claimed(): void
    {
        $this->slip(['period' => '22 Sep 2026 - 21 Okt 2026']);
        $this->recording('2026-10-01 04:00:00');
        $bank = app(PayrollCalculator::class)->detectPendingCarryOver('Worker', $this->payload()['period']);
        $this->assertFalse($bank['available']);
        $this->assertNotEmpty($bank['warnings']);
        $this->postJson('/api/payslips', $this->payload(['pending_carry_over_amount' => 50000,
            'pending_carry_over_data' => [['source_period' => '22 Sep 2026 - 21 Okt 2026', 'delta_min' => 60]]]))->assertUnprocessable();
    }

    public function test_unrelated_excluded_slip_and_forged_carry_are_rejected(): void
    {
        $other = $this->slip(['employee_name' => 'Other']);
        $this->getJson('/api/payroll/pending-carry-over?worker=Worker&period=22%20Okt%202026%20-%2015%20Nov%202026&exclude_slip_id='.$other->id)->assertUnprocessable();
        $this->postJson('/api/payslips', $this->payload(['pending_carry_over_amount' => 999999]))->assertUnprocessable();
    }

    public function test_resolving_paid_duplicates_is_blocked(): void
    {
        $first = $this->slip(['status' => 'sudah']);
        $this->slip();
        $this->postJson('/api/payslips/'.$first->id.'/resolve-duplicates')->assertConflict();
        $this->assertSame(0, Payslip::whereNotNull('superseded_by_id')->count());
    }

    public function test_overview_shows_only_remaining_credit_not_the_carry_already_in_its_slip(): void
    {
        $this->slip(['period' => '22 Sep 2026 - 21 Okt 2026', 'status' => 'sudah', 'total_approved_hours' => 1]);
        $this->recording('2026-10-01 04:00:00');
        $this->slip(['period' => 'Training (18 Sep 2026 - 20 Sep 2026)', 'status' => 'sudah', 'total_approved_hours' => 0.69]);
        StudioRecording::create(['record_id' => 'training-rounding', 'participant_email' => 'worker@example.test',
            'assigned_worker_name' => 'Worker', 'recorded_at' => '2026-09-19 04:00:00', 'billable_min' => 41.68, 'duration_min' => 41.68]);
        $this->slip(['pending_carry_over_amount' => 50000,
            'pending_carry_over_data' => [['source_period' => '22 Sep 2026 - 21 Okt 2026', 'delta_min' => 60]]]);
        $this->recording();
        $this->getJson('/api/payroll/live-summary?periodId=2026-10-22_2026-11-15')->assertOk()
            ->assertJsonPath('workers.0.pending_carry_over_amount', 50000)
            ->assertJsonPath('workers.0.available_pending_balance', 233)
            ->assertJsonPath('workers.0.available_pending_mins', 0.28);
    }

    public function test_pdf_summary_does_not_count_pending_as_rejected(): void
    {
        $slip = $this->slip(['total_logged_hours' => 2, 'evidence_data' => [
            ['date' => '2026-11-01', 'approved_mins' => 60, 'rejected_mins' => 30, 'pending_mins' => 30, 'total_video_mins' => 120],
        ]]);
        $html = view('slips.content', $slip->toArray() + ['forPdf' => true])->render();
        preg_match('/Rejected Work Hours.*?<\/tr>/s', $html, $rejected);
        $this->assertStringContainsString('0.50', $rejected[0]);
        $this->assertStringNotContainsString('1.00', $rejected[0]);
        $this->assertSame(51000, $slip->fresh()->total_thp);
    }

    public function test_pdf_carry_minutes_use_claim_precision_instead_of_rounded_hours(): void
    {
        $slip = $this->slip(['pending_carry_over_hours' => 0.17, 'pending_carry_over_amount' => 8683,
            'total_thp' => 59683, 'pending_carry_over_data' => [
                ['source_period' => 'First source', 'delta_min' => 10.14],
                ['source_period' => 'Second source', 'delta_min' => 0.28],
            ]]);
        $html = view('slips.content', $slip->toArray() + ['forPdf' => true])->render();
        preg_match('/Pencairan Sisa Pending Periode Lalu.*?<\/tr>/s', $html, $carry);
        $this->assertStringContainsString('10.4 mins', strip_tags($carry[0]));
        $this->assertStringNotContainsString('10.2 mins', strip_tags($carry[0]));
        $this->assertSame(59683, $slip->fresh()->total_thp);
    }
}
