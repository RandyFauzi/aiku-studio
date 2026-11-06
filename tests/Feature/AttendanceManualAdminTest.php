<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManualAdminTest extends TestCase
{
    use RefreshDatabase;

    private function contributorUser(string $name = 'Aufa Anugraha', string $email = 'aufa@example.com'): array
    {
        $person = Contributor::create([
            'nama' => $name,
            'email' => $email,
            'shift' => 'Tim 2 - Hitam',
        ]);
        $user = app(ContributorAccess::class)->sync($person);

        return ['contributor' => $person, 'user' => $user];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'name' => 'Randy Fauzi',
            'email' => 'admin@aiku.test',
        ]);
    }

    public function test_non_admin_cannot_create_manual_attendance(): void
    {
        ['user' => $worker] = $this->contributorUser();

        $res = $this->actingAs($worker)->postJson('/api/attendance/manual', [
            'contributor_id' => 1,
            'work_date' => '2026-11-06',
        ]);

        $res->assertForbidden();
    }

    public function test_admin_can_record_manual_attendance(): void
    {
        $admin = $this->adminUser();
        ['contributor' => $person, 'user' => $worker] = $this->contributorUser();

        $res = $this->actingAs($admin)->postJson('/api/attendance/manual', [
            'contributor_id' => $person->id,
            'work_date' => '2026-11-06',
            'time' => '08:35:00',
            'notes' => 'Kendala scan QR barcode di HP',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'message' => "Absensi manual untuk {$person->nama} berhasil disimpan.",
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'contributor_id' => $person->id,
            'contributor_name' => $person->nama,
            'work_date' => '2026-11-06',
            'checkin_method' => 'manual',
            'wifi_verified' => 1,
        ]);

        $attendance = Attendance::where('user_id', $worker->id)->where('work_date', '2026-11-06')->first();
        $this->assertNotNull($attendance);
        $this->assertStringContainsString('Manual Admin', $attendance->client_ip);
        $this->assertStringContainsString('Kendala scan QR barcode di HP', $attendance->client_ip);
    }

    public function test_admin_cannot_duplicate_manual_attendance_for_same_work_date(): void
    {
        $admin = $this->adminUser();
        ['contributor' => $person, 'user' => $worker] = $this->contributorUser();

        // First check-in
        $this->actingAs($admin)->postJson('/api/attendance/manual', [
            'contributor_id' => $person->id,
            'work_date' => '2026-11-06',
            'time' => '08:30:00',
        ])->assertOk();

        // Second check-in on the same date should fail validation
        $secondRes = $this->actingAs($admin)->postJson('/api/attendance/manual', [
            'contributor_id' => $person->id,
            'work_date' => '2026-11-06',
            'time' => '09:00:00',
        ]);

        $secondRes->assertStatus(422)
            ->assertJsonValidationErrors(['contributor_id']);

        $this->assertEquals(1, Attendance::where('user_id', $worker->id)->where('work_date', '2026-11-06')->count());
    }

    public function test_admin_can_delete_attendance(): void
    {
        $admin = $this->adminUser();
        ['contributor' => $person, 'user' => $worker] = $this->contributorUser();

        $attendance = Attendance::create([
            'user_id' => $worker->id,
            'contributor_id' => $person->id,
            'contributor_name' => $person->nama,
            'work_date' => '2026-11-06',
            'checked_in_at' => CarbonImmutable::now(),
            'checkin_method' => 'manual',
            'client_ip' => 'Manual Admin',
            'wifi_verified' => true,
        ]);

        $res = $this->actingAs($admin)->deleteJson("/api/attendance/{$attendance->id}");

        $res->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }
}
