@php
    $sections = [
        [
            'title' => 'Utama',
            'items' => [
                ['label' => 'Dashboard', 'path' => '', 'icon' => 'M3 10l9-7 9 7M5 9v11h5v-6h4v6h5V9'],
            ],
        ],
        [
            'title' => 'Operasional',
            'items' => [
                ['label' => 'Task List (Rekaman)', 'path' => 'recordings', 'icon' => 'M9 5H5v16h14V5h-4M9 3h6v4H9V3m0 10l2 2 4-4'],
                ['label' => 'Laporan Harian', 'path' => 'work-reports', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['label' => 'Absensi', 'path' => 'attendance', 'icon' => 'M8 3v4m8-4v4M4 10h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z'],
            ],
        ],
        [
            'title' => 'Payroll & Keuangan',
            'items' => [
                ['label' => 'Buat Slip Gaji', 'path' => 'create', 'icon' => 'M12 8v8m-4-4h8m5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Riwayat Slip Gaji', 'path' => 'payslips', 'icon' => 'M14 3H5v18h14V8l-5-5v5h5M9 12h6m-6 4h6'],
                ['label' => 'Reporting & Profit', 'path' => 'profit', 'icon' => 'M12 3v9h9A9 9 0 1012 3zM16 3.5V8h4.5'],
            ],
        ],
        [
            'title' => 'Tim & Pengaturan',
            'items' => [
                ['label' => 'Teams & Kontributor', 'path' => 'contributors', 'icon' => 'M16 21v-2a4 4 0 00-8 0v2M12 11a4 4 0 100-8 4 4 0 000 8m8 10v-2a4 4 0 00-3-3.87M4 21v-2a4 4 0 013-3.87'],
                ['label' => 'Pengaturan Shift', 'path' => 'shifts', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'HP & Akun Studio', 'path' => 'devices', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
            ],
        ],
    ];
@endphp
<a href="#admin-content" class="workspace-skip">Lewati navigasi</a>
@include('layouts.workspace-sidebar', ['admin' => true, 'sections' => $sections])
