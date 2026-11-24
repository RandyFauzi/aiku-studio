<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetDataAuditSummaryTool extends ReadTool
{
    protected const ABILITY = 'system';

    protected string $name = 'aiku_get_data_audit_summary';

    protected string $title = 'Audit Integritas Data Studio';

    protected string $description = 'Khusus admin: mendeteksi duplikat nama payroll, akun kontributor tanpa tautan, rekaman belum ter-assign, dan laporan belum tervalidasi.';

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'duplicate_payroll_names_count' => $schema->integer()->required(),
            'duplicate_payroll_names' => $schema->array()->required(),
            'contributor_users_without_link' => $schema->integer()->required(),
            'inactive_users_count' => $schema->integer()->required(),
            'unassigned_recordings_count' => $schema->integer()->required(),
            'unvalidated_daily_reports_count' => $schema->integer()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->dataAuditSummary();
    }
}
