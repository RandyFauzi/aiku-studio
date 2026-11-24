<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class GetMyPayslipTool extends ReadTool
{
    protected const ABILITY = 'payroll';

    protected string $name = 'aiku_get_my_payslip';

    protected string $title = 'Slip Gaji Terbaru / Periode';

    protected string $description = 'Mengambil arsip slip gaji final milik akun yang sedang terhubung (atau pekerja tertentu khusus admin).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()->description('Label periode slip gaji (opsional, default slip terbaru).')->nullable(),
            'worker_name' => $schema->string()->description('Nama pekerja (khusus admin; diabaikan untuk kontributor).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'found' => $schema->boolean()->required(),
            'employee_name' => $schema->string()->required(),
            'payslip' => $schema->object()->nullable(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->getMyPayslip(
            $access->currentUser(),
            $request->string('period')->toString() ?: null,
            $request->string('worker_name')->toString() ?: null,
        );
    }
}
