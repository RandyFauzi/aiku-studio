<?php

namespace App\Services;

use App\Models\ShiftSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftPatterns
{
    public function period(string $id): array
    {
        $resolver = app(PayrollPeriod::class);
        $period = $id === 'current' ? $resolver->cutoff() : $resolver->resolve(['periodId' => $id]);
        $canonical = $resolver->cutoff(CarbonImmutable::parse($period['startDate'], 'Asia/Makassar'));
        if ($canonical['id'] !== $period['id']) {
            throw ValidationException::withMessages(['periodId' => 'Gunakan periode cut-off resmi yang valid.']);
        }

        return $period;
    }

    public function key(array $period): string
    {
        return 'shift_period_'.$period['id'];
    }

    public function read(array $period): array
    {
        $saved = ShiftSetting::where('setting_key', $this->key($period))->value('setting_value');

        return ['config' => $saved['config'] ?? $this->legacy(), 'revision' => $saved['revision'] ?? 0, 'inherited' => $saved === null];
    }

    public function legacy(): array
    {
        return ShiftSetting::where('setting_key', 'main_shift_config')->value('setting_value') ?? config('shifts');
    }

    public function save(array $period, array $config, int $expected): array
    {
        return DB::transaction(function () use ($period, $config, $expected) {
            ShiftSetting::firstOrCreate(['setting_key' => $this->key($period)], ['setting_value' => ['revision' => 0]]);
            $row = ShiftSetting::where('setting_key', $this->key($period))->lockForUpdate()->firstOrFail();
            abort_if(($row->setting_value['revision'] ?? 0) !== $expected, 409, 'Pola periode sudah diubah admin lain. Muat ulang sebelum menyimpan.');
            $value = ['config' => $config, 'revision' => $expected + 1];
            $row->update(['setting_value' => $value]);

            return $value;
        }, 3);
    }
}
