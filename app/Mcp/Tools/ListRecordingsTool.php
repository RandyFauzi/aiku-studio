<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListRecordingsTool extends ReadTool
{
    protected const ABILITY = 'operations';

    protected string $name = 'aiku_list_recordings';

    protected string $title = 'Daftar Studio Recordings';

    protected string $description = 'Menampilkan daftar rekaman studio beserta durasi, billable_min, rejected_min, dan status QC/pembayaran.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Tanggal awal YYYY-MM-DD.')->nullable(),
            'end_date' => $schema->string()->description('Tanggal akhir YYYY-MM-DD.')->nullable(),
            'contributor_id' => $schema->integer()->description('Filter ID kontributor (khusus admin).')->nullable(),
            'billable_status' => $schema->string()->description('Filter billable_status (opsional).')->nullable(),
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
        return $queries->listRecordings(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
            $request->string('billable_status')->toString() ?: null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
