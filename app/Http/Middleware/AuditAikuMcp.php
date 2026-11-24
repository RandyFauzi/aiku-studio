<?php

namespace App\Http\Middleware;

use App\Models\McpConnection;
use App\Models\McpToolCall;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditAikuMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $traceId = (string) ($request->header('X-Trace-Id') ?: Str::uuid());
        $request->attributes->set('mcp_trace_id', $traceId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Trace-Id', $traceId);

        $this->recordAudit($request, $response, $traceId, $startedAt);

        return $response;
    }

    private function recordAudit(Request $request, Response $response, string $traceId, int $startedAt): void
    {
        try {
            $method = (string) $request->input('method', 'http');
            $toolName = $method === 'tools/call'
                ? (string) data_get($request->all(), 'params.name', '')
                : null;

            $arguments = data_get($request->all(), 'params.arguments');
            $inputDigest = is_array($arguments)
                ? $this->sanitizeDigest($arguments)
                : null;

            $status = $response->getStatusCode();
            $outcome = $this->resolveOutcome($response, $status);

            /** @var \App\Models\User|null $user */
            $user = $request->attributes->get('mcp_user') ?? $request->user();
            /** @var McpConnection|null $connection */
            $connection = $request->attributes->get('mcp_connection');

            $latencyMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

            McpToolCall::create([
                'trace_id' => $traceId,
                'mcp_connection_id' => $connection?->id,
                'user_id' => $user?->id,
                'role' => $user?->role,
                'method' => Str::limit($method, 64, ''),
                'tool_name' => $toolName !== '' ? Str::limit((string) $toolName, 100, '') : null,
                'input_digest' => $inputDigest,
                'outcome' => $outcome,
                'http_status' => $status,
                'latency_ms' => max(0, $latencyMs),
                'ip_address' => $request->ip(),
            ]);
        } catch (Throwable) {
            // Audit logging must never break the MCP protocol response.
        }
    }

    private function resolveOutcome(Response $response, int $status): string
    {
        if ($status === 401 || $status === 403) {
            return 'denied';
        }

        if ($status >= 400) {
            return 'error';
        }

        $content = $response->getContent();
        if (is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                if (isset($decoded['error'])) {
                    return 'error';
                }
                if (data_get($decoded, 'result.isError') === true) {
                    return 'tool_error';
                }
            }
        }

        return 'success';
    }

    private function sanitizeDigest(array $arguments): string
    {
        $sensitiveKeys = ['token', 'password', 'secret', 'authorization', 'no_rek'];
        $clean = [];

        foreach ($arguments as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';

                continue;
            }
            $clean[$key] = is_string($value) ? Str::limit($value, 120) : $value;
        }

        $json = json_encode($clean, JSON_UNESCAPED_UNICODE);

        return is_string($json) ? Str::limit($json, 1000) : '{}';
    }
}
