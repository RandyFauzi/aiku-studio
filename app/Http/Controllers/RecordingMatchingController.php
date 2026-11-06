<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\StudioRecording;
use App\Services\RecordingMatching;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordingMatchingController extends Controller
{
    public function index(Request $request, RecordingMatching $matching)
    {
        $data = $request->validate(['recording_date' => 'required|date_format:Y-m-d', 'report_date' => 'required|date_format:Y-m-d',
            'email' => 'nullable|string|max:128', 'page' => 'nullable|integer|min:1']);
        $windows = $matching->windows($data['recording_date']);
        $query = StudioRecording::where('recorded_at', '>=', CarbonImmutable::parse($windows[0]['start_at'])->utc())
            ->where('recorded_at', '<', CarbonImmutable::parse(end($windows)['end_at'])->utc());
        if (! empty($data['email'])) {
            $query->whereRaw('LOWER(TRIM(participant_email)) = ?', [mb_strtolower(trim($data['email']))]);
        }
        $records = $query->orderBy('recorded_at')->orderBy('record_id')->paginate(100);
        foreach ($windows as &$window) {
            $start = CarbonImmutable::parse($window['start_at'])->utc();
            $end = CarbonImmutable::parse($window['end_at'])->utc();
            $window['recordings'] = $records->getCollection()->filter(function ($record) use ($start, $end) {
                $time = CarbonImmutable::parse($record->getRawOriginal('recorded_at'), 'UTC');
                return $time->gte($start) && $time->lt($end);
            })->values();
        }
        unset($window);
        $config = $matching->config($data['report_date']);
        $reports = DailyWorkReport::where('work_date', $data['report_date'])->orderBy('contributor_name')->get();
        $people = Contributor::whereIn('id', $reports->pluck('contributor_id'))->get()->keyBy('id');
        $reports = $reports->map(function ($report) use ($matching, $config, $people) {
            $worker = collect($config['karyawan'])->first(fn ($worker) => ! empty($worker['contributor_id'])
                ? (int) $worker['contributor_id'] === (int) $report->contributor_id
                : mb_strtolower($worker['name']) === mb_strtolower($report->contributor_name));
            return ['id' => $report->id, 'revision' => $report->revision, 'contributor_id' => $report->contributor_id,
                'name' => $people->get($report->contributor_id)?->nama ?? $report->contributor_name,
                'can_confirm' => $people->has($report->contributor_id), 'work_date' => $report->work_date,
                'total_minutes' => $report->total_duration_minutes, 'reported_at' => $report->updated_at?->toIso8601String(),
                'shift_label' => empty($worker['sessions']) ? 'Tanpa sesi terjadwal' : 'Shift '.implode(', ', $worker['sessions']),
                'phones' => $matching->phones($report)];
        });
        return response()->json(['groups' => $windows, 'reports' => $reports, 'page' => $records->currentPage(),
            'last_page' => $records->lastPage(), 'total' => $records->total()]);
    }

    public function confirm(Request $request, RecordingMatching $matching)
    {
        $data = $request->validate(['report_id' => 'required|integer', 'expected_revision' => 'required|integer|min:0',
            'phone_key' => 'required|string|max:30', 'record_ids' => 'required|array|min:1|max:100',
            'record_ids.*' => 'required|string|max:64|distinct', 'reason' => 'required|string|min:8|max:500']);
        return DB::transaction(function () use ($data, $request, $matching) {
            $report = DailyWorkReport::whereKey($data['report_id'])->lockForUpdate()->firstOrFail();
            abort_if($report->revision !== $data['expected_revision'], 409, 'Laporan sudah berubah. Muat ulang sebelum konfirmasi.');
            $person = Contributor::findOrFail($report->contributor_id);
            $phone = collect($matching->phones($report))->firstWhere('key', $data['phone_key']);
            abort_unless($phone && count($phone['emails']), 422, 'Email HP belum tersedia. Lengkapi email pada laporan terlebih dahulu.');
            $records = StudioRecording::whereIn('record_id', $data['record_ids'])->orderBy('record_id')->lockForUpdate()->get();
            abort_if($records->count() !== count($data['record_ids']), 409, 'Sebagian recording tidak ditemukan. Muat ulang.');
            foreach ($records as $record) {
                abort_if($record->assigned_contributor_id || trim($record->assigned_worker_name ?? '') !== '' || $record->manual_payment_status === 'Paid', 409,
                    'Recording sudah memiliki pemilik atau sudah dibayar. Tidak ada perubahan disimpan.');
                abort_unless(in_array(mb_strtolower(trim($record->participant_email)), $phone['emails'], true), 422,
                    'Email recording tidak sesuai dengan HP pada laporan. Tidak ada perubahan disimpan.');
            }
            foreach ($records as $record) {
                $evidence = json_encode(['action' => 'owner_confirmed', 'admin_id' => $request->user()->id,
                    'report_id' => $report->id, 'report_revision' => $report->revision, 'work_date' => $report->work_date,
                    'contributor_id' => $person->id, 'phone_key' => $data['phone_key'], 'email' => $record->participant_email,
                    'email_source' => $phone['email_source'], 'reason' => $data['reason'],
                    'reported_minutes' => $phone['duration_minutes'], 'report_total_minutes' => $report->total_duration_minutes,
                    'recording_minutes' => $record->duration_min, 'atlas_recorded_at' => $record->getRawOriginal('recorded_at'),
                    'at' => now()->toIso8601String()], JSON_UNESCAPED_UNICODE);
                $record->update(['assigned_contributor_id' => $person->id, 'assigned_worker_name' => $person->nama,
                    'assigned_at' => now()]);
                DB::table('recording_owner_confirmations')->insert(['record_id' => $record->record_id,
                    'contributor_id' => $person->id, 'admin_id' => $request->user()->id, 'report_id' => $report->id,
                    'work_date' => $report->work_date, 'evidence' => $evidence, 'created_at' => now()]);
            }
            return response()->json(['updated' => $records->count()]);
        }, 3);
    }
}
