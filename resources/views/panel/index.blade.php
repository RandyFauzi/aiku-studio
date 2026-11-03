<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Executive Dashboard & Operations</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="executiveDashboardData()" x-init="init()" x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN APP CONTENT AREA -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <div class="hidden sm:flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-100 rounded-full text-xs font-semibold text-[#009B7E]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Studio Operations Connected</span>
                </div>
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-3">
                <!-- Sync Button -->
                <button @click="triggerSyncNow()" 
                        :disabled="syncing"
                        class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold transition shadow-2xs flex items-center gap-2 disabled:opacity-50">
                    <svg class="w-4 h-4 text-[#00B090]" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span class="hidden md:inline" x-text="syncing ? 'Menyinkronkan...' : 'Sinkronkan Data'"></span>
                </button>

                <!-- Primary Action: Buat Slip Gaji -->
                <a href="create.html" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Slip Gaji</span>
                </a>
                
            </div>
        </header>

        <!-- DASHBOARD BODY CONTAINER -->
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- HERO GREETING & OPERATIONS SUMMARY -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-sm relative overflow-hidden">
                <!-- Background Geometric Glow -->
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-[#00B090]/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute top-0 right-1/4 w-40 h-40 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2.5 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>AIKu Studio Command Center</span>
                            <span class="text-slate-500">•</span>
                            <span class="text-slate-300">Live Production Overview</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white mb-2">
                            Selamat datang, {{ auth()->user()->name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                            Pantau validasi rekaman video, tingkat kelulusan QC, performa kerja kontributor tim, serta distribusi pembayaran payroll secara akurat dan transparan.
                        </p>
                    </div>

                    <!-- Quick Action Chips -->
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                        <div class="bg-white/10 backdrop-blur-md border border-white/10 px-3.5 py-2 rounded-xl text-xs font-medium text-slate-200 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Periode Aktif: <strong>Okt 2026</strong></span>
                        </div>
                        <button @click="loadAllDashboardData()" class="bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/10 px-3 py-2 rounded-xl text-xs font-medium text-white transition flex items-center gap-1.5" title="Refresh Ringkasan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 EXECUTIVE KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- KPI 1: Total Durasi Billable (Approved) -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Produksi Disetujui</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#00B090] flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <div class="text-2xl sm:text-3xl font-black text-slate-900" x-text="billableHours"></div>
                        <span class="text-sm font-bold text-slate-400">Jam Billable</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                        <span>Total: <strong class="text-slate-700" x-text="totalRecordedHours + 'h'"></strong></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-[#009B7E] border border-emerald-100">
                            <span x-text="billablePercentage + '% Approved'"></span>
                        </span>
                    </div>
                </div>

                <!-- KPI 2: Total Realisasi Penggajian -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Realisasi Payroll</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <div class="text-2xl sm:text-3xl font-black text-slate-900" x-text="formatRupiah(totalPayrollPaid)"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                        <span>Slip Gaji Terbit:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                            <span x-text="totalSlipsCount + ' Dokumen'"></span>
                        </span>
                    </div>
                </div>

                <!-- KPI 3: Antrean Tugas Siap Rekap -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Antrean Rekap</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <div class="text-2xl sm:text-3xl font-black text-slate-900" x-text="recordingsStats.belumRekapCount || 0"></div>
                        <span class="text-sm font-bold text-slate-400">Tugas</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                        <span>Status Rekaman:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                            Siap Digenerate
                        </span>
                    </div>
                </div>

                <!-- KPI 4: Tim & Kontributor Aktif -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tim Kontributor</span>
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <div class="text-2xl sm:text-3xl font-black text-slate-900" x-text="contributors.length || 0"></div>
                        <span class="text-sm font-bold text-slate-400">Pekerja Aktif</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                        <span>Kategori Tim:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-100">
                            5 Shift Kerja
                        </span>
                    </div>
                </div>

            </div>

            <!-- ACTIONABLE ATTENTION & ALERTS CENTER -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Alert 1: Unassigned Tasks -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl p-5 flex flex-col justify-between shadow-2xs">
                    <div>
                        <div class="flex items-center gap-2 text-amber-800 font-bold text-xs uppercase tracking-wider mb-1.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Tugas Belum Ditandai</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1">
                            <span x-text="recordingsStats.unassignedCount || 0"></span> Rekaman Perlu Di-assign
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            Terdapat tugas rekaman studio yang belum dihubungkan ke kontributor pekerja. Tandai sekarang untuk perhitungan penggajian akurat.
                        </p>
                    </div>
                    <a href="recordings.html" class="inline-flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-bold text-amber-900 bg-amber-100/80 hover:bg-amber-200 transition">
                        <span>Buka & Tandai Tugas</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <!-- Alert 2: Pending QC -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200/80 rounded-2xl p-5 flex flex-col justify-between shadow-2xs">
                    <div>
                        <div class="flex items-center gap-2 text-blue-800 font-bold text-xs uppercase tracking-wider mb-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Validasi QC Pending</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1">
                            <span x-text="pendingHours"></span> Jam (<span x-text="Math.round(recordingsStats.pendingMinutes || 0)"></span> mnt) Menunggu
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            Sebagian rekaman video masih menunggu konfirmasi kelulusan QC client. Pantau status QC secara berkala.
                        </p>
                    </div>
                    <a href="recordings.html" class="inline-flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-bold text-blue-900 bg-blue-100/80 hover:bg-blue-200 transition">
                        <span>Cek Validasi QC</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <!-- Alert 3: Generate Payslip Ready -->
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200/80 rounded-2xl p-5 flex flex-col justify-between shadow-2xs">
                    <div>
                        <div class="flex items-center gap-2 text-emerald-800 font-bold text-xs uppercase tracking-wider mb-1.5">
                            <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Generate Slip Gaji</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1">
                            <span x-text="recordingsStats.belumRekapCount || 0"></span> Rekaman Siap Diproses
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            Seluruh rekaman tugas disetujui (Approved) siap digenerate menjadi dokumen slip gaji resmi beserta lampiran log video.
                        </p>
                    </div>
                    <a href="create.html" class="inline-flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-900 bg-emerald-100/80 hover:bg-emerald-200 transition">
                        <span>Buat Slip Gaji Baru</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

            </div>

            <!-- ANALYTICAL VISUAL GRIDS (2 COLUMNS: 7 / 5) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- LEFT COLUMN (7 COLS): QC Breakdown & Production Distribution -->
                <div class="lg:col-span-7 bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Kualitas Produksi & Breakdown QC</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Distribusi status durasi dari seluruh rekaman studio</p>
                        </div>
                        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">
                            Total: <span x-text="totalRecordedHours + ' Jam'"></span>
                        </span>
                    </div>

                    <!-- Multi-segment Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-600">Rasio Status Rekaman Studio</span>
                            <span class="text-[#009B7E] font-bold" x-text="billablePercentage + '% Approved Rate'"></span>
                        </div>
                        
                        <div class="h-4 w-full bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                            <div class="bg-[#00B090] transition-all duration-500" :style="'width: ' + billablePercentage + '%'" title="Billable Approved"></div>
                            <div class="bg-rose-500 transition-all duration-500" :style="'width: ' + rejectedPercentage + '%'" title="Rejected"></div>
                            <div class="bg-amber-400 transition-all duration-500" :style="'width: ' + pendingPercentage + '%'" title="Pending"></div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#00B090]"></span>
                                <span>Billable: <strong class="text-slate-700" x-text="billableHours + 'h (' + billablePercentage + '%)'"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <span>Reject: <strong class="text-slate-700" x-text="rejectedHours + 'h (' + rejectedPercentage + '%)'"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span>Pending: <strong class="text-slate-700" x-text="pendingHours + 'h (' + pendingPercentage + '%)'"></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Metric Mini-Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                        <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-100">
                            <div class="text-[11px] font-bold text-slate-400 uppercase">Billable (Appr)</div>
                            <div class="text-lg font-black text-[#009B7E] mt-1" x-text="billableHours + 'h'"></div>
                            <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.billableMinutes || 0).toLocaleString() + ' mnt'"></div>
                        </div>
                        <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-100">
                            <div class="text-[11px] font-bold text-slate-400 uppercase">Rejected</div>
                            <div class="text-lg font-black text-rose-500 mt-1" x-text="rejectedHours + 'h'"></div>
                            <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.rejectedMinutes || 0).toLocaleString() + ' mnt'"></div>
                        </div>
                        <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-100">
                            <div class="text-[11px] font-bold text-slate-400 uppercase">Pending QC</div>
                            <div class="text-lg font-black text-amber-500 mt-1" x-text="pendingHours + 'h'"></div>
                            <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.pendingMinutes || 0).toLocaleString() + ' mnt'"></div>
                        </div>
                        <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-100">
                            <div class="text-[11px] font-bold text-slate-400 uppercase">Total Logged</div>
                            <div class="text-lg font-black text-slate-800 mt-1" x-text="totalRecordedHours + 'h'"></div>
                            <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.totalMinutes || 0).toLocaleString() + ' mnt'"></div>
                        </div>
                    </div>

                    <!-- Progress Assignment Tim -->
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-semibold text-slate-700">Tingkat Penugasan Tugas ke Kontributor</span>
                            <span class="font-bold text-slate-900" x-text="assignedPercentage + '% Selesai Ditandai'"></span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex">
                            <div class="bg-indigo-600 transition-all duration-500" :style="'width: ' + assignedPercentage + '%'"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1.5">
                            <span>Ditandai: <strong class="text-slate-700" x-text="(recordingsStats.assignedCount || 0) + ' tugas'"></strong></span>
                            <span>Belum Ditandai: <strong class="text-amber-600" x-text="(recordingsStats.unassignedCount || 0) + ' tugas'"></strong></span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN (5 COLS): Top Kontributor Leaderboard -->
                <div class="lg:col-span-5 bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Top Kontributor Tim</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Produktivitas jam approved tertinggi</p>
                            </div>
                            <a href="contributors.html" class="text-xs font-semibold text-[#009B7E] hover:underline">
                                Kelola Tim →
                            </a>
                        </div>

                        <!-- Kontributor List -->
                        <div class="space-y-3">
                            <template x-for="(worker, idx) in topContributors" :key="idx">
                                <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-2xs"
                                             :class="['bg-emerald-600', 'bg-teal-600', 'bg-indigo-600', 'bg-blue-600', 'bg-purple-600', 'bg-slate-700'][idx % 6]"
                                             x-text="getWorkerInitials(worker.name)">
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900" x-text="worker.name"></div>
                                            <div class="text-[11px] text-slate-400" x-text="worker.slipCount + ' slip gaji diterbitkan'"></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-black text-[#009B7E]" x-text="worker.approvedHours.toFixed(1) + 'h'"></div>
                                        <div class="text-[11px] font-semibold text-slate-500" x-text="formatRupiah(worker.thp)"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer Link -->
                    <div class="pt-4 border-t border-slate-100 mt-4 text-center">
                        <a href="contributors.html" class="text-xs font-semibold text-slate-600 hover:text-[#00B090] transition flex items-center justify-center gap-1.5">
                            <span>Lihat Semua Daftar Tim (<span x-text="contributors.length"></span> Pekerja)</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>

            </div>

            <!-- RECENT PAYSLIPS FEED -->
            <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Dokumen Slip Gaji Terakhir</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-[#009B7E] border border-emerald-100" x-text="slips.length + ' Dokumen'"></span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Riwayat penerbitan slip pembayaran kontributor paling baru</p>
                    </div>

                    <a href="payslips.html" class="inline-flex items-center gap-2 text-xs font-bold text-[#009B7E] hover:text-[#007F67] transition">
                        <span>Buka Riwayat Slip Gaji Lengkap</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 pl-6 pr-3">Karyawan / Kontributor</th>
                                <th class="px-3 py-3">Periode</th>
                                <th class="px-3 py-3">Jam (Approved / Log)</th>
                                <th class="px-3 py-3">Take Home Pay</th>
                                <th class="px-3 py-3">Dibuat</th>
                                <th class="py-3 pl-3 pr-6 text-right">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <template x-if="recentSlips.length === 0">
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-400 font-medium">
                                        Belum ada slip gaji yang diterbitkan.
                                    </td>
                                </tr>
                            </template>
                            
                            <template x-for="slip in recentSlips" :key="slip.id">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 pl-6 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs shrink-0"
                                                 x-text="getWorkerInitials(slip.employee_name)">
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900" x-text="slip.employee_name"></div>
                                                <div class="text-[11px] text-slate-400" x-text="'#SLIP-' + String(slip.id).padStart(4, '0')"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 text-slate-600 font-medium" x-text="slip.period"></td>
                                    <td class="px-3 py-3.5 font-mono">
                                        <span class="font-bold text-[#009B7E]" x-text="parseFloat(slip.total_approved_hours).toFixed(2) + 'h'"></span>
                                        <span class="text-slate-400" x-text="' / ' + parseFloat(slip.total_logged_hours).toFixed(2) + 'h'"></span>
                                    </td>
                                    <td class="px-3 py-3.5 font-bold text-slate-900" x-text="formatRupiah(slip.total_thp)"></td>
                                    <td class="px-3 py-3.5 text-slate-400 text-[11px]" x-text="formatDate(slip.created_at)"></td>
                                    <td class="py-3.5 pl-3 pr-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="exportPdf(slip.id)" 
                                                    :disabled="isExporting === slip.id"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 transition shadow-2xs"
                                                    title="Unduh / Cetak PDF">
                                                <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                <span>PDF</span>
                                            </button>
                                            <a href="payslips.html" class="inline-flex items-center px-2 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Lihat di Riwayat">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50/50 border-t border-slate-100 text-center">
                    <a href="payslips.html" class="text-xs font-bold text-slate-600 hover:text-[#00B090] transition flex items-center justify-center gap-2">
                        <span>Buka Riwayat Lengkap Slip Gaji</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <script src="/assets/app.js"></script>
</body>
</html>
