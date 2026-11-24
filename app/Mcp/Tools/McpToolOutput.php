<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;

class McpToolOutput
{
    /**
     * @param  array<string, mixed>  $dataProperties
     * @return array<string, mixed>
     */
    public static function envelope(JsonSchema $schema, array $dataProperties): array
    {
        return [
            'ok' => $schema->boolean()->required(),
            'trace_id' => $schema->string()->required(),
            'observed_at' => $schema->string()->required(),
            'data' => $schema->object($dataProperties)->required(),
        ];
    }
}
