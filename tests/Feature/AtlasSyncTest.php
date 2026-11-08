<?php
namespace Tests\Feature;

use App\Models\StudioAccount;
use App\Models\StudioRecording;
use App\Services\AtlasSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AtlasSyncTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $records, int $total): string
    {
        return '<script>self.__next_f.push('.json_encode([1, '0:'.json_encode(['recordings' => $records, 'total' => $total])."\n"]).')</script>';
    }

    public function test_old_settled_page_does_not_hide_later_records(): void
    {
        config(['payroll.atlas_cookie' => 'test=1', 'payroll.atlas_url' => 'https://atlas.test']);
        StudioAccount::create(['name' => 'Studio', 'email' => 'studio@example.test', 'active' => true, 'participant_id' => 'id']);
        $record = ['userTaskId' => 'old', 'recordedAt' => '2020-01-01T00:00:00Z', 'durationSeconds' => 60];
        Http::fakeSequence()->push($this->page([$record], 26))->push($this->page([array_merge($record, ['userTaskId' => 'later'])], 26));
        $sync = app(AtlasSync::class);
        $sync->request();
        $sync->tick();
        $this->assertTrue($sync->state()['isRunning']);
        $sync->tick();
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'later']);
        $this->assertSame('completed', $sync->state()['stage']);
    }

    public function test_missing_participant_is_reported_as_incomplete(): void
    {
        config(['payroll.atlas_cookie' => 'test=1']);
        StudioAccount::create(['name' => 'Studio', 'email' => 'missing@example.test', 'active' => true]);
        Http::fake(['*' => Http::response('<html></html>')]);
        $sync = app(AtlasSync::class);
        $sync->request(); $sync->tick();
        $this->assertSame('failed', $sync->state()['stage']);
        $this->assertStringContainsString('missing@example.test', $sync->state()['error']);
    }

    public function test_invalid_record_does_not_abort_valid_records(): void
    {
        config(['payroll.atlas_cookie' => 'test=1']);
        StudioAccount::create(['name' => 'Studio', 'email' => 'studio@example.test', 'active' => true, 'participant_id' => 'id']);
        Http::fake(['*' => Http::response($this->page([['userTaskId' => 'broken'], ['userTaskId' => 'valid', 'recordedAt' => '2026-11-01', 'durationSeconds' => 60]], 2))]);
        $sync = app(AtlasSync::class); $sync->request(); $sync->tick();
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'valid']);
        $this->assertSame(1, $sync->state()['skippedCount']);
        $this->assertSame('failed', $sync->state()['stage']);
    }
    public function test_inactive_registered_account_is_included_but_foreign_email_is_not_saved(): void
    {
        config(['payroll.atlas_cookie' => 'test=1']);
        StudioAccount::create(['name' => 'Historical', 'email' => 'studio@example.test', 'active' => false, 'participant_id' => 'id']);
        $base = ['recordedAt' => '2026-10-24T06:30:00Z', 'durationSeconds' => 60];
        Http::fake(['*' => Http::response($this->page([
            array_merge($base, ['userTaskId' => 'foreign', 'participantEmail' => 'foreign@example.test']),
            array_merge($base, ['userTaskId' => 'ours', 'participantEmail' => 'studio@example.test']),
        ], 2))]);
        $sync = app(AtlasSync::class); $sync->request(); $sync->tick();
        $this->assertSame(1, $sync->state()['totalAccounts']);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'ours']);
        $this->assertDatabaseMissing('studio_recordings', ['record_id' => 'foreign']);
        $this->assertSame('failed', $sync->state()['stage']);
    }

    public function test_unknown_participants_share_one_discovery_request(): void
    {
        config(['payroll.atlas_cookie' => 'test=1']);
        foreach (['one', 'two'] as $name) StudioAccount::create(['name' => $name, 'email' => $name.'@example.test', 'active' => true]);
        Http::fake(['*' => Http::response('<html></html>')]);
        $sync = app(AtlasSync::class); $sync->request(); $sync->tick(); $sync->tick();
        Http::assertSentCount(1);
        $this->assertSame('failed', $sync->state()['stage']);
    }
}
