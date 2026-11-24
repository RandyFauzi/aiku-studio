<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListPayslipsTool extends ReadTool
{
    protected const ABILITY = 'payroll';

    protected string $name = 'aiku_list_payslips';

    protected string $title = 'Daftar Arsip Slip Gaji';

    protected string $description = 'Menampilkan daftar arsip slip gaji final (tidak termasuk yang sudah digantikan). Kontributor hanya melihat miliknya sendiri.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()->description('Filter label periode (opsional).')->nullable(),
            'worker_name' => $schema->string()->description('Filter nama pekerja (khusus admin).')->nullable(),
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
        return $queries->listPayslips(
            $access->currentUser(),
            $request->string('period')->toString() ?: null,
            $request->string('worker_name')->toString() ?: null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
