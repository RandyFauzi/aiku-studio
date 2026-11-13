<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Payslip;
use App\Models\StudioAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\AtlasParser;
use App\Services\AtlasSync;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayrollMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_panel_and_private_data_require_authentication(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->getJson('/api/slips')->assertUnauthorized();
        $this->getJson('/api/studio-recordings')->assertUnauthorized();
        $this->get('/pdf_slips/private.pdf')->assertRedirect('/login');
        $this->postJson('/api/system/shutdown')->assertNotFound();
    }

    public function test_all_migrated_pages_render_after_login(): void
    {
        $this->login();
        foreach (['/', '/index.html', '/contributors.html', '/create.html', '/payslips.html', '/profit.html', '/recordings.html', '/shifts.html'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('http://localhost:3000')->assertDontSee('Exit CMD')->assertSee('csrf-token');
        }
    }

    public function test_cutoff_handles_wita_midnight_and_year_rollover(): void
    {
        $resolver = app(PayrollPeriod::class);
        $before = $resolver->cutoff(CarbonImmutable::parse('2026-12-15T15:59:59Z'));
        $after = $resolver->cutoff(CarbonImmutable::parse('2026-12-15T16:00:00Z'));
        $this->assertSame('2026-11-16_2026-12-15', $before['id']);
        $this->assertSame('2026-12-16_2027-01-15', $after['id']);
        $this->assertSame('2026-12-15 16:00:00', $after['startUtc']);
        $this->assertSame('2027-01-15 15:59:59', $after['endUtc']);
        $this->assertSame('2026-12-16_2027-01-15', $resolver->resolve(['period' => '16 Des 2026 - 15 Jan 2027'])['id']);

        // Historical cycle preservation
        $this->assertSame('2026-09-22_2026-10-21', $resolver->resolve(['period' => '22 Sep 2026 - 21 Okt 2026'])['id']);
        // Transition cycle preservation
        $this->assertSame('2026-10-22_2026-11-15', $resolver->resolve(['period' => '22 Okt 2026 - 15 Nov 2026'])['id']);
    }

    public function test_payroll_calculation_uses_task_minutes_before_rounding_money(): void
    {
        $result = app(PayrollCalculator::class)->calculate([['date' => '2026-10-01', 'tasks' => [
            ['approved_mins' => 60, 'rejected_mins' => 5, 'pending_mins' => 10],
            ['approved_mins' => 1.25, 'rejected_mins' => 0, 'pending_mins' => 0, 'total_video_mins' => 2],
        ]]], 12500);
        $this->assertSame(63542, $result['total_thp']);
        $this->assertEqualsWithDelta(77 / 60, $result['total_logged_hours'], 0.000001);
        $this->assertSame(61.25, $result['evidence_data'][0]['approved_mins']);
    }

    public function test_live_summary_does_not_leak_a_bonus_or_status_from_another_period(): void
    {
        $this->login();
        Payslip::create(['employee_name' => 'Worker', 'period' => '22 Agu 2026 - 21 Sep 2026', 'bonus' => 90000, 'status' => 'sudah']);
        StudioRecording::create(['record_id' => 'a', 'participant_email' => 'a@example.com', 'assigned_worker_name' => 'Worker',
            'recorded_at' => '2026-09-21 16:00:00', 'duration_min' => 90, 'billable_min' => 60, 'pending_min' => 30]);
        StudioRecording::create(['record_id' => 'outside', 'participant_email' => 'a@example.com', 'assigned_worker_name' => 'Worker',
            'recorded_at' => '2026-09-21 15:59:59', 'duration_min' => 90, 'billable_min' => 90]);
        $this->getJson('/api/payroll/live-summary?periodId=2026-09-22_2026-10-21')->assertOk()
            ->assertJsonPath('workers.0.thp', 50000)->assertJsonPath('workers.0.bonus', 0)
            ->assertJsonPath('workers.0.status', 'belum')->assertJsonPath('workers.0.total_tasks', 1)
            ->assertJsonPath('workers.0.daily_breakdown.0.date', '2026-09-22');
        $live = app(PayrollCalculator::class)->liveSlip(app(PayrollPeriod::class)->resolve(['periodId' => '2026-09-22_2026-10-21']), 'Worker');
        $this->assertSame(0, $live['bonus']);
        $this->assertSame('belum', $live['status']);
    }

    public function test_contributor_crud_and_validation(): void
    {
        $this->login();
        $this->postJson('/api/contributors', ['nama' => '', 'email' => 'invalid'])->assertUnprocessable();
        $id = $this->postJson('/api/contributors', ['nama' => 'Person', 'email' => 'person@example.com'])->assertSuccessful()->json('id');
        $this->putJson('/api/contributors/'.$id, ['nama' => 'Updated'])->assertOk()->assertJsonPath('nama', 'Updated');
        $this->deleteJson('/api/contributors/'.$id)->assertOk();
        $this->assertDatabaseMissing('contributors', ['id' => $id]);
    }

    public function test_slip_save_edit_and_payment_status_synchronize(): void
    {
        $this->login();
        $payload = ['employee_name' => 'Worker', 'period' => 'Training (18 Sep 2026 - 20 Sep 2026)', 'bonus' => 1000,
            'evidence' => [['date' => '2026-09-18', 'approved_mins' => 60, 'rejected_mins' => 5, 'notes' => 'Keep me']]];
        $id = $this->postJson('/api/payslips', $payload)->assertOk()->json('data.id');
        $this->getJson('/api/payslips/'.$id)->assertOk()->assertJsonPath('total_thp', 51000)->assertJsonPath('evidence_data.0.notes', 'Keep me');
        $payload['bonus'] = 2000;
        $this->putJson('/api/payslips/'.$id, $payload)->assertOk();
        $this->patchJson('/api/payslips/'.$id.'/status', ['status' => 'sudah'])->assertOk();
        $this->assertDatabaseHas('payroll_statuses', ['worker_name' => 'Worker', 'period' => $payload['period'], 'status' => 'sudah']);
        $this->postJson('/api/payroll/status', ['worker_name' => 'Worker', 'period' => $payload['period'], 'status' => 'belum'])->assertOk();
        $this->assertDatabaseHas('payslips', ['id' => $id, 'status' => 'belum', 'total_thp' => 52000]);
        $this->deleteJson('/api/payslips/'.$id)->assertOk();
    }

    public function test_recording_filter_stats_and_assignments(): void
    {
        $this->login();
        $worker = Contributor::create(['nama' => 'Worker']);
        StudioRecording::create(['record_id' => 'one', 'participant_email' => 'a@example.com', 'recorded_at' => '2026-09-21 16:00:00', 'duration_min' => 60, 'processing_status' => 'processing']);
        StudioRecording::create(['record_id' => 'two', 'participant_email' => 'b@example.com', 'recorded_at' => '2026-09-21 15:59:59', 'duration_min' => 90]);
        $this->getJson('/api/studio-recordings?startDate=2026-09-22&endDate=2026-09-22&limit=1')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('stats.pendingMinutes', 60)->assertJsonPath('recordings.0.record_id', 'one');
        $this->putJson('/api/studio-recordings/batch-assign', ['record_ids' => ['one'], 'worker_name' => 'Worker', 'contributor_id' => $worker->id, 'payment_status' => 'Paid'])->assertOk()->assertJsonPath('updated', 1);
        $this->getJson('/api/studio-recordings?worker=Worker')->assertOk()->assertJsonPath('stats.paidCount', 1);
        $this->getJson('/api/studio-recordings?worker=unassigned&search=two')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/studio-recordings?limit=-1')->assertUnprocessable();
    }

    public function test_uploads_are_private_and_validate_file_types(): void
    {
        Storage::fake('local');
        $this->login();
        $url = $this->postJson('/api/upload-evidence', ['worker_name' => '../Worker', 'date' => '2026-10-01',
            'images' => [UploadedFile::fake()->image('proof.png')]])->assertOk()->json('urls.0');
        $this->get($url)->assertOk();
        $this->postJson('/api/upload-evidence', ['images' => [UploadedFile::fake()->create('script.php', 1, 'application/x-php')]])->assertUnprocessable();
    }

    public function test_atlas_parser_reads_the_existing_next_flight_format(): void
    {
        $payload = 'a:'.json_encode(['props' => ['recordings' => [['userTaskId' => 'x']], 'total' => 1]])."\n";
        $html = '<script>self.__next_f.push('.json_encode([1, $payload]).')</script>';
        $this->assertSame('x', app(AtlasParser::class)->recordings($html)['recordings'][0]['userTaskId']);
    }

    public function test_atlas_ingestion_preserves_all_manual_fields(): void
    {
        StudioRecording::create(['record_id' => 'existing', 'participant_email' => 'a@example.com', 'assigned_worker_name' => 'Worker', 'manual_payment_status' => 'Paid', 'notes' => 'Do not change']);
        $sync = app(AtlasSync::class);
        $sync->ingest(['userTaskId' => 'existing', 'recordedAt' => '2026-10-01T00:00:00Z', 'durationSeconds' => 3600, 'tileBillableHours' => 0.5, 'tilePendingHours' => 0.5], 'a@example.com');
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'existing', 'assigned_worker_name' => 'Worker', 'manual_payment_status' => 'Paid', 'notes' => 'Do not change', 'billable_min' => 30, 'pending_min' => 30]);
    }

    public function test_cron_sync_runs_without_a_browser_or_process_spawn(): void
    {
        config(['payroll.atlas_cookie' => 'test=value']);
        StudioAccount::create(['email' => 'a@example.com', 'name' => 'A', 'participant_id' => '11111111-1111-1111-1111-111111111111']);
        $payload = '0:'.json_encode(['recordings' => [['userTaskId' => 'new', 'recordedAt' => '2026-10-01T00:00:00Z', 'durationSeconds' => 60]], 'total' => 1])."\n";
        Http::fake(['*' => Http::response('<script>self.__next_f.push('.json_encode([1, $payload]).')</script>')]);
        app(AtlasSync::class)->request();
        app(AtlasSync::class)->tick();
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'new', 'duration_min' => 1]);
        $this->assertFalse(app(AtlasSync::class)->state()['isRunning']);
    }

    public function test_accounts_finance_and_shift_settings_persist(): void
    {
        $this->login();
        $this->postJson('/api/studio-accounts', ['raw_text' => 'newaccount;newaccount'])->assertOk()->assertJsonPath('addedCount', 1);
        $this->putJson('/api/studio-accounts/newaccount@kamerakitaid.site/toggle')->assertOk()->assertJsonPath('active', false);
        $this->postJson('/api/finance/settings', ['rateMode' => 'USD', 'clientRateUsd' => 5, 'kursUsd' => 16000, 'clientRateIdr' => 80000, 'operationalExpense' => 100])->assertOk();
        $this->getJson('/api/finance/settings')->assertOk()->assertJsonPath('settings.operationalExpense', 100);
        $config = config('shifts');
        $this->postJson('/api/shifts/config', $config)->assertOk();
        $this->getJson('/api/shifts/config')->assertOk()->assertJsonPath('config.start_time', '08:00');
    }

    public function test_pdf_export_is_a_real_pdf_and_escapes_worker_content(): void
    {
        Storage::fake('local');
        $this->login();
        $slip = Payslip::create(['employee_name' => '<script>alert(1)</script>', 'period' => 'Training', 'total_thp' => 50000, 'total_approved_hours' => 1,
            'evidence_data' => [['date' => '2026-09-18', 'approved_mins' => 60]]]);
        $this->get('/slip/'.$slip->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $result = $this->getJson('/api/payslips/'.$slip->id.'/export')->assertOk()->assertJsonPath('success', true)->json();
        $content = Storage::disk('local')->get('payroll'.$result['url']);
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(10000, strlen($content));
        $this->postJson('/api/payslips/'.$slip->id.'/email', ['email' => 'test@example.com'])->assertUnprocessable();
    }

    public function test_invalid_period_returns_validation_error(): void
    {
        $this->login();
        $this->getJson('/api/payroll/live-summary?periodId=invalid')->assertUnprocessable();
        $this->getJson('/api/payroll/live-summary?periodId=2026-02-30_2026-03-01')->assertUnprocessable();
    }
}
