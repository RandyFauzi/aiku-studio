<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use Illuminate\Console\Command;

class SyncDraftPayslips extends Command
{
    protected $signature = 'payroll:sync-drafts';

    protected $description = 'Sync all unpaid and unlocked draft payslips with live studio recordings';

    public function handle(PayrollCalculator $calculator, PayrollPeriod $resolver): int
    {
        $draftSlips = Payslip::where('status', 'belum')->where('is_locked', false)->get();
        $updated = 0;

        foreach ($draftSlips as $slip) {
            if (str_contains(strtolower($slip->period), 'training')) {
                continue;
            }
            try {
                $period = $resolver->resolve(['period' => $slip->period]);
                if ($calculator->recordings($period, $slip->employee_name)->exists()) {
                    $evidence = $calculator->evidence($period, $slip->employee_name);
                    $calculated = $calculator->calculate($evidence, (int) $slip->bonus, (int) $slip->pending_carry_over_amount, $slip->pending_carry_over_data);
                    $slip->update($calculated);
                    $updated++;
                }
            } catch (\Throwable $e) {
                $this->warn("Skipping slip #{$slip->id} ({$slip->employee_name}): ".$e->getMessage());
            }
        }

        $this->info("Successfully synchronized {$updated} draft payslips with live recordings.");

        return self::SUCCESS;
    }
}
