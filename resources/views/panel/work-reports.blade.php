<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kerja Harian - AIKu Studio</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">

    <style>
        [x-cloak] { display: none !important; }
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; background: white !important; height: auto !important; overflow: visible !important; }
            .print-only { display: block !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="{sidebarOpen:window.innerWidth >= 768}" @keydown.escape.window="sidebarOpen=false" x-cloak>

    <button type="button" x-show="sidebarOpen" @click="sidebarOpen=false" aria-label="Tutup menu" class="fixed inset-0 z-40 bg-slate-900/30 md:hidden"></button>

    <!-- LEFT SIDEBAR -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-white border-r border-slate-200/80 flex flex-col shrink-0 min-h-screen transition-all duration-300 z-50 md:relative"
           :class="sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-0 md:w-0 overflow-hidden hidden'">

        <!-- Brand Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-100">
            <a href="index.html" class="flex items-center gap-3 cursor-pointer group">
                <div class="relative flex items-center justify-center w-9 h-9">
                    <div class="w-7 h-7 rounded-full border-[3px] border-[#00B090] absolute -left-0.5"></div>
                    <div class="w-7 h-7 rounded-full border-[3px] border-emerald-400 absolute -right-0.5 mix-blend-multiply opacity-80"></div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-extrabold tracking-tight text-slate-900">AIKu</span>
                    <span class="text-xs font-bold text-[#00B090] bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-100">Studio</span>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Sidebar Navigation Menu -->
        <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
            <div class="space-y-1.5">
                <div class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Menu Utama</div>

                <a href="index.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Dashboard</span>
                </a>

                <a href="recordings.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Task List (Rekaman)</span>
                </a>

                <a href="payslips.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Riwayat Slip Gaji</span>
                </a>

                <a href="shifts.html" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition text-[#009B7E] bg-[#EDFAF8] shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Pengaturan Shift</span>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-[#00B090]"></span>
                </a>

                <a href="create.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Buat Slip Gaji</span>
                </a>

                <a href="contributors.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Teams & Kontributor</span>
                </a>

                <a href="profit.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    <span>Reporting & Profit</span>
                </a>
            </div>
        </div>

        <div class="px-4 pb-3">@include('attendance.nav')</div>
        <!-- Sidebar Footer Admin -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3 truncate">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-sm">
                    AK
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-slate-50">@include('work-reports.admin')</main>
<form method="post" action="/logout" class="fixed bottom-3 left-3 z-50">@csrf<button class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 shadow">Keluar akun</button></form>
</body></html>
