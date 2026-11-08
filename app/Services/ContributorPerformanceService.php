<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\DailyWorkReport;
use App\Models\ShiftDay;
use App\Models\StudioRecording;
use Illuminate\Support\Collection;

class ContributorPerformanceService
{
    /**
     * Get performance metrics, smart evaluation, and recommendations for a single contributor.
     */
    public function getContributorPerformance(Contributor $contributor): array
    {
        $name = trim($contributor->nama);
        $id = $contributor->id;
        $userId = $contributor->user?->id;

        // 1. Studio Recordings Metrics
        $recordings = StudioRecording::where(function ($query) use ($id, $name) {
            $query->where('assigned_contributor_id', $id)
                  ->orWhereRaw('LOWER(TRIM(assigned_worker_name)) = ?', [mb_strtolower($name)]);
        })->get();

        $totalRecordings = $recordings->count();
        $totalRecordedMin = round($recordings->sum('duration_min'), 1);
        $totalBillableMin = round($recordings->sum('billable_min'), 1);
        $totalRejectedMin = round($recordings->sum('rejected_min'), 1);
        $totalPendingMin = round($recordings->sum('pending_min'), 1);

        $approvalRate = $totalRecordedMin > 0
            ? round(($totalBillableMin / $totalRecordedMin) * 100, 1)
            : ($totalRecordings > 0 ? 100.0 : 0.0);

        $rejectRate = $totalRecordedMin > 0
            ? round(($totalRejectedMin / $totalRecordedMin) * 100, 1)
            : 0.0;

        // 2. Attendance & Leave Metrics
        $attendances = Attendance::where(function ($query) use ($id, $userId) {
            $query->where('contributor_id', $id);
            if ($userId) {
                $query->orWhere('user_id', $userId);
            }
        })->get();

        $totalAttendances = $attendances->count();

        // Count leaves from ShiftDays configurations
        $shiftDays = ShiftDay::all();
        $leaveCount = 0;
        $sickCount = 0;
        $offCount = 0;
        $scheduledCount = 0;

        foreach ($shiftDays as $shiftDay) {
            $config = $shiftDay->config;
            $karyawan = $config['karyawan'] ?? [];
            if (! is_array($karyawan)) {
                continue;
            }

            foreach ($karyawan as $w) {
                $matchesId = ! empty($w['contributor_id']) && (int) $w['contributor_id'] === $id;
                $matchesName = ! empty($w['name']) && mb_strtolower(trim($w['name'])) === mb_strtolower($name);

                if ($matchesId || $matchesName) {
                    $status = $w['status'] ?? 'scheduled';
                    if ($status === 'izin') {
                        $leaveCount++;
                    } elseif ($status === 'sakit') {
                        $sickCount++;
                    } elseif ($status === 'libur') {
                        $offCount++;
                    } else {
                        $scheduledCount++;
                    }
                }
            }
        }

        // 3. Daily Work Reports Metrics
        $workReports = DailyWorkReport::where(function ($query) use ($id, $name) {
            $query->where('contributor_id', $id)
                  ->orWhereRaw('LOWER(TRIM(contributor_name)) = ?', [mb_strtolower($name)]);
        })->get();

        $totalWorkReports = $workReports->count();
        $totalReportedMin = (int) $workReports->sum('total_duration_minutes');

        // 4. Smart Evaluation Engine (Score: 0 - 100)
        $isAdmin = ($contributor->role === 'admin');
        if ($isAdmin) {
            $eval = [
                'score' => null,
                'grade' => 'Manajemen',
                'badge_color' => 'purple',
                'is_admin' => true,
                'summary' => 'Akun Administrator / Manajemen Studio. Berfokus pada tata kelola operasional, verifikasi validasi tugas, dan administrasi tim.',
                'reasons' => [
                    'Akun memiliki hak akses administratif penuh ke seluruh sistem studio.',
                    'Dikecualikan dari penilaian target rekaman bot lapangan dan rasio persetujuan QA.',
                    "Tercatat {$totalAttendances} sesi presensi masuk dan {$totalWorkReports} laporan kerja operasional.",
                ],
                'recommendations' => [
                    'Lakukan pemantauan berkala terhadap status rekaman harian dan asistensi kontributor.',
                    'Pastikan antrean validasi QC dan rekap payroll per siklus 22-21 berjalan tepat waktu.',
                ],
            ];
        } else {
            $eval = $this->evaluatePerformance(
                $totalRecordings,
                $totalRecordedMin,
                $totalBillableMin,
                $totalRejectedMin,
                $approvalRate,
                $rejectRate,
                $totalAttendances,
                $leaveCount,
                $sickCount,
                $scheduledCount
            );
        }

        return [
            'contributor' => [
                'id' => $contributor->id,
                'nama' => $contributor->nama,
                'email' => $contributor->email,
                'no_wa' => $contributor->no_wa,
                'domisili' => $contributor->domisili,
                'shift' => $contributor->shift,
                'hp' => $contributor->hp,
                'no_rek' => $contributor->no_rek,
                'role' => $contributor->role,
                'login_enabled' => (bool) $contributor->email,
            ],
            'recordings' => [
                'total_count' => $totalRecordings,
                'total_duration_min' => $totalRecordedMin,
                'total_duration_hours' => round($totalRecordedMin / 60, 1),
                'billable_min' => $totalBillableMin,
                'billable_hours' => round($totalBillableMin / 60, 1),
                'rejected_min' => $totalRejectedMin,
                'rejected_hours' => round($totalRejectedMin / 60, 1),
                'pending_min' => $totalPendingMin,
                'pending_hours' => round($totalPendingMin / 60, 1),
                'approval_rate' => $approvalRate,
                'reject_rate' => $rejectRate,
            ],
            'attendance' => [
                'verified_attendances' => $totalAttendances,
                'leave_count' => $leaveCount,
                'sick_count' => $sickCount,
                'off_count' => $offCount,
                'scheduled_count' => $scheduledCount,
                'total_leaves' => $leaveCount + $sickCount,
            ],
            'work_reports' => [
                'total_reports' => $totalWorkReports,
                'total_reported_min' => $totalReportedMin,
                'total_reported_hours' => round($totalReportedMin / 60, 1),
            ],
            'evaluation' => $eval,
        ];
    }

