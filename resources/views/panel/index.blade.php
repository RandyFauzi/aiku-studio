<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Executive Dashboard & Operations</title>
    <meta name="theme-color" content="#edf6d9">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AIKu Studio">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
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
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="executiveDashboardData()" x-init="init()" x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN APP CONTENT AREA -->
    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
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
                <a href="/create" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Slip Gaji</span>
                </a>
                
            </div>
        </header>

        <!-- DASHBOARD BODY CONTAINER -->
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- HERO GREETING & OPERATIONS SUMMARY -->
            <div class="dashboard-hero-banner rounded-3xl p-5 sm:p-7 relative overflow-hidden shadow-xs">
                <!-- Decorative Subtle Background Pattern / Ambient Glow -->
                <div class="absolute -right-8 -bottom-8 w-60 h-60 bg-white/40 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute top-0 right-1/3 w-48 h-48 bg-[#c2ec99]/40 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <!-- Left: Brand Logo & Executive Welcome -->
                    <div class="flex items-start sm:items-center gap-4 sm:gap-5">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white/90 shadow-2xs border border-white/80 p-2 sm:p-2.5 flex items-center justify-center shrink-0">
                            <img src="/assets/logo.png" alt="AIKu Studio Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white/70 border border-white/80 text-[11px] font-bold text-[#1f4728] uppercase tracking-wider mb-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>AIKu Studio Command Center</span>
                                <span class="text-[#89a88f]">•</span>
                                <span class="text-[#3c6347]">Live Production Overview</span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[#14321d] mb-1">
                                Selamat datang, {{ auth()->user()->name }}
                            </h1>
                            <p class="text-xs sm:text-sm text-[#385942] max-w-2xl leading-relaxed">
                                Pantau validasi rekaman video, tingkat kelulusan QC, performa kerja kontributor tim, serta distribusi pembayaran payroll secara akurat dan transparan.
                            </p>
                        </div>
                    </div>

                    <!-- Right: Quick Action Chips -->
                    <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap self-start lg:self-center shrink-0">
                        <div class="hero-chip px-3.5 py-2 rounded-full text-xs font-medium flex items-center gap-2 shadow-2xs">
                            <svg class="w-4 h-4 text-[#20502f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Siklus Cutoff: <strong x-text="activePeriodName">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMM Y') }}</strong></span>
                        </div>
                        <button @click="loadAllDashboardData()" 
                                class="hero-chip hover:bg-white px-3.5 py-2 rounded-full text-xs font-medium transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                :disabled="loading"
                                title="Refresh Ringkasan">
                            <svg class="w-4 h-4 text-[#20502f]" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span x-text="loading ? 'Memuat...' : 'Refresh'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 EXECUTIVE KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- KPI 1: Produksi Disetujui -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Produksi Disetujui</span>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#00B090] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" x-text="billableHours">0</span>
                            <span class="text-xs font-bold text-slate-400">Jam Billable</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Total Log: <strong class="text-slate-700 font-bold" x-text="totalRecordedHours + 'h'"></strong></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-[#009B7E] border border-emerald-100/80" x-text="billablePercentage + '% Approved'"></span>
                    </div>
                </div>

                <!-- KPI 2: Realisasi Payroll -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Realisasi Payroll</span>
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl sm:text-2xl lg:text-[25px] font-black text-slate-900 tracking-tight font-mono" x-text="formatRupiah(totalPayrollPaid)">Rp 0</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Total Dokumen: <strong class="text-slate-700 font-bold" x-text="totalSlipsCount + ' Slip'"></strong></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100/80">Lunas Terbit</span>
                    </div>
                </div>

                <!-- KPI 3: Antrean Rekap -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Antrean Rekap</span>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" x-text="recordingsStats.belumRekapCount || 0">0</span>
                            <span class="text-xs font-bold text-slate-400">Tugas Siap</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Status Rekaman:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100/80">Siap Diproses</span>
                    </div>
                </div>

                <!-- KPI 4: Tim Kontributor -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tim Kontributor</span>
                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" x-text="contributors.length || 0">0</span>
                            <span class="text-xs font-bold text-slate-400">Pekerja Aktif</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Database Tim:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-100/80" x-text="contributors.length + ' Terdaftar'"></span>
                    </div>
                </div>

            </div>

            <!-- ACTIONABLE ATTENTION & ALERTS CENTER -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Alert 1: Unassigned Tasks -->
                <div class="bg-white border border-slate-200/80 border-t-4 border-t-amber-400 rounded-2xl p-5 flex flex-col justify-between shadow-2xs hover:shadow-xs transition group">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200/60 mb-2.5">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Tugas Belum Ditandai</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1 tracking-tight">
                            <span class="text-amber-600 font-black" x-text="recordingsStats.unassignedCount || 0"></span> Rekaman Perlu Di-assign
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Terdapat tugas rekaman studio yang belum dihubungkan ke kontributor pekerja. Tandai sekarang untuk perhitungan penggajian akurat.
                        </p>
                    </div>
                    <a href="/recordings" class="inline-flex items-center justify-between w-full px-4 py-2.5 rounded-xl text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-200/70 transition shadow-2xs">
                        <span>Buka & Tandai Tugas</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <!-- Alert 2: Pending QC -->
                <div class="bg-white border border-slate-200/80 border-t-4 border-t-blue-500 rounded-2xl p-5 flex flex-col justify-between shadow-2xs hover:shadow-xs transition group">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200/60 mb-2.5">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Validasi QC Pending</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1 tracking-tight">
                            <span class="text-blue-600 font-black" x-text="pendingHours"></span> Jam (<span x-text="Math.round(recordingsStats.pendingMinutes || 0)"></span> mnt) Menunggu
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Sebagian rekaman video masih menunggu konfirmasi kelulusan QC client. Pantau status QC secara berkala.
                        </p>
                    </div>
                    <a href="/recordings" class="inline-flex items-center justify-between w-full px-4 py-2.5 rounded-xl text-xs font-bold text-blue-900 bg-blue-50 hover:bg-blue-100 border border-blue-200/70 transition shadow-2xs">
                        <span>Cek Validasi QC</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

                <!-- Alert 3: Generate Slip Gaji -->
                <div class="bg-white border border-slate-200/80 border-t-4 border-t-emerald-500 rounded-2xl p-5 flex flex-col justify-between shadow-2xs hover:shadow-xs transition group">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/60 mb-2.5">
                            <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Generate Slip Gaji</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1 tracking-tight">
                            <span class="text-emerald-600 font-black" x-text="recordingsStats.belumRekapCount || 0"></span> Rekaman Siap Diproses
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Seluruh rekaman tugas disetujui (Approved) siap digenerate menjadi dokumen slip gaji resmi beserta lampiran log video.
                        </p>
                    </div>
                    <a href="/create" class="inline-flex items-center justify-between w-full px-4 py-2.5 rounded-xl text-xs font-bold text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/70 transition shadow-2xs">
                        <span>Buat Slip Gaji Baru</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

            </div>

            <!-- PRODUCTIVITY ANALYTICS & LEADERBOARD SECTION -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-6">
                
                <!-- Section Header with Mode Tabs & Period Selector -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Analisis Produktivitas & Kontribusi Tim</h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                  :class="performanceTab === 'monthly' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/70' : 'bg-blue-50 text-blue-800 border border-blue-200/70'">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 animate-pulse" :class="performanceTab === 'monthly' ? 'bg-emerald-500' : 'bg-blue-500'"></span>
                                <span x-text="performanceTab === 'monthly' ? 'Fokus Siklus Bulanan (22 - 21)' : 'Akumulasi Semua Waktu'"></span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Pantau perbandingan jam kerja disetujui, pembagian beban produksi tim, dan estimasi penerimaan kompensasi.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap self-start lg:self-auto">
                        <!-- Segmented Mode Tabs -->
                        <div class="inline-flex p-1 bg-slate-100/90 rounded-xl text-xs font-bold border border-slate-200/70 shadow-2xs">
                            <button type="button" 
                                    @click="performanceTab = 'monthly'" 
                                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                                    :class="performanceTab === 'monthly' ? 'bg-white text-emerald-800 shadow-xs font-extrabold' : 'text-slate-500 hover:text-slate-800'">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>Kinerja Bulan Ini</span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-emerald-100/80 text-emerald-800 font-bold">22-21</span>
                            </button>
                            <button type="button" 
                                    @click="performanceTab = 'all_time'" 
                                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                                    :class="performanceTab === 'all_time' ? 'bg-white text-blue-800 shadow-xs font-extrabold' : 'text-slate-500 hover:text-slate-800'">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                <span>Semua Waktu (All-Time)</span>
                            </button>
                        </div>

                        <!-- Dropdown Siklus bila di tab Bulanan -->
                        <template x-if="performanceTab === 'monthly' && periods.length > 0">
                            <select x-model="selectedPeriodId" @change="changePeriod(selectedPeriodId)" 
                                    class="text-xs font-bold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200/90 rounded-xl px-3 py-1.5 cursor-pointer outline-none focus:ring-2 focus:ring-[#00B090] shadow-2xs">
                                <template x-for="p in periods" :key="p.id">
                                    <option :value="p.id" x-text="p.name + (p.isCurrent ? ' (Siklus Aktif)' : '')"></option>
                                </template>
                            </select>
                        </template>
                    </div>
                </div>

                <!-- 4 Interactive Mini-KPI Pills based on active mode -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Stat 1 -->
                    <div class="rounded-xl p-3.5 border transition"
                         :class="performanceTab === 'monthly' ? 'bg-emerald-50/50 border-emerald-100/90' : 'bg-slate-50 border-slate-200/70'">
                        <div class="text-[11px] font-bold uppercase tracking-wider"
                             :class="performanceTab === 'monthly' ? 'text-emerald-800' : 'text-slate-500'">
                            <span x-text="performanceTab === 'monthly' ? 'Disetujui Siklus Ini' : 'Disetujui Sepanjang Masa'"></span>
                        </div>
                        <div class="text-xl font-black mt-1"
                             :class="performanceTab === 'monthly' ? 'text-[#009B7E]' : 'text-slate-900'"
                             x-text="performanceTab === 'monthly' ? (monthlyApprovedHours + 'h') : (billableHours + 'h')"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5"
                             x-text="performanceTab === 'monthly' ? (monthlyApprovalRate + '% Approved Rate') : (billablePercentage + '% Approved Rate')"></div>
                    </div>

                    <!-- Stat 2 -->
                    <div class="rounded-xl p-3.5 border transition"
                         :class="performanceTab === 'monthly' ? 'bg-blue-50/50 border-blue-100/90' : 'bg-slate-50 border-slate-200/70'">
                        <div class="text-[11px] font-bold uppercase tracking-wider"
                             :class="performanceTab === 'monthly' ? 'text-blue-800' : 'text-slate-500'">
                            <span x-text="performanceTab === 'monthly' ? 'Estimasi Beban Payroll' : 'Total Payroll Dicairkan'"></span>
                        </div>
                        <div class="text-xl font-black mt-1 font-mono"
                             :class="performanceTab === 'monthly' ? 'text-blue-700' : 'text-slate-900'"
                             x-text="formatRupiah(performanceTab === 'monthly' ? monthlyThp : totalPayrollPaid)"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5"
                             x-text="performanceTab === 'monthly' ? (monthlySudahCount + ' Slip Terbit • ' + monthlyBelumCount + ' Draft') : (totalSlipsCount + ' Dokumen Slip')"></div>
                    </div>

                    <!-- Stat 3 -->
                    <div class="rounded-xl p-3.5 border transition"
                         :class="performanceTab === 'monthly' ? 'bg-teal-50/50 border-teal-100/90' : 'bg-slate-50 border-slate-200/70'">
                        <div class="text-[11px] font-bold uppercase tracking-wider"
                             :class="performanceTab === 'monthly' ? 'text-teal-800' : 'text-slate-500'">
                            <span x-text="performanceTab === 'monthly' ? 'Kontributor Aktif Siklus Ini' : 'Total Pekerja Terdaftar'"></span>
                        </div>
                        <div class="text-xl font-black text-slate-900 mt-1"
                             x-text="performanceTab === 'monthly' ? (monthlyWorkersCount + ' Pekerja') : (contributors.length + ' Pekerja')"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5"
                             x-text="performanceTab === 'monthly' ? ('Dari ' + contributors.length + ' total tim terdaftar') : 'Database kontributor studio'"></div>
                    </div>

                    <!-- Stat 4 -->
                    <div class="rounded-xl p-3.5 border transition"
                         :class="performanceTab === 'monthly' ? 'bg-amber-50/50 border-amber-100/90' : 'bg-slate-50 border-slate-200/70'">
                        <div class="text-[11px] font-bold uppercase tracking-wider"
                             :class="performanceTab === 'monthly' ? 'text-amber-800' : 'text-slate-500'">
                            <span x-text="performanceTab === 'monthly' ? 'Total Jam Log Siklus Ini' : 'Total Jam Log Sepanjang Masa'"></span>
                        </div>
                        <div class="text-xl font-black mt-1"
                             :class="performanceTab === 'monthly' ? 'text-amber-700' : 'text-slate-800'"
                             x-text="performanceTab === 'monthly' ? (monthlyLoggedHours + 'h') : (totalRecordedHours + 'h')"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5"
                             x-text="performanceTab === 'monthly' ? (monthlyRejectedHours + 'h Reject • ' + monthlyPendingHours + 'h Pending') : (rejectedHours + 'h Reject • ' + pendingHours + 'h Pending')"></div>
                    </div>
                </div>

                <!-- 2-COLUMN ANALYTICS: Visual Chart (7 COLS) vs Leaderboard (5 COLS) -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start pt-2">
                    
                    <!-- LEFT COLUMN (7 COLS): Visual Horizontal Productivity Bar Chart -->
                    <div class="xl:col-span-7 min-w-0 bg-slate-50/70 border border-slate-200/70 rounded-2xl p-5 sm:p-6 space-y-5">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/70 pb-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#009B7E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    <span>Grafik Jam Kerja & Produktivitas Kontributor</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5"
                                   x-text="performanceTab === 'monthly' ? ('Perbandingan jam disetujui periode: ' + activePeriodName) : 'Akumulasi jam kerja disetujui dari seluruh slip gaji yang pernah terbit'"></p>
                            </div>
                            <span class="text-[11px] font-bold text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs shrink-0">
                                Top 6 Kontributor
                            </span>
                        </div>

                        <!-- Bar Chart List -->
                        <div class="space-y-4">
                            <template x-if="topContributors.length === 0">
                                <div class="py-10 text-center text-xs text-slate-400 font-medium">
                                    <span x-text="performanceTab === 'monthly' ? 'Belum ada data rekaman tugas pada siklus ini.' : 'Belum ada data slip gaji yang tercatat.'"></span>
                                </div>
                            </template>

                            <template x-for="(worker, idx) in topContributors" :key="idx">
                                <div class="space-y-1.5">
                                    <!-- Worker Header Row -->
                                    <div class="flex flex-col gap-1.5 text-xs sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                        <div class="flex flex-1 items-center gap-2 min-w-0">
                                            <span class="w-7 text-center font-bold text-[11px] shrink-0"
                                                  :class="idx === 0 ? 'text-amber-500 font-black' : (idx === 1 ? 'text-slate-400 font-black' : (idx === 2 ? 'text-amber-700 font-bold' : 'text-slate-400'))"
                                                  x-text="'#' + (idx + 1)">
                                            </span>
                                            <span class="min-w-0 font-bold text-slate-800 truncate" :title="worker.name" x-text="worker.name"></span>
                                            
                                            <!-- Mini badge status/tasks -->
                                            <template x-if="performanceTab === 'monthly'">
                                                <span class="hidden shrink-0 sm:inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium"
                                                      :class="worker.hasSlip ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                                                      x-text="worker.hasSlip ? 'Slip Terbit' : (worker.tasksCount + ' Tugas')">
                                                </span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0 pl-9 sm:pl-0">
                                            <span class="font-black text-[#009B7E]" x-text="worker.approvedHours.toFixed(1) + 'h'"></span>
                                            <span class="text-[11px] text-slate-400 font-mono" x-text="'(' + worker.sharePercentage + '%)'"></span>
                                        </div>
                                    </div>

                                    <!-- Visual Progress Bar -->
                                    <div class="h-3 w-full bg-slate-200/80 rounded-full overflow-hidden p-0.5 shadow-inner">
                                        <div class="h-full rounded-full transition-all duration-700 ease-out shadow-xs"
                                             :class="idx === 0 ? 'bg-gradient-to-r from-emerald-500 to-[#00B090]' : (idx === 1 ? 'bg-gradient-to-r from-teal-500 to-teal-400' : 'bg-[#00B090]/80')"
                                             :style="'width: ' + worker.barPercentage + '%'">
                                        </div>
                                    </div>

                                    <!-- Bottom Meta Row -->
                                    <div class="flex flex-wrap items-center justify-between gap-1 text-[10px] text-slate-400 px-0.5">
                                        <span x-text="performanceTab === 'monthly' ? ('Total Log: ' + worker.loggedHours.toFixed(1) + 'h') : (worker.slipCount + ' slip gaji resmi')"></span>
                                        <span class="font-semibold text-slate-600 font-mono" x-text="formatRupiah(worker.thp)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Insight Share & Transparency Box -->
                        <div class="pt-3 border-t border-slate-200/80 space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-1 text-xs text-slate-600">
                                <span class="font-semibold">Kontribusi Top 3 Tim:</span>
                                <span class="font-bold text-slate-900" x-text="topContributorsTotalShare + '% dari total jam disetujui'"></span>
                            </div>

                            <div class="p-3 bg-white rounded-xl border border-slate-200/70 text-[11px] text-slate-500 leading-relaxed">
                                <strong class="text-slate-700">💡 Transparansi Perhitungan Finansial:</strong>
                                Nilai THP dihitung dari Jam Approved (Rp 50.000/jam) ditambah bonus performa dan carry-over klaim pending bulan lalu. Oleh sebab itu, kontributor dengan jam kerja lebih sedikit dapat menerima THP lebih tinggi bila menerima bonus atau pencairan pending.
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN (5 COLS): Leaderboard Card -->
                    <div class="xl:col-span-5 min-w-0 bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3 mb-4">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Papan Peringkat Tim</h3>
                                    <p class="text-xs text-slate-400 mt-0.5"
                                       x-text="performanceTab === 'monthly' ? 'Peringkat produktivitas siklus berjalan' : 'Peringkat akumulasi slip gaji all-time'"></p>
                                </div>
                                <a href="/contributors" class="text-xs font-semibold text-[#009B7E] hover:underline flex items-center gap-1">
                                    <span>Kelola Tim</span>
                                    <span>→</span>
                                </a>
                            </div>

                            <!-- List Items -->
                            <div class="space-y-2">
                                <template x-if="topContributors.length === 0">
                                    <div class="py-8 text-center text-xs text-slate-400 font-medium">
                                        Belum ada kontributor pada kategori ini.
                                    </div>
                                </template>

                                <template x-for="(worker, idx) in topContributors" :key="idx">
                                    <div class="grid grid-cols-1 items-center gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                                        <div class="grid grid-cols-[1.75rem_2rem_minmax(0,1fr)] items-center gap-2.5 min-w-0">
                                            <!-- Rank Badge -->
                                            <span class="w-7 text-center text-xs font-bold shrink-0"
                                                  :class="idx === 0 ? 'text-amber-500 font-black' : (idx === 1 ? 'text-slate-400 font-black' : (idx === 2 ? 'text-amber-700 font-bold' : 'text-slate-300'))"
                                                  x-text="'#' + (idx + 1)">
                                            </span>

                                            <!-- Avatar -->
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-bold text-white shadow-2xs shrink-0"
                                                 :class="['bg-emerald-600', 'bg-teal-600', 'bg-indigo-600', 'bg-blue-600', 'bg-purple-600', 'bg-slate-700'][idx % 6]"
                                                 x-text="getWorkerInitials(worker.name)">
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-slate-900 truncate" :title="worker.name" x-text="worker.name"></div>
                                                <div class="text-[10px] text-slate-400 truncate"
                                                     x-text="performanceTab === 'monthly' ? (worker.tasksCount + ' Tugas • ' + (worker.hasSlip ? 'Slip Terbit' : 'Draft')) : (worker.slipCount + ' slip gaji diterbitkan')">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pl-24 text-left sm:pl-0 sm:text-right">
                                            <div class="text-xs font-black text-[#009B7E]" x-text="worker.approvedHours.toFixed(1) + 'h'"></div>
                                            <div class="text-[11px] font-semibold text-slate-500 font-mono" x-text="formatRupiah(worker.thp)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Footer Box -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-3">
                            <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Pekerja Terdaftar:</span>
                                </div>
                                <span class="font-bold text-slate-800" x-text="contributors.length + ' Orang'"></span>
                            </div>

                            <a href="/contributors" class="text-xs font-semibold text-slate-600 hover:text-[#00B090] transition flex items-center justify-center gap-1.5">
                                <span>Lihat Semua Daftar Tim (<span x-text="contributors.length"></span> Pekerja)</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>

                </div>

            </div>

            <!-- QC BREAKDOWN & PRODUCTION QUALITY MONITORING -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Kualitas Produksi & Breakdown QC Rekaman</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Distribusi kelulusan QC dan durasi dari seluruh rekaman studio</p>
                    </div>
                    <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200/70 self-start sm:self-auto">
                        Total Rekaman: <strong class="text-slate-900" x-text="totalRecordedHours + ' Jam'"></strong>
                    </span>
                </div>

                <!-- Multi-segment Progress Bar -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-slate-600">Rasio Status Rekaman Studio</span>
                        <span class="text-[#009B7E] font-bold" x-text="billablePercentage + '% Approved Rate'"></span>
                    </div>
                    
                    <div class="h-3.5 w-full bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                        <div class="bg-[#00B090] transition-all duration-500" :style="'width: ' + billablePercentage + '%'" title="Billable Approved"></div>
                        <div class="bg-rose-500 transition-all duration-500" :style="'width: ' + rejectedPercentage + '%'" title="Rejected"></div>
                        <div class="bg-amber-400 transition-all duration-500" :style="'width: ' + pendingPercentage + '%'" title="Pending"></div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 pt-1">
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
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                    <div class="bg-emerald-50/50 rounded-xl p-3 border border-emerald-100/80">
                        <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Billable (Appr)</div>
                        <div class="text-lg font-black text-[#009B7E] mt-1" x-text="billableHours + 'h'"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.billableMinutes || 0).toLocaleString() + ' mnt'"></div>
                    </div>
                    <div class="bg-rose-50/50 rounded-xl p-3 border border-rose-100/80">
                        <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">Rejected</div>
                        <div class="text-lg font-black text-rose-500 mt-1" x-text="rejectedHours + 'h'"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.rejectedMinutes || 0).toLocaleString() + ' mnt'"></div>
                    </div>
                    <div class="bg-amber-50/50 rounded-xl p-3 border border-amber-100/80">
                        <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Pending QC</div>
                        <div class="text-lg font-black text-amber-500 mt-1" x-text="pendingHours + 'h'"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.pendingMinutes || 0).toLocaleString() + ' mnt'"></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200/70">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Logged</div>
                        <div class="text-lg font-black text-slate-800 mt-1" x-text="totalRecordedHours + 'h'"></div>
                        <div class="text-[11px] text-slate-400 mt-0.5" x-text="Math.round(recordingsStats.totalMinutes || 0).toLocaleString() + ' mnt'"></div>
                    </div>
                </div>

                <!-- Progress Assignment Tim -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Tingkat Penugasan Tugas ke Kontributor</span>
                        <span class="font-bold text-slate-900" x-text="assignedPercentage + '% Selesai Ditandai'"></span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex">
                        <div class="bg-[#00B090] transition-all duration-500" :style="'width: ' + assignedPercentage + '%'"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Ditandai: <strong class="text-slate-700" x-text="(recordingsStats.assignedCount || 0) + ' tugas'"></strong></span>
                        <span>Belum Ditandai: <strong class="text-amber-600" x-text="(recordingsStats.unassignedCount || 0) + ' tugas'"></strong></span>
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

                    <a href="/payslips" class="inline-flex items-center gap-2 text-xs font-bold text-[#009B7E] hover:text-[#007F67] transition">
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
                                                    class="btn-compact inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200/90 hover:bg-slate-50 transition shadow-2xs"
                                                    title="Unduh / Cetak PDF">
                                                <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                <span>PDF</span>
                                            </button>
                                            <a href="/payslips" class="inline-flex items-center px-2 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Lihat di Riwayat">
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
                    <a href="/payslips" class="text-xs font-bold text-slate-600 hover:text-[#00B090] transition flex items-center justify-center gap-2">
                        <span>Buka Riwayat Lengkap Slip Gaji</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <script src="/assets/app.js?v={{ filemtime(public_path('assets/app.js')) }}"></script>
</body>
</html>
