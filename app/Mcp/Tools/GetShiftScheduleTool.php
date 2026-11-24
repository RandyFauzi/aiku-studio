<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetShiftScheduleTool extends ReadTool
{
    protected const ABILITY = 'attendance';

    protected string $name = 'aiku_get_shift_schedule';

    protected string $title = 'Jadwal Shift Bulanan';

    protected string $description = 'Mengambil jadwal shift bulanan (rutin + override tanggal spesifik) untuk kontributor atau seluruh studio (khusus admin).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()->description('Bulan format YYYY-MM (opsional, default bulan berjalan WITA).')->nullable(),
            'contributor_id' => $schema->integer()->description('ID kontributor (khusus admin).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->shiftSchedule(
            $access->currentUser(),
            $request->string('month')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
        );
    }
}
