@php
    $isPdf = !empty($forPdf);
    $ratePerHour = (int) config('payroll.hourly_rate', 50000);
    $bonusVal = (int) round((float) ($bonus ?? 0));
    $carryOverVal = (int) round((float) ($pending_carry_over_amount ?? 0));
    $carryOverHoursVal = (float) ($pending_carry_over_hours ?? 0);
    if ($carryOverHoursVal <= 0 && !empty($pending_carry_over_data) && is_array($pending_carry_over_data)) {
        $carryOverHoursVal = round(array_sum(array_column($pending_carry_over_data, 'delta_min')) / 60, 2);
    } elseif ($carryOverHoursVal <= 0 && $carryOverVal > 0 && $ratePerHour > 0) {
        $carryOverHoursVal = round($carryOverVal / $ratePerHour, 2);
    }

    $approvedHoursVal = (float) ($total_approved_hours ?? 0);
    $loggedHoursVal = (float) ($total_logged_hours ?? 0);
    $approvedPay = isset($total_thp)
        ? max(0, (int) round((float) $total_thp) - $bonusVal - $carryOverVal)
        : (int) round($approvedHoursVal * $ratePerHour);
    $calculatedTotalTHP = isset($total_thp)
        ? (int) round((float) $total_thp)
        : ($approvedPay + $bonusVal + $carryOverVal);

    $formatRupiah = fn($num) => 'Rp ' . number_format((float) $num, 0, ',', '.');
    $formatMins = function ($mins) {
        $val = number_format((float) $mins, 1, '.', '');
        return str_ends_with($val, '.0') ? substr($val, 0, -2) : $val;
    };

    $slipCode = is_numeric($id ?? null)
        ? 'SLIP-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
        : 'SLIP-' . ($id ?? '0000');

    $issuedDate = !empty($created_at)
        ? \Carbon\CarbonImmutable::parse($created_at)->setTimezone('Asia/Makassar')->locale('en')->isoFormat('MMM D, YYYY')
        : now('Asia/Makassar')->locale('en')->isoFormat('MMM D, YYYY');

    $evidence = $evidence_data ?? [];
    $files = app(\App\Services\PayrollFiles::class);

    $totalPendingMins = 0.0;
    $grandTotalTasks = 0;
    $processedDays = [];

    foreach ($evidence as $index => $ev) {
        $rawTasks = (!empty($ev['tasks']) && is_array($ev['tasks'])) ? $ev['tasks'] : [[
            'task_name' => $ev['task_name'] ?? 'Daily Video Log',
            'approved_mins' => (float) ($ev['approved_mins'] ?? 0),
            'rejected_mins' => (float) ($ev['rejected_mins'] ?? 0),
            'pending_mins' => (float) ($ev['pending_mins'] ?? 0),
            'total_video_mins' => (float) ($ev['total_video_mins'] ?? (((float) ($ev['approved_mins'] ?? 0)) + ((float) ($ev['rejected_mins'] ?? 0)) + ((float) ($ev['pending_mins'] ?? 0)))),
            'notes' => $ev['notes'] ?? '',
        ]];

        $grandTotalTasks += count($rawTasks);

        $dayApprovedMins = 0.0;
        $dayRejectedMins = 0.0;
        $dayPendingMins = 0.0;
        $dayTotalVideoMins = 0.0;
        $processedTasks = [];

        foreach ($rawTasks as $tIdx => $t) {
            $a = (float) ($t['approved_mins'] ?? 0);
            $r = (float) ($t['rejected_mins'] ?? 0);
            $p = (float) ($t['pending_mins'] ?? 0);
            $tot = (float) ($t['total_video_mins'] ?? 0);
            if ($tot <= 0) {
                $tot = $a + $r + $p;
            }
            $dayApprovedMins += $a;
            $dayRejectedMins += $r;
            $dayPendingMins += $p;
            $dayTotalVideoMins += $tot;

            $tDenom = $tot > 0 ? $tot : 1.0;
            $processedTasks[] = [
                'idx' => $tIdx + 1,
                'task_name' => $t['task_name'] ?? ('Task #' . ($tIdx + 1)),
                'time_str' => $t['time_str'] ?? '',
                'notes' => $t['notes'] ?? '',
                'approved_mins' => $a,
                'rejected_mins' => $r,
                'pending_mins' => $p,
                'total_mins' => $tot,
                'approved_hrs' => number_format($a / 60, 2, '.', ''),
                'rejected_hrs' => number_format($r / 60, 2, '.', ''),
                'pending_hrs' => number_format($p / 60, 2, '.', ''),
                'total_hrs' => number_format($tot / 60, 2, '.', ''),
                'appr_pct' => number_format(min(100, max(0, ($a / $tDenom) * 100)), 1, '.', ''),
                'pend_pct' => number_format(min(100, max(0, ($p / $tDenom) * 100)), 1, '.', ''),
                'rej_pct' => number_format(min(100, max(0, ($r / $tDenom) * 100)), 1, '.', ''),
            ];
        }

        $totalPendingMins += $dayPendingMins;

        $statusType = 'full';
        $statusLabel = 'Disetujui penuh';
        if ($dayApprovedMins > 0 && $dayRejectedMins > 0) {
            $statusType = 'partial';
            $statusLabel = 'Disetujui sebagian';
        } elseif ($dayApprovedMins == 0 && $dayRejectedMins > 0) {
            $statusType = 'rejected';
            $statusLabel = 'Ditolak';
        } elseif ($dayPendingMins > 0 && $dayApprovedMins == 0 && $dayRejectedMins == 0) {
            $statusType = 'pending';
            $statusLabel = 'Sedang Ditinjau';
        }

        $dayDenom = $dayTotalVideoMins > 0 ? $dayTotalVideoMins : 1.0;
        $formattedDate = $ev['date'] ?? '';
        try {
            if (!empty($ev['date'])) {
                $formattedDate = \Carbon\CarbonImmutable::parse($ev['date'])->locale('id')->translatedFormat('l, j M Y');
            }
        } catch (\Throwable $e) {
            // Keep raw date string if unparseable
        }

        $processedDays[] = [
            'index' => $index,
            'code' => 'H' . ($index + 1),
            'date' => $ev['date'] ?? '',
            'formatted_date' => $formattedDate ?: 'Tanggal Belum Diisi',
            'tasks_count' => count($rawTasks),
            'status_type' => $statusType,
            'status_label' => $statusLabel,
            'approved_mins' => $dayApprovedMins,
            'rejected_mins' => $dayRejectedMins,
            'pending_mins' => $dayPendingMins,
            'total_mins' => $dayTotalVideoMins,
            'approved_hrs' => number_format($dayApprovedMins / 60, 2, '.', ''),
            'rejected_hrs' => number_format($dayRejectedMins / 60, 2, '.', ''),
            'pending_hrs' => number_format($dayPendingMins / 60, 2, '.', ''),
            'total_hrs' => number_format($dayTotalVideoMins / 60, 2, '.', ''),
            'appr_pct' => number_format(min(100, max(0, ($dayApprovedMins / $dayDenom) * 100)), 1, '.', ''),
            'pend_pct' => number_format(min(100, max(0, ($dayPendingMins / $dayDenom) * 100)), 1, '.', ''),
            'rej_pct' => number_format(min(100, max(0, ($dayRejectedMins / $dayDenom) * 100)), 1, '.', ''),
            'notes' => $ev['notes'] ?? '',
            'image_urls' => $ev['image_urls'] ?? (!empty($ev['image_url']) ? [$ev['image_url']] : []),
            'tasks' => $processedTasks,
        ];
    }

    $rejectedHoursVal = max(0, $loggedHoursVal - $approvedHoursVal);
    $displayTotalHours = number_format($loggedHoursVal, 2, '.', '');
    $displayApprovedHours = number_format($approvedHoursVal, 2, '.', '');
    $displayRejectedHours = number_format($rejectedHoursVal, 2, '.', '');
    $displayPendingHours = number_format($totalPendingMins / 60, 2, '.', '');

    $approvedMinsTotal = $formatMins($approvedHoursVal * 60);
    $rejectedMinsTotal = $formatMins($rejectedHoursVal * 60);
    $pendingMinsFormatted = $formatMins($totalPendingMins);
    $carryOverMinsFormatted = $formatMins($carryOverHoursVal * 60);

    $overallApprPct = $loggedHoursVal > 0 ? number_format(min(100, max(0, ($approvedHoursVal / $loggedHoursVal) * 100)), 1, '.', '') : '0.0';
    $overallPendPct = $loggedHoursVal > 0 ? number_format(min(100, max(0, ($totalPendingMins / ($loggedHoursVal * 60)) * 100)), 1, '.', '') : '0.0';
    $overallRejPct = $loggedHoursVal > 0 ? number_format(min(100, max(0, (($loggedHoursVal - $approvedHoursVal - ($totalPendingMins / 60)) / $loggedHoursVal) * 100)), 1, '.', '') : '0.0';

    $bannerBase64 = null;
    if ($isPdf) {
        $bannerPath = public_path('assets/robot-banner-pdf.png');
        if (!is_file($bannerPath)) {
            $bannerPath = public_path('assets/robot-banner.png');
        }
        if (is_file($bannerPath)) {
            $bannerBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($bannerPath));
        }
    }