    /**
     * Compute smart evaluation grade, reasoning, and actionable recommendations.
     */
    private function evaluatePerformance(
        int $recordingsCount,
        float $recordedMin,
        float $billableMin,
        float $rejectedMin,
        float $approvalRate,
        float $rejectRate,
        int $attendances,
        int $leaveCount,
        int $sickCount,
        int $scheduledCount
    ): array {
        $score = 0;
        $reasons = [];
        $recommendations = [];

        // Quality Subscore (max 50 points)
        if ($recordingsCount === 0) {
            $qualityScore = 25;
            $reasons[] = 'Belum ada data rekaman bot yang ditugaskan kepada akun ini.';
            $recommendations[] = 'Mulai penugasan sesi rekaman perdana untuk menguji kepatuhan SOP dan akurasi durasi.';
        } elseif ($approvalRate >= 92.0 && $rejectRate <= 5.0) {
            $qualityScore = 50;
            $reasons[] = "Tingkat persetujuan (approval rate) sangat memuaskan: {$approvalRate}% ({$billableMin} menit billable).";
        } elseif ($approvalRate >= 80.0 && $rejectRate <= 15.0) {
            $qualityScore = 40;
            $reasons[] = "Tingkat persetujuan rekaman baik ({$approvalRate}%), dengan reject rate {$rejectRate}%.";
        } elseif ($approvalRate >= 65.0) {
            $qualityScore = 25;
            $reasons[] = "Approval rate berada di batas moderat ({$approvalRate}%). Ditemukan {$rejectedMin} menit rekaman ditolak (reject {$rejectRate}%).";
            $recommendations[] = 'Tinjau ulang panduan framing kamera dan stabilitas angle agar meminimalisir reject saat QA.';
        } else {
            $qualityScore = 12;
            $reasons[] = "Approval rate rendah ({$approvalRate}%). Rasio penolakan QA tinggi ({$rejectRate}% / {$rejectedMin} menit rejected).";
            $recommendations[] = 'Lakukan evaluasi menyeluruh terhadap hasil rekaman dan berikan asistensi ulang mengenai standar QA studio.';
            $recommendations[] = 'Periksa 20 detik awal setiap video sebelum melanjutkan sesi pengerjaan berikutnya.';
        }
        $score += $qualityScore;

        // Attendance & Discipline Subscore (max 30 points)
        $totalLeaves = $leaveCount + $sickCount;
        if ($attendances >= 15 && $totalLeaves <= 2) {
            $attScore = 30;
            $reasons[] = "Kehadiran sangat konsisten ({$attendances} hari presensi masuk) dengan frekuensi izin minimal ({$leaveCount} izin, {$sickCount} sakit).";
        } elseif ($attendances >= 5 && $totalLeaves <= 3) {
            $attScore = 24;
            $reasons[] = "Kehadiran aktif ({$attendances} hari masuk) dengan catatan {$totalLeaves} hari izin/sakit.";
        } elseif ($totalLeaves > 4) {
            $attScore = 12;
            $reasons[] = "Frekuensi izin/sakit cukup sering ({$leaveCount} kali izin, {$sickCount} kali sakit).";
            $recommendations[] = 'Tingkatkan kedisiplinan jadwal kerja atau ajukan penyesuaian shift jika sering berhalangan hadir.';
        } else {
            $attScore = 18;
            $reasons[] = "Tercatat {$attendances} hari presensi dan {$totalLeaves} hari izin/sakit.";
        }
        $score += $attScore;

        // Output & Volume Subscore (max 20 points)
        $hours = $recordedMin / 60;
        if ($hours >= 40) {
            $volScore = 20;
            $reasons[] = "Volume jam kerja sangat tinggi (total {$hours} jam rekaman terlaksana).";
        } elseif ($hours >= 15) {
            $volScore = 16;
            $reasons[] = "Volume jam kerja memadai ({$hours} jam rekaman).";
        } elseif ($hours >= 3) {
            $volScore = 10;
            $reasons[] = "Jam kerja masih dalam tahap awal/moderat ({$hours} jam rekaman).";
            $recommendations[] = 'Tingkatkan frekuensi pengerjaan sesi per shift agar hasil rekaman mingguan lebih optimal.';
        } else {
            $volScore = 5;
            $reasons[] = 'Total jam rekaman masih sangat minim (< 3 jam).';
        }
        $score += $volScore;

        // Overall Performance Grade & Badge
        if ($score >= 85) {
            $grade = 'Sangat Baik';
            $badgeColor = 'emerald';
            $summary = 'Pekerja berkinerja tinggi dengan kualitas rekaman konsisten dan kedisiplinan presensi yang sangat baik.';
            if (empty($recommendations)) {
                $recommendations[] = 'Pertahankan konsistensi kualitas. Kontributor siap untuk penugasan batch rekaman berprioritas tinggi.';
            }
        } elseif ($score >= 70) {
            $grade = 'Bagus';
            $badgeColor = 'teal';
            $summary = 'Pekerja memiliki kinerja stabil dan memenuhi standar operasional studio dengan baik.';
            if (empty($recommendations)) {
                $recommendations[] = 'Pertahankan ritme kerja dan pantau status rekaman harian agar approval rate tetap di atas 90%.';
            }
        } elseif ($score >= 50) {
            $grade = 'Cukup';
            $badgeColor = 'amber';
            $summary = 'Kinerja pekerja berada di level rata-rata. Terdapat beberapa aspek kualitas atau presensi yang perlu dioptimalkan.';
            if (empty($recommendations)) {
                $recommendations[] = 'Lakukan pendampingan ringan terkait kepatuhan SOP perekaman dan pemenuhan target durasi shift.';
            }
        } else {
            $grade = 'Perlu Evaluasi';
            $badgeColor = 'rose';
            $summary = 'Kinerja di bawah target studio. Memerlukan peninjauan dan evaluasi langsung dari admin operasional.';
            if (empty($recommendations)) {
                $recommendations[] = 'Jadwalkan sesi evaluasi tatap muka untuk membahas kendala teknis dan pencapaian target harian.';
            }
        }

        return [
            'score' => $score,
            'grade' => $grade,
            'badge_color' => $badgeColor,
            'summary' => $summary,
            'reasons' => $reasons,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Get aggregate performance summary for the whole studio.
     */
    public function getStudioPerformanceSummary(): array
    {
        $contributors = Contributor::orderBy('nama')->get();
        $recordings = StudioRecording::all();
        $attendances = Attendance::all();

        $totalRecordedMin = round($recordings->sum('duration_min'), 1);
        $totalBillableMin = round($recordings->sum('billable_min'), 1);
        $totalRejectedMin = round($recordings->sum('rejected_min'), 1);

        $studioApprovalRate = $totalRecordedMin > 0
            ? round(($totalBillableMin / $totalRecordedMin) * 100, 1)
            : 0.0;

        $gradeCounts = [
            'Sangat Baik' => 0,
            'Bagus' => 0,
            'Cukup' => 0,
            'Perlu Evaluasi' => 0,
        ];

        $contributorStats = [];

        foreach ($contributors as $person) {
            $perf = $this->getContributorPerformance($person);
            $grade = $perf['evaluation']['grade'];
            $isAdmin = ($person->role === 'admin');

            // Hanya pekerja produksi yang masuk ke penghitungan distribusi grade
            if (! $isAdmin && isset($gradeCounts[$grade])) {
                $gradeCounts[$grade]++;
            }

            $contributorStats[] = [
                'id' => $person->id,
                'nama' => $person->nama,
                'role' => $person->role,
                'is_admin' => $isAdmin,
                'score' => $perf['evaluation']['score'],
                'grade' => $grade,
                'badge_color' => $perf['evaluation']['badge_color'],
                'approval_rate' => $isAdmin ? null : $perf['recordings']['approval_rate'],
                'total_hours' => $perf['recordings']['total_duration_hours'],
                'attendances' => $perf['attendance']['verified_attendances'],
                'leaves' => $perf['attendance']['total_leaves'],
            ];
        }

        return [
            'studio' => [
                'total_contributors' => $contributors->count(),
                'total_recordings' => $recordings->count(),
                'total_hours' => round($totalRecordedMin / 60, 1),
                'billable_hours' => round($totalBillableMin / 60, 1),
                'rejected_hours' => round($totalRejectedMin / 60, 1),
                'studio_approval_rate' => $studioApprovalRate,
                'total_attendances' => $attendances->count(),
                'grade_distribution' => $gradeCounts,
            ],
            'contributors_summary' => $contributorStats,
        ];
    }
}
