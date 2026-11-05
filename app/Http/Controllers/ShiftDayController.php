<?php

namespace App\Http\Controllers;

use App\Models\ShiftDay;
use App\Services\ShiftConfiguration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShiftDayController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['month' => 'required_without:startDate|date_format:Y-m',
            'startDate' => 'required_with:endDate|date_format:Y-m-d', 'endDate' => 'required_with:startDate|date_format:Y-m-d|after_or_equal:startDate']);
        $start = isset($data['startDate']) ? CarbonImmutable::parse($data['startDate']) : CarbonImmutable::createFromFormat('!Y-m', $data['month']);
        $end = isset($data['endDate']) ? CarbonImmutable::parse($data['endDate']) : $start->endOfMonth();
        abort_if($start->diffInDays($end) > 62, 422, 'Rentang kalender maksimal 63 hari.');
        $days = ShiftDay::whereBetween('work_date', [$start->toDateString(), $end->toDateString()])->get()->keyBy('work_date');

        return response()->json(['days' => $days->map(fn ($day) => ['config' => $day->config, 'revision' => $day->revision, 'updated_at' => $day->updated_at])->all() ?: (object) []]);
    }

    public function save(Request $request, string $date, ShiftConfiguration $configuration)
    {
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
        $fields = $request->validate(['config' => 'required|array', 'expected_revision' => 'required|integer|min:0']);
        $config = $configuration->normalize($fields['config'], ($fields['config']['planner_version'] ?? 1) === 2, true);
        $day = DB::transaction(function () use ($date, $fields, $config) {
            $day = ShiftDay::where('work_date', $date)->lockForUpdate()->first();
            abort_if(($day?->revision ?? 0) !== $fields['expected_revision'], 409, 'Jadwal tanggal ini sudah diubah. Muat ulang tanggal sebelum menyimpan.');
            $day ??= new ShiftDay(['work_date' => $date, 'revision' => 0]);
            $day->config = $config;
            $day->revision++;
            $day->save();

            return $day;
        }, 3);

        return response()->json(['success' => true, 'day' => $day]);
    }

    public function destroy(Request $request, string $date)
    {
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
        $fields = $request->validate(['expected_revision' => 'required|integer|min:1']);
        DB::transaction(function () use ($date, $fields) {
            $day = ShiftDay::where('work_date', $date)->lockForUpdate()->first();
            abort_if(! $day || $day->revision !== $fields['expected_revision'], 409, 'Jadwal tanggal ini sudah berubah. Muat ulang tanggal sebelum menghapus.');
            $day->delete();
        });

        return response()->json(['success' => true]);
    }
}
