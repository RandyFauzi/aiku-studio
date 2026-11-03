<?php

namespace App\Http\Controllers;

use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use App\Services\ShiftConfiguration;
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

    public function shifts()
    {
        return response()->json(['success' => true, 'config' => ShiftSetting::where('setting_key', 'main_shift_config')->value('setting_value') ?? config('shifts')]);
    }

    public function saveShifts(Request $request)
    {
        $data = app(ShiftConfiguration::class)->normalize($request->all());
        ShiftSetting::updateOrCreate(['setting_key' => 'main_shift_config'], ['setting_value' => $data]);

        return response()->json(['success' => true, 'config' => $data, 'message' => 'Pengaturan shift berhasil disimpan!']);
    }

    public function accounts()
    {
        $accounts = StudioAccount::orderBy('id')->get();

        return response()->json(['success' => true, 'count' => $accounts->count(), 'accounts' => $accounts]);
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
