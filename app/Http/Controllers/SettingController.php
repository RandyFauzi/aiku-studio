<?php

namespace App\Http\Controllers;

use App\Models\DeviceAccount;
use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use App\Services\ShiftConfiguration;
use App\Services\ShiftPatterns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function finance()
    {
        return response()->json(['settings' => ShiftSetting::where('setting_key', 'finance_config')->value('setting_value')]);
    }

    public function saveFinance(Request $request)
    {
        $data = $request->validate(['rateMode' => 'required|in:USD,IDR', 'clientRateUsd' => 'required|numeric|min:0',
            'kursUsd' => 'required|numeric|min:0', 'clientRateIdr' => 'required|numeric|min:0', 'operationalExpense' => 'required|numeric|min:0']);
        ShiftSetting::updateOrCreate(['setting_key' => 'finance_config'], ['setting_value' => $data]);

        return response()->json(['success' => true]);
    }

    public function shifts(Request $request, ShiftPatterns $patterns)
    {
        if ($request->has('periodId')) {
            $request->validate(['periodId' => 'required|string|max:64']);
            $period = $patterns->period($request->query('periodId'));

            return response()->json(['success' => true, 'period' => $period, ...$patterns->read($period)]);
        }

        return response()->json(['success' => true, 'config' => ShiftSetting::where('setting_key', 'main_shift_config')->value('setting_value') ?? config('shifts')]);
    }

    public function saveShifts(Request $request)
    {
        if ($request->has('periodId')) {
            $fields = $request->validate(['periodId' => 'required|string|max:64', 'expected_revision' => 'required|integer|min:0', 'config' => 'required|array']);
            $patterns = app(ShiftPatterns::class);
            $period = $patterns->period($fields['periodId']);
            $config = app(ShiftConfiguration::class)->normalize($fields['config'], true);

            return response()->json(['success' => true, 'period' => $period, ...$patterns->save($period, $config, $fields['expected_revision'])]);
        }
        $data = app(ShiftConfiguration::class)->normalize($request->all());
        ShiftSetting::updateOrCreate(['setting_key' => 'main_shift_config'], ['setting_value' => $data]);

        return response()->json(['success' => true, 'config' => $data, 'message' => 'Pengaturan shift berhasil disimpan!']);
    }

    public function accounts()
    {
        $deviceAccounts = DeviceAccount::orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
        $devByEmail = $deviceAccounts->keyBy(fn ($item) => strtolower(trim($item->email)));

        $accounts = StudioAccount::orderBy('id')->get()->map(function ($acc) use ($devByEmail) {
            $cleanEmail = strtolower(trim($acc->email));
            $dev = $devByEmail->get($cleanEmail);

            $catatanAkun = ($dev && trim((string) $dev->account_name) !== '') ? trim($dev->account_name) : null;
            $phoneLabel = $dev ? ('HP ' . $dev->phone_label) : null;

            // Prioritaskan Catatan Akun dari HP & Akun Studio
            if ($catatanAkun) {
                $displayName = $catatanAkun;
            } elseif ($phoneLabel) {
                if (preg_match('/aiku[a-z]+(\d+)/i', $cleanEmail, $m)) {
                    $displayName = $phoneLabel . ' ' . $m[1];
                } else {
                    $displayName = $phoneLabel;
                }
            } else {
                $rawName = trim((string) $acc->name);
                if (is_numeric($rawName) || preg_match('/^\d+$/', $rawName)) {
                    if (preg_match('/aiku([a-z]+)(\d*)/i', $cleanEmail, $matches)) {
                        $pName = ucfirst($matches[1]);
                        $num = $matches[2] ? ' ' . $matches[2] : '';
                        $displayName = 'HP ' . $pName . $num;
                    } else {
                        $displayName = 'Akun #' . $rawName;
                    }
                } elseif ($rawName !== '' && ! str_contains($rawName, '@')) {
                    $displayName = $rawName;
                } else {
                    $displayName = explode('@', $acc->email)[0];
                }
            }

            // Jika di DB studio_accounts namanya masih angka gundul (seperti '5'), perbarui
            if (is_numeric($acc->name) || preg_match('/^\d+$/', (string) $acc->name)) {
                try {
                    StudioAccount::where('id', $acc->id)->update(['name' => $displayName]);
                } catch (\Throwable $e) {
                    // Ignore DB errors
                }
            }

            return [
                'id' => $acc->id,
                'email' => $acc->email,
                'name' => $displayName,
                'raw_name' => $acc->name,
                'account_name' => $catatanAkun,
                'phone_key' => $dev?->phone_key,
                'phone_label' => $dev?->phone_label,
                'active' => (bool) $acc->active,
                'participant_id' => $acc->participant_id,
            ];
        });

        // Sertakan juga akun dari DeviceAccount jika ada yang belum terdaftar di StudioAccount
        $existingEmails = $accounts->pluck('email')->map(fn ($e) => strtolower(trim($e)))->all();
        $extraAccounts = collect();
        foreach ($deviceAccounts as $dev) {
            $cleanDevEmail = strtolower(trim($dev->email));
            if (! in_array($cleanDevEmail, $existingEmails, true)) {
                $catatanAkun = trim((string) $dev->account_name) ?: null;
                $phoneLabel = 'HP ' . $dev->phone_label;
                if ($catatanAkun) {
                    $displayName = $catatanAkun;
                } else {
                    if (preg_match('/aiku[a-z]+(\d+)/i', $cleanDevEmail, $m)) {
                        $displayName = $phoneLabel . ' ' . $m[1];
                    } else {
                        $displayName = $phoneLabel;
                    }
                }

                $extraAccounts->push([
                    'id' => null,
                    'email' => $dev->email,
                    'name' => $displayName,
                    'raw_name' => $catatanAkun,
                    'account_name' => $catatanAkun,
                    'phone_key' => $dev->phone_key,
                    'phone_label' => $dev->phone_label,
                    'active' => (bool) $dev->is_active,
                    'participant_id' => null,
                ]);
                $existingEmails[] = $cleanDevEmail;
            }
        }

        $allAccounts = $accounts->concat($extraAccounts)->values();

        return response()->json(['success' => true, 'count' => $allAccounts->count(), 'accounts' => $allAccounts]);
    }

    public function addAccounts(Request $request)
    {
        $data = $request->validate(['email' => 'nullable|string|max:255', 'raw_text' => 'nullable|string|max:30000']);
        $added = [];
        foreach (preg_split('/[\r\n,;]+/', ($data['email'] ?? '')."\n".($data['raw_text'] ?? '')) as $email) {
            $email = strtolower(trim($email));
            if (! $email) {
                continue;
            }
            if (! str_contains($email, '@')) {
                $email .= '@'.config('payroll.account_domain');
            }
            Validator::make(['email' => $email], ['email' => 'required|email|max:128'])->validate();
            $account = StudioAccount::firstOrCreate(['email' => $email], ['name' => explode('@', $email)[0], 'active' => true]);
            if ($account->wasRecentlyCreated) {
                $added[] = $account;
            }
        }

        return response()->json(['success' => true, 'addedCount' => count($added), 'added' => $added, 'totalCount' => StudioAccount::count(), 'message' => 'Berhasil menambahkan '.count($added).' akun studio baru.']);
    }

    public function deleteAccount(string $email)
    {
        StudioAccount::where('email', strtolower($email))->firstOrFail()->delete();

        return response()->json(['success' => true, 'totalCount' => StudioAccount::count()]);
    }

    public function toggleAccount(string $email)
    {
        $account = StudioAccount::where('email', strtolower($email))->firstOrFail();
        $account->update(['active' => ! $account->active]);

        return response()->json(['success' => true, 'active' => $account->active, 'email' => $account->email]);
    }
}
