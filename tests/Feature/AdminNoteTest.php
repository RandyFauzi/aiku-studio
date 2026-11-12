<?php

namespace Tests\Feature;

use App\Models\AdminNote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_contributor_cannot_access_admin_notes(): void
    {
        $contributor = User::factory()->create(['role' => 'contributor']);

        $this->actingAs($contributor)
            ->getJson('/api/admin-notes')
            ->assertForbidden();

        $this->actingAs($contributor)
            ->postJson('/api/admin-notes', ['title' => 'Test'])
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get('/notes')
            ->assertForbidden();
    }

    public function test_admin_can_create_admin_note_and_todo(): void
    {
        $admin1 = User::factory()->create(['role' => 'admin', 'name' => 'Admin One']);
        $admin2 = User::factory()->create(['role' => 'admin', 'name' => 'Admin Two']);

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

        $response = $this->actingAs($admin1)->postJson('/api/admin-notes', [
            'title' => 'Cek HP Putih & Sesi',
            'content' => 'Ada sesi terhenti di jam 11 siang.',
            'type' => 'todo',
            'priority' => 'urgent',
            'note_date' => $today,
            'assigned_to_user_id' => $admin2->id,
            'is_pinned' => true,
            'checklist_items' => [
                ['text' => 'Buka HP Putih', 'completed' => false],
                ['text' => 'Hubungkan WiFi', 'completed' => false],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Cek HP Putih & Sesi')
            ->assertJsonPath('type', 'todo')
            ->assertJsonPath('priority', 'urgent')
            ->assertJsonPath('is_pinned', true)
            ->assertJsonPath('user.name', 'Admin One')
            ->assertJsonPath('assigned_to.name', 'Admin Two');

        $this->assertDatabaseHas('admin_notes', [
            'title' => 'Cek HP Putih & Sesi',
            'user_id' => $admin1->id,
            'assigned_to_user_id' => $admin2->id,
            'is_completed' => false,
        ]);
    }

    public function test_admin_can_create_note_with_empty_assigned_to(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin-notes', [
            'title' => 'Catatan Umum untuk Semua Admin',
            'type' => 'note',
            'priority' => 'normal',
            'assigned_to_user_id' => '',
        ]);

        $response->assertCreated()
            ->assertJsonPath('assigned_to_user_id', null);

        $this->assertDatabaseHas('admin_notes', [
            'title' => 'Catatan Umum untuk Semua Admin',
            'assigned_to_user_id' => null,
        ]);
    }

    public function test_admin_can_list_notes_with_date_and_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        $yesterday = CarbonImmutable::now('Asia/Makassar')->subDay()->toDateString();

        AdminNote::create([
            'user_id' => $admin->id,
            'title' => 'Catatan Hari Ini',
            'type' => 'note',
            'priority' => 'normal',
            'note_date' => $today,
            'is_completed' => false,
        ]);

        AdminNote::create([
            'user_id' => $admin->id,
            'title' => 'To-Do Urgent Hari Ini',
            'type' => 'todo',
            'priority' => 'urgent',
            'note_date' => $today,
            'is_completed' => false,
        ]);

        AdminNote::create([
            'user_id' => $admin->id,
            'title' => 'Catatan Kemarin',
            'type' => 'note',
            'priority' => 'normal',
            'note_date' => $yesterday,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin-notes?date='.$today);
        $response->assertOk()
            ->assertJsonCount(2, 'notes')
            ->assertJsonPath('stats.totalNotes', 2)
            ->assertJsonPath('stats.pendingTodos', 1)
            ->assertJsonPath('stats.urgentCount', 1);

        // All dates query
        $responseAll = $this->actingAs($admin)->getJson('/api/admin-notes?all_dates=1');
        $responseAll->assertOk()->assertJsonCount(3, 'notes');
    }

    public function test_admin_can_toggle_todo_completion(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Selesaikan']);
        $note = AdminNote::create([
            'user_id' => $admin->id,
            'title' => 'Tugas Shift Pagi',
            'type' => 'todo',
            'priority' => 'normal',
            'note_date' => CarbonImmutable::now('Asia/Makassar')->toDateString(),
            'is_completed' => false,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/admin-notes/{$note->id}/toggle");
        $response->assertOk()
            ->assertJsonPath('is_completed', true)
            ->assertJsonPath('completed_by.name', 'Admin Selesaikan');

        $this->assertTrue($note->fresh()->is_completed);
        $this->assertNotNull($note->fresh()->completed_at);
        $this->assertSame($admin->id, $note->fresh()->completed_by_user_id);

        // Toggle back
        $responseBack = $this->actingAs($admin)->patchJson("/api/admin-notes/{$note->id}/toggle");
        $responseBack->assertOk()->assertJsonPath('is_completed', false);
        $this->assertFalse($note->fresh()->is_completed);
        $this->assertNull($note->fresh()->completed_at);
    }

    public function test_admin_can_toggle_pin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $note = AdminNote::create([
            'user_id' => $admin->id,
            'title' => 'Penting Banget',
            'type' => 'note',
            'priority' => 'high',
            'note_date' => CarbonImmutable::now('Asia/Makassar')->toDateString(),
            'is_pinned' => false,
        ]);

        $this->actingAs($admin)->patchJson("/api/admin-notes/{$note->id}/pin")
            ->assertOk()
            ->assertJsonPath('is_pinned', true);

        $this->assertTrue($note->fresh()->is_pinned);
    }

    public function test_admin_can_update_and_delete_note(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $note = AdminNote::create([
            'user_id' => $admin->id,
            'title' => 'Judul Lama',
            'type' => 'note',
            'priority' => 'normal',
            'note_date' => CarbonImmutable::now('Asia/Makassar')->toDateString(),
        ]);

        $this->actingAs($admin)->putJson("/api/admin-notes/{$note->id}", [
            'title' => 'Judul Baru Diperbarui',
            'type' => 'delegation',
            'priority' => 'high',
            'note_date' => $note->note_date->format('Y-m-d'),
        ])->assertOk()->assertJsonPath('title', 'Judul Baru Diperbarui');

        $this->assertSame('Judul Baru Diperbarui', $note->fresh()->title);

        $this->actingAs($admin)->deleteJson("/api/admin-notes/{$note->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('admin_notes', ['id' => $note->id]);
    }
}
