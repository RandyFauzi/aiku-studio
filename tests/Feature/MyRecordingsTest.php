<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Payslip;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MyRecordingsTest extends TestCase
{
    use RefreshDatabase;

    private function worker(string $name = 'Alya', string $email = 'alya@example.test'): User
    {
        return app(ContributorAccess::class)->sync(Contributor::create(['nama' => $name, 'email' => $email]));
    }

    private function recording(string $id, array $attributes = []): void
    {
        StudioRecording::create($attributes + ['record_id' => $id, 'task_title' => 'Wrap a gift', 'recorded_at' => '2026-11-06 04:00:00',
            'participant_email' => 'shared@example.test', 'duration_min' => 30, 'billable_min' => 25, 'notes' => 'Private payroll note', 'manual_payment_status' => 'Paid']);
    }

    public function test_worker_sees_only_owned_recordings_with_no_payroll_fields_and_wita_month_filter(): void
    {
        $worker = $this->worker(); $other = $this->worker('Other', 'other@example.test');
        $this->recording('mine', ['assigned_contributor_id' => $worker->contributor_id, 'assigned_worker_name' => 'Old display name']);
        $this->recording('legacy', ['assigned_worker_name' => 'Alya']);
        $this->recording('november-start', ['assigned_contributor_id' => $worker->contributor_id, 'recorded_at' => '2026-10-31 16:00:00']);
        $this->recording('october', ['assigned_contributor_id' => $worker->contributor_id, 'recorded_at' => '2026-10-31 15:59:59']);
        $this->recording('other', ['assigned_contributor_id' => $other->contributor_id, 'assigned_worker_name' => 'Alya']);
        $this->recording('unassigned');
        $response = $this->actingAs($worker)->getJson('/api/me/recordings?month=2026-11&contributor_id='.$other->contributor_id)->assertOk()
            ->assertJsonPath('summary.total_recordings', 3)->assertJsonPath('summary.total_minutes', 90)->assertJsonCount(3, 'recordings')
            ->assertDontSee('Private payroll note')->assertDontSee('billable_min')->assertDontSee('manual_payment_status')->assertDontSee('participant_email');
        $ids = array_column($response->json('recordings'), 'record_id');
        $this->assertContains('mine', $ids); $this->assertContains('legacy', $ids); $this->assertNotContains('other', $ids);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->getJson('/api/me/recordings?month=2026-11&search=kado&limit=1')->assertOk()->assertJsonPath('pagination.last_page', 3)->assertJsonCount(1, 'recordings');
    }

    public function test_duplicate_names_never_grant_legacy_recording_access(): void
    {
        $worker = $this->worker(); Contributor::create(['nama' => 'Alya', 'email' => 'duplicate@example.test']);
        $this->recording('id-owned', ['assigned_contributor_id' => $worker->contributor_id]);
        $this->recording('ambiguous', ['assigned_worker_name' => 'Alya']);
        $this->actingAs($worker)->getJson('/api/me/recordings?month=2026-11')->assertOk()->assertJsonCount(1, 'recordings')->assertJsonPath('recordings.0.record_id', 'id-owned');
    }

    public function test_only_own_final_past_slip_is_visible_and_current_money_routes_are_closed(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 6)->setTime(4, 0));
        Storage::fake('local'); $worker = $this->worker();
        $past = Payslip::create(['employee_name' => 'Alya', 'period' => '22 Sep 2026 - 21 Okt 2026', 'status' => 'sudah', 'total_thp' => 123456, 'file_path' => '/pdf_slips/past.pdf']);
        $current = Payslip::create(['employee_name' => 'Alya', 'period' => '22 Okt 2026 - 21 Nov 2026', 'status' => 'sudah', 'total_thp' => 987654, 'file_path' => '/pdf_slips/current.pdf']);
        $draft = Payslip::create(['employee_name' => 'Alya', 'period' => '22 Sep 2026 - 21 Okt 2026', 'status' => 'belum', 'total_thp' => 999999]);
        $other = Payslip::create(['employee_name' => 'Other', 'period' => '22 Sep 2026 - 21 Okt 2026', 'status' => 'sudah', 'total_thp' => 888888]);
        Storage::disk('local')->put('payroll/pdf_slips/past.pdf', "%PDF-1.4\n%%EOF");
        Storage::disk('local')->put('payroll/pdf_slips/current.pdf', "%PDF-1.4\n%%EOF");
        Storage::disk('local')->put('payroll/pdf_slips/current.pdf.owner.json', json_encode(['employee_name' => 'Alya', 'contributor_id' => $worker->contributor_id]));
        $this->actingAs($worker)->getJson('/api/me/payslips')->assertOk()->assertJsonCount(1, 'slips')->assertJsonPath('slips.0.id', $past->id)
            ->assertJsonMissingPath('live_slip')->assertDontSee('987654')->assertDontSee('999999');
        $this->get('/slip/'.$past->id)->assertOk();
        $this->get('/pdf_slips/past.pdf')->assertOk();
        $this->getJson('/api/payslips/'.$past->id.'/export')->assertOk()->assertJsonPath('url', '/pdf_slips/past.pdf');
        foreach (['/slip/'.$current->id, '/slip/'.$draft->id, '/slip/'.$other->id, '/slip/live/Alya', '/api/payroll/export-live/Alya',
            '/api/payslips/'.$current->id.'/export', '/api/payslips/'.$draft->id.'/export', '/pdf_slips/current.pdf', '/api/payroll/live-summary'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->actingAs(User::factory()->create())->get('/slip/'.$current->id)->assertOk();
        $this->travelBack();
    }

    public function test_portal_has_recording_history_and_no_live_money_ui_and_requires_identity(): void
    {
        $this->getJson('/api/me/recordings')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/me/recordings')->assertForbidden();
        $worker = $this->worker();
        foreach (['/me', '/me/rekaman', '/me/profil', '/me/slip'] as $url) {
            $this->actingAs($worker)->get($url)->assertOk()->assertDontSee('liveSlip')->assertDontSee('Live Slip')->assertDontSee('total_approved_hours');
        }
        $this->get('/me/rekaman')->assertSee('Riwayat rekaman')->assertSee('Arsip slip lama');
        $this->getJson('/api/me/recordings?month=2026-13')->assertUnprocessable();
    }
}
