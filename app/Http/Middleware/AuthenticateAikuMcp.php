<?php

namespace App\Http\Middleware;

use App\Models\McpConnection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAikuMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('aiku_mcp.enabled', true)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'AIKu MCP server is currently disabled.',
                ],
                'id' => $request->input('id'),
            ], 503);
        }

        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $this->unauthorized($request, 'Bearer token is required.');
        }

        $hash = hash('sha256', $token);
        $connection = McpConnection::with('user')
            ->where('token_hash', $hash)
            ->first();

        if (! $connection || ! $connection->usableBy($connection->user)) {
            return $this->unauthorized($request, 'Invalid, expired, or revoked MCP token.');
        }

        $user = $connection->user;

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);
        $request->attributes->set('mcp_user', $user);
        $request->attributes->set('mcp_connection', $connection);

        $connection->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }

    private function unauthorized(Request $request, string $message): Response
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32001,
                'message' => $message,
            ],
            'id' => $request->input('id'),
        ], 401, [
            'WWW-Authenticate' => 'Bearer realm="AIKu Studio MCP"',
        ]);
    }
}
