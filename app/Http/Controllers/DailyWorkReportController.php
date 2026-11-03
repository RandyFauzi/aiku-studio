<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return response()->json(['today' => $today, 'phone_options' => DailyWorkReport::PHONES,
            'today_report' => DailyWorkReport::where('user_id', $request->user()->id)->where('work_date', $today)->first(),
            'reports' => DailyWorkReport::where('user_id', $request->user()->id)->whereBetween('work_date', [$start->toDateString(), $start->endOfMonth()->toDateString()])->orderByDesc('work_date')->get()]);
    }

    public function save(Request $request)
    {
        $person = $this->contributor($request);
        $fields = $request->validate(['work_date' => 'required|date_format:Y-m-d', 'total_duration_minutes' => 'required|integer|min:1|max:8640',
            'phones' => 'required|array|min:1|max:6', 'phones.*' => ['required', 'string', 'distinct', Rule::in(array_keys(DailyWorkReport::PHONES))],
            'expected_revision' => 'required|integer|min:0']);
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        if ($fields['work_date'] !== $today) {
            throw ValidationException::withMessages(['work_date' => 'Laporan hanya dapat dikirim atau diubah untuk hari ini (WITA). Muat ulang jika tanggal sudah berganti.']);
        }
        $row = DB::transaction(function () use ($request, $person, $fields, $today) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $row = DailyWorkReport::where('user_id', $request->user()->id)->where('work_date', $today)->first();
            abort_if(($row?->revision ?? 0) !== $fields['expected_revision'], 409, 'Laporan sudah berubah di sesi lain. Muat ulang data sebelum menyimpan.');
            $row ??= new DailyWorkReport(['user_id' => $request->user()->id, 'contributor_id' => $person->id, 'work_date' => $today, 'revision' => 0]);
            $row->fill(['contributor_name' => $person->nama, 'total_duration_minutes' => $fields['total_duration_minutes'], 'phones' => array_values($fields['phones'])]);
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

        return response()->json(['reports' => $rows, 'phone_options' => DailyWorkReport::PHONES, 'total_duration_minutes' => $rows->sum('total_duration_minutes')]);
    }
}
