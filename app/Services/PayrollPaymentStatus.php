<?php

namespace App\Services;

use App\Models\PayrollStatus;
use App\Models\Payslip;

class PayrollPaymentStatus
{
    // Caller owns the transaction. A period/name pair has one payment state,
    // even when it has multiple saved slip versions.
    public function set(string $worker, string $period, string $status): void
    {
        PayrollStatus::updateOrCreate(['worker_name' => $worker, 'period' => $period], ['status' => $status]);
        Payslip::where('employee_name', $worker)->where('period', $period)->update(['status' => $status]);
    }
}
