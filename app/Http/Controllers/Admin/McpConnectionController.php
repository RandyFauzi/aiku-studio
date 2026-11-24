<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\McpConnection;
use App\Models\McpToolCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class McpConnectionController extends Controller
{
    public function index(Request $request): View
    {
        $connections = McpConnection::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $recentCalls = McpToolCall::query()
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $endpointUrl = url('/mcp/aiku');

        return view('admin.integrations.mcp', [
            'connections' => $connections,
            'recentCalls' => $recentCalls,
            'endpointUrl' => $endpointUrl,
            'clients' => config('aiku_mcp.clients', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clientKeys = array_keys(config('aiku_mcp.clients', ['custom' => 'Custom']));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'client_type' => ['required', 'string', Rule::in($clientKeys)],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $expiresAt = isset($validated['expires_days'])
            ? now()->addDays((int) $validated['expires_days'])
            : null;

        $issued = McpConnection::issueToken(
            $request->user(),
            $validated['name'],
            $validated['client_type'],
            $expiresAt,
        );

        return redirect()
            ->route('admin.integrations.mcp')
            ->with('new_mcp_token', $issued['plain_token'])
            ->with('status', 'Kunci akses MCP berhasil dibuat.');
    }

    public function reveal(Request $request, McpConnection $connection): JsonResponse
    {
        abort_unless((int) $connection->user_id === (int) $request->user()->id, 403);

        if ($connection->isRevoked() || ! $connection->token_encrypted) {
            return response()->json(['message' => 'Kunci tidak tersedia atau sudah dicabut.'], 404);
        }

        return response()
            ->json(['token' => $connection->token_encrypted])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function destroy(Request $request, McpConnection $connection): RedirectResponse
    {
        abort_unless((int) $connection->user_id === (int) $request->user()->id, 403);

        $connection->revoke();

        return redirect()
            ->route('admin.integrations.mcp')
            ->with('status', 'Kunci akses MCP berhasil dicabut.');
    }
}
