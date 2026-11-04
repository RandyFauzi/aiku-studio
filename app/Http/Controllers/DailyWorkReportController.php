<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\DeviceAccount;
use App\Models\StudioRecording;
use App\Models\User;
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
        abort_unless($request->user()->role === 'contributor', 403);

        return Contributor::findOrFail($request->user()->contributor_id);
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
            'phone_durations' => 'nullable|array|max:6',
            'phone_durations.*' => 'integer|min:1|max:1440',
        ]);

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        if ($fields['work_date'] !== $today) {
            throw ValidationException::withMessages(['work_date' => 'Laporan hanya dapat dikirim atau diubah untuk hari ini (WITA). Muat ulang jika tanggal sudah berganti.']);
        }

        // Process phone_durations or fallback to phones + total_duration_minutes
        $phoneDurations = null;
        if (!empty($fields['phone_durations'])) {
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
        } elseif (!empty($fields['phones'])) {
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

        return response()->json([
            'reports' => $rows,
            'phone_options' => DailyWorkReport::PHONES,
            'total_duration_minutes' => $rows->sum('total_duration_minutes'),
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
            if (empty($durations) && !empty($report->phones)) {
                $durations = [];
                $evenSplit = count($report->phones) > 0 ? (int) round($report->total_duration_minutes / count($report->phones)) : 0;
                foreach ($report->phones as $pk) {
                    $durations[$pk] = $evenSplit;
                }
            }

            foreach ($durations as $phoneKey => $reportedMinutes) {
                $phoneLabel = DailyWorkReport::PHONES[$phoneKey] ?? ucfirst($phoneKey);

                // Get all emails linked to this phone
                $phoneEmails = $deviceAccounts->where('phone_key', $phoneKey)->pluck('email')->unique()->all();

                if (empty($phoneEmails)) {
                    $phoneDetails[] = [
                        'phone_key' => $phoneKey,
                        'phone_label' => $phoneLabel,
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
                $otherAssignedRecs = $recs->filter(fn ($r) => !empty($r->assigned_worker_name) && $r->assigned_contributor_id != $contributorId && strtolower(trim($r->assigned_worker_name ?? '')) !== strtolower(trim($workerName)));

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

        $updated = StudioRecording::whereIn('record_id', $data['record_ids'])->update([
            'assigned_contributor_id' => $data['contributor_id'],
            'assigned_worker_name' => $data['worker_name'],
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
}

