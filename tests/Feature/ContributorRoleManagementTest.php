<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Models\ShiftDay;
use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $email = 'admin@example.com'): User
    {
        return User::factory()->create(['email' => $email, 'role' => 'admin']);
    }

    private function createContributor(string $name = 'Budi', string $email = 'budi@example.com', string $role = 'contributor'): Contributor
    {
        $person = Contributor::create([
            'nama' => $name,
            'email' => $email,
            'role' => $role,
        ]);
        app(ContributorAccess::class)->sync($person, $role);

        return $person;
    }

    public function test_only_admin_can_access_contributor_crud_and_non_admin_is_forbidden(): void
    {
        $contributor = $this->createContributor('Ani', 'ani@example.com', 'contributor');
        $contributorUser = User::where('contributor_id', $contributor->id)->first();

        // Contributor user attempts to access /api/contributors -> 403 Forbidden
        $this->actingAs($contributorUser)
            ->getJson('/api/contributors')
            ->assertForbidden();

        $this->actingAs($contributorUser)
            ->postJson('/api/contributors', ['nama' => 'Test', 'email' => 'test@example.com'])
            ->assertForbidden();

        $this->actingAs($contributorUser)
            ->putJson('/api/contributors/'.$contributor->id, ['nama' => 'Hacked', 'role' => 'admin'])
            ->assertForbidden();

        $this->actingAs($contributorUser)
            ->patchJson('/api/contributors/'.$contributor->id.'/role', ['role' => 'admin'])
            ->assertForbidden();

        $this->actingAs($contributorUser)
            ->deleteJson('/api/contributors/'.$contributor->id)
            ->assertForbidden();

        // Admin user can access
        $admin = $this->createAdmin();
        $this->actingAs($admin)
            ->getJson('/api/contributors')
            ->assertOk();
    }

    public function test_admin_can_create_contributor_as_admin_or_pekerja(): void
    {
        $admin = $this->createAdmin();

        // Create as Pekerja (Contributor)
        $resWorker = $this->actingAs($admin)->postJson('/api/contributors', [
            'nama' => 'Pekerja Satu',
            'email' => 'worker1@example.com',
            'role' => 'contributor',
        ])->assertSuccessful();

        $this->assertDatabaseHas('contributors', ['email' => 'worker1@example.com', 'role' => 'contributor']);
        $this->assertDatabaseHas('users', ['email' => 'worker1@example.com', 'role' => 'contributor']);

        // Create as Admin
        $resAdmin = $this->actingAs($admin)->postJson('/api/contributors', [
            'nama' => 'Admin Dua',
            'email' => 'admin2@example.com',
            'role' => 'admin',
        ])->assertSuccessful();

        $this->assertDatabaseHas('contributors', ['email' => 'admin2@example.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'admin2@example.com', 'role' => 'admin']);
    }

    public function test_admin_can_change_role_via_put_and_patch(): void
    {
        $admin = $this->createAdmin();
        $contributor = $this->createContributor('Dedi', 'dedi@example.com', 'contributor');

        $this->assertDatabaseHas('contributors', ['id' => $contributor->id, 'role' => 'contributor']);
        $this->assertDatabaseHas('users', ['contributor_id' => $contributor->id, 'role' => 'contributor']);

        // Update to Admin via PATCH /api/contributors/{id}/role
        $this->actingAs($admin)
            ->patchJson('/api/contributors/'.$contributor->id.'/role', ['role' => 'admin'])
            ->assertOk()
            ->assertJson(['success' => true, 'role' => 'admin']);

        $this->assertDatabaseHas('contributors', ['id' => $contributor->id, 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['contributor_id' => $contributor->id, 'role' => 'admin']);

        // Switch back to Contributor via PUT /api/contributors/{id}
        $this->actingAs($admin)
            ->putJson('/api/contributors/'.$contributor->id, [
                'nama' => 'Dedi Updated',
                'email' => 'dedi@example.com',
                'role' => 'contributor',
            ])
            ->assertOk();

        $this->assertDatabaseHas('contributors', ['id' => $contributor->id, 'role' => 'contributor']);
        $this->assertDatabaseHas('users', ['contributor_id' => $contributor->id, 'role' => 'contributor']);
    }

    public function test_admin_cannot_demote_themselves_to_contributor(): void
    {
        $adminContributor = Contributor::create([
            'nama' => 'Admin Person',
            'email' => 'admin_person@example.com',
            'role' => 'admin',
        ]);
        $adminUser = app(ContributorAccess::class)->sync($adminContributor, 'admin');

        // Admin attempts to change their own role to contributor -> unprocessable
        $this->actingAs($adminUser)
            ->patchJson('/api/contributors/'.$adminContributor->id.'/role', ['role' => 'contributor'])
            ->assertUnprocessable();

        $this->actingAs($adminUser)
            ->putJson('/api/contributors/'.$adminContributor->id, [
                'nama' => 'Admin Person',
                'email' => 'admin_person@example.com',
                'role' => 'contributor',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $adminUser->id, 'role' => 'admin']);
        $this->assertDatabaseHas('contributors', ['id' => $adminContributor->id, 'role' => 'admin']);
    }

    public function test_promoted_admin_gets_panel_access_and_demoted_worker_redirects_to_portal(): void
    {
        $admin = $this->createAdmin();
        $person = $this->createContributor('Eko', 'eko@example.com', 'contributor');
        $user = User::where('contributor_id', $person->id)->first();

        // While contributor, / redirects to /me
        $this->actingAs($user)->get('/')->assertRedirect('/me');
        $this->actingAs($user)->get('/recordings.html')->assertForbidden();

        // Admin promotes Eko to admin
        $this->actingAs($admin)
            ->patchJson('/api/contributors/'.$person->id.'/role', ['role' => 'admin'])
            ->assertOk();

        $freshUser = $user->fresh();
        $this->assertEquals('admin', $freshUser->role);

        // Now Eko can access the admin panel
        $this->actingAs($freshUser)->get('/')->assertOk();
        $this->actingAs($freshUser)->get('/recordings.html')->assertOk();
    }

    public function test_admin_can_view_contributor_performance_and_studio_summary(): void
    {
        $admin = $this->createAdmin();
        $person = $this->createContributor('Ghina', 'ghina@example.com', 'contributor');

        // Studio summary
        $summaryRes = $this->actingAs($admin)->getJson('/api/contributors/studio-summary');
        $summaryRes->assertOk()
            ->assertJsonStructure([
                'studio' => ['total_contributors', 'total_hours', 'studio_approval_rate', 'grade_distribution'],
                'contributors_summary',
            ]);

        // Contributor performance detail
        $perfRes = $this->actingAs($admin)->getJson('/api/contributors/'.$person->id.'/performance');
        $perfRes->assertOk()
            ->assertJsonStructure([
                'contributor' => ['id', 'nama', 'email', 'role'],
                'recordings' => ['total_count', 'total_duration_hours', 'approval_rate', 'reject_rate'],
                'attendance' => ['verified_attendances', 'leave_count', 'sick_count'],
                'evaluation' => ['score', 'grade', 'badge_color', 'summary', 'reasons', 'recommendations'],
            ]);

        // Non-admin cannot access
        $user = User::where('contributor_id', $person->id)->first();
        $this->actingAs($user)->getJson('/api/contributors/'.$person->id.'/performance')->assertForbidden();
        $this->actingAs($user)->getJson('/api/contributors/studio-summary')->assertForbidden();
    }

    public function test_admin_can_update_contributor_name_and_cascades_to_all_relations(): void
    {
        $admin = $this->createAdmin();
        $contributor = $this->createContributor('Seira', 'seira@example.com', 'contributor');
        $user = User::where('contributor_id', $contributor->id)->firstOrFail();

        // 1. Studio Recordings (one with assigned_worker_name only, one with assigned_contributor_id)
        StudioRecording::create([
            'record_id' => 'rec-test-1',
            'participant_email' => 'seira@test.com',
            'assigned_worker_name' => 'Seira',
            'assigned_contributor_id' => null,
            'duration_min' => 60,
        ]);
        StudioRecording::create([
            'record_id' => 'rec-test-2',
            'participant_email' => 'seira@test.com',
            'assigned_worker_name' => 'Seira',
            'assigned_contributor_id' => $contributor->id,
            'duration_min' => 60,
        ]);

        // 2. Payslip
        Payslip::create([
            'employee_name' => 'Seira',
            'period' => '22 Oktober 2026 - 21 November 2026',
            'total_thp' => 1500000,
            'status' => 'belum',
        ]);

        // 3. Payroll Status
        PayrollStatus::create([
            'worker_name' => 'Seira',
            'period' => '22 Oktober 2026 - 21 November 2026',
            'status' => 'belum',
        ]);

        // 4. Daily Work Report
        DailyWorkReport::create([
            'user_id' => $user->id,
            'contributor_id' => $contributor->id,
            'contributor_name' => 'Seira',
            'work_date' => '2026-11-10',
            'total_duration_minutes' => 120,
            'phones' => [],
        ]);

        // 5. Attendance
        Attendance::create([
            'user_id' => $user->id,
            'contributor_id' => $contributor->id,
            'contributor_name' => 'Seira',
            'work_date' => '2026-11-10',
            'checked_in_at' => now(),
            'latitude' => -3.44,
            'longitude' => 114.85,
            'accuracy_m' => 10,
            'gps_captured_at' => now(),
        ]);

        // 6. Shift Day
        ShiftDay::create([
            'work_date' => '2026-11-10',
            'config' => [
                'karyawan' => [
                    ['name' => 'Seira', 'contributor_id' => $contributor->id, 'alat' => 'Alat 1', 'sessions' => [1]],
                ],
            ],
            'revision' => 1,
        ]);

        // 7. Shift Setting
        ShiftSetting::create([
            'setting_key' => 'main_shift_config',
            'setting_value' => [
                'karyawan' => [
                    ['name' => 'Seira', 'contributor_id' => $contributor->id, 'alat' => 'Alat 1', 'sessions' => [1]],
                ],
            ],
        ]);
        ShiftSetting::create([
            'setting_key' => 'shift_period_current',
            'setting_value' => [
                'config' => [
                    'karyawan' => [
                        ['name' => 'Seira', 'contributor_id' => $contributor->id, 'alat' => 'Alat 1', 'sessions' => [1]],
                    ],
                ],
                'revision' => 1,
            ],
        ]);

        // Admin updates name from 'Seira' to 'Siera'
        $response = $this->actingAs($admin)->putJson('/api/contributors/'.$contributor->id, [
            'nama' => 'Siera',
            'email' => 'seira@example.com',
            'role' => 'contributor',
            'no_rek' => '1234567890',
        ]);

        $response->assertOk();
        $this->assertEquals('Siera', $response->json('nama'));

        // Assert Contributor table
        $this->assertDatabaseHas('contributors', ['id' => $contributor->id, 'nama' => 'Siera', 'no_rek' => '1234567890']);
        $this->assertDatabaseMissing('contributors', ['nama' => 'Seira']);

        // Assert Users table
        $this->assertDatabaseHas('users', ['contributor_id' => $contributor->id, 'name' => 'Siera']);

        // Assert Studio Recordings
        $this->assertEquals(2, StudioRecording::where('assigned_worker_name', 'Siera')->where('assigned_contributor_id', $contributor->id)->count());
        $this->assertEquals(0, StudioRecording::where('assigned_worker_name', 'Seira')->count());

        // Assert Payslips
        $this->assertDatabaseHas('payslips', ['employee_name' => 'Siera']);
        $this->assertDatabaseMissing('payslips', ['employee_name' => 'Seira']);

        // Assert Payroll Status
        $this->assertDatabaseHas('payroll_statuses', ['worker_name' => 'Siera']);
        $this->assertDatabaseMissing('payroll_statuses', ['worker_name' => 'Seira']);

        // Assert Daily Work Reports
        $this->assertDatabaseHas('daily_work_reports', ['contributor_id' => $contributor->id, 'contributor_name' => 'Siera']);
        $this->assertDatabaseMissing('daily_work_reports', ['contributor_name' => 'Seira']);

        // Assert Attendances
        $this->assertDatabaseHas('attendances', ['contributor_id' => $contributor->id, 'contributor_name' => 'Siera']);
        $this->assertDatabaseMissing('attendances', ['contributor_name' => 'Seira']);

        // Assert ShiftDay
        $shiftDay = ShiftDay::where('work_date', '2026-11-10')->first();
        $this->assertEquals('Siera', $shiftDay->config['karyawan'][0]['name']);

        // Assert ShiftSetting
        $mainConfig = ShiftSetting::where('setting_key', 'main_shift_config')->first();
        $this->assertEquals('Siera', $mainConfig->setting_value['karyawan'][0]['name']);

        $periodConfig = ShiftSetting::where('setting_key', 'shift_period_current')->first();
        $this->assertEquals('Siera', $periodConfig->setting_value['config']['karyawan'][0]['name']);
    }

    public function test_admin_cannot_rename_contributor_to_an_existing_contributor_name(): void
    {
        $admin = $this->createAdmin();
        $contributorA = $this->createContributor('Alice', 'alice@example.com', 'contributor');
        $contributorB = $this->createContributor('Bob', 'bob@example.com', 'contributor');

        $this->actingAs($admin)->putJson('/api/contributors/'.$contributorB->id, [
            'nama' => 'Alice',
            'email' => 'bob@example.com',
            'role' => 'contributor',
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['nama']);
    }
}
