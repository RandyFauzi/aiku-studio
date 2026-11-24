<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListTaskTranslationsTool extends ReadTool
{
    protected const ABILITY = 'admin_ops';

    protected string $name = 'aiku_list_task_translations';

    protected string $title = 'Kamus Terjemahan Tugas';

    protected string $description = 'Khusus admin: melihat daftar terjemahan nama tugas laporan harian dan kata kunci pencocokannya.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Kata kunci pencarian nama tugas atau label.')->nullable(),
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
        return $queries->listTaskTranslations(
            $request->string('search')->toString() ?: null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
