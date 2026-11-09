<?php

namespace Tests\Feature;

use App\Models\StudioRecording;
use App\Models\User;
use App\Services\AtlasSync;
use App\Services\Robotics1618Sync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordingSourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sources_survive_repeated_syncs_and_are_returned_in_api(): void
    {
        $atlas = app(AtlasSync::class);
        $record = ['userTaskId' => 'shared-id', 'recordedAt' => '2026-10-24T06:30:00Z', 'durationSeconds' => 60];
        $atlas->ingest($record, 'studio@example.test');
        $row = StudioRecording::findOrFail('shared-id');
        $this->assertSame(['atlas'], $row->data_sources);
        $row->update(['notes' => 'Manual note', 'assigned_worker_name' => 'Budi', 'manual_payment_status' => 'Paid']);

        $file = tempnam(sys_get_temp_dir(), 'source_test_');
        try {
            file_put_contents($file, "recording_id,email,task,created_utc,duration_s\nshared-id,studio@example.test,Task,2026-10-24T06:30:00Z,60\nonly-1618,studio@example.test,Task,2026-10-24T07:00:00Z,60\n");
            (new \ReflectionMethod(Robotics1618Sync::class, 'processCsvStream'))->invoke(app(Robotics1618Sync::class), $file, ['studio@example.test' => 0]);
        } finally { unlink($file); }
        $atlas->ingest($record, 'studio@example.test');
        $row->refresh();
        $this->assertSame(['atlas', '1618'], $row->data_sources);
        $this->assertSame('Manual note', $row->notes);
        $this->assertSame('Paid', $row->manual_payment_status);
        $this->assertSame('Budi', $row->assigned_worker_name);
        $this->assertSame(['1618'], StudioRecording::findOrFail('only-1618')->data_sources);
        $this->assertSame(2, StudioRecording::count());

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson('/api/studio-recordings?search=shared-id')
            ->assertOk()->assertJsonPath('recordings.0.data_sources', ['atlas', '1618']);
    }

    public function test_legacy_records_are_not_assigned_an_invented_source(): void
    {
        $row = StudioRecording::create(['record_id' => 'legacy', 'participant_email' => 'studio@example.test', 'recorded_at' => '2026-10-24 06:30:00']);
        $this->assertSame([], $row->data_sources);
    }
}
