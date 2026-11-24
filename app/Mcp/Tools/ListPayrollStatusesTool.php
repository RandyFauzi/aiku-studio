<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListPayrollStatusesTool extends ReadTool
{
    protected const ABILITY = 'payroll';

    protected string $name = 'aiku_list_payroll_statuses';

    protected string $title = 'Status Pembayaran Payroll';

    protected string $description = 'Melihat status pembayaran payroll (belum/sudah) pada periode tertentu. Kontributor hanya melihat status miliknya.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()->description('Label periode payroll (opsional, default periode aktif).')->nullable(),
            'status' => $schema->string()->enum(['belum', 'sudah'])->description('Filter status pembayaran.')->nullable(),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maksimal baris (1-100).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()->required(),
            'total_matched' => $schema->integer()->required(),
            'returned' => $schema->integer()->required(),
            'items' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->listPayrollStatuses(
            $access->currentUser(),
            $request->string('period')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
