<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorPayslipTest extends TestCase
{
    use RefreshDatabase;

    private function contributorUser(string $name, string $email): User
    {
        $contributor = Contributor::create([
            'nama' => $name,
            'email' => $email,
            'role' => 'contributor',
        ]);

        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'contributor',
            'contributor_id' => $contributor->id,
            'is_active' => true,
        ]);
    }

    public function test_contributor_can_fetch_own_payslips_list(): void
    {
        $user = $this->contributorUser('Budi Santoso', 'budi@example.com');
        $otherUser = $this->contributorUser('Siti Aminah', 'siti@example.com');

        // Payslips for Budi
        $slipBudi = Payslip::create([
            'employee_name' => 'Budi Santoso',
            'period' => '22 Sep 2026 - 21 Okt 2026',
            'total_logged_hours' => 40.0,
            'total_approved_hours' => 38.0,
            'total_thp' => 1900000,
            'bonus' => 0,
            'status' => 'sudah',
        ]);

        // Payslips for Siti
        Payslip::create([
            'employee_name' => 'Siti Aminah',
            'period' => '22 Sep 2026 - 21 Okt 2026',
            'total_logged_hours' => 50.0,
            'total_approved_hours' => 45.0,
            'total_thp' => 2250000,
            'bonus' => 0,
            'status' => 'belum',
        ]);

        $response = $this->actingAs($user)->getJson('/api/me/payslips');

        $response->assertOk()
            ->assertJsonCount(1, 'slips')
            ->assertJsonPath('slips.0.id', $slipBudi->id)
            ->assertJsonPath('slips.0.employee_name', 'Budi Santoso');
    }

    public function test_contributor_can_view_own_slip_web_page_but_forbidden_on_others(): void
    {
        $user = $this->contributorUser('Budi Santoso', 'budi@example.com');

        $ownSlip = Payslip::create([
            'employee_name' => 'Budi Santoso',
            'period' => '22 Sep 2026 - 21 Okt 2026',
            'total_logged_hours' => 30.0,
            'total_approved_hours' => 25.0,
            'total_thp' => 1250000,
            'bonus' => 0,
            'status' => 'sudah',
            'evidence_data' => [],
        ]);

        $otherSlip = Payslip::create([
            'employee_name' => 'Other Worker',
            'period' => '22 Sep 2026 - 21 Okt 2026',
            'total_logged_hours' => 20.0,
            'total_approved_hours' => 20.0,
            'total_thp' => 1000000,
            'bonus' => 0,
            'status' => 'belum',
            'evidence_data' => [],
        ]);

        // Accessing own slip -> OK
        $this->actingAs($user)->get('/slip/'.$ownSlip->id)
            ->assertOk()
            ->assertSee('Budi Santoso');

        // Accessing other worker's slip -> 403 Forbidden
        $this->actingAs($user)->get('/slip/'.$otherSlip->id)
            ->assertForbidden();
    }

    public function test_contributor_cannot_view_any_live_slip_even_their_own(): void
    {
        $user = $this->contributorUser('Budi Santoso', 'budi@example.com');

        // Current amounts are no longer visible to workers.
        $this->actingAs($user)->get('/slip/live/Budi Santoso')
            ->assertForbidden();

        // Other worker live slip -> 403 Forbidden
        $this->actingAs($user)->get('/slip/live/Other Worker')
            ->assertForbidden();
    }
}
