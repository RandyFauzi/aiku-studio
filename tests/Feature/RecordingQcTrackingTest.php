<?php

namespace Tests\Feature;

use App\Models\StudioRecording;
use App\Models\User;
use App\Services\AtlasSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordingQcTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_ingest_does_not_record_qc_delta(): void
    {
        $atlas = app(AtlasSync::class);
        $record = [
            'userTaskId' => 'task-101',
            'recordedAt' => '2026-11-10T08:00:00Z',
            'durationSeconds' => 3600,
            'tileBillableHours' => 0.0,
            'tileRejectedHours' => 0.0,
            'tilePendingHours' => 1.0,
            'processingStatus' => 'processing',
        ];

        $atlas->ingest($record, 'studio@example.test');

        $row = StudioRecording::findOrFail('task-101');
        $this->assertEquals(0.0, $row->billable_min);
        $this->assertEquals(0.0, $row->rejected_min);
        $this->assertNull($row->prev_billable_min);
        $this->assertNull($row->prev_rejected_min);
        $this->assertNull($row->qc_changed_at);
        $this->assertNull($row->qc_delta);
    }

    public function test_subsequent_ingest_with_qc_change_tracks_up_and_down(): void
    {
        $atlas = app(AtlasSync::class);
        $record = [
            'userTaskId' => 'task-102',
            'recordedAt' => '2026-11-10T08:00:00Z',
            'durationSeconds' => 3600,
            'tileBillableHours' => 0.5, // 30 min billable
            'tileRejectedHours' => 0.0,
            'tilePendingHours' => 0.5,
            'processingStatus' => 'processing',
        ];

        // 1st ingest
        $atlas->ingest($record, 'studio@example.test');

        // 2nd ingest: Review approved naik jadi 50 min billable, 10 min reject
        $record['tileBillableHours'] = 0.8333; // ~50 min
        $record['tileRejectedHours'] = 0.1667; // ~10 min
        $record['tilePendingHours'] = 0.0;
        $record['processingStatus'] = 'processed';

        $atlas->ingest($record, 'studio@example.test');

        $row = StudioRecording::findOrFail('task-102');
        $this->assertNotNull($row->qc_changed_at);
        $this->assertEquals(30.0, $row->prev_billable_min);
        $this->assertEquals(0.0, $row->prev_rejected_min);

        $delta = $row->qc_delta;
        $this->assertNotNull($delta);
        $this->assertEquals(20.0, round($delta['billable_diff'], 1));
        $this->assertSame('up', $delta['billable_direction']);
        $this->assertEquals(10.0, round($delta['rejected_diff'], 1));
        $this->assertSame('up', $delta['rejected_direction']);
        $this->assertEquals(30.0, $delta['prev_billable']);
        $this->assertEquals(0.0, $delta['prev_rejected']);
        $this->assertNotNull($delta['changed_at_formatted']);
    }

    public function test_api_filters_by_qc_changed_and_returns_qc_delta_payload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Record with change
        $changed = StudioRecording::create([
            'record_id' => 'rec-changed',
            'participant_email' => 'studio@example.test',
            'recorded_at' => now(),
            'duration_min' => 60,
            'billable_min' => 50,
            'prev_billable_min' => 20,
            'rejected_min' => 10,
            'prev_rejected_min' => 0,
            'qc_changed_at' => now(),
        ]);

        // Record without change
        StudioRecording::create([
            'record_id' => 'rec-steady',
            'participant_email' => 'studio@example.test',
            'recorded_at' => now(),
            'duration_min' => 60,
            'billable_min' => 60,
            'rejected_min' => 0,
            'qc_changed_at' => null,
        ]);

        // Test filter qc_changed
        $response = $this->actingAs($admin)->getJson('/api/studio-recordings?status=qc_changed');
        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('recordings.0.record_id', 'rec-changed')
            ->assertJsonPath('recordings.0.qc_delta.billable_direction', 'up')
            ->assertJsonPath('recordings.0.qc_delta.billable_diff', 30)
            ->assertJsonPath('recordings.0.qc_delta.rejected_direction', 'up')
            ->assertJsonPath('recordings.0.qc_delta.rejected_diff', 10);
    }
}
