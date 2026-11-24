<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListDailyReportsTool extends ReadTool
{
    protected const ABILITY = 'operations';

    protected string $name = 'aiku_list_daily_reports';

    protected string $title = 'Daftar Laporan Kerja Harian';

    protected string $description = 'Menampilkan daftar laporan kerja harian beserta durasi total, HP yang dipakai, dan status validasi.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Tanggal awal YYYY-MM-DD.')->nullable(),
            'end_date' => $schema->string()->description('Tanggal akhir YYYY-MM-DD.')->nullable(),
            'contributor_id' => $schema->integer()->description('Filter ID kontributor (khusus admin).')->nullable(),
            'validated_only' => $schema->boolean()->description('Filter status validasi (true/false).')->nullable(),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maksimal baris (1-100).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->object()->required(),
            'total_matched' => $schema->integer()->required(),
            'returned' => $schema->integer()->required(),
            'items' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->listDailyReports(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
            $request->has('validated_only') ? $request->boolean('validated_only') : null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
