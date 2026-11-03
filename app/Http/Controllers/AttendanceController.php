<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\User;
use App\Services\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    private function contributor(Request $request): Contributor
    {
        abort_unless($request->user()->role === 'contributor', 403);

        return Contributor::findOrFail($request->user()->contributor_id);
    }

    public function mine(Request $request, ShiftSchedule $schedule)
    {
        $person = $this->contributor($request);
        $data = $request->validate(['month' => 'required|date_format:Y-m']);
        $start = CarbonImmutable::createFromFormat('!Y-m', $data['month'], 'Asia/Makassar');

        return response()->json(['name' => $person->nama, 'today' => CarbonImmutable::now('Asia/Makassar')->toDateString(),
            'today_attendance' => Attendance::where('user_id', $request->user()->id)->where('work_date', CarbonImmutable::now('Asia/Makassar')->toDateString())->first(),
            'schedule' => $schedule->forContributor($person, $data['month']),
            'attendances' => Attendance::where('user_id', $request->user()->id)->whereBetween('work_date', [$start->toDateString(), $start->endOfMonth()->toDateString()])->orderByDesc('work_date')->get()]);
    }

    public function checkIn(Request $request)
    {
        $person = $this->contributor($request);
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        if ($existing = Attendance::where('user_id', $request->user()->id)->where('work_date', $today)->first()) {
            return response()->json(['success' => true, 'already_checked_in' => true, 'attendance' => $existing]);
        }
        $fields = $request->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180',
            'accuracy_m' => 'required|numeric|min:0|max:1000000', 'captured_at' => 'required|date']);
        $captured = CarbonImmutable::parse($fields['captured_at'])->utc();
        if ($captured->lt(CarbonImmutable::now()->subMinutes(5)) || $captured->gt(CarbonImmutable::now()->addMinute())) {
            throw ValidationException::withMessages(['captured_at' => 'Lokasi sudah kedaluwarsa. Ambil GPS kembali dan periksa jam perangkat.']);
        }
        $row = DB::transaction(function () use ($request, $person, $today, $fields, $captured) {
            // Lock the account so concurrent clicks cannot create two check-ins.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            return Attendance::firstOrCreate(['user_id' => $request->user()->id, 'work_date' => $today],
                ['contributor_id' => $person->id, 'contributor_name' => $person->nama, 'checked_in_at' => now(),
                    'latitude' => $fields['latitude'], 'longitude' => $fields['longitude'], 'accuracy_m' => $fields['accuracy_m'], 'gps_captured_at' => $captured]);
        });

        return response()->json(['success' => true, 'already_checked_in' => ! $row->wasRecentlyCreated, 'attendance' => $row]);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d', 'contributor_id' => 'nullable|integer']);
        $rows = Attendance::where('work_date', $data['date'])->when($request->filled('contributor_id'), fn ($q) => $q->where('contributor_id', $data['contributor_id']))->orderBy('checked_in_at')->get();

        return response()->json(['date' => $data['date'], 'attendances' => $rows]);
    }
}
