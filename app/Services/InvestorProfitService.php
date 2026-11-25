<?php

namespace App\Services;

use App\Models\DeviceAccount;
use App\Models\Investor;
use App\Models\InvestorDeviceContract;
use App\Models\InvestorPayout;
use App\Models\StudioDevice;
use App\Models\StudioRecording;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class InvestorProfitService
{
    public function summaryForPeriod(array $period): array
    {
        $investors = Investor::with(['contracts' => fn ($q) => $q->orderBy('id')])
            ->orderBy('name')
            ->get();

        $devices = StudioDevice::orderBy('id')->get()->keyBy('key');
        $payouts = InvestorPayout::where('period_id', $period['id'])->get()->keyBy('investor_id');

        $deviceAccounts = DeviceAccount::orderByDesc('activated_at')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $accountsByEmail = [];
        $activeAccountByPhone = [];
        $allEmailsByPhone = [];

        foreach ($deviceAccounts as $acc) {
            $email = strtolower(trim((string) $acc->email));
            if ($email === '') {
                continue;
            }
            $accountsByEmail[$email][] = $acc;
            $allEmailsByPhone[$acc->phone_key][$email] = true;
            if ($acc->is_active && ! isset($activeAccountByPhone[$acc->phone_key])) {
                $activeAccountByPhone[$acc->phone_key] = $email;
            }
        }

        // Kumpulkan semua kontrak agar hanya memproses rekaman milik HP investor.
        $contractsByDevice = [];
        foreach ($investors as $investor) {
            foreach ($investor->contracts as $contract) {
                $contractsByDevice[$contract->device_key][] = $contract;
            }
        }

        $recordingsQuery = StudioRecording::query()
            ->select(['record_id', 'participant_email', 'recorded_at', 'duration_min', 'billable_min', 'rejected_min', 'pending_min']);

        if (empty($period['isTraining']) && ! empty($period['startUtc']) && ! empty($period['endUtc'])) {
            $recordingsQuery->whereBetween('recorded_at', [$period['startUtc'], $period['endUtc']]);
        }

        $recordings = empty($contractsByDevice) ? collect() : $recordingsQuery->get();

        $contractMetrics = [];
        $dailyTotals = [];

        foreach ($recordings as $rec) {
            $email = strtolower(trim((string) $rec->participant_email));
            if ($email === '' || empty($accountsByEmail[$email])) {
                continue;
            }

            $recordedAt = CarbonImmutable::parse($rec->getRawOriginal('recorded_at'), 'UTC');
            $deviceKey = $this->resolveDeviceKey($accountsByEmail[$email], $recordedAt);
            if (! $deviceKey || empty($contractsByDevice[$deviceKey])) {
                continue;
            }

            $witaDate = $recordedAt->setTimezone('Asia/Makassar')->toDateString();
            $matchedContract = $this->matchContract($contractsByDevice[$deviceKey], $witaDate);
            if (! $matchedContract) {
                continue;
            }

            $cid = $matchedContract->id;
            if (! isset($contractMetrics[$cid])) {
                $contractMetrics[$cid] = [
                    'tasks' => 0,
                    'billable_min' => 0.0,
                    'duration_min' => 0.0,
                    'rejected_min' => 0.0,
                    'pending_min' => 0.0,
                    'daily' => [],
                ];
            }

            $billable = (float) ($rec->billable_min ?? 0);
            $duration = (float) ($rec->duration_min ?? 0);
            $rejected = (float) ($rec->rejected_min ?? 0);
            $pending = (float) ($rec->pending_min ?? 0);

            $contractMetrics[$cid]['tasks']++;
            $contractMetrics[$cid]['billable_min'] += $billable;
            $contractMetrics[$cid]['duration_min'] += $duration;
            $contractMetrics[$cid]['rejected_min'] += $rejected;
            $contractMetrics[$cid]['pending_min'] += $pending;

            if (! isset($contractMetrics[$cid]['daily'][$witaDate])) {
                $contractMetrics[$cid]['daily'][$witaDate] = [
                    'date' => $witaDate,
                    'tasks' => 0,
                    'billable_min' => 0.0,
                    'duration_min' => 0.0,
                ];
            }
            $contractMetrics[$cid]['daily'][$witaDate]['tasks']++;
            $contractMetrics[$cid]['daily'][$witaDate]['billable_min'] += $billable;
            $contractMetrics[$cid]['daily'][$witaDate]['duration_min'] += $duration;

            if (! isset($dailyTotals[$witaDate])) {
                $dailyTotals[$witaDate] = [
                    'date' => $witaDate,
                    'tasks' => 0,
                    'billable_min' => 0.0,
                    'profit_idr' => 0,
                ];
            }
            $basisMin = $matchedContract->rate_basis === 'duration' ? $duration : $billable;
            $dailyTotals[$witaDate]['tasks']++;
            $dailyTotals[$witaDate]['billable_min'] += $billable;
            $dailyTotals[$witaDate]['profit_idr'] += (int) round(($basisMin / 60) * $matchedContract->rate_per_hour_idr);
        }

        $investorRows = [];
        $totals = [
            'investor_count' => $investors->count(),
            'active_investor_count' => $investors->where('is_active', true)->count(),
            'device_count' => 0,
            'total_tasks' => 0,
            'total_billable_hours' => 0.0,
            'total_logged_hours' => 0.0,
            'total_pending_hours' => 0.0,
            'total_rejected_hours' => 0.0,
            'total_investor_profit_idr' => 0,
            'total_paid_idr' => 0,
            'total_unpaid_idr' => 0,
        ];

        foreach ($investors as $investor) {
            $contractRows = [];
            $invTasks = 0;
            $invBillableMin = 0.0;
            $invDurationMin = 0.0;
            $invPendingMin = 0.0;
            $invRejectedMin = 0.0;
            $invProfitIdr = 0;
            $invDaily = [];

            foreach ($investor->contracts as $contract) {
                $totals['device_count']++;
                $m = $contractMetrics[$contract->id] ?? [
                    'tasks' => 0,
                    'billable_min' => 0.0,
                    'duration_min' => 0.0,
                    'rejected_min' => 0.0,
                    'pending_min' => 0.0,
                    'daily' => [],
                ];

                $billableHours = round($m['billable_min'] / 60, 2);
                $loggedHours = round($m['duration_min'] / 60, 2);
                $pendingHours = round($m['pending_min'] / 60, 2);
                $rejectedHours = round($m['rejected_min'] / 60, 2);
                $basisHours = $contract->rate_basis === 'duration' ? $loggedHours : $billableHours;
                $profitIdr = (int) round($basisHours * $contract->rate_per_hour_idr);
                $approvalRate = $m['duration_min'] > 0
                    ? round(($m['billable_min'] / $m['duration_min']) * 100, 1)
                    : 0.0;

                $activeDays = count($m['daily']);
                $avgDailyHours = $activeDays > 0 ? round($billableHours / $activeDays, 2) : 0.0;

                $dailyList = array_values(array_map(function ($d) use ($contract) {
                    $bHours = round($d['billable_min'] / 60, 2);
                    $lHours = round($d['duration_min'] / 60, 2);
                    $useHours = $contract->rate_basis === 'duration' ? $lHours : $bHours;

                    return [
                        'date' => $d['date'],
                        'tasks' => $d['tasks'],
                        'billable_hours' => $bHours,
                        'logged_hours' => $lHours,
                        'profit_idr' => (int) round($useHours * $contract->rate_per_hour_idr),
                    ];
                }, $m['daily']));
                usort($dailyList, fn ($a, $b) => strcmp($a['date'], $b['date']));

                foreach ($dailyList as $d) {
                    if (! isset($invDaily[$d['date']])) {
                        $invDaily[$d['date']] = ['date' => $d['date'], 'tasks' => 0, 'billable_hours' => 0.0, 'profit_idr' => 0];
                    }
                    $invDaily[$d['date']]['tasks'] += $d['tasks'];
                    $invDaily[$d['date']]['billable_hours'] = round($invDaily[$d['date']]['billable_hours'] + $d['billable_hours'], 2);
                    $invDaily[$d['date']]['profit_idr'] += $d['profit_idr'];
                }

                $device = $devices->get($contract->device_key);

                $contractRows[] = [
                    'id' => $contract->id,
                    'investor_id' => $investor->id,
                    'device_key' => $contract->device_key,
                    'device_label' => $device?->label ?? ucfirst(str_replace('_', ' ', $contract->device_key)),
                    'device_code' => $contract->device_code,
                    'active_email' => $activeAccountByPhone[$contract->device_key] ?? null,
                    'monitored_emails_count' => isset($allEmailsByPhone[$contract->device_key]) ? count($allEmailsByPhone[$contract->device_key]) : 0,
                    'rate_per_hour_idr' => $contract->rate_per_hour_idr,
                    'rate_basis' => $contract->rate_basis,
                    'effective_from' => $contract->effective_from?->toDateString(),
                    'effective_to' => $contract->effective_to?->toDateString(),
                    'is_active' => $contract->is_active,
                    'notes' => $contract->notes,
                    'tasks' => $m['tasks'],
                    'billable_hours' => $billableHours,
                    'logged_hours' => $loggedHours,
                    'pending_hours' => $pendingHours,
                    'rejected_hours' => $rejectedHours,
                    'approval_rate' => $approvalRate,
                    'active_days' => $activeDays,
                    'avg_daily_hours' => $avgDailyHours,
                    'profit_idr' => $profitIdr,
                    'daily' => $dailyList,
                ];

                $invTasks += $m['tasks'];
                $invBillableMin += $m['billable_min'];
                $invDurationMin += $m['duration_min'];
                $invPendingMin += $m['pending_min'];
                $invRejectedMin += $m['rejected_min'];
                $invProfitIdr += $profitIdr;
            }

            $invBillableHours = round($invBillableMin / 60, 2);
            $invLoggedHours = round($invDurationMin / 60, 2);
            $invPendingHours = round($invPendingMin / 60, 2);
            $invRejectedHours = round($invRejectedMin / 60, 2);
            $invApprovalRate = $invDurationMin > 0 ? round(($invBillableMin / $invDurationMin) * 100, 1) : 0.0;

            $payout = $payouts->get($investor->id);
            $payoutStatus = $payout?->status ?? 'pending';

            $totals['total_tasks'] += $invTasks;
            $totals['total_billable_hours'] = round($totals['total_billable_hours'] + $invBillableHours, 2);
            $totals['total_logged_hours'] = round($totals['total_logged_hours'] + $invLoggedHours, 2);
            $totals['total_pending_hours'] = round($totals['total_pending_hours'] + $invPendingHours, 2);
            $totals['total_rejected_hours'] = round($totals['total_rejected_hours'] + $invRejectedHours, 2);
            $totals['total_investor_profit_idr'] += $invProfitIdr;

            if ($payoutStatus === 'paid') {
                $totals['total_paid_idr'] += $invProfitIdr;
            } else {
                $totals['total_unpaid_idr'] += $invProfitIdr;
            }

            $invDailyList = array_values($invDaily);
            usort($invDailyList, fn ($a, $b) => strcmp($a['date'], $b['date']));

            $investorRows[] = [
                'id' => $investor->id,
                'code' => $investor->code,
                'name' => $investor->name,
                'no_wa' => $investor->no_wa,
                'email' => $investor->email,
                'bank_name' => $investor->bank_name,
                'no_rek' => $investor->no_rek,
                'account_holder' => $investor->account_holder,
                'is_active' => $investor->is_active,
                'notes' => $investor->notes,
                'device_count' => count($contractRows),
                'contracts' => $contractRows,
                'tasks' => $invTasks,
                'billable_hours' => $invBillableHours,
                'logged_hours' => $invLoggedHours,
                'pending_hours' => $invPendingHours,
                'rejected_hours' => $invRejectedHours,
                'approval_rate' => $invApprovalRate,
                'profit_idr' => $invProfitIdr,
                'payout_status' => $payoutStatus,
                'paid_at' => $payout?->paid_at?->toIso8601String(),
                'payout_notes' => $payout?->notes,
                'daily_series' => $invDailyList,
            ];
        }

        usort($investorRows, fn ($a, $b) => $b['profit_idr'] <=> $a['profit_idr'] ?: strcmp($a['name'], $b['name']));

        $dailyUtilization = array_values(array_map(function ($d) {
            return [
                'date' => $d['date'],
                'tasks' => $d['tasks'],
                'billable_hours' => round($d['billable_min'] / 60, 2),
                'profit_idr' => $d['profit_idr'],
            ];
        }, $dailyTotals));
        usort($dailyUtilization, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return [
            'period' => $period,
            'totals' => $totals,
            'investors' => $investorRows,
            'daily_utilization' => $dailyUtilization,
            'available_devices' => $devices->values()->map(fn (StudioDevice $d) => [
                'key' => $d->key,
                'label' => $d->label,
            ])->all(),
        ];
    }

    public function totalShareForPeriod(array $period): array
    {
        $summary = $this->summaryForPeriod($period);

        return [
            'total_investor_profit_idr' => $summary['totals']['total_investor_profit_idr'],
            'total_billable_hours' => $summary['totals']['total_billable_hours'],
            'device_count' => $summary['totals']['device_count'],
            'investor_count' => $summary['totals']['investor_count'],
        ];
    }

    /**
     * @param  array<int, DeviceAccount>  $accounts
     */
    private function resolveDeviceKey(array $accounts, CarbonImmutable $recordedAt): ?string
    {
        if (count($accounts) === 1) {
            return $accounts[0]->phone_key;
        }

        $distinctPhones = array_unique(array_map(fn ($a) => $a->phone_key, $accounts));
        if (count($distinctPhones) === 1) {
            return $accounts[0]->phone_key;
        }

        // Pilih akun yang diaktifkan paling baru tetapi sebelum/pada waktu rekaman dibuat.
        foreach ($accounts as $acc) {
            if ($acc->activated_at && $acc->activated_at->lessThanOrEqualTo($recordedAt)) {
                return $acc->phone_key;
            }
        }

        return $accounts[0]->phone_key ?? null;
    }

    /**
     * @param  array<int, InvestorDeviceContract>  $contracts
     */
    private function matchContract(array $contracts, string $witaDate): ?InvestorDeviceContract
    {
        foreach ($contracts as $contract) {
            if (! $contract->is_active) {
                continue;
            }
            $from = $contract->effective_from?->toDateString();
            $to = $contract->effective_to?->toDateString();
            if ($from !== null && $witaDate < $from) {
                continue;
            }
            if ($to !== null && $witaDate > $to) {
                continue;
            }

            return $contract;
        }

        return null;
    }
}
