<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class WorkerSlipArchive
{
    public function __construct(private ContributorIdentity $identity, private PayrollPeriod $periods) {}

    public function isPastFinal(Payslip $slip): bool
    {
        if ($slip->status !== 'sudah') return false;
        try {
            $period = $this->periods->resolve(['period' => $slip->period]);
            return $period['endDate'] < $this->periods->cutoff()['startDate'];
        } catch (ValidationException) {
            // An unrecognised period must not accidentally expose a current/draft amount.
            return false;
        }
    }

    public function forUser(User $user)
    {
        $name = $this->identity->payrollName($user);
        return Payslip::where('employee_name', $name)->where('status', 'sudah')->orderByDesc('id')->get()
            ->filter(fn ($slip) => $this->isPastFinal($slip))->values();
    }

    public function canView(User $user, Payslip $slip): bool
    {
        return $slip->employee_name === $this->identity->payrollName($user) && $this->isPastFinal($slip);
    }
}
