<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class CalculateLivePayrollTool extends ReadTool
{
    protected const ABILITY = 'admin_ops';

    protected string $name = 'aiku_calculate_live_payroll';

    protected string $title = 'Kalkulasi Payroll Live (Admin)';

    protected string $description = 'Khusus admin: menghitung estimasi payroll live dari studio_recordings pada periode tertentu tanpa mengubah database.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('Tanggal referensi YYYY-MM-DD untuk menentukan periode cutoff (default hari ini).')->nullable(),
            'worker_name' => $schema->string()->description('Nama pekerja spesifik (opsional; kosongkan untuk ringkasan seluruh studio).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->object()->required(),
            'mode' => $schema->string()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->calculateLivePayroll(
            $request->string('date')->toString() ?: null,
            $request->string('worker_name')->toString() ?: null,
        );
    }
}
