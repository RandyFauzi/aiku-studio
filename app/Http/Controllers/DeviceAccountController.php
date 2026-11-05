<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\StudioAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeviceAccountController extends Controller
{
    private function mutate(callable $callback): mixed
    {
        // Serialize the empty-phone case too; locking only existing rows cannot
        // prevent two simultaneous registrations from both becoming active.
        return Cache::lock('device-account-write', 30)->block(5, fn () => DB::transaction($callback, 3));
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Akses terbatas untuk Administrator.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $phonesConfig = DailyWorkReport::PHONES;
        $accounts = DeviceAccount::orderByDesc('is_active')
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->get();

        $phones = [];
        foreach ($phonesConfig as $key => $label) {
            $phoneAccounts = $accounts->where('phone_key', $key);
            $activeAccount = $phoneAccounts->firstWhere('is_active', true);

            $phones[] = [
                'key' => $key,
                'label' => $label,
                'active_email' => $activeAccount?->email,
                'active_account_name' => $activeAccount?->account_name,
                'activated_at' => $activeAccount?->activated_at?->format('Y-m-d H:i:s'),
                'total_accounts' => $phoneAccounts->count(),
            ];
        }

        return response()->json([
            'phones' => $phones,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $allowedPhones = array_keys(DailyWorkReport::PHONES);
        $data = $request->validate([
            'phone_key' => ['required', 'string', Rule::in($allowedPhones)],
            'email' => ['required', 'string', 'email', 'max:128'],
            'account_name' => ['nullable', 'string', 'max:128'],
        ]);

        $email = strtolower(trim($data['email']));
        $phoneKey = $data['phone_key'];
        $name = trim($data['account_name'] ?? '') ?: null;

        $account = $this->mutate(function () use ($phoneKey, $email, $name) {
            // Deactivate any currently active account on this phone
            DeviceAccount::where('phone_key', $phoneKey)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $entry = DeviceAccount::updateOrCreate(
                ['phone_key' => $phoneKey, 'email' => $email],
                [
                    'account_name' => $name,
                    'is_active' => true,
                    'activated_at' => now(),
                ]
            );

            // Register in studio accounts so Atlas recordings can be linked
            StudioAccount::firstOrCreate(
                ['email' => $email],
                ['name' => $name ?: $email, 'active' => true]
            );

            return $entry;
        });

        return response()->json([
            'success' => true,
            'message' => "Akun {$email} berhasil didaftarkan dan diaktifkan pada {$account->phone_label}.",
            'account' => $account,
        ], 201);
    }

    public function activate(Request $request, DeviceAccount $deviceAccount): JsonResponse
    {
        $this->ensureAdmin($request);

        $this->mutate(function () use ($deviceAccount) {
            $deviceAccount->refresh();
            // Deactivate other accounts for this phone
            DeviceAccount::where('phone_key', $deviceAccount->phone_key)
                ->where('id', '!=', $deviceAccount->id)
                ->update(['is_active' => false]);

            $deviceAccount->update([
                'is_active' => true,
                'activated_at' => now(),
            ]);

            StudioAccount::updateOrCreate(['email' => $deviceAccount->email], [
                'active' => true,
                'name' => $deviceAccount->account_name ?: $deviceAccount->email,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Akun {$deviceAccount->email} sekarang aktif di {$deviceAccount->phone_label}.",
            'account' => $deviceAccount->fresh(),
        ]);
    }

    public function update(Request $request, DeviceAccount $deviceAccount): JsonResponse
    {
        $this->ensureAdmin($request);
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'phone_key' => $request->input('phone_key') ?? $deviceAccount->phone_key,
        ]);

        $allowedPhones = array_keys(DailyWorkReport::PHONES);
        $data = $request->validate([
            'phone_key' => ['nullable', 'string', Rule::in($allowedPhones)],
            'email' => [
                'required',
                'string',
                'email',
                'max:128',
                Rule::unique('device_accounts')
                    ->where('phone_key', $request->input('phone_key', $deviceAccount->phone_key))
                    ->ignore($deviceAccount->id),
            ],
            'account_name' => ['nullable', 'string', 'max:128'],
        ]);

        $newEmail = strtolower(trim($data['email']));
        $phoneKey = $data['phone_key'] ?? $deviceAccount->phone_key;
        $name = trim($data['account_name'] ?? '') ?: null;

        $this->mutate(function () use ($deviceAccount, $phoneKey, $newEmail, $name) {
            $deviceAccount->refresh();
            if ($deviceAccount->is_active) {
                DeviceAccount::where('phone_key', $phoneKey)->whereKeyNot($deviceAccount->id)->update(['is_active' => false]);
            }
            $deviceAccount->update([
                'phone_key' => $phoneKey,
                'email' => $newEmail,
                'account_name' => $name,
            ]);

            // Studio accounts are shared references. Never rename an old email
            // or carry its external participant_id over to a different account.
            StudioAccount::firstOrCreate(['email' => $newEmail], [
                'name' => $name ?: $newEmail, 'active' => true,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Detail akun {$newEmail} berhasil diperbarui.",
            'account' => $deviceAccount->fresh(),
        ]);
    }

    public function destroy(Request $request, DeviceAccount $deviceAccount): JsonResponse
    {
        $this->ensureAdmin($request);

        $email = $deviceAccount->email;
        $label = $deviceAccount->phone_label;
        $this->mutate(function () use ($deviceAccount) {
            $deviceAccount->refresh();
            abort_if($deviceAccount->is_active, 422, 'Akun yang sedang aktif tidak dapat dihapus. Silakan aktifkan akun lain terlebih dahulu.');
            $deviceAccount->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Akun {$email} berhasil dihapus dari daftar {$label}.",
        ]);
    }
}
