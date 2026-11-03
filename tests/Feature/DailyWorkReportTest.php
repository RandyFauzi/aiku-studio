<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyWorkReportTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $email = 'reporter@example.com'): User
    {
        return app(ContributorAccess::class)->sync(Contributor::create(['nama' => $email, 'email' => $email]));
    }

    private function payload(): array
    {
        return ['work_date' => now('Asia/Makassar')->toDateString(), 'total_duration_minutes' => 150, 'phones' => ['putih', 'pink_st'], 'expected_revision' => 0];
    }

    public function test_employee_submits_and_updates_own_daily_duration_and_multiple_phones(): void
    {
        $user = $this->employee();
        $this->actingAs($user);
        $data = $this->payload() + ['user_id' => 999, 'contributor_id' => 999, 'contributor_name' => 'Forged name'];
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.user_id', $user->id)
            ->assertJsonPath('report.contributor_name', $user->name)->assertJsonPath('report.phones', ['putih', 'pink_st'])->assertJsonPath('report.revision', 1);
        $data['total_duration_minutes'] = 240;
        $data['expected_revision'] = 1;
        $data['phones'] = ['biru', 'hijau', 'hitam', 'pink'];
        $this->putJson('/api/me/work-report', $data)->assertOk()->assertJsonPath('report.total_duration_minutes', 240)->assertJsonPath('report.revision', 2);
        $this->assertDatabaseCount('daily_work_reports', 1);
        $this->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertOk()->assertJsonPath('today_report.revision', 2)->assertJsonCount(1, 'reports');
    }

    public function test_validation_rejects_empty_duration_invalid_or_duplicate_phone_and_wrong_date(): void
    {
        $this->actingAs($this->employee());
        foreach ([['total_duration_minutes' => 0], ['total_duration_minutes' => -5], ['total_duration_minutes' => 8641], ['phones' => []], ['phones' => ['ungu']], ['phones' => ['pink', 'pink']], ['work_date' => now('Asia/Makassar')->subDay()->toDateString()]] as $change) {
            $this->putJson('/api/me/work-report', array_replace($this->payload(), $change))->assertUnprocessable();
        }
        $this->assertDatabaseCount('daily_work_reports', 0);
    }

    public function test_reports_are_private_and_admin_gets_recap_for_selected_date(): void
    {
        $a = $this->employee('first@example.com');
        $b = $this->employee('second@example.com');
        $this->actingAs($a)->putJson('/api/me/work-report', $this->payload())->assertOk();
        $this->actingAs($b)->getJson('/api/me/work-reports?month='.now('Asia/Makassar')->format('Y-m'))->assertJsonCount(0, 'reports')->assertJsonPath('today_report', null);
        $this->getJson('/api/work-reports?date='.now('Asia/Makassar')->toDateString())->assertForbidden();
        $this->get('/work-reports.html')->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/api/work-reports?date='.now('Asia/Makassar')->toDateString())->assertOk()->assertJsonCount(1, 'reports')->assertJsonPath('total_duration_minutes', 150);
        $this->putJson('/api/me/work-report', $this->payload())->assertForbidden();
    }

    public function test_date_is_wita_and_stale_revision_does_not_overwrite_report(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(16, 0));
        $this->actingAs($this->employee());
        $this->putJson('/api/me/work-report', $this->payload())->assertOk()->assertJsonPath('report.work_date', '2026-11-04');
        $this->putJson('/api/me/work-report', array_replace($this->payload(), ['total_duration_minutes' => 700]))->assertConflict();
        $this->assertDatabaseHas('daily_work_reports', ['total_duration_minutes' => 150, 'revision' => 1]);
        $this->travelBack();
    }

    public function test_guest_cannot_submit_or_read_reports(): void
    {
        $this->putJson('/api/me/work-report', $this->payload())->assertUnauthorized();
        $this->getJson('/api/me/work-reports?month=2026-11')->assertUnauthorized();
        $this->getJson('/api/work-reports?date=2026-11-03')->assertUnauthorized();
    }
}
