<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetSyncHealthTool extends ReadTool
{
    protected const ABILITY = 'system';

    protected string $name = 'aiku_get_sync_health';

    protected string $title = 'Status Sinkronisasi Eksternal';

    protected string $description = 'Khusus admin: memeriksa timestamp sinkronisasi terakhir (Atlas & Robotics 1618), jumlah rekaman unassigned, dan status akun studio.';

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'last_atlas_synced_at' => $schema->string()->nullable(),
            'last_robotics_1618_synced_at' => $schema->string()->nullable(),
            'last_general_synced_at' => $schema->string()->nullable(),
            'total_recordings' => $schema->integer()->required(),
            'unassigned_recordings' => $schema->integer()->required(),
            'studio_accounts' => $schema->object()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->syncHealth();
    }
}
