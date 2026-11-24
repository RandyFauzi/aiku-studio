<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\AikuServer;
use App\Mcp\Tools\GetContextTool;
use App\Mcp\Tools\GetDataAuditSummaryTool;
use App\Mcp\Tools\GetMyPayslipTool;
use App\Mcp\Tools\ListAttendancesTool;
use App\Mcp\Tools\ListContributorsTool;
use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\McpConnection;
use App\Models\McpToolCall;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AikuMcpServerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = new User([
            'name' => 'Admin AIKu',
            'email' => 'admin@aiku.test',
            'password' => Hash::make('secret123'),
        ]);
        $user->role = 'admin';
        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function makeContributor(string $name, string $email): array
    {
        $contributor = Contributor::create([
            'nama' => $name,
            'email' => $email,
            'no_wa' => '081234567890',
            'no_rek' => '123456789012',
            'role' => 'contributor',
        ]);

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
        $user->role = 'contributor';
        $user->contributor_id = $contributor->id;
        $user->is_active = true;
        $user->save();

        return [$user, $contributor];
    }

    public function test_unauthenticated_mcp_request_returns_401(): void
    {
        $response = $this->postJson('/mcp/aiku', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(401)
            ->assertHeader('WWW-Authenticate');
    }

    public function test_admin_sees_all_22_tools_and_contributor_only_sees_permitted_tools(): void
    {
        $admin = $this->makeAdmin();
        [$contributor] = $this->makeContributor('Budi Santoso', 'budi@aiku.test');

        $adminIssued = McpConnection::issueToken($admin, 'Admin CLI', 'antigravity');
        $contribIssued = McpConnection::issueToken($contributor, 'Contrib CLI', 'claude_code');

        $adminList = $this->withToken($adminIssued['plain_token'])
            ->postJson('/mcp/aiku', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
            ]);

        $adminList->assertOk();
        $adminTools = collect($adminList->json('result.tools'))->pluck('name')->all();
        $this->assertCount(22, $adminTools);
        $this->assertContains('aiku_get_data_audit_summary', $adminTools);
        $this->assertContains('aiku_calculate_live_payroll', $adminTools);

        $contribList = $this->withToken($contribIssued['plain_token'])
            ->postJson('/mcp/aiku', [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ]);

        $contribList->assertOk();
        $contribTools = collect($contribList->json('result.tools'))->pluck('name')->all();
        $this->assertContains('aiku_get_context', $contribTools);
        $this->assertContains('aiku_get_my_payslip', $contribTools);
        $this->assertNotContains('aiku_get_data_audit_summary', $contribTools);
        $this->assertNotContains('aiku_calculate_live_payroll', $contribTools);
        $this->assertNotContains('aiku_list_contributors', $contribTools);
    }

    public function test_contributor_cannot_call_admin_only_tool_and_audit_is_recorded(): void
    {
        [$contributor] = $this->makeContributor('Siti Aminah', 'siti@aiku.test');
        $issued = McpConnection::issueToken($contributor, 'Siti CLI', 'custom');

        $response = $this->withToken($issued['plain_token'])
            ->postJson('/mcp/aiku', [
                'jsonrpc' => '2.0',
                'id' => 10,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'aiku_get_data_audit_summary',
                    'arguments' => [],
                ],
            ]);

        $response->assertStatus(400);
        $this->assertNotNull($response->headers->get('X-Trace-Id'));
        $this->assertDatabaseHas('mcp_tool_calls', [
            'user_id' => $contributor->id,
            'method' => 'tools/call',
            'tool_name' => 'aiku_get_data_audit_summary',
        ]);
    }

    public function test_attendance_tool_redacts_raw_gps_and_isolates_contributor_data(): void
    {
        $admin = $this->makeAdmin();
        [$userA, $contribA] = $this->makeContributor('Andi', 'andi@aiku.test');
        [$userB, $contribB] = $this->makeContributor('Dewi', 'dewi@aiku.test');

        Attendance::create([
            'user_id' => $userA->id,
            'contributor_id' => $contribA->id,
            'contributor_name' => $contribA->nama,
            'work_date' => now('Asia/Makassar')->toDateString(),
            'checked_in_at' => now('Asia/Makassar'),
            'latitude' => -8.650000,
            'longitude' => 115.216667,
            'accuracy_m' => 12.5,
            'checkin_method' => 'gps',
            'wifi_verified' => false,
        ]);

        Attendance::create([
            'user_id' => $userB->id,
            'contributor_id' => $contribB->id,
            'contributor_name' => $contribB->nama,
            'work_date' => now('Asia/Makassar')->toDateString(),
            'checked_in_at' => now('Asia/Makassar'),
            'latitude' => -8.651000,
            'longitude' => 115.217000,
            'accuracy_m' => 10.0,
            'checkin_method' => 'wifi',
            'wifi_verified' => true,
        ]);

        // Contributor A only sees their own attendance and never sees latitude/longitude.
        $resA = AikuServer::actingAs($userA)->tool(ListAttendancesTool::class, [
            'start_date' => now('Asia/Makassar')->toDateString(),
            'end_date' => now('Asia/Makassar')->toDateString(),
        ]);
        $resA->assertOk()
            ->assertSee('Andi')
            ->assertDontSee('Dewi')
            ->assertDontSee('-8.65')
            ->assertDontSee('115.216');

        // Admin sees both, still without raw GPS coordinates.
        $resAdmin = AikuServer::actingAs($admin)->tool(ListAttendancesTool::class, [
            'start_date' => now('Asia/Makassar')->toDateString(),
            'end_date' => now('Asia/Makassar')->toDateString(),
        ]);
        $resAdmin->assertOk()
            ->assertSee('Andi')
            ->assertSee('Dewi')
            ->assertDontSee('-8.65');
    }

    public function test_payslip_tool_isolates_contributor_and_blocks_other_worker_lookup(): void
    {
        [$userA] = $this->makeContributor('Rudi', 'rudi@aiku.test');
        [$userB] = $this->makeContributor('Tono', 'tono@aiku.test');

        Payslip::create([
            'employee_name' => 'Rudi',
            'period' => '16 Nov - 15 Des 2026',
            'total_logged_hours' => 40,
            'total_approved_hours' => 38,
            'total_thp' => 1500000,
            'bonus' => 100000,
            'status' => 'sudah',
            'is_locked' => true,
        ]);

        Payslip::create([
            'employee_name' => 'Tono',
            'period' => '16 Nov - 15 Des 2026',
            'total_logged_hours' => 50,
            'total_approved_hours' => 48,
            'total_thp' => 2500000,
            'bonus' => 0,
            'status' => 'belum',
            'is_locked' => false,
        ]);

        $res = AikuServer::actingAs($userA)->tool(GetMyPayslipTool::class, [
            'worker_name' => 'Tono',
        ]);

        $res->assertOk()
            ->assertSee('Rudi')
            ->assertDontSee('Tono')
            ->assertDontSee('2500000');
    }

    public function test_admin_tools_redact_sensitive_numbers_and_return_structured_envelope(): void
    {
        $admin = $this->makeAdmin();
        $this->makeContributor('Maya', 'maya@aiku.test');

        $ctx = AikuServer::actingAs($admin)->tool(GetContextTool::class);
        $ctx->assertOk()->assertSee('Asia/Makassar');

        $contribs = AikuServer::actingAs($admin)->tool(ListContributorsTool::class);
        $contribs->assertOk()
            ->assertSee('Maya')
            ->assertDontSee('081234567890')
            ->assertDontSee('123456789012');

        $audit = AikuServer::actingAs($admin)->tool(GetDataAuditSummaryTool::class);
        $audit->assertOk()->assertSee('duplicate_payroll_names_count');
    }

    public function test_revoked_or_account_version_mismatch_token_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $issued = McpConnection::issueToken($admin, 'Temp Token', 'custom');

        // Bump account_version simulates password/role change.
        $admin->forceFill(['account_version' => 2])->save();

        $this->withToken($issued['plain_token'])
            ->postJson('/mcp/aiku', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
            ])
            ->assertStatus(401);

        // Fresh token works, then revoke rejects it.
        $fresh = McpConnection::issueToken($admin, 'Fresh Token', 'custom');
        $this->withToken($fresh['plain_token'])
            ->postJson('/mcp/aiku', [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ])
            ->assertOk();

        $fresh['connection']->revoke();

        $this->withToken($fresh['plain_token'])
            ->postJson('/mcp/aiku', [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'tools/list',
            ])
            ->assertStatus(401);
    }

    public function test_admin_can_manage_mcp_connections_via_ui_routes(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.integrations.mcp'))
            ->assertOk()
            ->assertSee('Integrasi MCP');

        $this->actingAs($admin)
            ->post(route('admin.integrations.mcp.store'), [
                'name' => 'Antigravity Office',
                'client_type' => 'antigravity',
            ])
            ->assertRedirect(route('admin.integrations.mcp'));

        $conn = McpConnection::where('user_id', $admin->id)->firstOrFail();

        $reveal = $this->actingAs($admin)
            ->postJson(route('admin.integrations.mcp.reveal', $conn))
            ->assertOk()
            ->assertJsonStructure(['token']);
        $this->assertStringContainsString('no-store', (string) $reveal->headers->get('Cache-Control'));

        $this->actingAs($admin)
            ->delete(route('admin.integrations.mcp.destroy', $conn))
            ->assertRedirect(route('admin.integrations.mcp'));

        $this->assertNotNull($conn->fresh()->revoked_at);
    }
}
