<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\StudioRecording;
use App\Models\User;
use App\Services\ContributorIdentity;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyWorkReportController extends Controller
{
    private function contributor(Request $request): Contributor
    {
        return app(ContributorIdentity::class)->resolve($request->user());
    }

    public function mine(Request $request)
    {
        $this->contributor($request);
        $data = $request->validate(['month' => 'required|date_format:Y-m']);
        $start = CarbonImmutable::createFromFormat('!Y-m', $data['month'], 'Asia/Makassar');
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

        return response()->json([
            'today' => $today,
            'phone_options' => DailyWorkReport::PHONES,
            'today_report' => DailyWorkReport::where('user_id', $request->user()->id)->where('work_date', $today)->first(),
            'reports' => DailyWorkReport::where('user_id', $request->user()->id)->whereBetween('work_date', [$start->toDateString(), $start->endOfMonth()->toDateString()])->orderByDesc('work_date')->get(),
        ]);
    }

    public function save(Request $request)
    {
        $person = $this->contributor($request);
        $allowedKeys = array_keys(DailyWorkReport::PHONES);

        $fields = $request->validate([
            'work_date' => 'required|date_format:Y-m-d',
            'expected_revision' => 'required|integer|min:0',
            'total_duration_minutes' => 'nullable|integer|min:1|max:8640',
            'phones' => 'nullable|array|max:6',
            'phones.*' => ['string', 'distinct', Rule::in($allowedKeys)],
            'phone_durations' => ['nullable', 'array:'.implode(',', $allowedKeys), 'max:6'],
            'phone_durations.*' => 'integer|min:1|max:1440',
        ]);

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        if ($fields['work_date'] !== $today) {
            throw ValidationException::withMessages(['work_date' => 'Laporan hanya dapat dikirim atau diubah untuk hari ini (WITA). Muat ulang jika tanggal sudah berganti.']);
        }

        // Process phone_durations or fallback to phones + total_duration_minutes
        $phoneDurations = null;
        if (! empty($fields['phone_durations'])) {
            $cleanedDurations = [];
            foreach ($fields['phone_durations'] as $key => $mins) {
                if (in_array($key, $allowedKeys, true) && (int) $mins > 0) {
                    $cleanedDurations[$key] = (int) $mins;
                }
            }
            if (empty($cleanedDurations)) {
                throw ValidationException::withMessages(['phone_durations' => 'Isi menit durasi untuk setiap HP yang kamu pilih (minimal 1 menit).']);
            }
            $phoneDurations = $cleanedDurations;
            $phones = array_values(array_unique(array_keys($cleanedDurations)));
            $totalMinutes = array_sum($cleanedDurations);
        } elseif (! empty($fields['phones'])) {
            $phones = array_values($fields['phones']);
            $totalMinutes = (int) ($fields['total_duration_minutes'] ?? 0);
            if ($totalMinutes <= 0 || $totalMinutes > 8640) {
                throw ValidationException::withMessages(['total_duration_minutes' => 'Total durasi harus antara 1 sampai 8640 menit.']);
            }
        } else {
            throw ValidationException::withMessages(['phones' => 'Pilih minimal satu HP yang digunakan hari ini.']);
        }

        $row = DB::transaction(function () use ($request, $person, $fields, $today, $phones, $totalMinutes, $phoneDurations) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $row = DailyWorkReport::where('user_id', $request->user()->id)->where('work_date', $today)->first();
            abort_if(($row?->revision ?? 0) !== $fields['expected_revision'], 409, 'Laporan sudah berubah di sesi lain. Muat ulang data sebelum menyimpan.');
            $row ??= new DailyWorkReport(['user_id' => $request->user()->id, 'contributor_id' => $person->id, 'work_date' => $today, 'revision' => 0]);

            $attributes = [
                'contributor_name' => $person->nama,
                'total_duration_minutes' => $totalMinutes,
                'phones' => $phones,
            ];

            if (Schema::hasColumn('daily_work_reports', 'phone_durations')) {
                $attributes['phone_durations'] = $phoneDurations;
            }

            $row->fill($attributes);
            $row->revision++;
            $row->save();

            return $row;
        }, 3);

        return response()->json(['success' => true, 'report' => $row]);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d']);
        $rows = DailyWorkReport::where('work_date', $data['date'])->orderBy('contributor_name')->get();

        $phonesConfig = DailyWorkReport::PHONES;
        $deviceAccounts = DeviceAccount::all();
        $phonesWithActive = [];
        foreach ($phonesConfig as $key => $label) {
            $phoneAccounts = $deviceAccounts->where('phone_key', $key);
            $activeAccount = $phoneAccounts->firstWhere('is_active', true);
            $phonesWithActive[$key] = [
                'key' => $key,
                'label' => $label,
                'active_account_name' => $activeAccount?->account_name ?: ($activeAccount?->email ? explode('@', $activeAccount->email)[0] : null),
                'active_email' => $activeAccount?->email,
                'total_accounts' => $phoneAccounts->count(),
                'accounts' => $phoneAccounts->map(fn ($a) => [
                    'id' => $a->id,
                    'account_name' => $a->account_name,
                    'email' => $a->email,
                    'is_active' => (bool) $a->is_active,
                ])->values()->all(),
            ];
        }

        return response()->json([
            'reports' => $rows,
            'phone_options' => DailyWorkReport::PHONES,
            'phones_info' => $phonesWithActive,
            'total_duration_minutes' => $rows->sum('total_duration_minutes'),
            'contributors' => Contributor::orderBy('nama')->get(['id', 'nama', 'email', 'role']),
        ]);
    }

    public function assistMatches(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d']);
        $workDate = $data['date'];

        $witaStart = CarbonImmutable::parse($workDate, 'Asia/Makassar')->startOfDay()->utc();
        $witaEnd = CarbonImmutable::parse($workDate, 'Asia/Makassar')->endOfDay()->utc();

        $reports = DailyWorkReport::where('work_date', $workDate)->orderBy('contributor_name')->get();
        $deviceAccounts = DeviceAccount::all();

        $assistData = [];

        foreach ($reports as $report) {
            $contributorId = $report->contributor_id;
            $workerName = $report->contributor_name;
            $phoneDetails = [];

            // If phone_durations exists, use it; otherwise fallback from phones array
            $durations = $report->phone_durations;
            if (empty($durations) && ! empty($report->phones)) {
                $durations = [];
                $evenSplit = count($report->phones) > 0 ? (int) round($report->total_duration_minutes / count($report->phones)) : 0;
                foreach ($report->phones as $pk) {
                    $durations[$pk] = $evenSplit;
                }
            }

            foreach ($durations as $phoneKey => $reportedMinutes) {
                $phoneLabel = DailyWorkReport::PHONES[$phoneKey] ?? ucfirst($phoneKey);

                $phoneAccounts = $deviceAccounts->where('phone_key', $phoneKey);
                $activeAccount = $phoneAccounts->firstWhere('is_active', true);
                $activeAccountName = $activeAccount?->account_name ?: ($activeAccount?->email ? explode('@', $activeAccount->email)[0] : null);
                $activeEmail = $activeAccount?->email;

                // Get all emails linked to this phone
                $phoneEmails = $phoneAccounts->pluck('email')->unique()->all();

                if (empty($phoneEmails)) {
                    $phoneDetails[] = [
                        'phone_key' => $phoneKey,
                        'phone_label' => $phoneLabel,
                        'active_account_name' => $activeAccountName,
                        'active_email' => $activeEmail,
                        'reported_minutes' => (int) $reportedMinutes,
                        'emails' => [],
                        'status' => 'no_device_account',
                        'unassigned_count' => 0,
                        'unassigned_minutes' => 0,
                        'already_assigned_count' => 0,
                        'already_assigned_minutes' => 0,
                        'difference_minutes' => 0,
                        'candidate_record_ids' => [],
                        'my_record_ids' => [],
                        'tasks' => [],
                    ];

                    continue;
                }

                // Query recordings on that WITA day for these emails
                $recs = StudioRecording::whereIn('participant_email', $phoneEmails)
                    ->whereBetween('recorded_at', [$witaStart, $witaEnd])
                    ->orderBy('recorded_at')
                    ->get();

                $unassignedRecs = $recs->filter(fn ($r) => empty($r->assigned_worker_name));
                $myAssignedRecs = $recs->filter(fn ($r) => $r->assigned_contributor_id == $contributorId || strtolower(trim($r->assigned_worker_name ?? '')) === strtolower(trim($workerName)));
                $otherAssignedRecs = $recs->filter(fn ($r) => ! empty($r->assigned_worker_name) && $r->assigned_contributor_id != $contributorId && strtolower(trim($r->assigned_worker_name ?? '')) !== strtolower(trim($workerName)));

                $unassignedMinutes = round($unassignedRecs->sum('duration_min'), 1);
                $myAssignedMinutes = round($myAssignedRecs->sum('duration_min'), 1);
                $difference = round($unassignedMinutes - (int) $reportedMinutes, 1);

                $status = 'no_bot_data';
                if ($myAssignedRecs->count() > 0 && $unassignedRecs->count() === 0) {
                    $status = 'fully_verified';
                } elseif ($unassignedRecs->count() > 0) {
                    if (abs($difference) <= 15) {
                        $status = 'exact_match';
                    } elseif (abs($difference) <= 45) {
                        $status = 'close_match';
                    } elseif ($difference > 45) {
                        $status = 'over_bot';
                    } else {
                        $status = 'under_bot';
                    }
                }

                $tasksSample = $unassignedRecs->concat($myAssignedRecs)->take(20)->map(fn ($r) => [
                    'record_id' => $r->record_id,
                    'task_title' => $r->task_title ?: 'Tanpa Judul',
                    'recorded_utc' => $r->recorded_utc,
                    'duration_min' => round($r->duration_min, 1),
                    'billable_min' => round($r->billable_min, 1),
                    'processing_status' => $r->processing_status,
                    'assigned_worker_name' => $r->assigned_worker_name,
                    'is_assigned_to_me' => ($r->assigned_contributor_id == $contributorId || strtolower(trim($r->assigned_worker_name ?? '')) === strtolower(trim($workerName))),
                ])->values();

                $phoneDetails[] = [
                    'phone_key' => $phoneKey,
                    'phone_label' => $phoneLabel,
                    'active_account_name' => $activeAccountName,
                    'active_email' => $activeEmail,
                    'reported_minutes' => (int) $reportedMinutes,
                    'emails' => $phoneEmails,
                    'status' => $status,
                    'unassigned_count' => $unassignedRecs->count(),
                    'unassigned_minutes' => $unassignedMinutes,
                    'already_assigned_count' => $myAssignedRecs->count(),
                    'already_assigned_minutes' => $myAssignedMinutes,
                    'other_assigned_count' => $otherAssignedRecs->count(),
                    'difference_minutes' => $difference,
                    'candidate_record_ids' => $unassignedRecs->pluck('record_id')->values()->all(),
                    'my_record_ids' => $myAssignedRecs->pluck('record_id')->values()->all(),
                    'tasks' => $tasksSample,
                ];
            }

            $assistData[] = [
                'report_id' => $report->id,
                'contributor_id' => $contributorId,
                'contributor_name' => $workerName,
                'work_date' => $workDate,
                'total_reported_minutes' => $report->total_duration_minutes,
                'phones' => $phoneDetails,
            ];
        }

        return response()->json([
            'date' => $workDate,
            'assist_data' => $assistData,
        ]);
    }

    public function assistAssign(Request $request)
    {
        $data = $request->validate([
            'record_ids' => 'required|array|min:1|max:1000',
            'record_ids.*' => 'required|string|max:64',
            'contributor_id' => 'required|integer|exists:contributors,id',
            'worker_name' => 'required|string|max:100',
        ]);

        $person = Contributor::findOrFail($data['contributor_id']);
        $updated = StudioRecording::whereIn('record_id', $data['record_ids'])
            ->where(fn ($query) => $query->whereNull('assigned_worker_name')->orWhere('assigned_worker_name', ''))
            ->whereNull('assigned_contributor_id')->update([
                'assigned_contributor_id' => $data['contributor_id'],
                'assigned_worker_name' => $person->nama,
                'assigned_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menandai {$updated} rekaman ke {$data['worker_name']}.",
            'updated_count' => $updated,
        ]);
    }

    public function assistUnassign(Request $request)
    {
        $data = $request->validate([
            'record_ids' => 'required|array|min:1|max:1000',
            'record_ids.*' => 'required|string|max:64',
        ]);

        $updated = StudioRecording::whereIn('record_id', $data['record_ids'])->update([
            'assigned_contributor_id' => null,
            'assigned_worker_name' => null,
            'assigned_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil membatalkan penugasan {$updated} rekaman.",
            'updated_count' => $updated,
        ]);
    }

    public function adminStore(Request $request)
    {
        $allowedKeys = array_keys(DailyWorkReport::PHONES);

        $fields = $request->validate([
            'contributor_id' => 'required|integer|exists:contributors,id',
            'work_date' => 'required|date_format:Y-m-d',
            'phones' => 'nullable|array|max:6',
            'phones.*' => ['string', 'distinct', Rule::in($allowedKeys)],
            'phone_durations' => ['nullable', 'array:'.implode(',', $allowedKeys), 'max:6'],
            'phone_durations.*' => 'integer|min:1|max:1440',
            'total_duration_minutes' => 'nullable|integer|min:1|max:8640',
        ]);

        $contributor = Contributor::findOrFail($fields['contributor_id']);

        $user = User::where('contributor_id', $contributor->id)->first();
        if (! $user) {
            $user = app(\App\Services\ContributorAccess::class)->sync($contributor);
        }
        if (! $user) {
            $email = $contributor->email ?: ('contributor_'.$contributor->id.'@aikustudio.id');
            $user = User::firstOrCreate(
                ['contributor_id' => $contributor->id],
                ['name' => $contributor->nama, 'email' => $email, 'role' => 'contributor', 'password' => 'password', 'is_active' => true]
            );
        }

        $phoneDurations = null;
        if (! empty($fields['phone_durations'])) {
            $cleaned = [];
            foreach ($fields['phone_durations'] as $key => $mins) {
                if (in_array($key, $allowedKeys, true) && (int) $mins > 0) {
                    $cleaned[$key] = (int) $mins;
                }
            }
            if (empty($cleaned)) {
                throw ValidationException::withMessages(['phone_durations' => 'Isi menit durasi untuk setiap HP yang dipilih.']);
            }
            $phoneDurations = $cleaned;
            $phones = array_values(array_unique(array_keys($cleaned)));
            $totalMinutes = array_sum($cleaned);
        } elseif (! empty($fields['phones'])) {
            $phones = array_values($fields['phones']);
            $totalMinutes = (int) ($fields['total_duration_minutes'] ?? 0);
            if ($totalMinutes <= 0 || $totalMinutes > 8640) {
                throw ValidationException::withMessages(['total_duration_minutes' => 'Total durasi harus antara 1 sampai 8640 menit.']);
            }
        } else {
            throw ValidationException::withMessages(['phones' => 'Pilih minimal satu HP.']);
        }

        $existing = DailyWorkReport::where('contributor_id', $contributor->id)
            ->where('work_date', $fields['work_date'])
            ->first();

        if ($existing) {
            $existing->phones = $phones;
            $existing->total_duration_minutes = $totalMinutes;
            if (Schema::hasColumn('daily_work_reports', 'phone_durations')) {
                $existing->phone_durations = $phoneDurations;
            }
            $existing->revision++;
            $existing->save();

            return response()->json([
                'success' => true,
                'message' => 'Laporan kerja tanggal '.$fields['work_date'].' untuk '.$contributor->nama.' berhasil diperbarui.',
                'report' => $existing,
            ]);
        }

        $attributes = [
            'user_id' => $user->id,
            'contributor_id' => $contributor->id,
            'contributor_name' => $contributor->nama,
            'work_date' => $fields['work_date'],
            'total_duration_minutes' => $totalMinutes,
            'phones' => $phones,
            'revision' => 1,
        ];

        if (Schema::hasColumn('daily_work_reports', 'phone_durations')) {
            $attributes['phone_durations'] = $phoneDurations;
        }

        $report = DailyWorkReport::create($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Laporan kerja tanggal '.$fields['work_date'].' untuk '.$contributor->nama.' berhasil dibuat.',
            'report' => $report,
        ]);
    }

    public function adminUpdate(Request $request, DailyWorkReport $report)
    {
        $allowedKeys = array_keys(DailyWorkReport::PHONES);

        $fields = $request->validate([
            'phones' => 'nullable|array|max:6',
            'phones.*' => ['string', 'distinct', Rule::in($allowedKeys)],
            'phone_durations' => ['nullable', 'array:'.implode(',', $allowedKeys), 'max:6'],
            'phone_durations.*' => 'integer|min:1|max:1440',
            'total_duration_minutes' => 'nullable|integer|min:1|max:8640',
        ]);

        $phoneDurations = null;
        if (! empty($fields['phone_durations'])) {
            $cleaned = [];
            foreach ($fields['phone_durations'] as $key => $mins) {
                if (in_array($key, $allowedKeys, true) && (int) $mins > 0) {
                    $cleaned[$key] = (int) $mins;
                }
            }
            if (empty($cleaned)) {
                throw ValidationException::withMessages(['phone_durations' => 'Isi menit durasi untuk setiap HP yang dipilih.']);
            }
            $phoneDurations = $cleaned;
            $phones = array_values(array_unique(array_keys($cleaned)));
            $totalMinutes = array_sum($cleaned);
        } elseif (! empty($fields['phones'])) {
            $phones = array_values($fields['phones']);
            $totalMinutes = (int) ($fields['total_duration_minutes'] ?? 0);
            if ($totalMinutes <= 0 || $totalMinutes > 8640) {
                throw ValidationException::withMessages(['total_duration_minutes' => 'Total durasi harus antara 1 sampai 8640 menit.']);
            }
        } else {
            throw ValidationException::withMessages(['phones' => 'Pilih minimal satu HP.']);
        }

        $report->phones = $phones;
        $report->total_duration_minutes = $totalMinutes;
        if (Schema::hasColumn('daily_work_reports', 'phone_durations')) {
            $report->phone_durations = $phoneDurations;
        }
        $report->revision++;
        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'Laporan kerja berhasil diperbarui.',
            'report' => $report,
        ]);
    }

    public function adminDestroy(DailyWorkReport $report)
    {
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan kerja berhasil dihapus.',
        ]);
    }
}
