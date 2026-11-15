<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use App\Services\PayslipDraftSync;
use Illuminate\Console\Command;

class SyncDraftPayslips extends Command
{
    protected $signature = 'payroll:sync-drafts';

    protected $description = 'Sync all unpaid and unlocked draft payslips with live studio recordings';

    public function handle(PayrollCalculator $calculator, PayrollPeriod $resolver): int
    {
        $draftSlips = Payslip::whereNull('superseded_by_id')->where('status', 'belum')->where('is_locked', false)->get();
        $updated = 0;
        $failed = 0;

        foreach ($draftSlips as $slip) {
            if (str_contains(strtolower($slip->period), 'training')) {
                continue;
            }
            try {
                if (app(PayslipDraftSync::class)->sync($slip)) $updated++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Skipping slip #{$slip->id} ({$slip->employee_name}): ".$e->getMessage());
            }
        }

        $this->info("Successfully synchronized {$updated} draft payslips with live recordings.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
