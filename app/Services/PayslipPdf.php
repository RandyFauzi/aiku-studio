<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PayslipPdf
{
    public function export(array $data): array
    {
        $options = new Options;
        $cache = storage_path('framework/cache/dompdf');
        File::ensureDirectoryExists($cache);
        $options->set('tempDir', $cache);
        $options->set('fontCache', $cache);
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setChroot(storage_path('app/private'));
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('slips.pdf', $data)->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $name = 'Payslip_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['employee_name']).'_'.bin2hex(random_bytes(12)).'.pdf';
        Storage::disk('local')->put('payroll/pdf_slips/'.$name, $pdf->output());

        return ['success' => true, 'url' => '/pdf_slips/'.$name, 'fileName' => $name];
    }
}
