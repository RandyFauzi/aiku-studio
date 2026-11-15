<?php

namespace App\Services;

use App\Models\Payslip;
use Illuminate\Support\Facades\DB;

class PayslipDraftSync
{
    public function sync(Payslip $slip): bool
    {
        return DB::transaction(function () use ($slip) {
            $writer = app(PayslipWriter::class);
            $writer->lock($slip->employee_name);
            $slip->refresh();
            if ($slip->superseded_by_id || $slip->is_locked || $slip->status === 'sudah') return false;
            $writer->unique($slip->employee_name, $slip->period);
            $period = app(PayrollPeriod::class)->resolve(['period' => $slip->period]);
            if (! empty($period['isTraining'])) return false;
            $calculator = app(PayrollCalculator::class);
            $calculated = $calculator->calculate($calculator->evidence($period, $slip->employee_name),
                $slip->bonus, $slip->pending_carry_over_amount, $slip->pending_carry_over_data);
            $slip->update(array_merge($calculated, ['file_path' => null]));
            return true;
        }, 3);
    }
}