@endphp

@if($isPdf)
<div class="pdf-container">
    <!-- PAGE 1: PAYSLIP -->
    @if($bannerBase64)
        <img src="{{ $bannerBase64 }}" alt="AIKu Studio Banner" class="pdf-banner">
    @endif

    <table class="pdf-header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="pdf-logo-circle">AIKu</div>
                <h1 class="pdf-payslip-h1">PAYSLIP</h1>
            </td>
            <td style="vertical-align: top; text-align: right; padding-top: 6px;">
                <div class="pdf-slip-code">{{ $slipCode }}</div>
            </td>
        </tr>
    </table>

    <table class="pdf-meta-table">
        <tr>
            <td>
                <p class="pdf-meta-label">From</p>
                <p class="pdf-meta-strong">AI Ku Studio</p>
                <p class="pdf-meta-muted">Egocentric Video Studio</p>
                <p class="pdf-meta-muted">Banjarmasin, Indonesia</p>
                <p class="pdf-meta-muted">admin@aikustudio.id</p>
            </td>
            <td>
                <p class="pdf-meta-label">Bill To</p>
                <p class="pdf-meta-strong">{{ $employee_name }}</p>
                <p class="pdf-meta-muted">Video Contributor</p>
            </td>
            <td>
                <p class="pdf-meta-label">Issued</p>
                <p class="pdf-meta-strong" style="font-weight: 600;">{{ $issuedDate }}</p>
            </td>
            <td>
                <p class="pdf-meta-label">Period</p>
                <p class="pdf-meta-strong" style="font-weight: 600;">{{ $period }}</p>
            </td>
        </tr>
    </table>

    <div class="pdf-items-wrap">
        <table class="pdf-items-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 48%;">Description</th>
                    <th style="text-align: center; width: 22%;">Qty (Hrs)</th>
                    <th style="text-align: right; width: 15%;">Rate</th>
                    <th style="text-align: right; width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600; color: #111827;">Approved Work Hours (Total)</td>
                    <td style="text-align: center; color: #111827;">
                        {{ $displayApprovedHours }} <span style="font-size: 9.5px; color: #9ca3af;">({{ $approvedMinsTotal }} mins)</span>
                    </td>
                    <td style="text-align: right; color: #111827;">{{ $formatRupiah($ratePerHour) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #111827;">{{ $formatRupiah($approvedPay) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 500; color: #6b7280;">Rejected Work Hours (QA Failed)</td>
                    <td style="text-align: center; color: #6b7280;">
                        {{ $displayRejectedHours }} <span style="font-size: 9.5px; color: #9ca3af;">({{ $rejectedMinsTotal }} mins)</span>
                    </td>
                    <td style="text-align: right; color: #6b7280;">{{ $formatRupiah(0) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #6b7280;">{{ $formatRupiah(0) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 500; color: #ca8a04;">Pending Work Hours (Not Reviewed)</td>
                    <td style="text-align: center; color: #ca8a04;">
                        {{ $displayPendingHours }} <span style="font-size: 9.5px; color: #eab308;">({{ $pendingMinsFormatted }} mins)</span>
                    </td>
                    <td style="text-align: right; color: #ca8a04;">{{ $formatRupiah(0) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #ca8a04;">{{ $formatRupiah(0) }}</td>
                </tr>
                @if($carryOverVal > 0)
                <tr>
                    <td style="font-weight: 600; color: #047857;">Pencairan Sisa Pending Periode Lalu</td>
                    <td style="text-align: center; color: #047857;">
                        @if($carryOverHoursVal > 0)
                            {{ number_format($carryOverHoursVal, 2, '.', '') }} <span style="font-size: 9.5px; color: #10b981;">({{ $carryOverMinsFormatted }} mins)</span>
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: right; color: #047857;">{{ $carryOverHoursVal > 0 ? $formatRupiah($ratePerHour) : '-' }}</td>
                    <td style="text-align: right; font-weight: 700; color: #047857;">+{{ $formatRupiah($carryOverVal) }}</td>
                </tr>
                @endif
                @if($bonusVal > 0)
                <tr>
                    <td style="font-weight: 600; color: #047857;">Bonus / Insentif Tambahan</td>
                    <td style="text-align: center; color: #9ca3af;">-</td>
                    <td style="text-align: right; color: #9ca3af;">-</td>
                    <td style="text-align: right; font-weight: 700; color: #047857;">+{{ $formatRupiah($bonusVal) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <table class="pdf-totals-table">
            <tr>
                <td style="width: 62%;"></td>
                <td style="width: 38%;">
                    <table class="pdf-totals-inner">
                        <tr>
                            <td style="text-align: left;">Subtotal</td>
                            <td style="text-align: right;">{{ $formatRupiah($calculatedTotalTHP) }}</td>
                        </tr>
                        <tr class="pdf-tax-row">
                            <td style="text-align: left;">Tax (0%)</td>
                            <td style="text-align: right;">Rp 0</td>
                        </tr>
                        <tr class="pdf-grand-row">
                            <td style="text-align: left;">Total</td>
                            <td style="text-align: right;">{{ $formatRupiah($calculatedTotalTHP) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="pdf-footer-table">
        <tr>
            <td>
                <p class="pdf-meta-label">Payment Details</p>
                <p class="pdf-meta-muted" style="font-size: 10px;">Please refer to the internal finance system for direct bank transfer details.</p>
            </td>
            <td>
                <p class="pdf-meta-label">Notes</p>
                <p class="pdf-meta-muted" style="font-size: 10px;">Thank you for your hard work and contribution to AI Ku Studio!</p>
            </td>
        </tr>
    </table>

    <!-- PAGE 2: EVIDENCE & TASK LOGS -->
    <div class="page-break">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
            <tr>
                <td style="vertical-align: bottom;">
                    <div style="font-size: 19px; font-weight: 800; color: #111827; letter-spacing: -0.3px;">EVIDENCE &amp; TASK LOGS</div>
                    <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">Rincian log pengerjaan task harian yang tersinkronisasi dengan status review Verbos.</div>
                </td>
                <td style="vertical-align: bottom; text-align: right;">
                    <div style="font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;">Total Tasks</div>
                    <div style="font-size: 15px; font-weight: 800; color: #111827;">{{ $grandTotalTasks }} Tasks</div>
                </td>
            </tr>
        </table>

        <!-- Overall Summary Progress Card -->
        <div class="pdf-summary-card">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                <tr>
                    <td style="font-size: 10px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">Ringkasan Periode Keseluruhan</td>
                    <td style="text-align: right; font-size: 11.5px; font-weight: 800; color: #111827;">
                        {{ $displayTotalHours }}H <span style="font-weight: 400; color: #9ca3af;">({{ (int) round($loggedHoursVal * 60) }} MNT)</span>
                    </td>
                </tr>
            </table>

            <!-- Segmented Progress Bar -->
            <table style="width: 100%; border-collapse: collapse; height: 9px; background-color: #e5e7eb; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                <tr>
                    @if((float) $overallApprPct > 0)
                        <td style="width: {{ $overallApprPct }}%; background-color: #10b981; height: 9px; padding: 0;"></td>
                    @endif
                    @if((float) $overallPendPct > 0)
                        <td style="width: {{ $overallPendPct }}%; background-color: #fbbf24; height: 9px; padding: 0;"></td>
                    @endif
                    @if((float) $overallRejPct > 0)
                        <td style="width: {{ $overallRejPct }}%; background-color: #f43f5e; height: 9px; padding: 0;"></td>
                    @endif
                    @if((float) $overallApprPct <= 0 && (float) $overallPendPct <= 0 && (float) $overallRejPct <= 0)
                        <td style="width: 100%; background-color: #e5e7eb; height: 9px; padding: 0;"></td>
                    @endif
                </tr>
            </table>

            <!-- 4 Highlight Badges -->
            <table style="width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px;">
                <tr>
                    <td style="width: 25%; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 8px; text-align: center;">
                        <div style="font-size: 8.5px; font-weight: 700; color: #9ca3af; text-transform: uppercase;">Total Kirim</div>
                        <div style="font-size: 10.5px; font-weight: 800; color: #111827; margin-top: 2px;">{{ $displayTotalHours }}h</div>
                    </td>
                    <td style="width: 25%; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 6px 8px; text-align: center;">
                        <div style="font-size: 8.5px; font-weight: 700; color: #047857; text-transform: uppercase;">Approved</div>
                        <div style="font-size: 10.5px; font-weight: 800; color: #047857; margin-top: 2px;">{{ $displayApprovedHours }}h</div>
                    </td>
                    <td style="width: 25%; background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 6px 8px; text-align: center;">
                        <div style="font-size: 8.5px; font-weight: 700; color: #be123c; text-transform: uppercase;">Rejected</div>
                        <div style="font-size: 10.5px; font-weight: 800; color: #be123c; margin-top: 2px;">{{ $displayRejectedHours }}h</div>
                    </td>
                    <td style="width: 25%; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 6px 8px; text-align: center;">
                        <div style="font-size: 8.5px; font-weight: 700; color: #b45309; text-transform: uppercase;">Pending</div>
                        <div style="font-size: 10.5px; font-weight: 800; color: #b45309; margin-top: 2px;">{{ $displayPendingHours }}h</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="pdf-note-box">
            <strong>Catatan:</strong> Bagian task yang berstatus <em>Pending (Ditinjau)</em> akan otomatis diikutsertakan pada rekonsiliasi periode pembayaran berikutnya setelah hasil QA Verbos diterbitkan.
        </div>

        @forelse($processedDays as $day)
            <div class="pdf-day-card">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle;">
                            <span class="pdf-h-badge">{{ $day['code'] }}</span>
                            <span class="pdf-day-title">{{ $day['formatted_date'] }}</span>
                            <span class="pdf-task-pill">{{ $day['tasks_count'] }} Task</span>
                        </td>
                        <td style="vertical-align: middle; text-align: right;">
                            <span class="pdf-status-pill pdf-status-{{ $day['status_type'] }}">{{ $day['status_label'] }}</span>
                            <span style="font-size: 10.5px; font-weight: 800; color: #111827;">{{ number_format($day['total_mins'], 1, '.', '') }} mnt</span>
                            <span style="font-size: 9.5px; color: #9ca3af;">({{ $day['total_hrs'] }} h)</span>
                        </td>
                    </tr>
                </table>

                <div style="border-top: 1px solid #f3f4f6; margin: 8px 0 7px 0;"></div>

                <!-- Segmented Daily Progress Bar -->
                <table style="width: 100%; border-collapse: collapse; height: 6px; background-color: #e5e7eb; border-radius: 3px; overflow: hidden; margin-bottom: 7px;">
                    <tr>
                        @if((float) $day['appr_pct'] > 0)
                            <td style="width: {{ $day['appr_pct'] }}%; background-color: #10b981; height: 6px; padding: 0;"></td>
                        @endif
                        @if((float) $day['pend_pct'] > 0)
                            <td style="width: {{ $day['pend_pct'] }}%; background-color: #fbbf24; height: 6px; padding: 0;"></td>
                        @endif
                        @if((float) $day['rej_pct'] > 0)
                            <td style="width: {{ $day['rej_pct'] }}%; background-color: #f43f5e; height: 6px; padding: 0;"></td>
                        @endif
                        @if((float) $day['appr_pct'] <= 0 && (float) $day['pend_pct'] <= 0 && (float) $day['rej_pct'] <= 0)
                            <td style="width: 100%; background-color: #e5e7eb; height: 6px; padding: 0;"></td>
                        @endif
                    </tr>
                </table>

                <!-- Daily breakdown badges -->
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle;">
                            <span class="pdf-stat-chip-appr">
                                &bull; Approved: {{ $day['approved_hrs'] }}h <span style="font-weight: 400; color: #10b981;">({{ number_format($day['approved_mins'], 1, '.', '') }} mnt)</span>
                            </span>
                            <span class="pdf-stat-chip-rej">
                                &bull; Rejected: {{ $day['rejected_hrs'] }}h <span style="font-weight: 400; color: #f43f5e;">({{ number_format($day['rejected_mins'], 1, '.', '') }} mnt)</span>
                            </span>
                            @if($day['pending_mins'] > 0)
                                <span class="pdf-stat-chip-pend">
                                    &bull; Pending: {{ $day['pending_hrs'] }}h <span style="font-weight: 400; color: #f59e0b;">({{ number_format($day['pending_mins'], 1, '.', '') }} mnt)</span>
                                </span>
                            @endif
                        </td>
                        <td style="vertical-align: middle; text-align: right; font-size: 9.5px; font-weight: 800; color: #374151;">
                            Total: {{ $day['total_hrs'] }}h <span style="font-weight: 400; color: #9ca3af;">({{ number_format($day['total_mins'], 1, '.', '') }} mnt)</span>
                        </td>
                    </tr>
                </table>

                @foreach($day['image_urls'] as $image)
                    @php($src = $files->imageData($image))
                    @if($src)
                        <div style="margin-top: 8px;">
                            <img src="{{ $src }}" alt="Bukti aktivitas {{ $day['date'] }}" style="max-width: 100%; max-height: 260px; border-radius: 8px;">
                        </div>
                    @endif
                @endforeach
            </div>
        @empty
            <p style="color: #6b7280; font-size: 11px;">Tidak ada catatan task harian untuk periode ini.</p>
        @endforelse
    </div>
</div>
@else
    <!-- PAGE 1: PAYSLIP (WEB & PRINT VIEW) -->
    <div class="h-auto flex flex-col">
        <div class="gradient-header"></div>

        <!-- Top Header -->
        <div class="flex justify-between items-start mb-6">
            <div class="flex flex-col">
                <div class="w-14 h-14 bg-black text-white rounded-full flex items-center justify-center text-xl font-bold mb-4 tracking-tighter">
                    AIKu
                </div>
                <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-black mb-4">PAYSLIP</h1>
            </div>
            <div class="text-right text-sm font-semibold text-gray-500 mt-2">
                <div>{{ $slipCode }}</div>
                @if(($status ?? 'belum') === 'sudah')
                    <span class="inline-block mt-1.5 px-3 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">SUDAH DIBAYAR</span>
                @else
                    <span class="inline-block mt-1.5 px-3 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">BELUM DIBAYAR</span>
                @endif
            </div>
        </div>

        <!-- 4-Column Meta Info -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8 text-sm">
            <div>
                <p class="text-gray-400 font-semibold mb-2 text-xs uppercase tracking-wider">From</p>
                <p class="font-bold text-gray-900">AI Ku Studio</p>
                <p class="text-gray-500">Egocentric Video Studio</p>
                <p class="text-gray-500">Banjarmasin, Indonesia</p>
                <p class="text-gray-500">admin@aikustudio.id</p>
            </div>
            <div>
                <p class="text-gray-400 font-semibold mb-2 text-xs uppercase tracking-wider">Bill To</p>
                <p class="font-bold text-gray-900">{{ $employee_name }}</p>
                <p class="text-gray-500">Video Contributor</p>
            </div>
            <div>
                <p class="text-gray-400 font-semibold mb-2 text-xs uppercase tracking-wider">Issued</p>
                <p class="text-gray-900 font-medium">{{ $issuedDate }}</p>
            </div>
            <div>
                <p class="text-gray-400 font-semibold mb-2 text-xs uppercase tracking-wider">Period</p>
                <p class="text-gray-900 font-medium">{{ $period }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="border-t border-gray-200 pt-4 mb-8">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-gray-400 font-semibold border-b border-gray-200">
                        <th class="text-left pb-4 w-1/2">Description</th>
                        <th class="text-center pb-4">Qty (Hrs)</th>
                        <th class="text-right pb-4">Rate</th>
                        <th class="text-right pb-4">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-gray-900">
                    <tr class="border-b border-gray-100">
                        <td class="py-4 font-medium">Approved Work Hours (Total)</td>
                        <td class="text-center py-4">
                            {{ $displayApprovedHours }} <span class="text-xs text-gray-400 font-normal">({{ $approvedMinsTotal }} mins)</span>
                        </td>
                        <td class="text-right py-4">{{ $formatRupiah($ratePerHour) }}</td>
                        <td class="text-right py-4 font-bold">{{ $formatRupiah($approvedPay) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="py-4 text-gray-500 font-medium">Rejected Work Hours (QA Failed)</td>
                        <td class="text-center py-4 text-gray-500">
                            {{ $displayRejectedHours }} <span class="text-xs text-gray-400 font-normal">({{ $rejectedMinsTotal }} mins)</span>
                        </td>
                        <td class="text-right py-4 text-gray-500">{{ $formatRupiah(0) }}</td>
                        <td class="text-right py-4 text-gray-500 font-bold">{{ $formatRupiah(0) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="py-4 text-yellow-600 font-medium">Pending Work Hours (Not Reviewed)</td>
                        <td class="text-center py-4 text-yellow-600">
                            {{ $displayPendingHours }} <span class="text-xs text-yellow-500 font-normal">({{ $pendingMinsFormatted }} mins)</span>
                        </td>
                        <td class="text-right py-4 text-yellow-600">{{ $formatRupiah(0) }}</td>
                        <td class="text-right py-4 text-yellow-600 font-bold">{{ $formatRupiah(0) }}</td>
                    </tr>
                    @if($carryOverVal > 0)
                    <tr class="border-b border-gray-100">
                        <td class="py-4 text-emerald-700 font-medium">Pencairan Sisa Pending Periode Lalu</td>
                        <td class="text-center py-4 text-emerald-700">
                            @if($carryOverHoursVal > 0)
                                {{ number_format($carryOverHoursVal, 2, '.', '') }} <span class="text-xs text-emerald-500 font-normal">({{ $carryOverMinsFormatted }} mins)</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right py-4 text-emerald-700">{{ $carryOverHoursVal > 0 ? $formatRupiah($ratePerHour) : '-' }}</td>
                        <td class="text-right py-4 text-emerald-700 font-bold">+{{ $formatRupiah($carryOverVal) }}</td>
                    </tr>
                    @endif
                    @if($bonusVal > 0)
                    <tr class="border-b border-gray-100">
                        <td class="py-4 text-emerald-700 font-medium">Bonus / Insentif Tambahan</td>
                        <td class="text-center py-4 text-gray-400">-</td>
                        <td class="text-right py-4 text-gray-400">-</td>
                        <td class="text-right py-4 text-emerald-700 font-bold">+{{ $formatRupiah($bonusVal) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <!-- Totals -->
            <div class="flex justify-end mt-4">
                <div class="w-full sm:w-1/3">
                    <div class="flex justify-between py-2 text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>{{ $formatRupiah($calculatedTotalTHP) }}</span>
                    </div>
                    <div class="flex justify-between py-2 text-sm text-gray-600 border-b border-gray-200">
                        <span>Tax (0%)</span>
                        <span>Rp 0</span>
                    </div>
                    <div class="flex justify-between py-3 text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span>{{ $formatRupiah($calculatedTotalTHP) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Details -->
        <div class="mt-6 text-sm pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-between gap-8">
            <div class="flex-1">
                <p class="text-gray-400 font-semibold mb-1 text-xs uppercase tracking-wider">Payment Details</p>
                <p class="text-gray-600 font-medium text-xs">Please refer to the internal finance system for direct bank transfer details.</p>
            </div>
            <div class="flex-1">
                <p class="text-gray-400 font-semibold mb-1 text-xs uppercase tracking-wider">Notes</p>
                <p class="text-gray-600 font-medium text-xs">Thank you for your hard work and contribution to AI Ku Studio!</p>
            </div>
        </div>
    </div>

    <!-- PAGE 2: EVIDENCE & TASK LOGS -->
    <div class="page-break pt-10">
        <div class="flex justify-between items-end mb-4">
            <div>
                <h3 class="text-2xl font-extrabold text-gray-900 tracking-tight">EVIDENCE &amp; TASK LOGS</h3>
                <p class="text-xs text-gray-500 font-medium">Rincian log pengerjaan task harian yang tersinkronisasi dengan status review Verbos.</p>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Tasks</span>
                <p class="text-lg font-black text-gray-900">{{ $grandTotalTasks }} Tasks</p>
            </div>
        </div>

        <!-- Overall Summary Progress Card -->
        <div class="mb-6 p-5 bg-gray-50 rounded-2xl border border-gray-200 shadow-sm space-y-3">
            <div class="flex justify-between items-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                <span>Ringkasan Periode Keseluruhan</span>
                <span class="text-gray-900 font-black text-sm">{{ $displayTotalHours }}h <span class="text-gray-400 font-normal">({{ (int) round($loggedHoursVal * 60) }} mnt)</span></span>
            </div>

            <!-- Big Segmented Progress Bar -->
            <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden flex shadow-inner">
                <div style="width: {{ $overallApprPct }}%" class="bg-emerald-500" title="Approved"></div>
                <div style="width: {{ $overallPendPct }}%" class="bg-amber-400" title="Pending"></div>
                <div style="width: {{ $overallRejPct }}%" class="bg-rose-500" title="Rejected"></div>
            </div>

            <!-- 4 Highlight Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-center">
                <div class="bg-white p-2 rounded-xl border border-gray-200/80">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase">Total Kirim</span>
                    <span class="text-xs font-black text-gray-900">{{ $displayTotalHours }}h</span>
                </div>
                <div class="bg-emerald-50/70 p-2 rounded-xl border border-emerald-200/80">
                    <span class="block text-[10px] font-bold text-emerald-700 uppercase">Approved</span>
                    <span class="text-xs font-black text-emerald-700">{{ $displayApprovedHours }}h</span>
                </div>
                <div class="bg-rose-50/70 p-2 rounded-xl border border-rose-200/80">
                    <span class="block text-[10px] font-bold text-rose-700 uppercase">Rejected</span>
                    <span class="text-xs font-black text-rose-700">{{ $displayRejectedHours }}h</span>
                </div>
                <div class="bg-amber-50/70 p-2 rounded-xl border border-amber-200/80">
                    <span class="block text-[10px] font-bold text-amber-700 uppercase">Pending</span>
                    <span class="text-xs font-black text-amber-700">{{ $displayPendingHours }}h</span>
                </div>
            </div>
        </div>

        <p class="text-xs text-amber-800 mb-6 bg-amber-50 p-3 rounded-xl border border-amber-200 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><strong>Catatan:</strong> Bagian task yang berstatus <em>Pending (Ditinjau)</em> akan otomatis diikutsertakan pada rekonsiliasi periode pembayaran berikutnya setelah hasil QA Verbos diterbitkan.</span>
        </p>

        <div class="space-y-3">
            @forelse($processedDays as $day)
                <div class="mb-4 bg-white border border-gray-200 rounded-2xl shadow-sm evidence-card break-inside-avoid overflow-hidden transition-all">
                    <!-- Accordion Header: Click to Expand / Collapse -->
                    <div class="p-4 bg-gray-50/70 border-b border-gray-100 cursor-pointer hover:bg-gray-100/70 transition-colors select-none"
                         onclick="toggleDayAccordion('day-body-{{ $day['index'] }}', 'day-chevron-{{ $day['index'] }}')">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 font-extrabold text-xs flex items-center justify-center border border-indigo-100 shrink-0">
                                    {{ $day['code'] }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-black text-gray-900 text-sm tracking-tight">{{ $day['formatted_date'] }}</h4>
                                        <span class="bg-gray-200/80 text-gray-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">{{ $day['tasks_count'] }} Task</span>
                                    </div>
                                    <p class="text-[11px] text-gray-400 font-medium no-print">Klik untuk buka/tutup rincian task harian</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 self-end sm:self-center">
                                @if($day['status_type'] === 'full')
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $day['status_label'] }}</span>
                                @elseif($day['status_type'] === 'partial')
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">{{ $day['status_label'] }}</span>
                                @elseif($day['status_type'] === 'rejected')
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200">{{ $day['status_label'] }}</span>
                                @else
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">{{ $day['status_label'] }}</span>
                                @endif

                                <div class="text-right">
                                    <span class="text-xs font-black text-gray-900">{{ number_format($day['total_mins'], 1, '.', '') }} mnt</span>
                                    <span class="text-[11px] text-gray-400 font-medium">({{ $day['total_hrs'] }} h)</span>
                                </div>
                                <!-- Chevron Icon -->
                                <div class="w-7 h-7 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-500 no-print">
                                    <svg id="day-chevron-{{ $day['index'] }}" class="w-4 h-4 transition-transform duration-200 {{ $day['index'] === 0 ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- DAILY ACCUMULATION PROGRESS BAR & STATS (Visible in Web & Print) -->
                        <div class="mt-3 pt-2.5 border-t border-gray-200/70 space-y-2">
                            <!-- Segmented Daily Progress Bar -->
                            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden flex shadow-inner">
                                <div style="width: {{ $day['appr_pct'] }}%" class="bg-emerald-500" title="Approved: {{ $day['appr_pct'] }}%"></div>
                                <div style="width: {{ $day['pend_pct'] }}%" class="bg-amber-400" title="Pending: {{ $day['pend_pct'] }}%"></div>
                                <div style="width: {{ $day['rej_pct'] }}%" class="bg-rose-500" title="Rejected: {{ $day['rej_pct'] }}%"></div>
                            </div>

                            <!-- Daily accumulation breakdown badges -->
                            <div class="flex flex-wrap items-center justify-between text-[11px] gap-2 pt-0.5">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50/90 px-2 py-0.5 rounded-md border border-emerald-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Approved: {{ $day['approved_hrs'] }}h <span class="text-emerald-500 font-normal">({{ number_format($day['approved_mins'], 1, '.', '') }} mnt)</span></span>
                                    </span>
                                    <span class="inline-flex items-center gap-1 font-bold text-rose-700 bg-rose-50/90 px-2 py-0.5 rounded-md border border-rose-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Rejected: {{ $day['rejected_hrs'] }}h <span class="text-rose-500 font-normal">({{ number_format($day['rejected_mins'], 1, '.', '') }} mnt)</span></span>
                                    </span>
                                    @if($day['pending_mins'] > 0)
                                        <span class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50/90 px-2 py-0.5 rounded-md border border-amber-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            <span>Pending: {{ $day['pending_hrs'] }}h <span class="text-amber-500 font-normal">({{ number_format($day['pending_mins'], 1, '.', '') }} mnt)</span></span>
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] font-extrabold text-gray-700">
                                    Total: {{ $day['total_hrs'] }}h <span class="text-gray-400 font-normal">({{ number_format($day['total_mins'], 1, '.', '') }} mnt)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Body with Individual Tasks -->
                    <div id="day-body-{{ $day['index'] }}" class="evidence-body p-4 space-y-3 bg-white {{ $day['index'] === 0 ? '' : 'hidden' }}">
                        <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider pb-1 border-b border-gray-100">
                            <span>Rincian Tugas ({{ $day['tasks_count'] }} Task)</span>
                            <span class="text-[11px] text-gray-400 font-normal">Verbos Video Log Task Breakdown</span>
                        </div>
                        <div class="space-y-2.5">
                            @foreach($day['tasks'] as $t)
                                <div class="evidence-task-card bg-gray-50/80 rounded-xl p-3.5 border border-gray-200/80 space-y-2">
                                    <div class="flex justify-between items-center gap-3">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="w-4 h-4 rounded-full bg-white text-gray-500 font-extrabold text-[10px] flex items-center justify-center border border-gray-200 shrink-0">{{ $t['idx'] }}</span>
                                            <h5 class="text-xs font-bold text-gray-900 leading-snug">{{ $t['task_name'] }}</h5>
                                            @if(!empty($t['time_str']))
                                                <span class="text-[11px] text-gray-400 font-medium">&middot; {{ $t['time_str'] }}</span>
                                            @endif
                                        </div>
                                        <div class="text-right text-[11px] font-bold text-gray-700 shrink-0">
                                            {{ number_format($t['total_mins'], 1, '.', '') }} mnt <span class="text-gray-400 font-normal">({{ $t['total_hrs'] }}h)</span>
                                        </div>
                                    </div>

                                    <!-- Mini Progress Bar for Task -->
                                    <div class="h-1.5 w-full bg-gray-200 rounded-full overflow-hidden flex">
                                        <div style="width: {{ $t['appr_pct'] }}%" class="bg-emerald-500"></div>
                                        <div style="width: {{ $t['pend_pct'] }}%" class="bg-amber-400"></div>
                                        <div style="width: {{ $t['rej_pct'] }}%" class="bg-rose-500"></div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between text-[11px] pt-0.5 text-gray-600">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center gap-1 font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>{{ number_format($t['approved_mins'], 1, '.', '') }} mnt lolos ({{ $t['approved_hrs'] }}h)</span>
                                            </span>
                                            <span class="inline-flex items-center gap-1 font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                <span>{{ number_format($t['rejected_mins'], 1, '.', '') }} mnt ditolak ({{ $t['rejected_hrs'] }}h)</span>
                                            </span>
                                            @if($t['pending_mins'] > 0)
                                                <span class="inline-flex items-center gap-1 font-medium">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                                    <span>{{ number_format($t['pending_mins'], 1, '.', '') }} mnt ditinjau</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($day['notes']))
                            <div class="mt-2 text-xs text-gray-600 bg-amber-50/60 p-2.5 rounded-xl border border-amber-200/60">
                                <span class="font-bold text-amber-900">Catatan Harian:</span> {{ $day['notes'] }}
                            </div>
                        @endif

                        @foreach($day['image_urls'] as $image)
                            @php($src = $files->evidencePath($image) ? $image : null)
                            @if($src)
                                <img class="max-w-full max-h-[520px] rounded-xl border border-gray-200 mt-2" src="{{ $src }}" alt="Bukti aktivitas tanggal {{ $day['date'] }}">
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada catatan task harian untuk periode ini.</p>
            @endforelse
        </div>
    </div>
@endif
