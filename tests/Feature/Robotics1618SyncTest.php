<?php

namespace Tests\Feature;

use App\Models\DeviceAccount;
use App\Models\StudioAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\Robotics1618Sync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Robotics1618SyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'email' => 'admin@aiku.test',
            'role' => 'admin',
        ]);
        $this->worker = User::factory()->create([
            'email' => 'worker@aiku.test',
            'role' => 'contributor',
        ]);
    }

    public function test_get_allowed_studio_emails_combines_device_and_active_studio_accounts(): void
    {
        DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'aikuputih2@kamerakitaid.site',
            'is_active' => true,
        ]);

        StudioAccount::create([
            'email' => 'aikupink2@kamerakitaid.site',
            'name' => 'Akun Pink',
            'active' => true,
        ]);

        // Inactive account should not be included from StudioAccount
        StudioAccount::create([
            'email' => 'inactive@kamerakitaid.site',
            'name' => 'Inactive',
            'active' => false,
        ]);

        $service = app(Robotics1618Sync::class);
        $emails = $service->getAllowedStudioEmails();

        $this->assertContains('aikuputih2@kamerakitaid.site', $emails);
        $this->assertContains('aikupink2@kamerakitaid.site', $emails);
        $this->assertNotContains('inactive@kamerakitaid.site', $emails);
    }

    public function test_csv_stream_only_ingests_allowed_studio_emails(): void
    {
        DeviceAccount::create([
            'phone_key' => 'putih',
            'email' => 'aikuputih2@kamerakitaid.site',
            'is_active' => true,
        ]);

        $service = app(Robotics1618Sync::class);
        $allowedLookup = array_flip($service->getAllowedStudioEmails());

        // Create a temporary mock CSV with 3 rows: 1 matching studio email, 2 non-matching external emails
        $csvContent = implode("\n", [
            'task,email,created_utc,submitted_utc,duration_s,status,billable,billable_h,rejected_h,pending_h,deferred_h,reviewed_utc,reconciliation,rejection_reasons,pay_period_start,recording_id',
            'Sew a button,external_person1@example.com,2026-11-01T17:29:07.135Z,,1800,ready_for_review,yes,0.5000,0.0000,0.0000,0.0000,2026-11-01T20:52:40.25Z,ok,,2026-11-01,ext_rec_001',
            'Cooking task,aikuputih2@kamerakitaid.site,2026-11-02T10:00:00.000Z,,3600,accepted,yes,1.0000,0.0000,0.0000,0.0000,2026-11-02T12:00:00.00Z,ok,,2026-11-02,studio_rec_123',
            'Iron clothes,external_person2@example.com,2026-11-03T11:00:00.000Z,,2400,ready_for_review,no,0.0000,0.6666,0.0000,0.0000,,,2026-11-03,ext_rec_002',
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_1618_csv_');
        file_put_contents($tempFile, $csvContent);

        // Call private processCsvStream via reflection or wrapper
        $refMethod = new \ReflectionMethod(Robotics1618Sync::class, 'processCsvStream');
        $stats = $refMethod->invoke($service, $tempFile, $allowedLookup);

        @unlink($tempFile);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['matched']);

        // Check database: studio_rec_123 MUST exist
        $this->assertDatabaseHas('studio_recordings', [
            'record_id' => 'studio_rec_123',
            'participant_email' => 'aikuputih2@kamerakitaid.site',
            'task_title' => 'Cooking task',
            'duration_sec' => 3600.0,
            'duration_min' => 60.0,
            'billable_min' => 60.0,
            'billable_status' => 'yes',
        ]);

        // Check database: external records MUST NOT exist
        $this->assertDatabaseMissing('studio_recordings', [
            'record_id' => 'ext_rec_001',
        ]);
        $this->assertDatabaseMissing('studio_recordings', [
            'record_id' => 'ext_rec_002',
        ]);
    }

    public function test_sync_1618_api_requires_admin_authorization(): void
    {
        $this->actingAs($this->worker)
            ->postJson('/api/studio-recordings/sync-1618')
            ->assertForbidden();

        $this->actingAs($this->worker)
            ->getJson('/api/studio-recordings/1618-status')
            ->assertForbidden();

        // Admin can access status
        $this->actingAs($this->admin)
            ->getJson('/api/studio-recordings/1618-status')
            ->assertOk()
            ->assertJsonStructure(['isRunning', 'state']);
    }
}
