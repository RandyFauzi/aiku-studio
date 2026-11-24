<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetContributorPerformanceTool extends ReadTool
{
    protected const ABILITY = 'admin_ops';

    protected string $name = 'aiku_get_contributor_performance';

    protected string $title = 'Performa Kontributor';

    protected string $description = 'Khusus admin: ringkasan performa kontributor dalam rentang tanggal (hari hadir, laporan harian, dan menit billable vs rejected).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Tanggal awal YYYY-MM-DD (default 30 hari terakhir).')->nullable(),
            'end_date' => $schema->string()->description('Tanggal akhir YYYY-MM-DD (default hari ini).')->nullable(),
            'contributor_id' => $schema->integer()->description('Filter ID kontributor spesifik (opsional).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->object()->required(),
            'returned' => $schema->integer()->required(),
            'items' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->contributorPerformance(
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
        );
    }
}
