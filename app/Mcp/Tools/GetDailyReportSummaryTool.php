<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetDailyReportSummaryTool extends ReadTool
{
    protected const ABILITY = 'operations';

    protected string $name = 'aiku_get_daily_report_summary';

    protected string $title = 'Ringkasan Laporan Kerja Harian';

    protected string $description = 'Mengagregasi total menit laporan harian, jumlah laporan tervalidasi, dan distribusi menit per perangkat HP.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Tanggal awal YYYY-MM-DD.')->nullable(),
            'end_date' => $schema->string()->description('Tanggal akhir YYYY-MM-DD.')->nullable(),
            'contributor_id' => $schema->integer()->description('Filter ID kontributor (khusus admin).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->object()->required(),
            'reports_count' => $schema->integer()->required(),
            'validated_count' => $schema->integer()->required(),
            'total_logged_minutes' => $schema->integer()->required(),
            'total_logged_hours' => $schema->number()->required(),
            'minutes_by_phone' => $schema->object()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->dailyReportSummary(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
        );
    }
}
