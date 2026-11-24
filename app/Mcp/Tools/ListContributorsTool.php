<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListContributorsTool extends ReadTool
{
    protected const ABILITY = 'admin_ops';

    protected string $name = 'aiku_list_contributors';

    protected string $title = 'Daftar Kontributor Studio';

    protected string $description = 'Khusus admin: menampilkan daftar kontributor beserta status akun tertaut (nomor WA dan rekening disensor otomatis).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('Filter role kontributor (opsional).')->nullable(),
            'active_only' => $schema->boolean()->description('Filter hanya akun yang aktif.')->nullable(),
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
        return $queries->listContributors(
            $request->string('role')->toString() ?: null,
            $request->has('active_only') ? $request->boolean('active_only') : null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
