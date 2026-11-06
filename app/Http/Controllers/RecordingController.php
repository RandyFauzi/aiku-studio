<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\StudioRecording;
use App\Services\RecordingMatching;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecordingController extends Controller
{
    public function index(Request $request, RecordingMatching $matching)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'limit' => 'sometimes|integer|min:1|max:10000',
            'startDate' => 'nullable|date_format:Y-m-d', 'endDate' => 'nullable|date_format:Y-m-d',
            'email' => 'nullable|string|max:255', 'worker' => 'nullable|string|max:255', 'status' => 'nullable|string|max:64', 'search' => 'nullable|string|max:255']);
        $query = StudioRecording::query();
        if ($request->filled('email') && $request->email !== 'all') {
            $query->where('participant_email', strtolower(trim($request->email)));
        }
        if ($request->filled('worker') && $request->worker !== 'all') {
            $request->worker === 'unassigned'
                ? $query->where(fn ($q) => $q->whereNull('assigned_worker_name')->orWhere('assigned_worker_name', ''))
                : $query->where('assigned_worker_name', trim($request->worker));
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('manual_payment_status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                foreach (['task_title', 'record_id', 'participant_email', 'assigned_worker_name'] as $field) {
                    $q->orWhere($field, 'like', '%'.trim($request->search).'%');
                }
            });
        }
        if ($request->filled('startDate')) {
            $query->where('recorded_at', '>=', CarbonImmutable::parse($request->startDate, 'Asia/Makassar')->startOfDay()->utc());
        }
        if ($request->filled('endDate')) {
            $query->where('recorded_at', '<=', CarbonImmutable::parse($request->endDate, 'Asia/Makassar')->endOfDay()->utc()->format('Y-m-d H:i:s'));
        }
        $aggregates = (clone $query)->selectRaw("COUNT(*) as totalRecords, COALESCE(SUM(duration_min),0) as totalMinutes, COALESCE(SUM(billable_min),0) as billableMinutes, COALESCE(SUM(rejected_min),0) as rejectedMinutes,
            COALESCE(SUM(CASE WHEN pending_min = 0 AND (processing_status = 'processing' OR (billable_min = 0 AND rejected_min = 0 AND duration_min > 0)) THEN duration_min ELSE pending_min END),0) as pendingMinutes,
            COALESCE(SUM(CASE WHEN assigned_worker_name IS NOT NULL AND TRIM(assigned_worker_name) != '' THEN 1 ELSE 0 END),0) as assignedCount,
            COALESCE(SUM(CASE WHEN manual_payment_status = 'Paid' THEN 1 ELSE 0 END),0) as paidCount,
            COALESCE(SUM(CASE WHEN manual_payment_status = 'Belum' THEN 1 ELSE 0 END),0) as belumCount")->first()->getAttributes();
        $stats = array_map(fn ($v) => round((float) $v, 2), $aggregates);
        $stats['unassignedCount'] = $stats['totalRecords'] - $stats['assignedCount'];
        $stats['belumRekapCount'] = $stats['totalRecords'] - $stats['paidCount'] - $stats['belumCount'];
        $page = $request->integer('page', 1);
        $limit = $request->integer('limit', 50);

        $recordings = $query->orderByDesc('recorded_at')->orderByDesc('createdAt')->orderByDesc('record_id')->offset(($page - 1) * $limit)->limit($limit)->get();
        foreach ($recordings as $recording) {
            $recording->setAttribute('shift_group', $matching->groupFor($recording->getRawOriginal('recorded_at')));
        }
        return response()->json(['recordings' => $recordings,
            'total' => (int) $stats['totalRecords'], 'page' => $page, 'limit' => $limit, 'totalPages' => (int) ceil($stats['totalRecords'] / $limit),
            'lastSyncedAt' => StudioRecording::max('last_synced_at'), 'stats' => $stats]);
    }

    public function assign(Request $request)
    {
        $data = $request->validate(['record_ids' => 'required|array|min:1|max:10000', 'record_ids.*' => 'required|string|max:64',
            'worker_name' => 'sometimes|nullable|string|max:100', 'contributor_id' => 'sometimes|nullable|integer|exists:contributors,id',
            'payment_status' => 'sometimes|nullable|in:Paid,Belum,Belum Rekap', 'notes' => 'sometimes|nullable|string|max:10000']);
        $update = [];
        if (array_key_exists('worker_name', $data)) {
            $update['assigned_worker_name'] = trim($data['worker_name'] ?? '') ?: null;
            $update['assigned_contributor_id'] = $data['contributor_id'] ?? null;
            if ($update['assigned_worker_name'] === null) {
                $update['assigned_contributor_id'] = null;
            } elseif ($update['assigned_contributor_id'] !== null) {
                $update['assigned_worker_name'] = Contributor::findOrFail($update['assigned_contributor_id'])->nama;
            } else {
                $people = Contributor::where('nama', $update['assigned_worker_name'])->get();
                if ($people->count() > 1) {
                    throw ValidationException::withMessages(['worker_name' => 'Nama tidak unik. Pilih kontributor berdasarkan ID.']);
                }
                $update['assigned_contributor_id'] = $people->first()?->id;
            }
            $update['assigned_at'] = $update['assigned_worker_name'] ? now() : null;
        }
        if (array_key_exists('payment_status', $data)) {
            $update['manual_payment_status'] = $data['payment_status'] ?: 'Belum Rekap';
        }
        if (array_key_exists('notes', $data)) {
            $update['notes'] = $data['notes'];
        }
        $count = $update ? StudioRecording::whereIn('record_id', $data['record_ids'])->update($update) : 0;

        return response()->json(['success' => true, 'updated' => $count]);
    }
}
