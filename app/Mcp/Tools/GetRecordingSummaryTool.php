<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetRecordingSummaryTool extends ReadTool
{
    protected const ABILITY = 'operations';

    protected string $name = 'aiku_get_recording_summary';

    protected string $title = 'Ringkasan Durasi Recording';

    protected string $description = 'Mengembalikan ringkasan total rekaman dan menit (duration, billable, rejected, pending, deferred) pada rentang tanggal.';

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
            'total_recordings' => $schema->integer()->required(),
            'duration_min' => $schema->number()->required(),
            'billable_min' => $schema->number()->required(),
            'rejected_min' => $schema->number()->required(),
            'pending_min' => $schema->number()->required(),
            'deferred_min' => $schema->number()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->recordingSummary(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
        );
    }
}
