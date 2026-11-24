<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetContextTool extends ReadTool
{
    protected const ABILITY = 'context';

    protected string $name = 'aiku_get_context';

    protected string $title = 'Konteks Akun & Studio';

    protected string $description = 'Mengembalikan informasi akun aktif, peran, daftar kemampuan, timezone bisnis (Asia/Makassar), dan periode payroll aktif.';

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->object([
                'id' => $schema->integer(),
                'name' => $schema->string(),
                'email' => $schema->string(),
                'role' => $schema->string(),
                'contributor_id' => $schema->integer()->nullable(),
                'contributor_name' => $schema->string()->nullable(),
            ])->required(),
            'abilities' => $schema->array()->items($schema->string())->required(),
            'timezone' => $schema->string()->required(),
            'current_time_wita' => $schema->string()->required(),
            'active_payroll_period' => $schema->object()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->context($access->currentUser());
    }
}
