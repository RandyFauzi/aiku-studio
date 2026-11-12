<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Riwayat Slip Gaji & Rekap Payroll</title>
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
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="dashboardData()" x-cloak>

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
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-3">
                <a href="/create" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Slip Manual</span>
                </a>

                
            </div>
        </header>

        <div class="w-full max-w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Breadcrumb & Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Payroll / Riwayat Dokumen & Rekap Otomatis</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Riwayat Slip Gaji & Rekap Payroll</h2>
                    <p class="text-xs text-slate-500 mt-1">Perhitungan otomatis dinamis berbasis penandaan worker rekaman Verbos. Cut-off siklus tanggal 22 - 21 setiap bulan.</p>
                </div>
                
                <!-- Period Selector Controls -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-xs">
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Siklus Cut-Off:</span>
                        </div>
                        <select x-model="selectedPeriodId" @change="onPeriodChange()" class="bg-transparent border-none font-bold text-slate-800 cursor-pointer focus:ring-0 text-xs sm:text-sm outline-none py-0 pr-6">
                            <template x-for="p in periods" :key="p.id">
                                <option :value="p.id" x-text="p.name + (p.isCurrent ? ' (Periode Berjalan)' : (p.isTraining ? ' (Arsip DB)' : ' (Siklus Selesai)'))"></option>
                            </template>
                        </select>
                    </div>

                    <!-- View Mode Pill Tabs -->
                    <div class="inline-flex p-1 bg-slate-200/70 rounded-xl text-xs font-semibold">
                        <button @click="if (isTrainingView) { const nonTraining = periods.find(p => !p.isTraining); if (nonTraining) selectPeriod(nonTraining.id); }"
                                class="px-3 py-1.5 rounded-lg transition-all"
                                :class="!isTrainingView ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            ⚡ Rekap Otomatis
                        </button>
                        <button @click="selectPeriod('training_2026')"
                                class="px-3 py-1.5 rounded-lg transition-all"
                                :class="isTrainingView ? 'bg-white text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            🎓 Arsip Training
                        </button>
                    </div>
                </div>
            </div>

            <!-- Highlight Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
                <!-- Total Uang -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs relative overflow-hidden group col-span-2 sm:col-span-1">
                    <div class="absolute -right-3 -bottom-3 bg-emerald-50 h-20 w-20 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Take Home Pay</h4>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight relative z-10" x-text="formatRupiah(currentStats.thp)"></div>
                    <div class="text-[10px] font-medium text-emerald-600 mt-1 relative z-10">@ Rp 50.000 / Jam Appr</div>
                </div>

                <!-- Total Durasi Kirim -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-blue-50 h-20 w-20 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Logged</h4>
                    <div class="text-xl sm:text-2xl font-black text-blue-600 relative z-10"><span x-text="currentStats.logged"></span><span class="text-sm font-semibold text-blue-400 ml-1">h</span></div>
                    <div class="text-[10px] text-slate-400 mt-1 relative z-10">Total Durasi Masuk</div>
                </div>
                
                <!-- Total Approved -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-emerald-50 h-20 w-20 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Approved</h4>
                    <div class="text-xl sm:text-2xl font-black text-[#009B7E] relative z-10"><span x-text="currentStats.approved"></span><span class="text-sm font-semibold text-emerald-400 ml-1">h</span></div>
                    <div class="text-[10px] text-emerald-600 font-medium mt-1 relative z-10">Jam yang Dibayar</div>
                </div>
                
                <!-- Total Rejected -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-rose-50 h-20 w-20 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total Rejected</h4>
                    <div class="text-xl sm:text-2xl font-black text-rose-500 relative z-10"><span x-text="currentStats.rejected"></span><span class="text-sm font-semibold text-rose-300 ml-1">h</span></div>
                    <div class="text-[10px] text-rose-400 mt-1 relative z-10">Tidak Dibayar</div>
                </div>
                
                <!-- Total Worker / Dokumen -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-slate-100 h-20 w-20 rounded-full opacity-60 group-hover:scale-110 transition-transform"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10" x-text="isTrainingView ? 'Total Slip DB' : 'Worker Aktif'"></h4>
                    <div class="text-xl sm:text-2xl font-black text-slate-800 relative z-10"><span x-text="currentStats.count"></span><span class="text-xs font-semibold text-slate-400 ml-1" x-text="isTrainingView ? 'Slip' : 'Orang'"></span></div>
                    <div class="text-[10px] font-semibold text-slate-500 mt-1 relative z-10 flex items-center gap-1.5">
                        <span class="inline-flex items-center gap-0.5 text-emerald-600 font-bold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span x-text="countSudah + ' Sudah'"></span></span>
                        <span>•</span>
                        <span class="inline-flex items-center gap-0.5 text-amber-600 font-bold"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span x-text="countBelum + ' Belum'"></span></span>
                    </div>
                </div>
            </div>

            <!-- Controls & Search Bar with Status Filter -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 max-w-md">
                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari nama worker / kontributor..." 
                               class="w-full pl-9 pr-4 py-2 bg-slate-50 hover:bg-slate-100/80 focus:bg-white text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00B090] focus:border-transparent transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <!-- Status Filter Tabs: Semua | Belum | Sudah -->
                    <div class="inline-flex items-center p-1 bg-slate-100/80 rounded-xl text-xs font-semibold shrink-0">
                        <button id="filter-status-all" 
                                @click="statusFilter = 'all'" 
                                type="button"
                                class="px-3 py-1.5 rounded-lg transition-all"
                                :class="statusFilter === 'all' ? 'bg-white text-slate-800 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            Semua
                        </button>
                        <button id="filter-status-belum" 
                                @click="statusFilter = 'belum'" 
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all"
                                :class="statusFilter === 'belum' ? 'bg-white text-amber-800 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Belum</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800" x-text="countBelum"></span>
                        </button>
                        <button id="filter-status-sudah" 
                                @click="statusFilter = 'sudah'" 
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all"
                                :class="statusFilter === 'sudah' ? 'bg-white text-emerald-800 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Sudah</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800" x-text="countSudah"></span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs font-medium text-slate-500 self-end md:self-center">
                    <span x-show="!isTrainingView">Menampilkan <strong class="text-slate-800" x-text="filteredLiveWorkers.length"></strong> dari <strong class="text-slate-800" x-text="liveWorkers.length"></strong> worker</span>
                    <span x-show="isTrainingView">Menampilkan <strong class="text-slate-800" x-text="filteredTrainingSlips.length"></strong> dari <strong class="text-slate-800" x-text="trainingSlips.length"></strong> slip</span>
                    <button @click="loadSelectedPeriodData()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Refresh Data">
                        <svg class="w-4 h-4" :class="isLoading ? 'animate-spin text-[#00B090]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
            </div>

            <!-- VIEW 1: DYNAMIC LIVE PAYROLL TABLE (Cut-off 22 - 21) -->
            <div x-show="!isTrainingView" class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th scope="col" class="py-3.5 pl-5 pr-3">Worker / Kontributor</th>
                                <th scope="col" class="px-3 py-3.5 text-center">Hari Kerja</th>
                                <th scope="col" class="px-3 py-3.5 text-center">Total Tasks</th>
                                <th scope="col" class="px-3 py-3.5">Approved (Dibayar)</th>
                                <th scope="col" class="px-3 py-3.5">Rejected</th>
                                <th scope="col" class="px-3 py-3.5">Total Logged</th>
                                <th scope="col" class="px-3 py-3.5">Take Home Pay</th>
                                <th scope="col" class="px-3 py-3.5 text-center">Status</th>
                                <th scope="col" class="py-3.5 pl-3 pr-5 text-right min-w-[280px]">Aksi Payroll</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <!-- Loading State -->
                            <template x-if="isLoading">
                                <tr>
                                    <td colspan="9" class="px-6 py-14 text-center text-sm text-slate-400 font-medium">
                                        <div class="flex justify-center items-center gap-2.5">
                                            <svg class="animate-spin h-5 w-5 text-[#00B090]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>Menghitung rekap payroll otomatis...</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <template x-if="!isLoading && filteredLiveWorkers.length === 0">
                                <tr>
                                    <td colspan="9" class="px-6 py-16 text-center">
                                        <div class="max-w-md mx-auto space-y-3">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                                                📋
                                            </div>
                                            <div class="text-sm font-bold text-slate-700">Belum Ada Rekaman Ditandai Untuk Periode Ini</div>
                                            <p class="text-xs text-slate-400">
                                                Worker yang ditandai namanya pada menu Task List (Rekaman) dalam rentang tanggal siklus ini akan langsung muncul dan dihitung secara otomatis di sini.
                                            </p>
                                            <a href="/recordings" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#00B090] text-white hover:bg-[#009B7E] transition shadow-xs">
                                                <span>Buka Task List & Tandai Worker</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Worker Rows -->
                            <template x-for="(worker, idx) in filteredLiveWorkers" :key="worker.worker_name">
                                <tr class="hover:bg-slate-50/70 transition-colors group">
                                    <!-- Worker info -->
                                    <td class="py-3.5 pl-5 pr-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs shrink-0">
                                                <span x-text="worker.worker_name.substring(0, 2)"></span>
                                            </div>
                                            <div>
                                                <div class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-[#009B7E] transition-colors" x-text="worker.worker_name"></div>
                                                <div class="text-[10px] text-slate-400 font-mono">Rate: Rp 50.000 / Jam</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Hari Kerja -->
                                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span x-text="worker.work_days_count + ' Hari'"></span>
                                        </span>
                                    </td>

                                    <!-- Total Tasks -->
                                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                                            <span x-text="worker.total_tasks + ' tasks'"></span>
                                        </span>
                                    </td>

                                    <!-- Approved Duration -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="inline-flex items-center gap-1 text-xs font-extrabold text-[#009B7E]">
                                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                <span x-text="worker.approved_hours + ' Jam'"></span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 ml-4" x-text="worker.approved_min + ' menit'"></span>
                                        </div>
                                    </td>

                                    <!-- Rejected Duration -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-500">
                                                <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                <span x-text="worker.rejected_hours + ' Jam'"></span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 ml-4" x-text="worker.rejected_min + ' menit'"></span>
                                        </div>
                                    </td>

                                    <!-- Total Logged -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-700" x-text="worker.logged_hours + ' Jam'"></div>
                                        <div class="text-[10px] text-slate-400" x-text="worker.total_duration_min + ' menit'"></div>
                                    </td>

                                    <!-- Take Home Pay (THP) -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight" x-text="formatRupiah(worker.thp)"></div>
                                        <template x-if="worker.bonus > 0">
                                            <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 mt-0.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>+<span x-text="formatRupiah(worker.bonus)"></span> (Bonus)</span>
                                            </div>
                                        </template>
                                        <template x-if="worker.pending_carry_over_amount > 0">
                                            <div class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 mt-0.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                <span>+<span x-text="formatRupiah(worker.pending_carry_over_amount)"></span> (Sisa Pending)</span>
                                            </div>
                                        </template>
                                        <template x-if="!worker.pending_carry_over_amount && worker.available_pending_balance > 0">
                                            <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-300 mt-1 cursor-help" :title="'Tersedia saldo pending ' + worker.available_pending_hours + ' jam dari periode lalu'">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                <span>Bank: +<span x-text="formatRupiah(worker.available_pending_balance)"></span></span>
                                            </div>
                                        </template>
                                    </td>

                                    <!-- Status Bayar Button (Pilihan: Belum | Sudah) -->
                                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                        <div class="inline-flex items-center p-0.5 rounded-xl bg-slate-100 border border-slate-200/90 text-xs shadow-2xs">
                                            <!-- Opsi Belum -->
                                            <button @click="setWorkerStatus(worker, 'belum')" 
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                    :class="worker.status !== 'sudah' 
                                                        ? 'bg-amber-500 text-white shadow-xs' 
                                                        : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/50'"
                                                    title="Pilih Status: Belum Dibayar">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="worker.status !== 'sudah' ? 'bg-white' : 'bg-slate-300'"></span>
                                                <span>Belum</span>
                                            </button>

                                            <!-- Opsi Sudah -->
                                            <button @click="setWorkerStatus(worker, 'sudah')" 
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                    :class="worker.status === 'sudah' 
                                                        ? 'bg-[#00B090] text-white shadow-xs' 
                                                        : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/50'"
                                                    title="Pilih Status: Sudah Dibayar">
                                                <svg x-show="worker.status === 'sudah'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span x-show="worker.status !== 'sudah'" class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                                <span>Sudah</span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 pl-3 pr-5 whitespace-nowrap text-right">
                                        <div class="flex justify-end items-center gap-1.5">
                                            <!-- Rincian Harian Modal Button -->
                                            <button @click="openDailyModal(worker)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 border border-slate-200 hover:border-emerald-200 text-xs font-semibold transition-all shadow-2xs"
                                                    title="Lihat Rincian Kerja Per Hari">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <span>Rincian</span>
                                            </button>

                                            <!-- Edit Slip Button -->
                                            <a :href="worker.slip_id ? ('/create?id=' + worker.slip_id) : ('/create?worker=' + encodeURIComponent(worker.worker_name) + '&periodId=' + encodeURIComponent(selectedPeriodId))"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold transition shadow-2xs"
                                               title="Edit / Buat Slip Gaji Worker Ini">
                                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Edit Slip</span>
                                            </a>

                                            <!-- Lihat Web Slip Button -->
                                            <button @click="openLiveSlip(worker.worker_name)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white font-semibold text-xs transition shadow-xs"
                                                    title="Buka Slip Web & Cetak PDF">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                <span>Lihat Slip</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW 2: TRAINING ARSIP TABLE (Preserved Database Slips 1-9) -->
            <div x-show="isTrainingView" class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th scope="col" class="py-3.5 pl-5 pr-3">Employee</th>
                                <th scope="col" class="px-3 py-3.5">Period</th>
                                <th scope="col" class="px-3 py-3.5">Hours (Appr / Log)</th>
                                <th scope="col" class="px-3 py-3.5">Take Home Pay</th>
                                <th scope="col" class="px-3 py-3.5 text-center">Status</th>
                                <th scope="col" class="px-3 py-3.5">Dibuat Pada</th>
                                <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="item in filteredTrainingSlips" :key="item.id">
                                <tr class="hover:bg-slate-50/70 transition-colors group">
                                    <!-- Employee Info -->
                                    <td class="py-3.5 pl-5 pr-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs shrink-0">
                                                <span x-text="item.employee_name ? item.employee_name.substring(0,2) : 'XX'"></span>
                                            </div>
                                            <div>
                                                <div class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-purple-700 transition-colors" x-text="item.employee_name || 'Unknown'"></div>
                                                <div class="text-[10px] text-slate-400 font-mono">#SLIP-<span x-text="String(item.id).padStart(4, '0')"></span></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Period -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-100"
                                              x-text="formatPeriod(item.period)">
                                        </span>
                                    </td>

                                    <!-- Hours -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60" title="Approved Hours">
                                                <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                <span x-text="(parseFloat(item.total_approved_hours) || 0).toFixed(2) + 'h'"></span>
                                            </span>
                                            <span class="text-xs text-slate-400 font-medium" title="Logged Hours">
                                                / <span x-text="(parseFloat(item.total_logged_hours) || 0).toFixed(2) + 'h'"></span>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Take Home Pay -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight" x-text="formatRupiah(item.total_thp || 0)"></div>
                                    </td>

                                    <!-- Status Slip Button (Pilihan: Belum | Sudah) -->
                                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                        <div class="inline-flex items-center p-0.5 rounded-xl bg-slate-100 border border-slate-200/90 text-xs shadow-2xs">
                                            <button @click="setSlipStatus(item, 'belum')" 
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                    :class="item.status !== 'sudah' 
                                                        ? 'bg-amber-500 text-white shadow-xs' 
                                                        : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/50'"
                                                    title="Pilih Status: Belum Dibayar">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="item.status !== 'sudah' ? 'bg-white' : 'bg-slate-300'"></span>
                                                <span>Belum</span>
                                            </button>
                                            <button @click="setSlipStatus(item, 'sudah')" 
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                    :class="item.status === 'sudah' 
                                                        ? 'bg-[#00B090] text-white shadow-xs' 
                                                        : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/50'"
                                                    title="Pilih Status: Sudah Dibayar">
                                                <svg x-show="item.status === 'sudah'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span x-show="item.status !== 'sudah'" class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                                <span>Sudah</span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Date Created -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs font-semibold text-slate-700" x-text="formatDate(item.created_at)"></div>
                                        <div class="text-[10px] text-slate-400" x-text="formatTime(item.created_at)"></div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 pl-3 pr-5 whitespace-nowrap text-right min-w-[320px]">
                                        <div class="flex justify-end items-center gap-1.5">
                                            <!-- View Detail Button -->
                                            <a :href="'/slip/' + item.id" target="_blank" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/70 font-semibold text-xs transition-colors shadow-2xs" 
                                               title="Lihat Slip PDF">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>View</span>
                                            </a>

                                            <!-- Send Email Button -->
                                            <button @click="sendEmail(item.id)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-[#00B090] border border-indigo-200/70 font-semibold text-xs transition-colors shadow-2xs" 
                                                    title="Kirim via Email">
                                                <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                                <span>Send</span>
                                            </button>

                                            <!-- Edit Button -->
                                            <a :href="'/create?id=' + item.id" 
                                               class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" 
                                               title="Edit Slip">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </a>

                                            <!-- Delete Button -->
                                            <button @click="deleteSlip(item.id)" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" 
                                                    title="Hapus Slip">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- ========================================================= -->
    <!-- MODAL RINCIAN KERJA HARIAN WORKER (DAILY BREAKDOWN)       -->
    <!-- ========================================================= -->
    <div x-show="showDailyModal" 
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-100"
             @click.away="closeDailyModal()"
             x-show="showDailyModal"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0">

            <!-- Modal Header -->
            <div class="p-6 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                        <span x-text="selectedWorkerDaily ? selectedWorkerDaily.worker_name.substring(0, 2) : 'AK'"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-extrabold text-slate-900" x-text="selectedWorkerDaily?.worker_name"></h3>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                                Rate: Rp 50.000 / Jam
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Rincian Kehadiran & Jam Kerja Harian &bull; Periode: <strong class="text-slate-700" x-text="selectedPeriodObj?.name"></strong>
                        </p>
                    </div>
                </div>

                <button @click="closeDailyModal()" class="w-8 h-8 rounded-full bg-slate-200/80 text-slate-500 hover:text-slate-800 hover:bg-slate-300 transition flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Quick Summary Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-4 bg-slate-100/60 border-b border-slate-200 text-center">
                <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Hari Kerja</div>
                    <div class="text-base font-extrabold text-slate-800" x-text="selectedWorkerDaily?.work_days_count + ' Hari'"></div>
                </div>
                <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Tasks</div>
                    <div class="text-base font-extrabold text-slate-800" x-text="selectedWorkerDaily?.total_tasks + ' Rekaman'"></div>
                </div>
                <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[10px] uppercase font-bold text-emerald-600">Jam Approved</div>
                    <div class="text-base font-extrabold text-emerald-700" x-text="selectedWorkerDaily?.approved_hours + 'h'"></div>
                </div>
                <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                    <div class="text-[10px] uppercase font-bold text-rose-500">Jam Rejected</div>
                    <div class="text-base font-extrabold text-rose-600" x-text="selectedWorkerDaily?.rejected_hours + 'h'"></div>
                </div>
                <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs col-span-2 sm:col-span-1">
                    <div class="text-[10px] uppercase font-bold text-[#009B7E]">Total Gaji (THP)</div>
                    <div class="text-base font-black text-slate-900" x-text="formatRupiah(selectedWorkerDaily?.thp || 0)"></div>
                    <template x-if="selectedWorkerDaily?.bonus > 0">
                        <div class="text-[10px] font-bold text-emerald-600 mt-0.5">
                            +<span x-text="formatRupiah(selectedWorkerDaily?.bonus)"></span> Bonus
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Daily Table Body -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 max-h-[55vh]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[540px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5 px-3">Tanggal Kerja</th>
                            <th class="py-2.5 px-3 text-center">Tasks</th>
                            <th class="py-2.5 px-3">Approved</th>
                            <th class="py-2.5 px-3">Rejected</th>
                            <th class="py-2.5 px-3">Total Durasi</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Gaji Harian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-for="day in (selectedWorkerDaily?.daily_breakdown || [])" :key="day.date">
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Tanggal -->
                                <td class="py-3 px-3 font-semibold text-slate-900 whitespace-nowrap">
                                    <div x-text="day.formattedDate"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="day.date"></div>
                                </td>

                                <!-- Tasks -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700" x-text="day.tasks_count + ' task'"></span>
                                </td>

                                <!-- Approved -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-extrabold text-[#009B7E]" x-text="day.approved_hours + ' Jam'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="day.approved_min + ' menit'"></div>
                                </td>

                                <!-- Rejected -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-bold text-rose-500" x-text="day.rejected_hours + ' Jam'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="day.rejected_min + ' menit'"></div>
                                </td>

                                <!-- Total -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800" x-text="day.total_hours + ' Jam'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="day.total_min + ' menit'"></div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700 border border-emerald-200': day.status === 'Disetujui penuh',
                                              'bg-blue-50 text-blue-700 border border-blue-200': day.status === 'Disetujui sebagian',
                                              'bg-rose-50 text-rose-700 border border-rose-200': day.status === 'Ditolak',
                                              'bg-amber-50 text-amber-700 border border-amber-200': day.status === 'Sedang Ditinjau'
                                          }"
                                          x-text="day.status">
                                    </span>
                                </td>

                                <!-- Gaji Harian -->
                                <td class="py-3 px-3 text-right font-extrabold text-slate-900 whitespace-nowrap"
                                    x-text="formatRupiah(Math.round(day.approved_hours * window.AIKU_CONFIG.hourlyRate))">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs text-slate-500 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Perhitungan otomatis: Approved Jam &times; Rp 50.000</span>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <a :href="'/create?worker=' + encodeURIComponent(selectedWorkerDaily?.worker_name || '') + '&periodId=' + encodeURIComponent(selectedPeriodId)"
                       class="px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs sm:text-sm font-semibold transition flex items-center gap-2 shadow-2xs">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Edit Slip</span>
                    </a>
                    <button @click="openLiveSlip(selectedWorkerDaily.worker_name)" 
                            class="px-4 py-2 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white text-xs sm:text-sm font-semibold transition flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        <span>Buka Dokumen Slip Resmi</span>
                    </button>
                    <button @click="closeDailyModal()" 
                            class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs sm:text-sm font-semibold transition">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script src="/assets/app.js"></script>
</body>
</html>
