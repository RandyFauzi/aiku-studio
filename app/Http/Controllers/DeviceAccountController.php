<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\StudioAccount;
use App\Models\StudioDevice;
use App\Services\KameraKitaPartnerClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function storePhone(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate(['label' => 'required|string|max:60']);
        $label = preg_replace('/\s+/u', ' ', trim($data['label']));
        $label = preg_replace('/^HP\s+/iu', '', $label);
        $key = Str::slug($label, '_');
        if ($key === '' || $key === 'sendiri' || strlen($key) > 64) {
            throw ValidationException::withMessages(['label' => 'Gunakan nama HP yang jelas, misalnya Merah atau Samsung A15. Nama Sendiri khusus HP pribadi.']);
        }
        $device = $this->mutate(function () use ($key, $label) {
            if (StudioDevice::where('key', $key)->exists()) {
                throw ValidationException::withMessages(['label' => 'Nama HP sudah terdaftar. Pilih perangkat tersebut dari daftar.']);
            }
            return StudioDevice::create(['key' => $key, 'label' => $label]);
        });
        return response()->json(['success' => true, 'phone' => $device->only(['key', 'label'])], 201);
    }

    public function index(Request $request, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);

        $phonesConfig = DailyWorkReport::phoneOptions();
        $accounts = DeviceAccount::orderByDesc('is_active')
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->get();

        $ownerAccess = $this->isStudioOwner($request);
        if (! $ownerAccess) {
            $accounts->each(fn (DeviceAccount $account) => $account->makeHidden([
                'kamerakita_partner_id', 'kamerakita_partner_code', 'kamerakita_status',
            ]));
        }

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
            'kamerakita' => [
                'configured' => $ownerAccess && (bool) config('services.kamerakita.url') && (bool) config('services.kamerakita.token'),
                'owner_access' => $ownerAccess,
                'parents' => $ownerAccess ? $this->safeParents($kamerakita) : [],
            ],
        ]);
    }

    private function safeParents(KameraKitaPartnerClient $client): array
    {
        try {
            $parents = $client->parents();
            $allowed = (array) config('services.kamerakita.allowed_parent_codes', ['KMK-847', 'KMK-210']);

            if (! empty($allowed)) {
                $allowedUpper = array_map('strtoupper', $allowed);
                $parents = array_values(array_filter($parents, function ($parent) use ($allowedUpper) {
                    $code = strtoupper((string) ($parent['mitra_id'] ?? ''));
                    return in_array($code, $allowedUpper, true);
                }));

                usort($parents, function ($a, $b) use ($allowedUpper) {
                    $posA = array_search(strtoupper((string) ($a['mitra_id'] ?? '')), $allowedUpper, true);
                    $posB = array_search(strtoupper((string) ($b['mitra_id'] ?? '')), $allowedUpper, true);
                    $posA = $posA === false ? PHP_INT_MAX : $posA;
                    $posB = $posB === false ? PHP_INT_MAX : $posB;
                    return $posA <=> $posB;
                });
            }

            return $parents;
        } catch (\Throwable) {
            return [];
        }
    }

    private function isStudioOwner(Request $request): bool
    {
        $ownerEmail = strtolower(trim((string) config('services.kamerakita.owner_email')));
        return $ownerEmail !== '' && strtolower((string) $request->user()?->email) === $ownerEmail;
    }

    public function store(Request $request, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);

        $allowedPhones = array_keys(DailyWorkReport::phoneOptions());
        $data = $request->validate([
            'phone_key' => ['required', 'string', Rule::in($allowedPhones)],
            'email' => ['required', 'string', 'email', 'max:128'],
            'account_name' => ['nullable', 'string', 'max:128'],
            'create_kamerakita_partner' => ['nullable', 'boolean'],
            'full_name' => [Rule::requiredIf($request->boolean('create_kamerakita_partner')), 'nullable', 'string', 'max:255'],
            'whatsapp_number' => [Rule::requiredIf($request->boolean('create_kamerakita_partner')), 'nullable', 'string', 'max:20'],
            'mitra_parent_id' => [Rule::requiredIf($request->boolean('create_kamerakita_partner')), 'nullable', 'uuid'],
        ]);

        if ($request->boolean('create_kamerakita_partner')) {
            abort_unless($this->isStudioOwner($request), 403, 'Integrasi mitra KameraKita khusus owner AIKU Studio.');
        }

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

            // Register / update in studio accounts with Catatan Akun or HP Label
            $phoneLabel = DailyWorkReport::phoneOptions()[$phoneKey] ?? ucfirst($phoneKey);
            StudioAccount::updateOrCreate(
                ['email' => $email],
                ['name' => $name ?: ('HP ' . $phoneLabel), 'active' => true]
            );

            return $entry;
        });

        if ($request->boolean('create_kamerakita_partner')) {
            try {
                $existing = $kamerakita->findByEmail($email);
                $partner = $existing ?: $kamerakita->create([
                    'full_name' => $data['full_name'],
                    'email' => $email,
                    'password' => Str::password(32),
                    'whatsapp_number' => $data['whatsapp_number'],
                    'mitra_parent_id' => $data['mitra_parent_id'],
                    'phone_key' => $phoneKey,
                    'account_name' => $name,
                ]);

                $account->update([
                    'kamerakita_partner_id' => $partner['id'] ?? null,
                    'kamerakita_partner_code' => $partner['mitra_id'] ?? null,
                    'kamerakita_status' => $partner['status'] ?? 'active',
                ]);
            } catch (\Throwable $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun HP tersimpan, tetapi pembuatan mitra KameraKita gagal: '.$exception->getMessage(),
                    'account' => $account->fresh(),
                ], 502);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Akun {$email} berhasil didaftarkan dan diaktifkan pada {$account->phone_label}.",
            'account' => $account->fresh(),
        ], 201);
    }

    public function linkKamerakitaPartner(Request $request, DeviceAccount $deviceAccount, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($this->isStudioOwner($request), 403, 'Integrasi mitra KameraKita khusus owner AIKU Studio.');

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'mitra_parent_id' => 'required|uuid',
        ]);

        $email = $deviceAccount->email;
        $phoneKey = $deviceAccount->phone_key;
        $name = trim($data['full_name']);

        try {
            $existing = $kamerakita->findByEmail($email);
            $partner = $existing ?: $kamerakita->create([
                'full_name' => $data['full_name'],
                'email' => $email,
                'password' => Str::password(32),
                'whatsapp_number' => $data['whatsapp_number'],
                'mitra_parent_id' => $data['mitra_parent_id'],
                'phone_key' => $phoneKey,
                'account_name' => $name,
            ]);

            $deviceAccount->update([
                'kamerakita_partner_id' => $partner['id'] ?? null,
                'kamerakita_partner_code' => $partner['mitra_id'] ?? null,
                'kamerakita_status' => $partner['status'] ?? 'active',
                'account_name' => $name ?: $deviceAccount->account_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Akun {$email} berhasil ditautkan ke mitra KameraKita ({$deviceAccount->kamerakita_partner_code}).",
                'account' => $deviceAccount->fresh(),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menautkan ke mitra KameraKita: '.$exception->getMessage(),
            ], 502);
        }
    }

    public function activate(Request $request, DeviceAccount $deviceAccount, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);
        if ($deviceAccount->kamerakita_partner_id) {
            abort_unless($this->isStudioOwner($request), 403, 'Integrasi mitra KameraKita khusus owner AIKU Studio.');
        }

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

            $phoneLabel = DailyWorkReport::phoneOptions()[$deviceAccount->phone_key] ?? ucfirst($deviceAccount->phone_key);
            StudioAccount::updateOrCreate(['email' => $deviceAccount->email], [
                'active' => true,
                'name' => $deviceAccount->account_name ?: ('HP ' . $phoneLabel),
            ]);
        });

        if ($deviceAccount->kamerakita_partner_id) {
            try {
                $partner = $kamerakita->setStatus($deviceAccount->kamerakita_partner_id, 'active');
                $deviceAccount->update(['kamerakita_status' => $partner['status'] ?? 'active']);
            } catch (\Throwable) {
                return response()->json(['message' => 'Akun HP aktif, tetapi status mitra KameraKita gagal diperbarui.'], 502);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Akun {$deviceAccount->email} sekarang aktif di {$deviceAccount->phone_label}.",
            'account' => $deviceAccount->fresh(),
        ]);
    }

    public function setKamerakitaStatus(Request $request, DeviceAccount $deviceAccount, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($this->isStudioOwner($request), 403, 'Integrasi mitra KameraKita khusus owner AIKU Studio.');
        abort_unless($deviceAccount->kamerakita_partner_id, 404, 'Akun ini belum tertaut ke mitra KameraKita.');
        $data = $request->validate(['status' => 'required|in:active,inactive,suspended']);

        try {
            $partner = $kamerakita->setStatus($deviceAccount->kamerakita_partner_id, $data['status']);
            $deviceAccount->update(['kamerakita_status' => $partner['status'] ?? $data['status']]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'Status mitra KameraKita gagal diperbarui: '.$exception->getMessage()], 502);
        }

        return response()->json(['success' => true, 'message' => 'Status mitra KameraKita diperbarui.', 'account' => $deviceAccount->fresh()]);
    }

    public function showKamerakitaPartner(Request $request, DeviceAccount $deviceAccount, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($this->isStudioOwner($request), 403, 'Integrasi mitra KameraKita khusus owner AIKU Studio.');
        abort_unless($deviceAccount->kamerakita_partner_id, 404, 'Akun ini belum tertaut ke mitra KameraKita.');

        try {
            return response()->json(['data' => $kamerakita->partner($deviceAccount->kamerakita_partner_id)]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'Profil mitra gagal dimuat: '.$exception->getMessage()], 502);
        }
    }

    public function updateKamerakitaPartner(Request $request, DeviceAccount $deviceAccount, KameraKitaPartnerClient $kamerakita): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($this->isStudioOwner($request), 403, 'Integrasi mitra KameraKita khusus owner AIKU Studio.');
        abort_unless($deviceAccount->kamerakita_partner_id, 404, 'Akun ini belum tertaut ke mitra KameraKita.');
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'mitra_parent_id' => 'required|uuid',
        ]);

        try {
            $partner = $kamerakita->update($deviceAccount->kamerakita_partner_id, $data);
            $deviceAccount->update(['account_name' => $data['full_name']]);
            return response()->json(['success' => true, 'message' => 'Profil mitra KameraKita berhasil diperbarui.', 'data' => $partner]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'Profil mitra gagal diperbarui: '.$exception->getMessage()], 502);
        }
    }

    public function update(Request $request, DeviceAccount $deviceAccount): JsonResponse
    {
        $this->ensureAdmin($request);
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'phone_key' => $request->input('phone_key') ?? $deviceAccount->phone_key,
        ]);

        $allowedPhones = array_keys(DailyWorkReport::phoneOptions());
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

        if ($deviceAccount->kamerakita_partner_id && $data['email'] !== $deviceAccount->email) {
            abort(422, 'Email akun mitra terhubung tidak dapat diganti dari daftar HP agar identitas KameraKita tetap konsisten.');
        }

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
            $phoneLabel = DailyWorkReport::phoneOptions()[$phoneKey] ?? ucfirst($phoneKey);
            StudioAccount::updateOrCreate(['email' => $newEmail], [
                'name' => $name ?: ('HP ' . $phoneLabel),
                'active' => true,
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

        abort_if($deviceAccount->kamerakita_partner_id, 422, 'Akun ini terhubung ke mitra KameraKita. Nonaktifkan mitra dan simpan tautannya; jangan hapus riwayat HP.');

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
