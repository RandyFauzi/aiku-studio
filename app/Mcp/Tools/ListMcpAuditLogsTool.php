<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListMcpAuditLogsTool extends ReadTool
{
    protected const ABILITY = 'system';

    protected string $name = 'aiku_list_mcp_audit_logs';

    protected string $title = 'Log Audit Pemanggilan MCP';

    protected string $description = 'Khusus admin: melihat riwayat pemanggilan tool MCP beserta trace_id, outcome, status HTTP, dan latency.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'tool_name' => $schema->string()->description('Filter nama tool MCP (opsional).')->nullable(),
            'outcome' => $schema->string()->enum(['success', 'error', 'denied', 'tool_error'])->description('Filter hasil eksekusi.')->nullable(),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maksimal baris (1-100).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'total_matched' => $schema->integer()->required(),
            'returned' => $schema->integer()->required(),
            'items' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->listMcpAuditLogs(
            $request->string('tool_name')->toString() ?: null,
            $request->string('outcome')->toString() ?: null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
