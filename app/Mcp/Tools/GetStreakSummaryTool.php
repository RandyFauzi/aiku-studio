<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetStreakSummaryTool extends ReadTool
{
    protected const ABILITY = 'operations';

    protected string $name = 'aiku_get_streak_summary';

    protected string $title = 'Ringkasan Record Streak';

    protected string $description = 'Melihat progres harian record_streak_days (target menit, verified menit, status open/achieved/missed, dan deadline WITA).';

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
            'by_status' => $schema->object()->required(),
            'items' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->streakSummary(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
        );
    }
}
