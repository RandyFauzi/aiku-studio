<?php
namespace Tests\Feature;

use App\Models\StudioAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\AtlasSession;
use App\Services\AtlasSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AtlasSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::preventStrayRequests();
        config(['payroll.atlas_url' => 'https://atlas.test', 'payroll.atlas_cookie' => 'expired=1',
            'payroll.atlas_username' => 'studio@example.test', 'payroll.atlas_password' => 'private-test-password']);
    }

    private function page(array $rows = [], int $total = 0): string
    {
        return '<script>self.__next_f.push('.json_encode([1, '0:'.json_encode(['recordings' => $rows, 'total' => $total])."\n"]).')</script>';
    }

    private function form(): string
    {
        return '<form><input type="hidden" name="$ACTION_REF_1" value=""><input type="hidden" name="$ACTION_1:0" value="{&quot;id&quot;:&quot;dynamic&quot;}"><input name="email"><input name="password" type="password"></form>';
    }

    public function test_expiry_mid_sync_reauthenticates_and_retries_same_page(): void
    {
        $pageTwo = 0; $logins = 0;
        $row = ['userTaskId' => 'first', 'recordedAt' => '2026-10-24T06:30:00Z', 'durationSeconds' => 60];
        Http::fake(function ($request) use (&$pageTwo, &$logins, $row) {
            if (str_ends_with($request->url(), '/login')) {
                if ($request->method() === 'GET') return Http::response($this->form());
                $logins++;
                return Http::response('', 303, ['Location' => '/recordings', 'Set-Cookie' => 'session=fresh; Path=/; HttpOnly; Secure']);
            }
            if (str_contains($request->url(), 'page=2')) {
                if (++$pageTwo === 1) return Http::response('', 307, ['Location' => '/login']);
                return Http::response($this->page([array_merge($row, ['userTaskId' => 'second'])], 26));
            }
            if (str_contains($request->url(), 'page=1')) return Http::response($this->page([$row], 26));
            return Http::response($this->page());
        });
        StudioAccount::create(['name' => 'Studio', 'email' => 'studio@example.test', 'participant_id' => 'id', 'active' => true]);
        $sync = app(AtlasSync::class); $sync->request(); $sync->tick(); $sync->tick();
        $this->assertSame('completed', $sync->state()['stage'], $sync->state()['message']);
        $this->assertSame(2, StudioRecording::count());
        $this->assertSame(1, $logins);
        $this->assertSame(2, $pageTwo);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r->hasFile('password', 'private-test-password') && $r->hasFile('$ACTION_1:0', '{"id":"dynamic"}'));
        $this->assertStringContainsString('session=fresh', $sync->cookie());
        $this->assertStringNotContainsString('private-test-password', Storage::disk('local')->get(AtlasSession::SESSION_PATH));
    }

    public function test_rejected_login_is_bounded_and_does_not_expose_secrets(): void
    {
        Http::fake(fn ($r) => $r->url() === 'https://atlas.test/login' && $r->method() === 'GET'
            ? Http::response($this->form()) : Http::response('private-test-password response must not leak', 401));
        $session = app(AtlasSession::class);
        for ($i = 0; $i < 2; $i++) {
            try { $session->refresh('expired=1'); $this->fail('Expected login failure'); }
            catch (\RuntimeException $e) {
                $this->assertStringNotContainsString('private-test-password', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }
        Http::assertSentCount(2);
        Storage::disk('local')->assertMissing(AtlasSession::SESSION_PATH);
    }

    public function test_encrypted_credentials_and_new_private_session_take_precedence(): void
    {
        config(['payroll.atlas_username' => '', 'payroll.atlas_password' => '']);
        Storage::disk('local')->put(AtlasSession::CREDENTIALS_PATH, Crypt::encryptString(json_encode(['email' => 'studio@example.test', 'password' => 'secret'])));
        $this->assertTrue(app(AtlasSession::class)->canLogin());
        Storage::disk('local')->put(AtlasSession::SESSION_PATH, json_encode(['cookies' => [['name' => 'session', 'value' => 'new', 'domain' => 'atlas.test', 'expires' => -1]]]));
        $this->assertSame('session=new', app(AtlasSession::class)->refresh('expired=1'));
        Http::assertNothingSent();
    }

    public function test_missing_session_can_queue_and_login_before_fetching(): void
    {
        config(['payroll.atlas_cookie' => '']);
        Http::fakeSequence()->push($this->form())->push('', 303, ['Set-Cookie' => 'session=renewed; Path=/; Secure'])
            ->push($this->page())->push($this->page());
        StudioAccount::create(['name' => 'Studio', 'email' => 'studio@example.test', 'participant_id' => 'id', 'active' => true]);
        $sync = app(AtlasSync::class); $sync->request(); $sync->tick();
        $this->assertSame('completed', $sync->state()['stage'], $sync->state()['message']);
        Http::assertSentCount(4);
    }

    public function test_rejected_replay_does_not_loop_logins(): void
    {
        Http::fakeSequence()->push('', 307, ['Location' => '/login'])->push($this->form())
            ->push('', 303, ['Set-Cookie' => 'session=fresh; Path=/; Secure'])->push($this->page())->push('', 403);
        try {
            (new \ReflectionMethod(AtlasSync::class, 'fetch'))->invoke(app(AtlasSync::class), '/recordings?page=2');
            $this->fail('Expected denied response');
        } catch (\RuntimeException $e) { $this->assertStringContainsString('403', $e->getMessage()); }
        Http::assertSentCount(5);
    }

    public function test_latest_update_api_has_explicit_utc_offset(): void
    {
        StudioRecording::create(['record_id' => 'time', 'participant_email' => 'studio@example.test', 'recorded_at' => '2026-11-11 01:00:00', 'last_synced_at' => '2026-11-11 04:41:59']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson('/api/studio-recordings/sync-status')
            ->assertOk()->assertJsonPath('lastSyncedAt', '2026-11-11T04:41:59+00:00');
        $this->getJson('/api/studio-recordings')->assertOk()->assertJsonPath('lastSyncedAt', '2026-11-11T04:41:59+00:00');
    }
}
