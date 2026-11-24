<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PayrollReconciliationPrompt extends Prompt
{
    protected string $name = 'aiku-payroll-reconciliation';

    protected string $title = 'Rekonsiliasi Payroll Periode';

    protected string $description = 'Memandu pemeriksaan selisih antara recording yang disetujui, kalkulasi live payroll, status pembayaran, dan arsip slip.';

    public function arguments(): array
        {
        return [
            new Argument('date', 'Tanggal referensi YYYY-MM-DD di dalam periode yang ingin direkonsiliasi.', required: false),
        ];
    }

    public function handle(Request $request): Response
    {
        $date = $request->string('date')->toString() ?: now('Asia/Makassar')->toDateString();

        return Response::text(<<<PROMPT
Lakukan rekonsiliasi payroll AIKu Studio untuk periode yang mencakup tanggal {$date}:
1. Panggil `aiku_get_payroll_period` (`date={$date}`) untuk mendapatkan rentang `startDate`, `endDate`, dan `label`.
2. Panggil `aiku_get_recording_summary` pada rentang periode tersebut.
3. Panggil `aiku_list_payroll_statuses` dan `aiku_list_payslips` untuk `period` yang sesuai.
4. Jika saya admin, panggil `aiku_calculate_live_payroll` (`date={$date}`) dan `aiku_get_data_audit_summary` untuk mendeteksi nama duplikat atau recording unassigned.
5. Bandingkan hasilnya dan tampilkan temuan selisih atau pekerja yang statusnya masih `belum`.
PROMPT);
    }
}
