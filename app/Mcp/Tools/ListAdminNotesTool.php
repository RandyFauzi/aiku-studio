<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListAdminNotesTool extends ReadTool
{
    protected const ABILITY = 'admin_ops';

    protected string $name = 'aiku_list_admin_notes';

    protected string $title = 'Catatan & Tugas Admin';

    protected string $description = 'Khusus admin: melihat daftar catatan admin, prioritas, pin, dan status penyelesaian.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'note_date' => $schema->string()->description('Filter tanggal catatan YYYY-MM-DD (opsional).')->nullable(),
            'pinned_only' => $schema->boolean()->description('Filter hanya catatan yang di-pin.')->nullable(),
            'completed' => $schema->boolean()->description('Filter status selesai (true/false).')->nullable(),
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
        return $queries->listAdminNotes(
            $request->string('note_date')->toString() ?: null,
            $request->has('pinned_only') ? $request->boolean('pinned_only') : null,
            $request->has('completed') ? $request->boolean('completed') : null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
