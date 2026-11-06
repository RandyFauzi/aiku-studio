<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTasksTest extends TestCase
{
    use RefreshDatabase;

    private function worker(): User
    {
        return app(ContributorAccess::class)->sync(Contributor::create(['nama' => 'Alya', 'email' => 'alya@example.test']));
    }

    private function payload(): array
    {
        return ['work_date' => now('Asia/Makassar')->toDateString(), 'phones' => ['putih', 'sendiri'],
            'phone_durations' => ['putih' => 60, 'sendiri' => 30], 'expected_revision' => 0,
            'phone_tasks' => ['putih' => ['Wrap a gift', 'Break down cardboard'], 'sendiri' => ['New task']]];
    }

    public function test_worker_saves_normalized_tasks_per_phone_and_they_survive_reload(): void
    {
        $payload = $this->payload(); $payload['phone_tasks']['putih'] = ['  Wrap   a gift ', 'wrap a gift', 'Break down cardboard'];
        $this->actingAs($this->worker())->putJson('/api/me/work-report', $payload)->assertOk()
            ->assertJsonPath('report.phone_tasks.putih', ['Wrap a gift', 'Break down cardboard'])
            ->assertJsonPath('report.phone_tasks.sendiri', ['New task']);
        $this->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertOk()
            ->assertJsonPath('today_report.phone_tasks.sendiri.0', 'New task');
        $this->assertDatabaseCount('report_task_options', 3);
        $this->assertDatabaseCount('device_accounts', 0);
    }

    public function test_empty_missing_foreign_phone_and_excessive_task_input_are_rejected(): void
    {
        $this->actingAs($this->worker());
        $payload = $this->payload(); unset($payload['phone_tasks']['sendiri']);
        $this->putJson('/api/me/work-report', $payload)->assertUnprocessable()->assertJsonValidationErrors('phone_tasks.sendiri');
        $payload = $this->payload(); $payload['phone_tasks']['pink'] = ['Wrong phone'];
        $this->putJson('/api/me/work-report', $payload)->assertUnprocessable();
        $payload = $this->payload(); $payload['phone_tasks']['putih'] = ['   '];
        $this->putJson('/api/me/work-report', $payload)->assertUnprocessable();
        $payload = $this->payload(); $payload['phone_tasks']['putih'] = array_fill(0, 31, 'Task');
        $this->putJson('/api/me/work-report', $payload)->assertUnprocessable();
        $payload = $this->payload(); $payload['phone_tasks']['putih'] = [str_repeat('a', 201)];
        $this->putJson('/api/me/work-report', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('daily_work_reports', 0);
        $this->assertDatabaseCount('report_task_options', 0);
    }

    public function test_catalog_combines_recent_atlas_titles_and_new_saved_tasks_without_exposing_recordings(): void
    {
        $this->actingAs($this->worker())->putJson('/api/me/work-report', $this->payload())->assertOk();
        StudioRecording::create(['record_id' => 'atlas', 'participant_email' => 'private@example.test', 'task_title' => 'wrap a gift', 'recorded_at' => now()]);
        $response = $this->getJson('/api/report-tasks?search=gift')->assertOk()->assertJsonCount(1, 'tasks')
            ->assertJsonPath('tasks.0.source', 'atlas')->assertDontSee('private@example.test');
        $this->getJson('/api/report-tasks?search=New')->assertOk()->assertJsonPath('tasks.0.source', 'report');
        StudioRecording::create(['record_id' => 'later', 'participant_email' => 'private@example.test', 'task_title' => 'New task', 'recorded_at' => now()]);
        $this->getJson('/api/report-tasks?search=New')->assertOk()->assertJsonCount(1, 'tasks')->assertJsonPath('tasks.0.source', 'atlas');
    }

    public function test_legacy_update_preserves_tasks_but_removes_unselected_phone_tasks_and_stale_save_does_not_pollute_catalog(): void
    {
        $this->actingAs($this->worker())->putJson('/api/me/work-report', $this->payload())->assertOk();
        $payload = $this->payload(); $payload['phone_tasks']['putih'] = ['Do not save'];
        $this->putJson('/api/me/work-report', $payload)->assertConflict();
        $this->assertDatabaseMissing('report_task_options', ['title' => 'Do not save']);
        $payload['expected_revision'] = 1; $payload['phones'] = ['putih']; $payload['phone_durations'] = ['putih' => 60]; unset($payload['phone_tasks']);
        $this->putJson('/api/me/work-report', $payload)->assertOk()->assertJsonPath('report.phone_tasks.putih.0', 'Wrap a gift');
        $this->assertArrayNotHasKey('sendiri', DailyWorkReport::first()->phone_tasks);
    }

    public function test_admin_can_create_edit_and_read_tasks_in_matching_and_confirm_audit(): void
    {
        $worker = $this->worker(); $admin = User::factory()->create(); $payload = $this->payload();
        $payload['contributor_id'] = $worker->contributor_id; $payload['phone_emails'] = ['putih' => 'shared@example.test'];
        $result = $this->actingAs($admin)->postJson('/api/work-reports', $payload)->assertOk();
        $id = $result->json('report.id');
        $payload['phone_tasks']['putih'] = ['Changed task'];
        $this->putJson('/api/work-reports/'.$id, $payload)->assertOk()->assertJsonPath('report.phone_tasks.putih', ['Changed task']);
        $date = $payload['work_date'];
        $this->getJson('/api/recording-matching?recording_date='.$date.'&report_date='.$date)->assertOk()->assertJsonPath('reports.0.phones.0.tasks', ['Changed task']);
        StudioRecording::create(['record_id' => 'claimed', 'participant_email' => 'shared@example.test', 'task_title' => 'Changed task', 'recorded_at' => now()]);
        $this->postJson('/api/recording-matching/confirm', ['report_id' => $id, 'expected_revision' => 2, 'phone_key' => 'putih', 'record_ids' => ['claimed'], 'reason' => 'Email dan task diperiksa sesuai laporan.'])->assertOk();
        $evidence = json_decode(DB::table('recording_owner_confirmations')->first()->evidence, true);
        $this->assertSame(['Changed task'], $evidence['reported_tasks']);
    }

    public function test_guest_cannot_read_catalog_and_catalog_search_is_bounded(): void
    {
        $this->getJson('/api/report-tasks')->assertUnauthorized();
        $this->actingAs($this->worker())->getJson('/api/report-tasks?search='.str_repeat('x', 101))->assertUnprocessable();
    }

    public function test_indonesian_labels_synonyms_multiple_keywords_and_minor_typos_find_english_recordings(): void
    {
        $this->actingAs($this->worker());
        foreach (['Wrap a gift', 'Break down cardboard', 'Empty and reorganize the bookshelf', 'Pack a bag for the day'] as $index => $title) {
            StudioRecording::create(['record_id' => 'language-'.$index, 'participant_email' => 'shared@example.test', 'task_title' => $title, 'recorded_at' => now()]);
        }
        foreach (['bungkus hadiah', 'kado bungkus', 'bungk'] as $search) {
            $this->getJson('/api/report-tasks?search='.urlencode($search))->assertOk()
                ->assertJsonPath('tasks.0.title', 'Wrap a gift')->assertJsonPath('tasks.0.label', 'Membungkus kado');
        }
        $this->getJson('/api/report-tasks?search=kardos%20bongkar')->assertOk()->assertJsonPath('tasks.0.title', 'Break down cardboard');
        $this->getJson('/api/report-tasks?search=rak%20buku%20rapikan')->assertOk()->assertJsonPath('tasks.0.title', 'Empty and reorganize the bookshelf');
        $payload = $this->payload(); $payload['phone_tasks']['putih'] = ['Wrap a gift'];
        $this->putJson('/api/me/work-report', $payload)->assertOk()->assertJsonPath('report.phone_tasks.putih.0', 'Wrap a gift');
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'language-0', 'task_title' => 'Wrap a gift']);
    }

    public function test_admin_can_set_translation_and_extra_search_words_without_rewriting_report_identity(): void
    {
        $worker = $this->worker();
        StudioRecording::create(['record_id' => 'unknown', 'participant_email' => 'shared@example.test', 'task_title' => 'Special studio operation', 'recorded_at' => now()]);
        $data = ['title' => 'Special studio operation', 'label' => 'Menata perlengkapan studio', 'keywords' => 'kamera, properti, beberes'];
        $this->actingAs($worker)->putJson('/api/report-tasks/label', $data)->assertForbidden();
        $this->actingAs(User::factory()->create())->putJson('/api/report-tasks/label', $data)->assertOk();
        $this->actingAs($worker)->getJson('/api/report-tasks?search=properti%20kamera')->assertOk()
            ->assertJsonPath('tasks.0.title', 'Special studio operation')->assertJsonPath('tasks.0.label', 'Menata perlengkapan studio')->assertJsonPath('can_edit_labels', false);
        $this->getJson('/api/report-tasks?selected%5B0%5D=Special%20studio%20operation')->assertOk()
            ->assertJsonPath('selected_labels.Special studio operation', 'Menata perlengkapan studio');
        $this->assertDatabaseCount('report_task_translations', 1);
        $this->assertDatabaseHas('studio_recordings', ['record_id' => 'unknown', 'task_title' => 'Special studio operation']);
    }

    public function test_atlas_checklist_still_loads_without_optional_catalog_and_translation_tables(): void
    {
        StudioRecording::create(['record_id' => 'fallback', 'participant_email' => 'private@example.test', 'task_title' => 'Wrap a gift', 'recorded_at' => now()]);
        \Illuminate\Support\Facades\Schema::drop('report_task_options');
        \Illuminate\Support\Facades\Schema::drop('report_task_translations');
        $this->actingAs($this->worker())->getJson('/api/report-tasks?search=kado')->assertOk()
            ->assertJsonPath('tasks.0.title', 'Wrap a gift')->assertJsonPath('tasks.0.label', 'Membungkus kado');
    }
}
