<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class PayrollRulesResource extends Resource
{
    protected string $uri = 'policy://aiku/payroll-rules';

    protected string $name = 'aiku-payroll-rules';

    protected string $title = 'Aturan Cutoff & Payroll AIKu Studio';

    protected string $description = 'Kebijakan periode cutoff payroll, timezone bisnis Asia/Makassar, dan perbedaan jam laporan harian dengan jam recording yang disetujui.';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        return Response::text(<<<'MD'
# Aturan Payroll & Cutoff AIKu Studio

- **Timezone Bisnis**: `Asia/Makassar` (WITA). Seluruh tanggal kerja (`work_date`) dan periode cutoff dihitung dalam WITA.
- **Siklus Cutoff Resmi**:
  - Siklus Historis (< 22 Okt 2026): tanggal **22 s/d 21** bulan berikutnya.
  - Siklus Transisi: **22 Okt 2026 s/d 15 Nov 2026**.
  - Siklus Baru (>= 16 Nov 2026): tanggal **16 s/d 15** bulan berikutnya.
- **Sumber Perhitungan**:
  - Payroll dihitung dari `studio_recordings` (`billable_min` yang disetujui) dan bonus, bukan otomatis dari durasi absensi atau laporan harian (`daily_work_reports`).
  - Slip gaji final (`payslips`) yang sudah diterbitkan dapat berbeda dari kalkulasi live apabila ada revisi atau *superseded* slip.
- **Isolasi Nama Payroll**:
  - Relasi `payslips.employee_name` dan `payroll_statuses.worker_name` menggunakan pencocokan nama kontributor secara ketat (*fail-closed* bila ditemukan duplikat nama).
MD);
    }
}
