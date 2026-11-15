<?php

namespace App\Services;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayslipWriter
{
    public function period(string $label): string
    {
        try {
            return app(PayrollPeriod::class)->resolve(['period' => trim($label)])['name'];
        } catch (ValidationException) {
            return trim($label); // Preserve historical/manual labels.
        }
    }

    public function workerKey(string $worker): string
    {
        return hash('sha256', mb_strtolower(trim($worker)));
    }

    public function key(string $worker, string $period): string
    {
        return hash('sha256', $this->workerKey($worker).'|'.$this->period($period));
    }

    public function forWorker(string $worker): Collection
    {
        return Payslip::whereRaw('LOWER(TRIM(employee_name)) = ?', [mb_strtolower(trim($worker))])
            ->whereNull('superseded_by_id')->orderBy('id')->get();
    }

    public function matching(string $worker, string $period): Collection
    {
        $canonical = $this->period($period);
        return $this->forWorker($worker)->filter(fn ($slip) => $this->period($slip->period) === $canonical)->values();
    }

    public function unique(string $worker, string $period): ?Payslip
    {
        $slips = $this->matching($worker, $period);
        abort_if($slips->count() > 1, 409, 'Ada beberapa slip untuk pekerja dan periode ini. Pilih slip utama di Slip Tersimpan setelah memeriksa bonus dan carry-over.');
        return $slips->first();
    }

    // Call inside a transaction, before reading slips or balances.
    public function lock(string $worker): void
    {
        $key = $this->workerKey($worker);
        DB::table('payroll_write_locks')->insertOrIgnore(['worker_key' => $key]);
        DB::table('payroll_write_locks')->where('worker_key', $key)->lockForUpdate()->first();
    }

    public function editable(Payslip $slip): void
    {
        abort_if($slip->superseded_by_id !== null, 409, 'Slip ini sudah menjadi arsip duplikat dan tidak dapat diubah.');
        abort_if($slip->is_locked || $slip->status === 'sudah', 409, 'Slip sudah final/terkunci. Snapshot pembayaran tidak dapat diubah atau dihapus.');
    }

    public function carry(array $fields, ?Payslip $slip = null): array
    {
        $amount = (int) ($fields['pending_carry_over_amount'] ?? $slip?->pending_carry_over_amount ?? 0);
        $data = $fields['pending_carry_over_data'] ?? $slip?->pending_carry_over_data;
        if (array_key_exists('pending_carry_over_data', $fields)) $data = $fields['pending_carry_over_data'];
        // Keep an existing historical reservation even if live QC has since changed.
        if ($slip && $amount === (int) $slip->pending_carry_over_amount && $data == $slip->pending_carry_over_data) {
            return [$amount, $data];
        }
        if ($amount === 0 && empty($data)) return [0, null];

        $bank = app(PayrollCalculator::class)->detectPendingCarryOver($fields['employee_name'], $fields['period'], $slip?->id);
        $available = collect($bank['tasks'])->keyBy('source_period');
        $selected = [];
        foreach ($data ?? [] as $claim) {
            if (! is_array($claim) || ! is_string($claim['source_period'] ?? null) || ! is_numeric($claim['delta_min'] ?? null)) {
                throw ValidationException::withMessages(['pending_carry_over_data' => 'Rincian sumber dan menit carry-over tidak valid.']);
            }
            $source = $this->period($claim['source_period'] ?? '');
            $task = $available->get($source);
            if (! $task || isset($selected[$source]) || abs((float) ($claim['delta_min'] ?? 0) - $task['delta_min']) > 0.001) {
                throw ValidationException::withMessages(['pending_carry_over_data' => 'Saldo pending sudah berubah, terpakai, atau sumbernya belum final. Muat ulang saldo sebelum menyimpan.']);
            }
            $selected[$source] = $task;
        }
        $expected = (int) round(array_sum(array_column($selected, 'delta_min')) / 60 * config('payroll.hourly_rate'));
        if (! $selected || $expected !== $amount) {
            throw ValidationException::withMessages(['pending_carry_over_amount' => 'Nominal carry-over harus sesuai saldo yang diverifikasi server.']);
        }
        return [$expected, array_values($selected)];
    }

    public function resolveDuplicates(Payslip $selected, int $actorId): void
    {
        DB::transaction(function () use ($selected, $actorId) {
            $this->lock($selected->employee_name);
            $selected->refresh();
            $this->editable($selected);
            $slips = $this->matching($selected->employee_name, $selected->period);
            foreach ($slips as $slip) $this->editable($slip);
            // No totals/evidence are merged or deleted. The administrator chooses the snapshot.
            foreach ($slips as $slip) {
                if ($slip->id !== $selected->id) $slip->update([
                    'active_key' => null, 'superseded_by_id' => $selected->id,
                    'superseded_at' => now(), 'superseded_by_user_id' => $actorId,
                ]);
            }
            $selected->update(['active_key' => $this->key($selected->employee_name, $selected->period)]);
        }, 3);
    }
}
