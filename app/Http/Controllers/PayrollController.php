<?php

namespace App\Http\Controllers;

use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Models\StudioRecording;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function periods(PayrollPeriod $resolver)
    {
        $current = $resolver->cutoff();
        $current['isCurrent'] = true;
        $periods = [$current];
        $anchor = CarbonImmutable::parse($current['startDate'], 'Asia/Makassar');
        for ($i = 1; $i <= 5; $i++) {
            $periods[] = $resolver->cutoff($anchor->subMonthsNoOverflow($i));
        }
        $periods[] = $resolver->resolve(['periodId' => 'training_2026']);
        $default = $current['id'];
        foreach ($periods as $period) {
            if (empty($period['isTraining']) && StudioRecording::whereBetween('recorded_at', [$period['startUtc'], $period['endUtc']])->exists()) {
                $default = $period['id'];
                break;
            }
        }

        return response()->json(['periods' => $periods, 'currentPeriod' => $current, 'defaultPeriodId' => $default]);
    }

    public function summary(Request $request, PayrollPeriod $resolver, PayrollCalculator $calculator)
    {
        return response()->json($calculator->summary($resolver->resolve($request->query())));
    }

    public function evidence(Request $request, PayrollPeriod $resolver, PayrollCalculator $calculator)
    {
        $request->validate(['worker' => 'required|string|max:255']);
        $period = $resolver->resolve($request->query());
        $slip = Payslip::where('employee_name', $request->worker)->where('period', $period['name'])->latest('id')->first();

        return response()->json(['existingSlipId' => $slip?->id, 'employee_name' => $request->worker,
            'period' => $period['name'], 'bonus' => $slip?->bonus ?? 0,
            'pending_carry_over_amount' => $slip?->pending_carry_over_amount ?? 0,
            'pending_carry_over_hours' => $slip?->pending_carry_over_hours ?? 0,
            'pending_carry_over_data' => $slip?->pending_carry_over_data,
            'is_locked' => (bool) ($slip?->is_locked || $slip?->status === 'sudah'),
            'evidence' => ! empty($slip?->evidence_data) ? $slip->evidence_data : $calculator->evidence($period, $request->worker)]);
    }

    public function pendingCarryOver(Request $request, PayrollCalculator $calculator)
    {
        $request->validate([
            'worker' => 'required|string|max:255',
            'period' => 'required|string|max:255',
            'exclude_slip_id' => 'nullable|integer',
        ]);

        return response()->json($calculator->detectPendingCarryOver(
            $request->input('worker'),
            $request->input('period'),
            $request->filled('exclude_slip_id') ? (int) $request->input('exclude_slip_id') : null
        ));
    }

    public function status(Request $request)
    {
        $fields = $request->validate(['worker_name' => 'required|string|max:255', 'period' => 'required|string|max:255',
            'status' => 'required|in:belum,sudah', 'notes' => 'nullable|string|max:10000']);
        DB::transaction(function () use ($fields) {
            PayrollStatus::updateOrCreate(['worker_name' => $fields['worker_name'], 'period' => $fields['period']],
                array_filter(['status' => $fields['status'], 'notes' => $fields['notes'] ?? null], fn ($v) => $v !== null));
            Payslip::where('employee_name', $fields['worker_name'])->where('period', $fields['period'])->update(['status' => $fields['status']]);
        });

        return response()->json(['success' => true] + $fields);
    }
}
