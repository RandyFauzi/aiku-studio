<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\StudioRecording;
use App\Services\ContributorIdentity;
use App\Services\TaskLanguage;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class MyRecordingController extends Controller
{
    public function index(Request $request, ContributorIdentity $identity, TaskLanguage $language)
    {
        $person = $identity->resolve($request->user());
        $data = $request->validate(['month' => 'nullable|date_format:Y-m', 'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1', 'limit' => 'nullable|integer|min:1|max:50']);
        $month = $data['month'] ?? CarbonImmutable::now('Asia/Makassar')->format('Y-m');
        $start = CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Makassar');
        $legacySafe = Contributor::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($person->nama))])->count() === 1;
        $query = StudioRecording::where(function ($query) use ($person, $legacySafe) {
            $query->where('assigned_contributor_id', $person->id);
            if ($legacySafe) {
                $query->orWhere(fn ($legacy) => $legacy->whereNull('assigned_contributor_id')->whereRaw('LOWER(TRIM(assigned_worker_name)) = ?', [mb_strtolower(trim($person->nama))]));
            }
        })->where('recorded_at', '>=', $start->utc())->where('recorded_at', '<', $start->addMonth()->utc());
        if (! empty($data['search'])) {
            $search = $data['search'];
            $titles = (clone $query)->select('task_title')->distinct()->pluck('task_title')->filter(function ($title) use ($language, $search) {
                return $language->score($search, ['title' => $title ?? ''] + $language->describe($title ?? '')) > 0;
            })->values()->all();
            $query->where(fn ($query) => $query->whereIn('task_title', $titles)->orWhere('record_id', 'like', '%'.$search.'%'));
        }
        $summary = (clone $query)->selectRaw('COUNT(*) as total, COALESCE(SUM(duration_min), 0) as minutes')->first();
        // An explicit allowlist keeps every amount, rate, note and payment field out of the worker response.
        $rows = $query->select(['record_id', 'task_title', 'recorded_at', 'duration_min', 'processing_status', 'assigned_at'])
            ->orderByDesc('recorded_at')->orderBy('record_id')->paginate($data['limit'] ?? 20);
        $records = $rows->getCollection()->map(fn ($record) => [
            'record_id' => $record->record_id, 'task_title' => $record->task_title,
            'task_label' => $language->describe($record->task_title ?? '')['label'],
            'recorded_at' => CarbonImmutable::parse($record->getRawOriginal('recorded_at'), 'UTC')->toIso8601String(),
            'duration_minutes' => round((float) $record->duration_min, 2), 'processing_status' => $record->processing_status,
        ]);
        return response()->json(['recordings' => $records, 'month' => $month,
            'summary' => ['total_recordings' => (int) $summary->total, 'total_minutes' => round((float) $summary->minutes, 2)],
            'pagination' => ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()]])
            ->header('Cache-Control', 'private, no-store');
    }
}
