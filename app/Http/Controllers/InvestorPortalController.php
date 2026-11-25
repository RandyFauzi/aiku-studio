<?php

namespace App\Http\Controllers;

use App\Models\InvestorPayout;
use App\Services\InvestorProfitService;
use App\Services\PayrollPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestorPortalController extends Controller
{
    public function dashboard(Request $request, PayrollPeriod $resolver, InvestorProfitService $service): JsonResponse
    {
        $user = $request->user();
        $investor = $user->investor;
        abort_unless($investor !== null, 403, 'Akun tidak terhubung ke profil investor.');

        $current = $resolver->cutoff();
        $current['isCurrent'] = true;
        $periods = [$current];
        $cursor = $current;
        for ($i = 1; $i <= 5; $i++) {
            $cursor = $resolver->previous($cursor);
            $periods[] = $cursor;
        }

        $period = $request->filled('periodId')
            ? $resolver->resolve($request->query())
            : $current;

        $summary = $service->summaryForPeriod($period, $investor->id);
        $row = $summary['investors'][0] ?? null;

        $payoutHistory = InvestorPayout::where('investor_id', $investor->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (InvestorPayout $p) => [
                'id' => $p->id,
                'period_id' => $p->period_id,
                'period_name' => $p->period_name,
                'total_billable_hours' => (float) $p->total_billable_hours,
                'total_amount_idr' => (int) $p->total_amount_idr,
                'status' => $p->status,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'notes' => $p->notes,
            ])
            ->values()
            ->all();

        return response()->json([
            'investor' => [
                'id' => $investor->id,
                'code' => $investor->code,
                'name' => $investor->name,
                'no_wa' => $investor->no_wa,
                'email' => $investor->email,
                'bank_name' => $investor->bank_name,
                'no_rek' => $investor->no_rek,
                'account_holder' => $investor->account_holder,
                'is_active' => (bool) $investor->is_active,
                'login_email' => $user->email,
                'login_name' => $user->name,
            ],
            'periods' => $periods,
            'selected_period' => $period,
            'summary' => $row,
            'payout_history' => $payoutHistory,
        ]);
    }
}
