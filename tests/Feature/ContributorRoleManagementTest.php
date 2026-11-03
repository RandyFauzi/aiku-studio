<?php

namespace Tests\Feature;

use App\Models\Contributor;
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
}
