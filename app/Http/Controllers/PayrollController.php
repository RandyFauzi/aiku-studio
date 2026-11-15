<?php

namespace App\Http\Controllers;

use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Models\StudioRecording;
use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use App\Services\PayslipWriter;
use App\Services\PayrollPaymentStatus;
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
        $cursor = $current;
        for ($i = 1; $i <= 5; $i++) {
            $cursor = $resolver->previous($cursor);
            $periods[] = $cursor;
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
        $slip = app(PayslipWriter::class)->unique($request->worker, $period['name']);
        $isLocked = (bool) ($slip && ($slip->is_locked || $slip->status === 'sudah'));
        $forceRefresh = $request->boolean('refresh');
        $useLive = ! $isLocked && ($forceRefresh || ! $slip); // Existing documents stay snapshots until explicit refresh.

        return response()->json([
            'existingSlipId' => $slip?->id,
            'employee_name' => $request->worker,
            'period' => $period['name'],
            'total_thp' => $slip?->total_thp,
            'bonus' => $slip?->bonus ?? 0,
            'pending_carry_over_amount' => $slip?->pending_carry_over_amount ?? 0,
            'pending_carry_over_hours' => $slip?->pending_carry_over_hours ?? 0,
            'pending_carry_over_data' => $slip?->pending_carry_over_data,
            'is_locked' => $isLocked,
            'evidence' => $useLive ? $calculator->evidence($period, $request->worker) : $slip->evidence_data,
        ]);
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
            'status' => 'required|in:belum,sudah', 'notes' => 'nullable|string|max:10000',
            'expected_thp' => 'required_if:status,sudah|nullable|integer|min:0']);
        DB::transaction(function () use ($fields) {
            app(PayrollPaymentStatus::class)->set($fields['worker_name'], $fields['period'], $fields['status'], $fields['notes'] ?? null, true, $fields['expected_thp'] ?? null);
        }, 3);

        return response()->json(['success' => true] + $fields);
    }
}
