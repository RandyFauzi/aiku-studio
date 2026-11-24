<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetPayrollPeriodTool extends ReadTool
{
    protected const ABILITY = 'context';

    protected string $name = 'aiku_get_payroll_period';

    protected string $title = 'Info Periode Cutoff Payroll';

    protected string $description = 'Menghitung rentang tanggal dan label periode cutoff payroll resmi (Asia/Makassar) untuk tanggal tertentu atau periode sebelum/sesudahnya.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('Tanggal referensi YYYY-MM-DD (opsional, default hari ini).')->nullable(),
            'direction' => $schema->string()->enum(['current', 'previous', 'next'])->description('Arah periode relatif terhadap tanggal referensi.')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->object()->required(),
            'direction' => $schema->string()->required(),
            'reference_date' => $schema->string()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->payrollPeriodInfo(
            $request->string('date')->toString() ?: null,
            $request->string('direction')->toString() ?: 'current',
        );
    }
}
