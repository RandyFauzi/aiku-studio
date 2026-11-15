<?php

namespace App\Services;

use App\Models\PayrollStatus;
use App\Models\Payslip;

class PayrollPaymentStatus
{
    // Caller owns the transaction; every mutation shares the worker lock.
    public function set(string $worker, string $period, string $status, ?string $notes = null, bool $freezeLive = false, ?int $expectedThp = null): void
    {
        $writer = app(PayslipWriter::class);
        $writer->lock($worker);
        $period = $writer->period($period);
        $slip = $writer->unique($worker, $period);
        if ($slip && $freezeLive && $status === 'sudah' && ! $slip->is_locked && $slip->status !== 'sudah') {
            // The live table finalizes the estimate shown there. Saved-slip actions finalize its snapshot.
            $range = app(PayrollPeriod::class)->resolve(['period' => $period]);
            $calculator = app(PayrollCalculator::class);
            $slip->update(array_merge($calculator->calculate($calculator->evidence($range, $slip->employee_name),
                $slip->bonus, $slip->pending_carry_over_amount, $slip->pending_carry_over_data), ['file_path' => null]));
        }
        if (! $slip && $status === 'sudah') {
            $range = app(PayrollPeriod::class)->resolve(['period' => $period]);
            $calculator = app(PayrollCalculator::class);
            $slip = Payslip::create(array_merge([
                'employee_name' => trim($worker), 'period' => $period,
                'active_key' => $writer->key($worker, $period), 'status' => 'sudah', 'is_locked' => true,
            ], $calculator->calculate($calculator->evidence($range, $worker))));
        }
        abort_if($status === 'sudah' && $expectedThp !== null && (int) $slip?->total_thp !== $expectedThp,
            409, 'Nilai THP berubah sejak rekap dimuat. Muat ulang dan periksa nominal sebelum finalisasi.');
        PayrollStatus::updateOrCreate(['worker_name' => trim($worker), 'period' => $period],
            array_filter(['status' => $status, 'notes' => $notes], fn ($value) => $value !== null));
        // Returning payment state to "belum" never unlocks the paid snapshot.
        if ($slip) $slip->update(['status' => $status, 'is_locked' => $slip->is_locked || $slip->status === 'sudah' || $status === 'sudah']);
    }
}
