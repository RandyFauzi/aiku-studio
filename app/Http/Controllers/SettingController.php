<?php

namespace App\Http\Controllers;

use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
        $data = $request->validate(['start_time' => 'required|date_format:H:i', 'charging_duration_min' => 'required|integer|min:0|max:1440',
            'session_duration_min' => 'required|integer|min:1|max:1440', 'sessions_count' => 'required|integer|min:1|max:50',
            'tools_count' => 'required|integer|min:1|max:1000', 'hp_per_tool' => 'required|integer|min:1|max:100',
            'hourly_rate' => 'required|numeric|min:0', 'qa_yield_rate' => 'required|numeric|min:0|max:100',
            'day_off' => 'required|in:none,monday,tuesday,wednesday,thursday,friday,saturday,sunday', 'karyawan' => 'present|array|max:1000',
            'karyawan.*.name' => 'required|string|max:255|distinct:ignore_case',
            'karyawan.*.short' => 'sometimes|nullable|string|max:255', 'karyawan.*.role' => 'sometimes|nullable|string|max:64',
            'karyawan.*.alat' => 'required|string|regex:/^Alat [1-9][0-9]*$/',
            'karyawan.*.sessions' => 'present|array|max:50',
            'karyawan.*.sessions.*' => 'integer|min:1|max:'.(int) $request->input('sessions_count')]);
        $occupied = [];
        foreach ($data['karyawan'] as $index => &$worker) {
            $sessions = array_map('intval', $worker['sessions']);
            if (count(array_unique($sessions)) !== count($sessions)) {
                throw ValidationException::withMessages(["karyawan.$index.sessions" => 'Sesi yang sama tidak boleh dipilih dua kali.']);
            }
            if ((int) substr($worker['alat'], 5) > $data['tools_count']) {
                throw ValidationException::withMessages(["karyawan.$index.alat" => 'Alat yang dipilih tidak tersedia.']);
            }
            foreach ($sessions as $session) {
                $slot = $worker['alat'].':'.$session;
                if (isset($occupied[$slot])) {
                    throw ValidationException::withMessages(["karyawan.$index.sessions" => $worker['alat'].' sesi '.$session.' sudah digunakan '.$occupied[$slot].'.']);
                }
                $occupied[$slot] = $worker['name'];
            }
            sort($sessions);
            $worker['sessions'] = $sessions;
            $worker['daily_work_hrs'] = count($sessions) * $data['session_duration_min'] / 60;
            $worker['daily_prod_hrs'] = $worker['daily_work_hrs'] * $data['hp_per_tool'];
        }
        unset($worker);
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
