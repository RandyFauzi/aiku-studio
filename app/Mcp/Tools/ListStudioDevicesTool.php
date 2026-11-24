<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListStudioDevicesTool extends ReadTool
{
    protected const ABILITY = 'operations';

    protected string $name = 'aiku_list_studio_devices';

    protected string $title = 'Daftar Perangkat HP Studio';

    protected string $description = 'Menampilkan daftar perangkat HP studio dan jumlah akun aktif per perangkat (tanpa kredensial sensitif).';

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'total_devices' => $schema->integer()->required(),
            'devices' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->studioDevices($access->currentUser());
    }
}
