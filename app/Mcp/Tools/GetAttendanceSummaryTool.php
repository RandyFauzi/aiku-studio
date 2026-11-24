<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetAttendanceSummaryTool extends ReadTool
{
    protected const ABILITY = 'attendance';

    protected string $name = 'aiku_get_attendance_summary';

    protected string $title = 'Ringkasan Absensi';

    protected string $description = 'Mengembalikan agregasi jumlah check-in per metode (GPS/Wi-Fi/QR) dan per kontributor dalam rentang tanggal.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Tanggal awal YYYY-MM-DD (default 30 hari terakhir).')->nullable(),
            'end_date' => $schema->string()->description('Tanggal akhir YYYY-MM-DD (default hari ini).')->nullable(),
            'contributor_id' => $schema->integer()->description('Filter ID kontributor (khusus admin).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->object()->required(),
            'total_checkins' => $schema->integer()->required(),
            'unique_days' => $schema->integer()->required(),
            'by_method' => $schema->object()->required(),
            'by_contributor' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->attendanceSummary(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
        );
    }
}
