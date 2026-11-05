<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $email = 'worker@example.com'): User
    {
        $person = Contributor::create(['nama' => $email, 'email' => $email]);

        return app(ContributorAccess::class)->sync($person);
    }

    public function test_contributor_can_login_with_registered_email_and_default_password(): void
    {
        $user = $this->employee();
        $this->assertTrue(Hash::check('password', $user->password));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/me');
        $this->get('/me')->assertOk()->assertViewIs('portal.home');
        foreach (['/api/slips', '/api/contributors', '/api/attendance?date=2026-11-03', '/api/shifts/days?month=2026-11', '/shifts.html', '/contributors.html'] as $path) {
            $this->getJson($path)->assertForbidden();
        }
        $this->get('/')->assertRedirect('/me');
    }

    public function test_account_updates_preserve_password_and_cannot_take_over_admin_email(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $user = $this->employee();
        $user->password = 'a-custom-password';
        $user->save();
        $this->actingAs($admin)->putJson('/api/contributors/'.$user->contributor_id, ['nama' => 'Updated', 'email' => 'new@example.com'])->assertOk();
        $this->assertTrue(Hash::check('a-custom-password', $user->fresh()->password));
        $this->putJson('/api/contributors/'.$user->contributor_id, ['nama' => 'Updated', 'email' => 'admin@example.com'])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'email' => 'admin@example.com']);
        $this->deleteJson('/api/contributors/'.$user->contributor_id)->assertOk();
        $this->actingAs($user->fresh())->getJson('/api/me/attendance?month=2026-11')->assertUnauthorized();
    }

    public function test_checkin_uses_server_wita_date_and_is_idempotent_without_checkout(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(16, 5));
        $user = $this->employee();
        $this->actingAs($user);
        $payload = ['latitude' => -3.319, 'longitude' => 114.59, 'accuracy_m' => 8, 'captured_at' => now()->toIso8601String(), 'user_id' => 999, 'work_date' => '2000-01-01'];
        $this->postJson('/api/me/attendance', $payload)->assertOk()->assertJsonPath('attendance.work_date', '2026-11-03')->assertJsonPath('attendance.user_id', $user->id);
        $this->postJson('/api/me/attendance', $payload)->assertOk()->assertJsonPath('already_checked_in', true);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertArrayNotHasKey('checked_out_at', Attendance::first()->getAttributes());
        $this->getJson('/api/me/attendance?month=2026-11')->assertOk()->assertJsonCount(1, 'attendances');
        $this->travelBack();
    }

    public function test_invalid_or_stale_gps_does_not_create_attendance_and_records_are_private(): void
    {
        $a = $this->employee('one@example.com');
        $b = $this->employee('two@example.com');
        $this->actingAs($a)->postJson('/api/me/attendance', [])->assertUnprocessable();
        $this->postJson('/api/me/attendance', ['latitude' => 100, 'longitude' => 20, 'accuracy_m' => 5, 'captured_at' => now()->toIso8601String()])->assertUnprocessable();
        $this->postJson('/api/me/attendance', ['latitude' => -3, 'longitude' => 114, 'accuracy_m' => 5, 'captured_at' => now()->subHour()->toIso8601String()])->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
        $this->postJson('/api/me/attendance', ['latitude' => -3, 'longitude' => 114, 'accuracy_m' => 5, 'captured_at' => now()->toIso8601String()])->assertOk();
        $this->actingAs($b)->getJson('/api/me/attendance?month='.now('Asia/Makassar')->format('Y-m'))->assertOk()->assertJsonCount(0, 'attendances');
        $this->actingAs(User::factory()->create())->getJson('/api/attendance?date='.now('Asia/Makassar')->toDateString())->assertOk()->assertJsonCount(1, 'attendances');
    }

    public function test_date_rosters_are_isolated_persistent_and_revision_checked(): void
    {
        $this->actingAs(User::factory()->create());
        $config = config('shifts');
        $config['karyawan'][0]['sessions'] = [2];
        $this->putJson('/api/shifts/days/2026-11-03', ['config' => $config, 'expected_revision' => 0])->assertOk()->assertJsonPath('day.revision', 1);
        $this->getJson('/api/shifts/days?month=2026-11')->assertOk()->assertJsonPath('days.2026-11-03.config.karyawan.0.sessions', [2]);
        $this->getJson('/api/shifts/days?month=2026-12')->assertOk()->assertJsonPath('days', []);
        $this->getJson('/api/shifts/config')->assertJsonPath('config.karyawan.0.sessions', [1, 2]);
        $this->putJson('/api/shifts/days/2026-11-03', ['config' => $config, 'expected_revision' => 0])->assertConflict();
        $this->deleteJson('/api/shifts/days/2026-11-03', ['expected_revision' => 2])->assertConflict();
        $this->deleteJson('/api/shifts/days/2026-11-03', ['expected_revision' => 1])->assertOk();
        $this->assertDatabaseCount('shift_days', 0);
        $this->putJson('/api/shifts/days/2026-02-30', ['config' => $config, 'expected_revision' => 0])->assertUnprocessable();
    }

    public function test_employee_schedule_respects_weekly_off_and_date_override(): void
    {
        $user = $this->employee();
        $config = config('shifts');
        $config['karyawan'] = [['name' => $user->name, 'contributor_id' => $user->contributor_id, 'alat' => 'Alat 1', 'sessions' => [3]]];
        $this->actingAs(User::factory()->create())->postJson('/api/shifts/config', $config)->assertOk();
        $this->putJson('/api/shifts/days/2026-11-02', ['config' => $config, 'expected_revision' => 0])->assertOk();
        $this->actingAs($user)->getJson('/api/me/attendance?month=2026-11')->assertOk()
            ->assertJsonPath('schedule.1.date', '2026-11-02')->assertJsonPath('schedule.1.sessions.0.id', 3)
            ->assertJsonPath('schedule.8.sessions', []);
        $this->assertDatabaseCount('shift_days', 1);
    }
}
