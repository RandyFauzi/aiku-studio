<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\InvestorDeviceContract;
use App\Models\InvestorPayout;
use App\Models\StudioDevice;
use App\Services\InvestorProfitService;
use App\Services\PayrollPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvestorController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Akses terbatas untuk Administrator.');
    }

    public function analytics(Request $request, PayrollPeriod $resolver, InvestorProfitService $service): JsonResponse
    {
        $this->ensureAdmin($request);
        $period = $resolver->resolve($request->query());

        return response()->json($service->summaryForPeriod($period));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $this->validateInvestor($request);

        $investor = DB::transaction(function () use ($data) {
            if (empty($data['code'])) {
                $nextNum = (Investor::max('id') ?? 0) + 1;
                $data['code'] = 'INV-'.str_pad((string) $nextNum, 2, '0', STR_PAD_LEFT);
            }

            return Investor::create($data);
        });

        return response()->json([
            'success' => true,
            'investor' => $investor,
            'message' => "Investor {$investor->name} ({$investor->code}) berhasil ditambahkan.",
        ], 201);
    }

    public function update(Request $request, Investor $investor): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $this->validateInvestor($request, $investor);
        $investor->update($data);

        return response()->json([
            'success' => true,
            'investor' => $investor->fresh(),
            'message' => "Data investor {$investor->name} berhasil diperbarui.",
        ]);
    }

    public function destroy(Request $request, Investor $investor): JsonResponse
    {
        $this->ensureAdmin($request);
        $name = $investor->name;
        $investor->delete();

        return response()->json([
            'success' => true,
            'message' => "Investor {$name} beserta kontrak HP-nya telah dihapus.",
        ]);
    }

    public function storeContract(Request $request, Investor $investor): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'device_key' => 'nullable|string|max:64',
            'new_device_label' => 'nullable|string|max:60',
            'device_code' => ['required', 'string', 'max:64', Rule::unique('investor_device_contracts', 'device_code')],
            'rate_per_hour_idr' => 'required|integer|min:0|max:10000000',
            'rate_basis' => 'nullable|string|in:billable,duration',
            'effective_from' => 'nullable|date_format:Y-m-d',
            'effective_to' => 'nullable|date_format:Y-m-d|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $contract = DB::transaction(function () use ($investor, $data) {
            $deviceKey = trim((string) ($data['device_key'] ?? ''));

            if ($deviceKey === '') {
                $rawLabel = trim((string) ($data['new_device_label'] ?? ''));
                if ($rawLabel === '') {
                    $rawLabel = $data['device_code'];
                }
                $cleanLabel = preg_replace('/^HP\s+/iu', '', preg_replace('/\s+/u', ' ', $rawLabel));
                $deviceKey = Str::slug($cleanLabel ?: $data['device_code'], '_');

                if ($deviceKey === '' || $deviceKey === 'sendiri' || strlen($deviceKey) > 64) {
                    throw ValidationException::withMessages([
                        'new_device_label' => 'Nama/kode HP tidak valid. Gunakan kombinasi huruf/angka yang jelas.',
                    ]);
                }

                StudioDevice::firstOrCreate(
                    ['key' => $deviceKey],
                    ['label' => $cleanLabel ?: $data['device_code']]
                );
            } else {
                abort_unless(StudioDevice::where('key', $deviceKey)->exists(), 422, 'Perangkat HP yang dipilih tidak ditemukan.');
            }

            return InvestorDeviceContract::create([
                'investor_id' => $investor->id,
                'device_key' => $deviceKey,
                'device_code' => strtoupper(trim($data['device_code'])),
                'rate_per_hour_idr' => (int) $data['rate_per_hour_idr'],
                'rate_basis' => $data['rate_basis'] ?? 'billable',
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to' => $data['effective_to'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'contract' => $contract,
            'message' => "Unit HP {$contract->device_code} berhasil ditautkan ke {$investor->name}.",
        ], 201);
    }

    public function updateContract(Request $request, InvestorDeviceContract $contract): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'device_code' => ['required', 'string', 'max:64', Rule::unique('investor_device_contracts', 'device_code')->ignore($contract->id)],
            'rate_per_hour_idr' => 'required|integer|min:0|max:10000000',
            'rate_basis' => 'nullable|string|in:billable,duration',
            'effective_from' => 'nullable|date_format:Y-m-d',
            'effective_to' => 'nullable|date_format:Y-m-d|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $contract->update([
            'device_code' => strtoupper(trim($data['device_code'])),
            'rate_per_hour_idr' => (int) $data['rate_per_hour_idr'],
            'rate_basis' => $data['rate_basis'] ?? $contract->rate_basis,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => $data['is_active'] ?? $contract->is_active,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'contract' => $contract->fresh(),
            'message' => "Kontrak HP {$contract->device_code} berhasil diperbarui.",
        ]);
    }

    public function destroyContract(Request $request, InvestorDeviceContract $contract): JsonResponse
    {
        $this->ensureAdmin($request);
        $code = $contract->device_code;
        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => "Kontrak unit HP {$code} telah dihapus.",
        ]);
    }

    public function updatePayout(Request $request, Investor $investor, PayrollPeriod $resolver, InvestorProfitService $service): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'period_id' => 'required|string|max:64',
            'status' => 'required|string|in:pending,paid',
            'notes' => 'nullable|string|max:1000',
        ]);

        $period = $resolver->resolve(['periodId' => $data['period_id']]);
        $summary = $service->summaryForPeriod($period);
        $row = collect($summary['investors'])->firstWhere('id', $investor->id);

        $payout = InvestorPayout::updateOrCreate(
            ['investor_id' => $investor->id, 'period_id' => $period['id']],
            [
                'period_name' => $period['name'],
                'total_billable_hours' => $row['billable_hours'] ?? 0,
                'total_amount_idr' => $row['profit_idr'] ?? 0,
                'status' => $data['status'],
                'paid_at' => $data['status'] === 'paid' ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'payout' => $payout,
            'message' => $data['status'] === 'paid'
                ? "Bagi hasil {$investor->name} untuk {$period['name']} ditandai Sudah Dibayar."
                : "Status bagi hasil {$investor->name} dikembalikan ke Pending.",
        ]);
    }

    private function validateInvestor(Request $request, ?Investor $existing = null): array
    {
        $rawCode = trim((string) $request->input('code', ''));
        $request->merge([
            'code' => $rawCode !== '' ? strtoupper($rawCode) : null,
            'name' => trim((string) $request->input('name', '')),
        ]);

        return $request->validate([
            'code' => ['nullable', 'string', 'max:32', Rule::unique('investors', 'code')->ignore($existing?->id)],
            'name' => 'required|string|max:120',
            'no_wa' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:128',
            'bank_name' => 'nullable|string|max:60',
            'no_rek' => 'nullable|string|max:80',
            'account_holder' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);
    }
}
