<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Rekaman Tugas</title>
    <meta name="theme-color" content="#edf6d9">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .badge-accepted {
            background-color: #E6F9F0;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .badge-pending {
            background-color: #FFF7ED;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        .badge-canceled {
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        .badge-paid {
            background-color: #E6F9F0;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .badge-unpaid {
            background-color: #F8FAFC;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }

        /* Walking Robot & Cyber Sync Animations */
        .robot-bobbing {
            animation: robot-bob 0.8s ease-in-out infinite alternate;
        }
        @keyframes robot-bob {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-8px); }
        }
        .robot-leg-left-anim {
            transform-origin: 48px 101px;
            animation: leg-swing-left 0.5s ease-in-out infinite alternate;
        }
        .robot-leg-right-anim {
            transform-origin: 72px 101px;
            animation: leg-swing-right 0.5s ease-in-out infinite alternate;
        }
        @keyframes leg-swing-left {
            0% { transform: rotate(20deg); }
            100% { transform: rotate(-20deg); }
        }
        @keyframes leg-swing-right {
            0% { transform: rotate(-20deg); }
            100% { transform: rotate(20deg); }
        }
        .robot-arm-left-anim {
            transform-origin: 34px 72px;
            animation: arm-swing-left 0.5s ease-in-out infinite alternate;
        }
        .robot-arm-right-anim {
            transform-origin: 86px 72px;
            animation: arm-swing-right 0.5s ease-in-out infinite alternate;
        }
        @keyframes arm-swing-left {
            0% { transform: rotate(-15deg); }
            100% { transform: rotate(15deg); }
        }
        @keyframes arm-swing-right {
            0% { transform: rotate(15deg); }
            100% { transform: rotate(-15deg); }
        }
        .conveyor-track {
            background: repeating-linear-gradient(90deg, #475569 0px, #475569 8px, #1e293b 8px, #1e293b 16px);
        }
        .conveyor-active {
            animation: conveyor-move 0.5s linear infinite;
        }
        @keyframes conveyor-move {
            from { background-position: 0 0; }
            to { background-position: 32px 0; }
        }
        .laser-scanner {
            animation: laser-sweep 1s ease-in-out infinite alternate;
        }
        @keyframes laser-sweep {
            0% { transform: translateX(-9px); opacity: 0.3; }
            100% { transform: translateX(9px); opacity: 1; }
        }
        .progress-shimmer {
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.4) 50%, rgba(255,255,255,0) 100%);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .pulse-path {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: dash 1.5s linear infinite;
        }
        @keyframes dash {
            to {
                stroke-dashoffset: 0;
            }
        }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="modernRecordingsApp()" x-init="initApp()" x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN APP CONTENT AREA -->
    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            
            <!-- Left: Toggle Sidebar -->
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>

            <!-- Right Header Controls: Bot, Auto-Sync, Exit, Admin -->
            <div class="flex items-center gap-3">

                <!-- REALTIME BOT RUNNING PILL (Click to open Walking Robot tracker) -->
                <button x-show="isSyncing || botProgress.isRunning" 
                        @click="openConsoleModal()"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="flex items-center gap-2 bg-gradient-to-r from-emerald-950/90 to-slate-900 border border-emerald-500/50 hover:border-emerald-400 text-emerald-300 px-3 py-1.5 rounded-xl text-xs font-semibold shadow-lg shadow-emerald-950/50 transition cursor-pointer group"
                        title="Klik untuk melihat progress realtime & robot bot">
                    <!-- Mini beacon -->
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-300 group-hover:text-white transition hidden xs:inline">Bot Live:</span>
                    <span class="font-extrabold font-mono text-emerald-400 text-sm" x-text="(botProgress.percent || 0) + '%'"></span>
                    <span class="text-[11px] text-slate-400 font-mono hidden md:inline" x-show="botProgress.totalAccounts" x-text="'[' + (botProgress.currentIndex || 0) + '/' + botProgress.totalAccounts + ']'"></span>
                    <svg class="w-3.5 h-3.5 text-emerald-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <!-- LAST SYNC TIME BADGE (Indikator Waktu Update Terakhir) -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200/90 shadow-2xs text-xs text-slate-700 hover:border-slate-300 transition"
                     :title="'Data rekaman terakhir ditarik bot pada: ' + formattedLastUpdateFull">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></div>
                    <div class="text-[11px] leading-tight">
                        <div class="text-[10px] text-slate-400 font-medium">Terakhir Update:</div>
                        <div class="font-bold text-slate-800 font-mono tracking-tight" x-text="formattedLastUpdate">--:-- WITA</div>
                    </div>
                </div>

                <!-- 1. Tombol Bot AIKu Studio & Auto-Sync Popover -->
                <div class="relative flex items-center bg-slate-900 rounded-xl p-0.5 shadow-xs border border-slate-800">
                    <!-- Main Action: Jalankan Bot Sekarang -->
                    <button @click="triggerSyncAtlas()" :disabled="isSyncing" 
                            class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-2 text-xs md:text-sm font-semibold text-white hover:text-emerald-300 transition disabled:opacity-50">
                        <svg :class="isSyncing ? 'animate-spin text-emerald-400' : 'text-emerald-400'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isSyncing ? ('Sinkron: ' + (botProgress.percent || 0) + '%') : (window.innerWidth < 640 ? 'Bot' : 'Bot AIKu Studio')"></span>
                    </button>

                    <!-- Divider -->
                    <div class="h-5 w-[1px] bg-slate-700"></div>

                    <!-- Dropdown Toggle for Auto-Sync -->
                    <button @click="showAutoSyncModal = !showAutoSyncModal" 
                            class="px-2 py-2 text-slate-300 hover:text-white transition flex items-center gap-1.5"
                            title="Pengaturan Auto-Sync Berkala">
                        <span class="w-2 h-2 rounded-full" :class="autoSync.enabled ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showAutoSyncModal ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Auto-Sync Popover Menu -->
                    <div x-show="showAutoSyncModal" @click.outside="showAutoSyncModal = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         class="absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 p-4 z-50 text-slate-800 text-xs">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <div class="font-bold text-slate-900 text-sm">Jadwal Auto-Sync</div>
                                <div class="text-[11px] text-slate-400">Sinkronkan rekaman berkala</div>
                            </div>
                            <!-- Switch -->
                            <button @click="toggleAutoSync()" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="autoSync.enabled ? 'bg-[#00B090]' : 'bg-slate-200'">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out" :class="autoSync.enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>

                        <!-- Interval Selection -->
                        <div class="py-3 border-b border-slate-100 space-y-2">
                            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Interval Waktu</div>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button @click="changeAutoSyncInterval(5)" class="py-1.5 rounded-lg font-medium text-center border transition" :class="autoSync.intervalMinutes === 5 ? 'bg-emerald-50 border-[#00B090] text-[#009B7E] font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">5m</button>
                                <button @click="changeAutoSyncInterval(15)" class="py-1.5 rounded-lg font-medium text-center border transition" :class="autoSync.intervalMinutes === 15 ? 'bg-emerald-50 border-[#00B090] text-[#009B7E] font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">15m</button>
                                <button @click="changeAutoSyncInterval(30)" class="py-1.5 rounded-lg font-medium text-center border transition" :class="autoSync.intervalMinutes === 30 ? 'bg-emerald-50 border-[#00B090] text-[#009B7E] font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">30m</button>
                                <button @click="changeAutoSyncInterval(60)" class="py-1.5 rounded-lg font-medium text-center border transition" :class="autoSync.intervalMinutes === 60 ? 'bg-emerald-50 border-[#00B090] text-[#009B7E] font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">1 Jam</button>
                            </div>
                        </div>

                        <!-- Status & Next Run -->
                        <div class="py-2.5 text-[11px] space-y-1.5 text-slate-500 border-b border-slate-100">
                            <div class="flex justify-between">
                                <span>Status Jadwal:</span>
                                <span class="font-semibold" :class="autoSync.enabled ? 'text-emerald-600' : 'text-slate-400'" x-text="autoSync.enabled ? 'Aktif (' + autoSync.intervalMinutes + ' Menit Sekali)' : 'Non-aktif'"></span>
                            </div>
                            <div class="flex justify-between" x-show="autoSync.enabled && autoSync.nextRun">
                                <span>Sinkron Berikutnya:</span>
                                <span class="font-semibold text-slate-700" x-text="formatTimeOnly(autoSync.nextRun)"></span>
                            </div>
                            <div class="flex justify-between items-center pt-0.5">
                                <span>Terakhir Diperbarui:</span>
                                <span class="font-bold text-slate-800 font-mono text-[11px]" x-text="formattedLastUpdateFull"></span>
                            </div>
                        </div>

                        <!-- Button View Console Log -->
                        <button @click="showAutoSyncModal = false; openConsoleModal()" class="w-full mt-2 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-center transition flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Lihat Console Log Terminal</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Terminal Log Button -->
                <button @click="openConsoleModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition relative" title="Buka Log Terminal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                </button>
                

            </div>

        </header>

        <!-- PAGE BODY CONTAINER -->
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- BREADCRUMB & TOP ACTIONS ROW -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mb-1 font-medium">
                        <span>Studio</span>
                        <span>/</span>
                        <span class="text-slate-700 font-semibold">Rekaman Tugas</span>
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Validasi Tugas & Rekaman</h1>
                </div>

                <!-- Action Controls: Avatars Stack, Tandai Akun Ini, Slip Gaji -->
                <div class="flex items-center gap-3 flex-wrap">
                    
                    <!-- Avatar Stack (+8 Members) -->
                    <div class="flex items-center -space-x-2 mr-2">
                        <template x-for="(w, idx) in quickWorkers.slice(0, 4)" :key="idx">
                            <div class="w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[10px] font-bold text-white shadow-xs"
                                 :class="['bg-emerald-600', 'bg-teal-600', 'bg-indigo-600', 'bg-blue-600'][idx % 4]"
                                 :title="w.name"
                                 x-text="w.initials">
                            </div>
                        </template>
                        <div class="w-8 h-8 rounded-full border-2 border-white bg-slate-800 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">
                            +5
                        </div>
                    </div>

                    <!-- Tandai Akun Ini -->
                    <button x-show="filters.email !== 'all'" 
                            @click="openQuickAssignAccountModal()"
                            class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 px-3.5 py-2 rounded-xl text-xs font-semibold transition shadow-2xs flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Tandai Semua di Akun Ini</span>
                    </button>

                    <!-- Kelola Akun Studio -->
                    <button @click="openAccountsModal()" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 px-3.5 py-2 rounded-xl text-xs font-semibold transition shadow-2xs flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Akun Studio (<span x-text="accountsList.length"></span>)</span>
                    </button>

                    <!-- Export Excel -->
                    <button @click="refreshStatsOnly()" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 px-3.5 py-2 rounded-xl text-xs font-semibold transition shadow-2xs flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Refresh Data</span>
                    </button>

                    <!-- Primary Action: + Slip Gaji -->
                    <a href="/create" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3 sm:px-4 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-sm shadow-[#00B090]/25 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        <span class="hidden sm:inline">Buat Slip Gaji</span>
                        <span class="sm:hidden">+ Slip</span>
                    </a>

                </div>
            </div>

            <!-- STATISTIC SUMMARY CARDS (EXACT DRIBBLE / HUGO STYLE) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                
                <!-- CARD 1: MULTI-METRIC (Total Estimasi Durasi Billable / Pending / Canceled) -->
                <div class="md:col-span-6 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 mb-4">
                            <span>Total Estimasi Durasi Jam Kerja</span>
                            <div class="flex items-center gap-1 text-[11px] text-slate-400 font-normal">
                                <span x-text="formatWITA(new Date())"></span>
                            </div>
                        </div>

                        <!-- 3 Metric Columns -->
                        <div class="grid grid-cols-3 gap-4 py-1">
                            <!-- Approved / Billable -->
                            <div>
                                <div class="text-2xl font-black text-slate-900 tracking-tight" x-text="formatDuration(stats.billableMinutes)"></div>
                                <div class="flex items-center gap-1.5 text-xs text-emerald-600 font-semibold mt-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Approved</span>
                                </div>
                            </div>
                            <!-- Pending -->
                            <div>
                                <div class="text-2xl font-black text-slate-900 tracking-tight" x-text="formatDuration(stats.pendingMinutes)"></div>
                                <div class="flex items-center gap-1.5 text-xs text-amber-600 font-semibold mt-1">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Pending</span>
                                </div>
                            </div>
                            <!-- Canceled / Rejected -->
                            <div>
                                <div class="text-2xl font-black text-slate-900 tracking-tight" x-text="formatDuration(stats.rejectedMinutes)"></div>
                                <div class="flex items-center gap-1.5 text-xs text-rose-600 font-semibold mt-1">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    <span>Canceled</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <span>Akumulasi Total: <strong class="text-slate-700 font-bold" x-text="formatDuration(stats.totalMinutes)"></strong></span>
                        <span class="text-[11px] text-[#00B090] font-semibold">Atlas Realtime Data</span>
                    </div>
                </div>

                <!-- CARD 2: TOTAL REKAMAN (Big Counter + Growth Badge) -->
                <div class="md:col-span-3 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                        <span>Total Rekaman</span>
                        <span class="text-slate-300 font-bold">•••</span>
                    </div>

                    <div class="my-auto py-2 flex items-baseline justify-between">
                        <div class="text-4xl font-black text-slate-900 tracking-tight" x-text="stats.totalRecords"></div>
                        <div class="flex items-center gap-1 bg-[#EDFAF8] text-[#009B7E] text-xs font-bold px-2 py-1 rounded-lg border border-emerald-100">
                            <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span>100% Atlas</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <span>Terbayar: <strong class="text-emerald-600" x-text="stats.paidCount + ' Slip'"></strong></span>
                        <span>Belum: <strong class="text-amber-600" x-text="stats.belumRekapCount"></strong></span>
                    </div>
                </div>

                <!-- CARD 3: TOTAL MEMBER / DITUGASKAN -->
                <div class="md:col-span-3 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                        <span>Status Penugasan Tim</span>
                        <span class="text-slate-300 font-bold">•••</span>
                    </div>

                    <div class="my-auto py-2 flex items-center justify-between">
                        <div>
                            <div class="text-4xl font-black text-slate-900 tracking-tight" x-text="stats.assignedCount"></div>
                            <div class="text-xs text-slate-400 font-medium mt-0.5">rekaman ditugaskan</div>
                        </div>

                        <!-- Avatars Stack -->
                        <div class="flex items-center -space-x-2">
                            <template x-for="(w, idx) in quickWorkers.slice(0, 3)" :key="idx">
                                <div class="w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-[10px] font-bold text-white shadow-xs"
                                     :class="['bg-emerald-600', 'bg-indigo-600', 'bg-teal-600'][idx % 3]"
                                     :title="w.name"
                                     x-text="w.initials">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                        <span>Belum Ditugaskan:</span>
                        <span class="text-rose-600 font-bold" x-text="stats.unassignedCount + ' rekaman'"></span>
                    </div>
                </div>

            </div>

            <!-- FILTER & SEARCH CONTROLS ROW -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                
                <!-- Left: Search Box + Akun Studio Searchable Dropdown -->
                <div class="flex items-center gap-2.5 flex-1 min-w-[280px] max-w-xl">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" 
                               x-model="filters.search" 
                               @keyup.enter="applyFilters(1)"
                               placeholder="Cari ID, task, pekerja..." 
                               class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#00B090] transition">
                    </div>

                    <!-- Dropdown Filter Akun Studio (Dengan Pencarian) -->
                    <div class="relative shrink-0" x-data="{ openAccountDropdown: false, searchAccount: '' }">
                        <button @click="openAccountDropdown = !openAccountDropdown; if(openAccountDropdown) $nextTick(() => $refs.accountSearchInput.focus())"
                                class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200/90 text-slate-700 py-2 px-3 rounded-xl text-xs font-semibold flex items-center gap-2 transition cursor-pointer shadow-2xs"
                                :class="filters.email !== 'all' ? 'border-[#00B090] text-[#009B7E] bg-emerald-50/50' : ''">
                            <div class="flex items-center gap-1.5 truncate max-w-[140px]">
                                <span class="w-2 h-2 rounded-full shrink-0" :class="filters.email !== 'all' ? 'bg-[#00B090]' : 'bg-slate-400'"></span>
                                <span class="truncate" x-text="filters.email === 'all' ? 'Semua Akun' : filters.email.split('@')[0]"></span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200" :class="openAccountDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <!-- Floating Searchable Dropdown Menu -->
                        <div x-show="openAccountDropdown" 
                             @click.outside="openAccountDropdown = false; searchAccount = ''"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                             class="absolute left-0 mt-1.5 w-72 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 text-xs text-slate-700"
                             x-cloak>
                            
                            <!-- Header inside Dropdown -->
                            <div class="flex items-center justify-between px-1 mb-2">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pilih Akun Studio</span>
                                <span class="text-[10px] text-[#009B7E] bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded font-bold" x-text="studioEmails.length + ' akun'"></span>
                            </div>

                            <!-- Search Input inside Dropdown -->
                            <div class="relative mb-2">
                                <input type="text"
                                       x-ref="accountSearchInput"
                                       x-model="searchAccount"
                                       placeholder="Cari nama akun studio..."
                                       class="w-full bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 rounded-xl pl-8 pr-7 py-1.5 text-xs focus:ring-1 focus:ring-[#00B090] focus:border-[#00B090] outline-none transition">
                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <button x-show="searchAccount" @click="searchAccount = ''" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600">✕</button>
                            </div>

                            <!-- Account List (Scrollable) -->
                            <div class="max-h-60 overflow-y-auto space-y-0.5 custom-scrollbar pr-1">
                                <!-- Option: Semua Akun -->
                                <button @click="filters.email = 'all'; applyFilters(1); openAccountDropdown = false; searchAccount = ''"
                                        class="w-full text-left flex items-center justify-between px-2.5 py-2 rounded-xl transition group"
                                        :class="filters.email === 'all' ? 'bg-[#EDFAF8] text-[#009B7E] font-bold' : 'hover:bg-slate-50 text-slate-700'">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" :class="filters.email === 'all' ? 'bg-[#00B090]' : 'bg-slate-300'"></span>
                                        <span class="text-xs">Semua Akun (All)</span>
                                    </div>
                                    <span x-show="filters.email === 'all'" class="text-[#00B090] font-black">✓</span>
                                </button>

                                <div class="my-1 border-t border-slate-100"></div>

                                <!-- Filtered Accounts Loop -->
                                <template x-for="email in filteredStudioEmails(searchAccount)" :key="email">
                                    <button @click="filters.email = email; applyFilters(1); openAccountDropdown = false; searchAccount = ''"
                                            class="w-full text-left flex items-center justify-between px-2.5 py-1.5 rounded-xl transition group"
                                            :class="filters.email === email ? 'bg-[#EDFAF8] text-[#009B7E] font-bold' : 'hover:bg-slate-50 text-slate-700'">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="w-2 h-2 rounded-full shrink-0" :class="filters.email === email ? 'bg-[#00B090]' : 'bg-slate-300'"></span>
                                            <div class="truncate">
                                                <div class="text-xs font-semibold leading-tight" x-text="email.split('@')[0]"></div>
                                                <div class="text-[10px] text-slate-400 truncate leading-tight" x-text="email"></div>
                                            </div>
                                        </div>
                                        <span x-show="filters.email === email" class="text-[#00B090] font-black shrink-0 ml-1">✓</span>
                                    </button>
                                </template>

                                <!-- Empty State -->
                                <div x-show="filteredStudioEmails(searchAccount).length === 0" 
                                     class="text-center py-4 text-xs text-slate-400">
                                    Tidak ditemukan akun "<span x-text="searchAccount" class="text-slate-700 font-semibold"></span>"
                                </div>
                            </div>

                            <!-- Footer Button: Tambah / Kelola Akun -->
                            <div class="pt-2 border-t border-slate-100 mt-1">
                                <button @click="openAccountsModal(); openAccountDropdown = false" 
                                        class="w-full text-center py-2 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100/80 text-[#009B7E] font-bold text-xs transition flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    <span>+ Tambah / Kelola Akun Studio</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center & Right: Presets Tanggal WITA & Dropdowns -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    
                    <!-- Atur Range Tanggal (Start Date s/d End Date) -->
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200/90 rounded-xl px-3 py-1.5 text-xs text-slate-700">
                        <span class="text-[11px] font-semibold text-slate-400 hidden sm:inline">Range Tanggal:</span>
                        <input type="date" 
                               x-model="filters.startDate" 
                               @change="applyFilters(1)" 
                               title="Tanggal Mulai"
                               class="bg-transparent text-xs font-semibold text-slate-700 focus:outline-none cursor-pointer">
                        <span class="text-slate-400 text-[11px] font-medium">s/d</span>
                        <input type="date" 
                               x-model="filters.endDate" 
                               @change="applyFilters(1)" 
                               title="Tanggal Selesai"
                               class="bg-transparent text-xs font-semibold text-slate-700 focus:outline-none cursor-pointer">
                        <button x-show="filters.startDate || filters.endDate" 
                                @click="filters.startDate = ''; filters.endDate = ''; applyFilters(1)" 
                                class="text-slate-400 hover:text-rose-500 p-0.5 rounded transition" 
                                title="Reset filter tanggal">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Filter Pekerja -->
                    <select x-model="filters.worker" @change="applyFilters(1)"
                            class="bg-slate-50 border border-slate-200/90 text-slate-700 text-xs font-semibold py-2 px-3 rounded-xl focus:outline-none focus:border-[#00B090] cursor-pointer">
                        <option value="all">Semua Pekerja</option>
                        <option value="unassigned">Belum Ditugaskan</option>
                        <template x-for="c in contributors" :key="c.contributor_id">
                            <option :value="c.name" x-text="c.name"></option>
                        </template>
                    </select>

                    <!-- Filter Status QC -->
                    <select x-model="filters.status" @change="applyFilters(1)"
                            class="bg-slate-50 border border-slate-200/90 text-slate-700 text-xs font-semibold py-2 px-3 rounded-xl focus:outline-none focus:border-[#00B090] cursor-pointer">
                        <option value="all">Semua Status QC</option>
                        <option value="approved">Approved (Billable)</option>
                        <option value="rejected">Rejected</option>
                        <option value="pending">Pending</option>
                    </select>

                    <!-- View Toggle: List / Grid -->
                    <div class="flex items-center bg-slate-100 rounded-xl p-0.5 border border-slate-200/60">
                        <button class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-800 shadow-2xs flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                            <span>List</span>
                        </button>
                        <button class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            <span>Grid</span>
                        </button>
                    </div>

                    <!-- Reset Filters -->
                    <button @click="resetFilters()" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition" title="Reset Semua Filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>

                </div>

            </div>

            <!-- TABLE CONTAINER (EXACT DRIBBLE STYLE) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                
                <div class="overflow-x-auto custom-scrollbar w-full">
                    <table class="w-full min-w-[980px] text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/95 border-b border-slate-200/90 text-slate-500 font-bold uppercase tracking-wider text-[11px] sticky top-0 z-10 backdrop-blur-md whitespace-nowrap">
                                <th class="py-2.5 px-2 w-8 text-center">
                                    <input type="checkbox" @change="toggleSelectAll($event)" :checked="isAllSelected" class="rounded text-[#00B090] focus:ring-[#00B090] w-3.5 h-3.5 cursor-pointer">
                                </th>
                                <th class="py-2.5 px-2 min-w-[130px]">Pekerja (Ditandai)</th>
                                <th class="py-2.5 px-1.5 w-20">Status Bayar</th>
                                <th class="py-2.5 px-2.5 min-w-[140px]">Akun Studio</th>
                                <th class="py-2.5 px-2 min-w-[140px] max-w-[240px]">Nama Tugas (Task)</th>
                                <th class="py-2.5 px-1.5 min-w-[125px]">Waktu Rekam (WITA)</th>
                                <th class="py-2.5 px-1.5 w-18 text-right">Durasi Total</th>
                                <th class="py-2.5 px-1.5 w-16 text-center">Status QC</th>
                                <th class="py-2.5 px-1.5 w-22 text-right text-emerald-700">Billable (Appr)</th>
                                <th class="py-2.5 px-1.5 w-18 text-right text-rose-700">Reject</th>
                                <th class="py-2.5 px-1.5 w-18 text-right text-amber-700 pr-2">Pending</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <!-- Loading Skeleton -->
                            <tr x-show="isLoading" x-cloak>
                                <td colspan="11" class="p-16 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="animate-spin h-7 w-7 text-brand-600 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        <span class="text-sm font-semibold text-slate-600">Memuat data rekaman studio...</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr x-show="!isLoading && recordings.length === 0" x-cloak>
                                <td colspan="11" class="p-16 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700 mb-1">Tidak Ada Rekaman</span>
                                        <span class="text-xs text-slate-500">Coba ubah filter atau jalankan "Sinkronkan Atlas" untuk menarik data baru.</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row Loop -->
                            <template x-for="(rec, index) in recordings" :key="rec.record_id">
                                <tr :class="isRowSelected(rec.record_id) ? 'bg-[#EDFAF8] hover:bg-[#D5F4EF]/80 border-l-[3px] border-l-[#00B090]' : 'hover:bg-slate-50/80 bg-white border-l-[3px] border-l-transparent'"
                                    @click="toggleRowSelection(rec.record_id, index, $event)"
                                    class="transition-all duration-150 group cursor-pointer select-none border-b border-slate-100">
                                    
                                    <!-- Checkbox -->
                                    <td class="py-2.5 px-2 text-center" @click.stop>
                                        <input type="checkbox" 
                                               :value="String(rec.record_id)"
                                               :checked="isRowSelected(rec.record_id)"
                                               @change="toggleRowSelection(rec.record_id, index, $event)"
                                               class="rounded text-[#00B090] focus:ring-[#00B090] w-4 h-4 cursor-pointer accent-[#00B090]">
                                    </td>

                                    <!-- Pekerja (Ditandai Untuk) - Modern Pill with Popover -->
                                    <td class="py-2.5 px-2.5" @click.stop>
                                        <div class="relative inline-block text-left">
                                            <!-- Assigned Pill -->
                                            <button x-show="rec.assigned_worker_name"
                                                    @click="togglePopover(rec.record_id)"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold shadow-2xs hover:shadow-sm border transition max-w-[170px]"
                                                    :class="getWorkerStyle(rec.assigned_worker_name)">
                                                <span class="w-5 h-5 rounded-lg flex items-center justify-center text-[10px] font-black uppercase"
                                                      :class="getWorkerAvatarStyle(rec.assigned_worker_name)"
                                                      x-text="getWorkerInitials(rec.assigned_worker_name)"></span>
                                                <span class="truncate" x-text="getWorkerShortName(rec.assigned_worker_name)"></span>
                                                <svg class="w-3 h-3 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>

                                            <!-- Unassigned Action Pill -->
                                            <button x-show="!rec.assigned_worker_name"
                                                    @click="togglePopover(rec.record_id)"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100/80 border border-dashed border-amber-300 transition">
                                                <span class="w-4 h-4 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center font-bold text-[10px]">+</span>
                                                <span>Tandai Pekerja</span>
                                            </button>

                                            <!-- Popover Picker Menu -->
                                            <div x-show="activePopover === rec.record_id"
                                                 x-cloak
                                                 @click.outside="activePopover = null"
                                                 class="absolute left-0 mt-1 w-64 bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 p-2 py-2 text-xs">
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 py-1 mb-1">Pilih Pekerja:</div>
                                                <div class="max-h-60 overflow-y-auto custom-scrollbar space-y-0.5">
                                                    <!-- Option Unassign -->
                                                    <button @click="assignWorker(rec.record_id, null); activePopover = null"
                                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-rose-50 text-rose-600 font-semibold flex items-center gap-2">
                                                        <span class="w-5 h-5 rounded-md bg-rose-100 flex items-center justify-center text-[10px]">✕</span>
                                                        <span>Hapus Tanda (Kosongkan)</span>
                                                    </button>
                                                    
                                                    <!-- List of Contributors -->
                                                    <template x-for="c in contributors" :key="c.contributor_id || c.id">
                                                        <button @click="assignWorker(rec.record_id, (c.name || c.nama)); activePopover = null"
                                                                :class="rec.assigned_worker_name === (c.name || c.nama) ? 'bg-indigo-50 font-bold text-indigo-700' : 'hover:bg-slate-100 text-slate-700'"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-lg flex items-center justify-between transition">
                                                            <div class="flex items-center gap-2 truncate">
                                                                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-black"
                                                                      :class="getWorkerAvatarStyle((c.name || c.nama))"
                                                                      x-text="getWorkerInitials((c.name || c.nama))"></span>
                                                                <span class="truncate" x-text="(c.name || c.nama)"></span>
                                                            </div>
                                                            <span x-show="rec.assigned_worker_name === (c.name || c.nama)" class="text-indigo-600 font-black">✓</span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status Bayar - Click to Cycle Toggle -->
                                    <td class="py-2.5 px-2.5" @click.stop>
                                        <button @click="cyclePaymentStatus(rec.record_id, rec.manual_payment_status)"
                                                :title="'Klik untuk ganti status bayar'"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 border-emerald-300/80 hover:bg-emerald-100': rec.manual_payment_status === 'Paid',
                                                    'bg-amber-50 text-amber-700 border-amber-300/80 hover:bg-amber-100': rec.manual_payment_status === 'Belum',
                                                    'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200/80': !rec.manual_payment_status || rec.manual_payment_status === 'Belum Rekap'
                                                }"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold border transition shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                  :class="{
                                                      'bg-emerald-500': rec.manual_payment_status === 'Paid',
                                                      'bg-amber-500': rec.manual_payment_status === 'Belum',
                                                      'bg-slate-400': !rec.manual_payment_status || rec.manual_payment_status === 'Belum Rekap'
                                                  }"></span>
                                            <span x-text="rec.manual_payment_status || 'Belum Rekap'"></span>
                                        </button>
                                    </td>

                                    <!-- Akun Studio -->
                                    <td class="py-2.5 px-2.5">
                                        <div class="font-bold text-slate-900 leading-tight" x-text="getAccountName(rec.participant_email)"></div>
                                        <div class="text-[11px] text-slate-500 font-mono truncate max-w-[190px]" :title="rec.participant_email + ' (' + rec.record_id + ')'" x-text="rec.participant_email"></div>
                                    </td>

                                    <!-- Nama Tugas -->
                                    <td class="py-2.5 px-2.5">
                                        <div class="font-semibold text-slate-800 line-clamp-1" :title="rec.task_title" x-text="rec.task_title"></div>
                                    </td>

                                    <!-- Waktu Rekam (WITA) -->
                                    <td class="py-2.5 px-2 whitespace-nowrap text-slate-600 font-mono text-[11px]" x-text="formatWita(rec.recorded_at, rec.recorded_utc)"></td>

                                    <!-- Durasi Total -->
                                    <td class="py-2.5 px-2 text-right whitespace-nowrap font-mono">
                                        <span class="font-bold text-slate-900" x-text="(rec.duration_min || 0).toFixed(2) + 'm'"></span>
                                        <div class="text-[10px] text-slate-400" x-text="(rec.duration_sec || 0).toFixed(0) + 's'"></div>
                                    </td>

                                    <!-- Status QC -->
                                    <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                        <span :class="rec.processing_status === 'processed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'" class="px-2 py-0.5 rounded-lg text-[10px] font-bold border" x-text="rec.processing_status || '-'"></span>
                                    </td>

                                    <!-- Durasi Billable (Approved) -->
                                    <td class="py-2.5 px-2 text-right whitespace-nowrap font-mono">
                                        <span x-show="(rec.billable_min || 0) > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 text-xs">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            <span x-text="(rec.billable_min || 0).toFixed(2) + 'm'"></span>
                                        </span>
                                        <span x-show="!(rec.billable_min > 0)" class="text-slate-300">-</span>
                                    </td>

                                    <!-- Durasi Reject -->
                                    <td class="py-2.5 px-2 text-right whitespace-nowrap font-mono">
                                        <span x-show="(rec.rejected_min || 0) > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 font-bold border border-rose-200 text-xs">
                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            <span x-text="(rec.rejected_min || 0).toFixed(2) + 'm'"></span>
                                        </span>
                                        <span x-show="!(rec.rejected_min > 0)" class="text-slate-300">-</span>
                                    </td>

                                    <!-- Durasi Pending -->
                                    <td class="py-2.5 px-2 text-right whitespace-nowrap font-mono">
                                        <span x-show="getPendingMin(rec) > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-bold border border-amber-200 text-xs">
                                            <svg class="w-3 h-3 text-amber-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            <span x-text="getPendingMin(rec).toFixed(2) + 'm'"></span>
                                        </span>
                                        <span x-show="!(getPendingMin(rec) > 0)" class="text-slate-300">-</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION FOOTER -->
                <div class="py-4 px-6 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800 font-semibold" x-text="recordings.length ? ((pagination.page - 1) * pagination.limit + 1) : 0"></strong> 
                        s/d <strong class="text-slate-800 font-semibold" x-text="Math.min(pagination.page * pagination.limit, pagination.total)"></strong> 
                        dari <strong class="text-slate-800 font-semibold" x-text="pagination.total"></strong> total data rekaman
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Limit Selector -->
                        <select x-model="pagination.limit" @change="applyFilters(1)" class="bg-white border border-slate-200 py-1.5 px-2.5 rounded-xl text-xs font-medium text-slate-700 focus:outline-none">
                            <option value="25">25 baris</option>
                            <option value="50">50 baris</option>
                            <option value="100">100 baris</option>
                        </select>

                        <!-- Previous Button -->
                        <button @click="pagination.page > 1 && applyFilters(pagination.page - 1)" 
                                :disabled="pagination.page <= 1"
                                class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-2xs">
                            Sebelumnya
                        </button>

                        <span class="px-2 font-bold text-slate-800">
                            <span x-text="pagination.page"></span> / <span x-text="pagination.totalPages || 1"></span>
                        </span>

                        <!-- Next Button -->
                        <button @click="pagination.page < pagination.totalPages && applyFilters(pagination.page + 1)" 
                                :disabled="pagination.page >= pagination.totalPages"
                                class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-2xs">
                            Selanjutnya
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- FLOATING ACTION DOCK (Modern Linear-Style Quick Assignment Bar) -->
    <div x-show="selectedIds.length > 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-6"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-6"
         class="fixed bottom-4 sm:bottom-6 inset-x-2 sm:inset-x-0 mx-auto w-auto sm:w-max max-w-[calc(100vw-1rem)] sm:max-w-[95vw] bg-slate-900/95 backdrop-blur-md text-white px-3 sm:px-4 py-2 sm:py-2.5 rounded-2xl shadow-2xl border border-slate-700 flex flex-wrap items-center justify-between sm:justify-start gap-2 sm:gap-3 z-40 text-xs"
         x-data="{ dropdownOpen: false, searchWorker: '' }"
         x-cloak>
        
        <!-- Selection Counter Badge -->
        <div class="flex items-center gap-2 border-r border-slate-700 pr-3 shrink-0">
            <span class="w-5 h-5 rounded-full bg-[#00B090] text-slate-900 font-extrabold flex items-center justify-center text-[11px]" x-text="selectedIds.length"></span>
            <span class="font-medium text-slate-300 whitespace-nowrap">item terpilih</span>
        </div>

        <!-- Live Selected Duration Metrics (Approved & Rejected Hours) -->
        <div class="flex items-center gap-2 border-r border-slate-700 pr-3 shrink-0">
            <!-- Approved Hours Badge -->
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 font-mono shadow-xs" 
                 title="Total Durasi Approved yang Dipilih">
                <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-[10px] text-emerald-400 font-semibold uppercase tracking-wider hidden sm:inline">Approved:</span>
                <span class="font-black text-xs text-white" x-text="selectedStats.approvedHours + ' jam'"></span>
                <span class="text-[10px] text-emerald-400 font-normal" x-text="'(' + selectedStats.approvedMin.toFixed(1) + 'm)'"></span>
            </div>

            <!-- Rejected Hours Badge -->
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-rose-500/15 border border-rose-500/40 text-rose-300 font-mono shadow-xs" 
                 title="Total Durasi Rejected yang Dipilih">
                <svg class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span class="text-[10px] text-rose-400 font-semibold uppercase tracking-wider hidden sm:inline">Reject:</span>
                <span class="font-black text-xs text-white" x-text="selectedStats.rejectedHours + ' jam'"></span>
                <span class="text-[10px] text-rose-400 font-normal" x-text="'(' + selectedStats.rejectedMin.toFixed(1) + 'm)'"></span>
            </div>

            <!-- Pending Badge (Conditionally shown if > 0) -->
            <template x-if="selectedStats.pendingMin > 0">
                <div class="hidden md:flex items-center gap-1.5 px-2 py-1 rounded-xl bg-amber-500/15 border border-amber-500/40 text-amber-300 font-mono shadow-xs"
                     title="Total Durasi Pending yang Dipilih">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                    <span class="text-[10px] text-amber-400 font-semibold uppercase tracking-wider">Pending:</span>
                    <span class="font-black text-xs text-white" x-text="selectedStats.pendingHours + ' jam'"></span>
                </div>
            </template>
        </div>

        <!-- Worker Assignment Section with Quick Buttons & Searchable Dropdown -->
        <div class="flex items-center gap-1.5">
            <span class="text-[11px] text-slate-400 mr-1 hidden sm:inline">Tugaskan:</span>
            
            <!-- Quick 1-Click Worker Assignment Buttons (Top 4) -->
            <div class="flex items-center gap-1.5 overflow-x-auto max-w-[200px] sm:max-w-xs">
                <template x-for="w in quickWorkers.slice(0, 4)" :key="w.name">
                    <button @click="applyFastAssign(w.name)" 
                            class="bg-slate-800 hover:bg-[#00B090] hover:text-white px-2.5 py-1.5 rounded-xl font-semibold transition text-slate-200 shrink-0 border border-slate-700"
                            x-text="w.short"
                            :title="'Tugaskan ke ' + w.name"></button>
                </template>
            </div>

            <!-- SEARCHABLE DROPDOWN BUTTON FOR OTHER WORKERS -->
            <div class="relative">
                <button @click="dropdownOpen = !dropdownOpen; if(dropdownOpen) $nextTick(() => $refs.workerSearchInput.focus())" 
                        class="bg-slate-800 hover:bg-slate-700 text-emerald-400 hover:text-emerald-300 px-3 py-1.5 rounded-xl font-semibold transition shrink-0 border border-slate-700 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span>Lainnya...</span>
                    <svg class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <!-- Floating Searchable Dropdown Menu (Opens Above the floating bar) -->
                <div x-show="dropdownOpen" 
                     @click.outside="dropdownOpen = false; searchWorker = ''"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                     class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 sm:left-0 sm:translate-x-0 w-72 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl p-2.5 z-50 text-slate-200"
                     x-cloak>
                    
                    <!-- Search Header -->
                    <div class="flex items-center justify-between px-1 mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pilih / Cari Kontributor</span>
                        <span class="text-[10px] text-emerald-400 bg-emerald-950/80 border border-emerald-800/80 px-1.5 py-0.5 rounded" x-text="allAssignableWorkers.length + ' pekerja'"></span>
                    </div>

                    <!-- Search Input Field with clear icon -->
                    <div class="relative mb-2">
                        <input type="text"
                               x-ref="workerSearchInput"
                               x-model="searchWorker"
                               placeholder="Ketik nama atau tim..."
                               class="w-full bg-slate-950 border border-slate-700 text-white placeholder-slate-500 rounded-xl pl-8 pr-7 py-2 text-xs focus:ring-1 focus:ring-[#00B090] focus:border-[#00B090] outline-none transition">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <button x-show="searchWorker" @click="searchWorker = ''" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-white">✕</button>
                    </div>

                    <!-- Contributor List -->
                    <div class="max-h-56 overflow-y-auto space-y-1 custom-scroll pr-1">
                        <template x-for="c in filteredAssignableWorkers(searchWorker)" :key="c.name">
                            <button @click="applyFastAssign(c.name); dropdownOpen = false; searchWorker = ''"
                                    class="w-full text-left flex items-center justify-between px-2.5 py-2 rounded-xl hover:bg-slate-800 transition group border border-transparent hover:border-slate-700">
                                <div class="flex items-center gap-2.5 truncate">
                                    <div class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 text-[#00B090] group-hover:bg-[#00B090] group-hover:text-white font-bold flex items-center justify-center text-[10px] shrink-0 transition"
                                         x-text="getWorkerInitials(c.name)"></div>
                                    <div class="truncate">
                                        <div class="text-xs font-semibold text-slate-200 group-hover:text-white truncate" x-text="c.name"></div>
                                        <div class="text-[10px] text-slate-400 truncate" x-text="c.team || 'Kontributor Studio'"></div>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 group-hover:text-[#00B090] shrink-0 ml-2">Pilih →</span>
                            </button>
                        </template>

                        <!-- Empty State if no match -->
                        <div x-show="filteredAssignableWorkers(searchWorker).length === 0" 
                             class="text-center py-5 text-xs text-slate-400">
                            Tidak ditemukan pekerja dengan nama "<span x-text="searchWorker" class="text-white"></span>"
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Status Buttons -->
        <div class="flex items-center gap-1.5 border-l border-slate-700 pl-3">
            <button @click="applyFastStatus('Paid')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-xl font-bold transition shadow-xs">
                ✓ Lunas
            </button>
            <button @click="applyFastStatus('Belum Rekap')" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded-xl font-semibold transition border border-slate-700">
                Belum
            </button>
        </div>

        <!-- Deselect / Clear -->
        <button @click="selectedIds = []" class="text-slate-400 hover:text-white p-1 ml-1 rounded-lg transition" title="Batal Pilih">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

    </div>

    <!-- MODAL: KELOLA AKUN STUDIO (DYNAMIC STUDIO ACCOUNTS MANAGER) -->
    <div x-show="showAccountsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50" x-cloak>
        <div @click.outside="showAccountsModal = false" class="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl border border-slate-200 flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#00B090] flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Kelola Akun AIKu Studio</h3>
                        <p class="text-xs text-slate-500">Daftar akun yang dipantau bot untuk scraping & sinkronisasi data dari Atlas.</p>
                    </div>
                </div>
                <button @click="showAccountsModal = false" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-100 transition">✕</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                
                <!-- Card Tambah Akun Baru -->
                <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#00B090]"></span>
                            Tambah Akun Studio Baru
                        </span>
                        <button @click="isBatchAccountMode = !isBatchAccountMode" class="text-[11px] font-semibold text-[#009B7E] hover:underline" x-text="isBatchAccountMode ? 'Mode Input Satuan' : '+ Input Banyak Sekaligus (Batch)'"></button>
                    </div>

                    <!-- Single Mode -->
                    <div x-show="!isBatchAccountMode" class="flex gap-2">
                        <input type="text" 
                               x-model="newAccountInput" 
                               @keyup.enter="addNewStudioAccount()"
                               placeholder="Contoh: hajinew@kamerakitaid.site atau ketik hajinew saja"
                               class="flex-1 px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00B090] focus:ring-1 focus:ring-[#00B090]">
                        <button @click="addNewStudioAccount()" 
                                :disabled="!newAccountInput.trim()"
                                class="bg-[#00B090] hover:bg-[#009B7E] disabled:opacity-50 text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            <span>Tambah Akun</span>
                        </button>
                    </div>

                    <!-- Batch Mode -->
                    <div x-show="isBatchAccountMode" class="space-y-2" x-cloak>
                        <textarea x-model="batchAccountInput" 
                                  rows="3" 
                                  placeholder="Paste daftar email akun di sini (pisahkan baris baru atau koma). Domain @kamerakitaid.site otomatis ditambahkan jika tidak ditulis."
                                  class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00B090] focus:ring-1 focus:ring-[#00B090]"></textarea>
                        <div class="flex justify-end gap-2">
                            <button @click="isBatchAccountMode = false" class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-700">Batal</button>
                            <button @click="addNewStudioAccount(true)" 
                                    :disabled="!batchAccountInput.trim()"
                                    class="bg-[#00B090] hover:bg-[#009B7E] disabled:opacity-50 text-white px-4 py-1.5 rounded-xl text-xs font-bold transition shadow-xs">
                                Tambahkan Semua Akun
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Daftar Akun Terdaftar -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akun Terdaftar</h4>
                            <span class="text-[11px] font-bold text-[#009B7E] bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100" x-text="accountsList.length + ' Akun Total'"></span>
                        </div>

                        <!-- Search within accounts modal -->
                        <div class="relative w-48 sm:w-56">
                            <input type="text" 
                                   x-model="modalAccountSearch" 
                                   placeholder="Filter akun..." 
                                   class="w-full pl-7 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-[#00B090]">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <!-- Accounts List Grid/Table -->
                    <div class="border border-slate-200/90 rounded-2xl overflow-hidden max-h-72 overflow-y-auto divide-y divide-slate-100">
                        <template x-for="acc in filteredModalAccounts" :key="acc.email">
                            <div class="px-4 py-2.5 flex items-center justify-between hover:bg-slate-50 transition">
                                <div class="flex items-center gap-3 truncate">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs shrink-0"
                                         x-text="acc.name.substring(0, 2).toUpperCase()"></div>
                                    <div class="truncate">
                                        <div class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                            <span x-text="acc.name"></span>
                                            <span x-show="acc.active" class="w-2 h-2 rounded-full bg-[#00B090]" title="Aktif"></span>
                                            <span x-show="!acc.active" class="w-2 h-2 rounded-full bg-slate-300" title="Nonaktif"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 truncate" x-text="acc.email"></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- Toggle Active Button -->
                                    <button @click="toggleStudioAccount(acc.email)" 
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition border"
                                            :class="acc.active ? 'bg-emerald-50 text-[#009B7E] border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                            x-text="acc.active ? 'Aktif' : 'Nonaktif'"
                                            :title="acc.active ? 'Klik untuk nonaktifkan sementara' : 'Klik untuk aktifkan'"></button>

                                    <!-- Delete Button -->
                                    <button @click="deleteStudioAccount(acc.email)" 
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                            title="Hapus akun dari daftar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredModalAccounts.length === 0" class="p-8 text-center text-xs text-slate-400">
                            Tidak ditemukan akun dengan kata kunci tersebut.
                        </div>
                    </div>
                </div>

                <!-- Info Box Bot Integration -->
                <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-3.5 flex items-start gap-3 text-xs text-emerald-900">
                    <svg class="w-5 h-5 text-[#00B090] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="leading-relaxed">
                        <strong>Bot Sinkronisasi Otomatis Terhubung:</strong> Setiap akun yang berstatus <em>Aktif</em> di daftar ini akan otomatis dibaca oleh bot saat sinkronisasi dijalankan (baik via tombol Web Panel maupun file .bat). Anda tidak perlu merevisi script secara manual lagi!
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Tersimpan di: <code class="font-mono text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200">storage/studio_accounts.json</code></span>
                <button @click="showAccountsModal = false" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition">
                    Selesai
                </button>
            </div>

        </div>
    </div>

    <!-- REALTIME BOT PROGRESS & ROBOT ANIMATION MODAL -->
    <div x-show="showConsoleModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 z-50" x-cloak>
        <div @click.outside="showConsoleModal = false" class="bg-slate-950 text-slate-200 rounded-3xl w-full max-w-3xl overflow-hidden shadow-2xl border border-slate-800 flex flex-col max-h-[92vh] transition-all">
            
            <!-- STAGE 1: LIVE BOT PROGRESS & ANIMATED WALKING ROBOT -->
            <div class="relative bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 p-5 sm:p-6 border-b border-slate-800 overflow-hidden">
                <!-- Cyber subtle grid background -->
                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#00B090 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>

                <!-- Top Modal Bar -->
                <div class="relative flex items-center justify-between mb-3 z-10">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block animate-ping"></span>
                        <span class="text-xs font-mono font-bold tracking-wider text-emerald-400 ml-1.5 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="isSyncing || botProgress.isRunning"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="(isSyncing || botProgress.isRunning) ? 'Bot AIKu Studio Sedang Berjalan Realtime' : 'Status Bot AIKu Studio'"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-mono font-semibold px-2.5 py-0.5 rounded-full border transition"
                              :class="(isSyncing || botProgress.isRunning) ? 'border-emerald-500/40 bg-emerald-950/70 text-emerald-300 animate-pulse' : (botProgress.stage === 'error' ? 'border-rose-500/50 bg-rose-950/80 text-rose-300 font-bold' : 'border-slate-700 bg-slate-900 text-slate-400')"
                              x-text="(isSyncing || botProgress.isRunning) ? '● LIVE SCRAPING' : (botProgress.stage === 'error' ? '✖ GAGAL' : 'IDLE / SELESAI')"></span>
                        <button @click="showConsoleModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg transition" title="Tutup / Berjalan di Latar Belakang">✕</button>
                    </div>
                </div>

                <!-- Center: WALKING ROBOT & REAL-TIME PROGRESS INFO -->
                <div class="relative flex flex-col sm:flex-row items-center justify-between gap-5 py-1 z-10">
                    
                    <!-- Left: Animated Walking Robot Character -->
                    <div class="flex flex-col items-center justify-center shrink-0">
                        <div class="relative w-32 h-32 sm:w-36 sm:h-36 flex items-center justify-center">
                            
                            <!-- Floating Data Particles -->
                            <div class="absolute -top-1 left-3 text-[10px] font-mono text-cyan-400 font-bold" :class="(isSyncing || botProgress.isRunning) ? 'animate-pulse' : ''">0101</div>
                            <div class="absolute top-3 -right-1 text-[10px] font-mono text-emerald-400 font-bold" :class="(isSyncing || botProgress.isRunning) ? 'animate-bounce' : ''">Atlas✓</div>
                            <div class="absolute bottom-6 -left-2 text-[10px] font-mono text-teal-300 font-bold opacity-80" :class="(isSyncing || botProgress.isRunning) ? 'animate-pulse' : ''">Sync⚡</div>

                            <!-- Glow Halo behind Robot -->
                            <div class="absolute w-28 h-28 rounded-full bg-emerald-500/15 blur-xl"></div>

                            <!-- Custom SVG Walking Robot -->
                            <svg viewBox="0 0 120 120" class="w-28 h-28 sm:w-32 sm:h-32 relative z-10 transition-transform" :class="(isSyncing || botProgress.isRunning) ? 'robot-bobbing' : ''">
                                <!-- Head Antenna -->
                                <line x1="60" y1="28" x2="60" y2="12" stroke="#00B090" stroke-width="3" stroke-linecap="round"/>
                                <circle cx="60" cy="10" r="5" fill="#00E5B8" class="animate-pulse"/>
                                <!-- Radar Signal Ping -->
                                <circle cx="60" cy="10" r="10" stroke="#00E5B8" stroke-width="1.5" fill="none" opacity="0.6" class="animate-ping" x-show="isSyncing || botProgress.isRunning"/>

                                <!-- Robot Head -->
                                <rect x="36" y="26" width="48" height="34" rx="10" fill="#1E293B" stroke="#00B090" stroke-width="2.5"/>
                                <!-- Ears / Bolts -->
                                <rect x="30" y="38" width="6" height="10" rx="2" fill="#334155"/>
                                <rect x="84" y="38" width="6" height="10" rx="2" fill="#334155"/>

                                <!-- Visor Face / Screen -->
                                <rect x="42" y="32" width="36" height="20" rx="6" fill="#0F172A"/>
                                
                                <!-- LED Scanning Eyes (Running vs Idle) -->
                                <g x-show="isSyncing || botProgress.isRunning">
                                    <line x1="46" y1="42" x2="74" y2="42" stroke="#065F46" stroke-width="3" stroke-linecap="round"/>
                                    <circle cx="50" cy="42" r="3.5" fill="#10B981" class="animate-pulse"/>
                                    <circle cx="70" cy="42" r="3.5" fill="#10B981" class="animate-pulse"/>
                                    <ellipse cx="60" cy="42" rx="4" ry="2" fill="#6EE7B7" opacity="0.9" class="laser-scanner"/>
                                </g>
                                <g x-show="!isSyncing && !botProgress.isRunning">
                                    <circle cx="51" cy="42" r="3.5" fill="#10B981"/>
                                    <circle cx="69" cy="42" r="3.5" fill="#10B981"/>
                                    <!-- Friendly Smile -->
                                    <path d="M 55 47 Q 60 50 65 47" stroke="#10B981" stroke-width="2" fill="none" stroke-linecap="round"/>
                                </g>

                                <!-- Neck -->
                                <rect x="54" y="60" width="12" height="5" fill="#475569"/>

                                <!-- Robot Body / Chest -->
                                <rect x="34" y="65" width="52" height="36" rx="8" fill="#1E293B" stroke="#00B090" stroke-width="2.5"/>
                                
                                <!-- Chest Heartbeat Pulse -->
                                <rect x="42" y="71" width="36" height="14" rx="4" fill="#0F172A"/>
                                <path d="M 44 78 L 49 78 L 52 74 L 56 82 L 60 76 L 63 78 L 76 78" stroke="#00E5B8" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round" class="pulse-path"/>

                                <!-- Battery / Progress Indicator Dots -->
                                <circle cx="48" cy="92" r="2.5" :fill="(botProgress.percent || 0) >= 25 ? '#10B981' : '#475569'"/>
                                <circle cx="56" cy="92" r="2.5" :fill="(botProgress.percent || 0) >= 50 ? '#10B981' : '#475569'"/>
                                <circle cx="64" cy="92" r="2.5" :fill="(botProgress.percent || 0) >= 75 ? '#10B981' : '#475569'"/>
                                <circle cx="72" cy="92" r="2.5" :fill="(botProgress.percent || 0) >= 95 ? '#10B981' : '#475569'"/>

                                <!-- Left Arm -->
                                <g :class="(isSyncing || botProgress.isRunning) ? 'robot-arm-left-anim' : ''">
                                    <path d="M 34 72 L 20 84" stroke="#475569" stroke-width="4" stroke-linecap="round"/>
                                    <circle cx="20" cy="84" r="4" fill="#00B090"/>
                                </g>
                                <!-- Right Arm -->
                                <g :class="(isSyncing || botProgress.isRunning) ? 'robot-arm-right-anim' : ''">
                                    <path d="M 86 72 L 100 84" stroke="#475569" stroke-width="4" stroke-linecap="round"/>
                                    <circle cx="100" cy="84" r="4" fill="#00B090"/>
                                </g>

                                <!-- Left Leg (Animated Walking) -->
                                <g :class="(isSyncing || botProgress.isRunning) ? 'robot-leg-left-anim' : ''">
                                    <line x1="48" y1="101" x2="48" y2="114" stroke="#475569" stroke-width="5" stroke-linecap="round"/>
                                    <rect x="42" y="112" width="13" height="5" rx="2.5" fill="#00B090"/>
                                </g>

                                <!-- Right Leg (Animated Walking) -->
                                <g :class="(isSyncing || botProgress.isRunning) ? 'robot-leg-right-anim' : ''">
                                    <line x1="72" y1="101" x2="72" y2="114" stroke="#475569" stroke-width="5" stroke-linecap="round"/>
                                    <rect x="67" y="112" width="13" height="5" rx="2.5" fill="#00B090"/>
                                </g>
                            </svg>

                            <!-- Conveyor Belt under robot feet -->
                            <div class="absolute bottom-1 w-32 sm:w-36 h-2 rounded-full overflow-hidden bg-slate-800 border border-slate-700/80">
                                <div class="w-full h-full conveyor-track" :class="(isSyncing || botProgress.isRunning) ? 'conveyor-active' : ''"></div>
                            </div>
                        </div>
                        
                        <div class="mt-1 text-[11px] font-mono text-emerald-400 font-semibold tracking-wide flex items-center gap-1.5">
                            <span>Bot AIKu Studio</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" :class="(isSyncing || botProgress.isRunning) ? 'animate-ping' : ''"></span>
                        </div>
                    </div>

                    <!-- Right: Progress Percentage, Shimmer Bar, Account & Pipeline -->
                    <div class="flex-1 w-full space-y-3">
                        
                        <!-- Percentage Readout & Total Records -->
                        <div class="flex items-end justify-between">
                            <div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                    <span>Progress Sinkronisasi</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold" 
                                          :class="botProgress.stage === 'error' ? 'bg-rose-950/90 text-rose-300 border border-rose-500/50' : 'bg-slate-800 text-emerald-400'"
                                          x-text="botProgress.stage ? botProgress.stage.toUpperCase() : 'STANDBY'"></span>
                                </div>
                                <div class="text-3xl sm:text-4xl font-extrabold tracking-tight mt-0.5 flex items-baseline gap-2"
                                     :class="botProgress.stage === 'error' ? 'text-rose-400' : 'text-white'">
                                    <span x-text="(botProgress.percent || 0) + '%'"></span>
                                    <span class="text-xs font-medium text-slate-400" x-show="botProgress.totalAccounts" 
                                          x-text="'(' + (botProgress.currentIndex || 0) + ' dari ' + botProgress.totalAccounts + ' akun)'"></span>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <div class="text-xs text-slate-400">Data Ditemukan: <span class="font-bold text-emerald-400 font-mono" x-text="(botProgress.recordsCount || 0) + ' data'"></span></div>
                                <div class="text-[11px] text-slate-400 mt-1">Terakhir Update: <span class="text-slate-200 font-mono font-semibold" x-text="formattedLastUpdate"></span></div>
                            </div>
                        </div>

                        <!-- Modern Shimmer Progress Bar -->
                        <div class="relative w-full h-3.5 bg-slate-800/80 rounded-full p-0.5 overflow-hidden border border-slate-700/80 shadow-inner">
                            <div class="h-full rounded-full transition-all duration-500 relative"
                                 :class="botProgress.stage === 'error' ? 'bg-gradient-to-r from-rose-600 via-rose-500 to-red-500' : 'bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400'"
                                 :style="'width: ' + Math.max(botProgress.percent || 0, 3) + '%'">
                                <div class="absolute inset-0 bg-white/20 progress-shimmer" x-show="isSyncing || botProgress.isRunning"></div>
                            </div>
                        </div>

                        <!-- Current Account & Live Status Box -->
                        <div class="rounded-xl p-3 flex items-start gap-3 border transition"
                             :class="botProgress.stage === 'error' ? 'bg-rose-950/20 border-rose-900/60' : 'bg-slate-950/80 border-slate-800'">
                            <div class="w-8 h-8 rounded-lg font-mono font-bold flex items-center justify-center text-xs shrink-0 mt-0.5 border"
                                 :class="botProgress.stage === 'error' ? 'bg-rose-950/90 border-rose-500/50 text-rose-400' : 'bg-emerald-950/80 border-emerald-500/30 text-emerald-400'">
                                <span x-text="botProgress.stage === 'error' ? '!' : (botProgress.currentIndex || '0')"></span>
                            </div>
                            <div class="flex-1 truncate">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="truncate font-mono" :class="botProgress.stage === 'error' ? 'text-rose-300' : 'text-white'" x-text="botProgress.stage === 'error' ? 'Terjadi Kesalahan' : (botProgress.currentAccount || 'Menyiapkan sinkronisasi...')"></span>
                                    <span class="text-[10px] text-slate-400 shrink-0 ml-2 font-mono" x-text="botProgress.updatedAt ? formatTimeOnly(botProgress.updatedAt) : ''"></span>
                                </div>
                                <div class="text-[11px] truncate mt-0.5 font-mono"
                                     :class="botProgress.stage === 'error' ? 'text-rose-400 font-semibold' : 'text-emerald-400/90'"
                                     x-text="botProgress.message || 'Bot siap dijalankan kapan saja.'"></div>
                            </div>
                        </div>

                        <!-- 4 Stages Pipeline Tracker -->
                        <div class="grid grid-cols-4 gap-1.5 pt-0.5 text-[10px] font-mono text-center">
                            <div class="py-1 px-1 rounded-lg border transition truncate"
                                 :class="(botProgress.percent || 0) >= 2 && (botProgress.percent || 0) <= 75 ? 'bg-emerald-950/90 border-emerald-500 text-emerald-300 font-bold shadow-xs' : ((botProgress.percent || 0) > 75 ? 'bg-slate-900 border-slate-800 text-slate-500' : 'bg-slate-900/50 border-slate-800 text-slate-600')">
                                1. Atlas Live
                            </div>
                            <div class="py-1 px-1 rounded-lg border transition truncate"
                                 :class="(botProgress.percent || 0) > 75 && (botProgress.percent || 0) <= 88 ? 'bg-emerald-950/90 border-emerald-500 text-emerald-300 font-bold shadow-xs' : ((botProgress.percent || 0) > 88 ? 'bg-slate-900 border-slate-800 text-slate-500' : 'bg-slate-900/50 border-slate-800 text-slate-600')">
                                2. Web MySQL
                            </div>
                            <div class="py-1 px-1 rounded-lg border transition truncate"
                                 :class="(botProgress.percent || 0) > 88 && (botProgress.percent || 0) < 100 ? 'bg-emerald-950/90 border-emerald-500 text-emerald-300 font-bold shadow-xs' : ((botProgress.percent || 0) >= 100 ? 'bg-slate-900 border-slate-800 text-slate-500' : 'bg-slate-900/50 border-slate-800 text-slate-600')">
                                3. Database Sync
                            </div>
                            <div class="py-1 px-1 rounded-lg border transition truncate"
                                 :class="botProgress.stage === 'error' ? 'bg-rose-950/90 border-rose-500 text-rose-300 font-bold shadow-xs' : ((botProgress.percent || 0) >= 100 ? 'bg-emerald-950/90 border-emerald-500 text-emerald-300 font-bold shadow-xs' : 'bg-slate-900/50 border-slate-800 text-slate-600')">
                                <span x-text="botProgress.stage === 'error' ? '4. Gagal ✕' : '4. Selesai ✓'"></span>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- STAGE 2: LIVE TERMINAL CONSOLE LOG STREAM -->
            <div class="p-4 flex-1 overflow-y-auto font-mono text-xs text-emerald-400 bg-slate-950 whitespace-pre-wrap leading-relaxed select-text min-h-[160px] max-h-60"
                 x-ref="consoleLogBox">
                <template x-if="botProgress.recent_logs && botProgress.recent_logs.length">
                    <div>
                        <template x-for="(log, lIdx) in botProgress.recent_logs" :key="lIdx">
                            <div class="hover:bg-slate-900/50 px-1 rounded py-0.5" x-text="log"></div>
                        </template>
                    </div>
                </template>
                <template x-if="!botProgress.recent_logs || !botProgress.recent_logs.length">
                    <span x-text="consoleOutput || 'Belum ada rekaman log terminal. Klik tombol Bot AIKu Studio untuk menjalankan sinkronisasi.'"></span>
                </template>
            </div>

            <!-- Footer: Controls & Background minimize button -->
            <div class="px-5 py-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400 font-mono flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="(isSyncing || botProgress.isRunning) ? 'bg-emerald-400 animate-ping' : 'bg-slate-600'"></span>
                    <span x-text="(isSyncing || botProgress.isRunning) ? 'Sedang streaming...' : 'Bot idle'"></span>
                </div>
                
                <div class="flex items-center gap-3">
                    <button @click="copyConsoleLog()" class="hover:text-emerald-400 flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span>Salin Log</span>
                    </button>
                    
                    <button @click="fetchAutoSyncStatus()" class="hover:text-emerald-400 flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Segarkan</span>
                    </button>

                    <button @click="showConsoleModal = false" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold px-3 py-1 rounded-xl transition">
                        Tutup ke Latar Belakang
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- APPLICATION JAVASCRIPT LOGIC -->
    <script>
        function modernRecordingsApp() {
            return {
                sidebarOpen: (window.innerWidth < 768 ? false : (localStorage.getItem('aiku_sidebar_open') !== null ? localStorage.getItem('aiku_sidebar_open') === 'true' : true)),
                studioEmails: [
                    "hajisukur@kamerakitaid.site", "hajiwhite@kamerakitaid.site", "hajiputih@kamerakitaid.site",
                    "hajiputih4@kamerakitaid.site", "hajiwati@kamerakitaid.site", "hajiblack@kamerakitaid.site",
                    "hajihirang@kamerakitaid.site", "hajihirang4@kamerakitaid.site", "hajiplus@kamerakitaid.site",
                    "hajjpinkst@kamerakitaid.site", "hajipinkst2@kamerakitaid.site", "hajipinkst4@kamerakitaid.site",
                    "hajigufron@kamerakitaid.site", "hajibiru@kamerakitaid.site", "hajiblue@kamerakitaid.site",
                    "hajibiru4@kamerakitaid.site", "hajimail@kamerakitaid.site", "hajipink@kamerakitaid.site",
                    "hajipink2@kamerakitaid.site", "hajipink4@kamerakitaid.site", "hajiipin@kamerakitaid.site",
                    "hajihijau@kamerakitaid.site", "hajigreen@kamerakitaid.site", "hajihijau4@kamerakitaid.site",
                    "hajinuga@kamerakitaid.site", "stmariam2001@kamerakitaid.site", "hajimariyam@kamerakitaid.site",
                    "hajinuga2@kamerakitaid.site", "hajizikri@kamerakitaid.site"
                ],
                quickWorkers: [
                    { name: 'Aufa Anugraha', short: 'Aufa', initials: 'AU' },
                    { name: 'Muhamad Zikri Fadhilah', short: 'Zikri', initials: 'ZK' },
                    { name: 'Ghina Mawaddah', short: 'Ghina', initials: 'GH' },
                    { name: 'Muhammad Rio Hadi', short: 'Rio', initials: 'RO' },
                    { name: 'Rahmatullah', short: 'Rahmat', initials: 'RH' },
                    { name: 'Muhammad Diyoga', short: 'Yoga', initials: 'YG' },
                    { name: 'Martina Martha Tilopha', short: 'Bu Tina', initials: 'BT' },
                    { name: 'Fahri', short: 'Fahri', initials: 'FH' },
                    { name: 'Aida Fitri', short: 'Aida', initials: 'AD' }
                ],
                contributors: [],
                recordings: [],
                selectedIds: [],
                lastSelectedIndex: null,
                activePopover: null,
                isLoading: false,
                isSyncing: false,
                syncPollingTimer: null,
                lastSyncedAt: null,
                showAutoSyncModal: false,
                showConsoleModal: false,
                consoleOutput: '',
                botProgress: {
                    isRunning: false,
                    stage: 'idle',
                    currentAccount: '',
                    currentIndex: 0,
                    totalAccounts: 29,
                    percent: 0,
                    message: 'Bot siap dijalankan kapan saja.',
                    recordsCount: 0,
                    recent_logs: [],
                    updatedAt: null
                },
                accountsList: [],
                showAccountsModal: false,
                isBatchAccountMode: false,
                newAccountInput: '',
                batchAccountInput: '',
                modalAccountSearch: '',
                autoSync: {
                    enabled: false,
                    intervalMinutes: 15,
                    lastRun: null,
                    nextRun: null
                },
                filters: {
                    email: 'all',
                    worker: 'all',
                    status: 'all',
                    search: '',
                    datePreset: 'all',
                    startDate: '',
                    endDate: ''
                },
                pagination: {
                    page: 1,
                    limit: 50,
                    total: 0,
                    totalPages: 1
                },
                stats: {
                    totalRecords: 0,
                    totalMinutes: 0,
                    billableMinutes: 0,
                    rejectedMinutes: 0,
                    pendingMinutes: 0,
                    assignedCount: 0,
                    unassignedCount: 0,
                    paidCount: 0,
                    belumCount: 0,
                    belumRekapCount: 0
                },

                get isAllSelected() {
                    return this.recordings.length > 0 && this.recordings.every(r => this.isRowSelected(r.record_id));
                },

                get selectedStats() {
                    const ids = this.selectedIds || [];
                    if (!ids.length) {
                        return {
                            count: 0,
                            approvedMin: 0,
                            approvedHours: '0.00',
                            rejectedMin: 0,
                            rejectedHours: '0.00',
                            pendingMin: 0,
                            pendingHours: '0.00',
                            totalMin: 0,
                            totalHours: '0.00'
                        };
                    }

                    const idSet = new Set(ids.map(String));
                    let appr = 0;
                    let rej = 0;
                    let pend = 0;
                    let total = 0;

                    (this.recordings || []).forEach(r => {
                        if (idSet.has(String(r.record_id))) {
                            const b = parseFloat(r.billable_min) || 0;
                            const rj = parseFloat(r.rejected_min) || 0;
                            const p = parseFloat(this.getPendingMin(r)) || 0;
                            const t = parseFloat(r.duration_min) || (b + rj + p);

                            appr += b;
                            rej += rj;
                            pend += p;
                            total += t;
                        }
                    });

                    return {
                        count: ids.length,
                        approvedMin: appr,
                        approvedHours: (appr / 60).toFixed(2),
                        rejectedMin: rej,
                        rejectedHours: (rej / 60).toFixed(2),
                        pendingMin: pend,
                        pendingHours: (pend / 60).toFixed(2),
                        totalMin: total,
                        totalHours: (total / 60).toFixed(2)
                    };
                },

                get formattedLastUpdate() {
                    if (!this.lastSyncedAt) return 'Belum Sinkron';
                    try {
                        const d = new Date(this.lastSyncedAt);
                        if (isNaN(d.getTime())) return '-';

                        const timeStr = new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d);

                        const dateStr = new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            day: 'numeric',
                            month: 'short'
                        }).format(d);

                        const nowWita = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
                        const dWita = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(d);

                        if (nowWita === dWita) {
                            return `Hari Ini, ${timeStr} WITA`;
                        } else {
                            return `${dateStr}, ${timeStr} WITA`;
                        }
                    } catch (e) {
                        return '-';
                    }
                },

                get formattedLastUpdateFull() {
                    if (!this.lastSyncedAt) return 'Belum ada riwayat sinkronisasi';
                    try {
                        const d = new Date(this.lastSyncedAt);
                        if (isNaN(d.getTime())) return '-';
                        return new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: false
                        }).format(d) + ' WITA';
                    } catch (e) {
                        return '-';
                    }
                },

                isRowSelected(recordId) {
                    const target = String(recordId);
                    return this.selectedIds.some(id => String(id) === target);
                },

                copyConsoleLog() {
                    let text = '';
                    if (this.botProgress.recent_logs && this.botProgress.recent_logs.length) {
                        text = this.botProgress.recent_logs.join('\n');
                    } else {
                        text = this.consoleOutput || '';
                    }
                    if (navigator.clipboard && text) {
                        navigator.clipboard.writeText(text);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Tersalin!',
                                text: 'Log terminal bot berhasil disalin ke clipboard.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    }
                },

                async fetchAutoSyncStatus() {
                    try {
                        const res = await fetch('/api/studio-recordings/sync-status');
                        if (res.ok) {
                            const data = await res.json();
                            const wasRunning = this.isSyncing;
                            this.isSyncing = Boolean(data.isRunning);
                            if (data.lastSyncedAt) this.lastSyncedAt = data.lastSyncedAt;
                            if (data.autoSync) this.autoSync = data.autoSync;
                            if (data.progress) {
                                this.botProgress = { ...this.botProgress, ...data.progress };
                                if (data.progress.recent_logs && data.progress.recent_logs.length) {
                                    this.consoleOutput = data.progress.recent_logs.join('\n');
                                }
                            }
                            if (data.liveOutput) {
                                this.consoleOutput = data.liveOutput;
                            } else if (data.lastResult && data.lastResult.output && !this.consoleOutput) {
                                this.consoleOutput = data.lastResult.output;
                            }

                            // If bot is active (via .bat or schedule), automatically start smooth polling!
                            if (!wasRunning && data.isRunning) {
                                this.startLiveSyncPolling();
                            }
                        }
                    } catch (e) {
                        console.error('Error fetching sync status:', e);
                    }
                },

                startLiveSyncPolling() {
                    if (this.syncPollingTimer) clearInterval(this.syncPollingTimer);
                    this.syncPollingTimer = setInterval(async () => {
                        try {
                            const res = await fetch('/api/studio-recordings/sync-status');
                            const data = await res.json();
                            this.isSyncing = Boolean(data.isRunning);
                            if (data.lastSyncedAt) this.lastSyncedAt = data.lastSyncedAt;
                            if (data.progress) {
                                this.botProgress = { ...this.botProgress, ...data.progress };
                                if (data.progress.recent_logs && data.progress.recent_logs.length) {
                                    this.consoleOutput = data.progress.recent_logs.join('\n');
                                }
                            }
                            if (data.liveOutput) {
                                this.consoleOutput = data.liveOutput;
                            }

                            this.$nextTick(() => {
                                const box = this.$refs.consoleLogBox;
                                if (box) box.scrollTop = box.scrollHeight;
                            });

                            if (!data.isRunning) {
                                clearInterval(this.syncPollingTimer);
                                this.syncPollingTimer = null;
                                this.applyFilters(this.pagination.page);

                                const isError = (data.progress && data.progress.stage === 'error') || 
                                                (data.lastResult && data.lastResult.exitCode !== 0 && data.lastResult.exitCode !== null);

                                if (isError) {
                                    const errMsg = data.progress?.message || data.lastResult?.error || 'Sinkronisasi bot terhenti atau mengalami kegagalan.';
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Sinkronisasi Gagal!',
                                            text: errMsg,
                                            confirmButtonColor: '#ef4444'
                                        });
                                    }
                                } else {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Sinkronisasi Selesai!',
                                            text: 'Seluruh data rekaman studio terbaru telah tersinkronkan ke database web.',
                                            timer: 2500,
                                            showConfirmButton: false
                                        });
                                    }
                                }
                            }
                        } catch (e) {
                            clearInterval(this.syncPollingTimer);
                            this.syncPollingTimer = null;
                        }
                    }, 1200);
                },

                async toggleAutoSync() {
                    try {
                        const res = await fetch('/api/studio-recordings/auto-sync-toggle', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ enabled: !this.autoSync.enabled, intervalMinutes: this.autoSync.intervalMinutes })
                        });
                        const data = await res.json();
                        if (data.autoSync) {
                            this.autoSync = data.autoSync;
                            Swal.fire({
                                icon: this.autoSync.enabled ? 'success' : 'info',
                                title: this.autoSync.enabled ? 'Auto-Sync Diaktifkan' : 'Auto-Sync Dinonaktifkan',
                                text: this.autoSync.enabled ? `Bot akan otomatis menarik data setiap ${this.autoSync.intervalMinutes} menit.` : 'Bot hanya berjalan ketika tombol diklik manual.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    } catch (e) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: e.message });
                    }
                },

                async changeAutoSyncInterval(mins) {
                    try {
                        const res = await fetch('/api/studio-recordings/auto-sync-toggle', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ enabled: true, intervalMinutes: mins })
                        });
                        const data = await res.json();
                        if (data.autoSync) {
                            this.autoSync = data.autoSync;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async openConsoleModal() {
                    await this.fetchAutoSyncStatus();
                    this.showConsoleModal = true;
                },

                formatTimeOnly(dtStr) {
                    if (!dtStr) return '-';
                    try {
                        const d = new Date(dtStr);
                        return new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d) + ' WITA';
                    } catch (e) {
                        return '-';
                    }
                },

                async initApp() {
                    // Watch sidebarOpen in recordingsApp
                    this.$watch('sidebarOpen', val => { if (window.innerWidth >= 768) { try { localStorage.setItem('aiku_sidebar_open', String(val)); } catch(e) {} } });
                    await this.fetchContributors();
                    this.fetchStudioAccounts();
                    await this.fetchRecordings(1);
                    await this.fetchAutoSyncStatus();
                    setInterval(() => {
                        this.fetchAutoSyncStatus();
                    }, 10000);
                },

                async fetchContributors() {
                    try {
                        const res = await fetch('/api/contributors');
                        if (res.ok) {
                            const data = await res.json();
                            const rawList = Array.isArray(data) ? data : (data.contributors || []);
                            this.contributors = rawList.map(c => ({
                                ...c,
                                id: c.id || c.contributor_id,
                                contributor_id: c.contributor_id || c.id,
                                name: c.name || c.nama || '',
                                nama: c.nama || c.name || ''
                            }));
                        }
                    } catch (e) {
                        console.error('Error fetching contributors:', e);
                    }
                },

                get allAssignableWorkers() {
                    const list = [];
                    const seen = new Set();
                    
                    if (this.contributors && this.contributors.length > 0) {
                        this.contributors.forEach(c => {
                            const name = (c.nama || c.name || '').trim();
                            if (name && !seen.has(name.toLowerCase())) {
                                seen.add(name.toLowerCase());
                                list.push({
                                    name: name,
                                    team: c.hp || (c.shift ? `Shift ${c.shift}` : 'Kontributor Studio')
                                });
                            }
                        });
                    }
                    
                    if (this.quickWorkers && this.quickWorkers.length > 0) {
                        this.quickWorkers.forEach(w => {
                            const name = (w.name || '').trim();
                            if (name && !seen.has(name.toLowerCase())) {
                                seen.add(name.toLowerCase());
                                list.push({
                                    name: name,
                                    team: 'Kontributor Studio'
                                });
                            }
                        });
                    }
                    
                    return list.sort((a, b) => a.name.localeCompare(b.name));
                },

                filteredAssignableWorkers(query) {
                    const list = this.allAssignableWorkers;
                    if (!query || !query.trim()) return list;
                    const q = query.trim().toLowerCase();
                    return list.filter(w => 
                        (w.name && w.name.toLowerCase().includes(q)) || 
                        (w.team && w.team.toLowerCase().includes(q))
                    );
                },

                getAccountName(email) {
                    if (!email) return '-';
                    const cleanEmail = String(email).trim().toLowerCase();
                    const found = (this.accountsList || []).find(a => a.email && a.email.toLowerCase() === cleanEmail);
                    if (found && found.name) {
                        return found.name;
                    }
                    return cleanEmail.split('@')[0];
                },

                async fetchStudioAccounts() {
                    try {
                        const res = await fetch('/api/studio-accounts');
                        if (res.ok) {
                            const data = await res.json();
                            this.accountsList = data.accounts || [];
                            const activeEmails = this.accountsList.filter(a => a.active).map(a => a.email);
                            if (activeEmails.length > 0) {
                                this.studioEmails = activeEmails;
                            }
                        }
                    } catch (e) {
                        console.error('Error fetching studio accounts:', e);
                    }
                },

                openAccountsModal() {
                    this.showAccountsModal = true;
                    this.newAccountInput = '';
                    this.batchAccountInput = '';
                    this.modalAccountSearch = '';
                    this.fetchStudioAccounts();
                },

                get filteredModalAccounts() {
                    if (!this.modalAccountSearch || !this.modalAccountSearch.trim()) {
                        return this.accountsList;
                    }
                    const q = this.modalAccountSearch.trim().toLowerCase();
                    return this.accountsList.filter(a => 
                        a.email.toLowerCase().includes(q) || 
                        a.name.toLowerCase().includes(q)
                    );
                },

                async addNewStudioAccount(isBatch = false) {
                    let payload = {};
                    if (isBatch) {
                        if (!this.batchAccountInput.trim()) return;
                        payload = { raw_text: this.batchAccountInput.trim() };
                    } else {
                        if (!this.newAccountInput.trim()) return;
                        payload = { email: this.newAccountInput.trim() };
                    }

                    try {
                        const res = await fetch('/api/studio-accounts', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil Disimpan',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                            this.newAccountInput = '';
                            this.batchAccountInput = '';
                            this.isBatchAccountMode = false;
                            await this.fetchStudioAccounts();
                        } else {
                            alert('Gagal menambah akun: ' + (data.error || 'Terjadi kesalahan'));
                        }
                    } catch (e) {
                        console.error('Error saving account:', e);
                        alert('Gagal menghubungi server');
                    }
                },

                async deleteStudioAccount(email) {
                    const accName = email.split('@')[0];
                    if (typeof Swal !== 'undefined') {
                        const confirm = await Swal.fire({
                            title: 'Hapus Akun Studio?',
                            text: `Apakah Anda yakin ingin menghapus akun "${accName}" (${email}) dari daftar pantauan bot?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Ya, Hapus!',
                            cancelButtonText: 'Batal'
                        });
                        if (!confirm.isConfirmed) return;
                    }

                    try {
                        const res = await fetch(`/api/studio-accounts/${encodeURIComponent(email)}`, {
                            method: 'DELETE'
                        });
                        const data = await res.json();
                        if (data.success) {
                            if (this.filters.email === email) {
                                this.filters.email = 'all';
                                this.applyFilters(1);
                            }
                            await this.fetchStudioAccounts();
                        } else {
                            alert('Gagal menghapus akun: ' + (data.error || 'Terjadi kesalahan'));
                        }
                    } catch (e) {
                        console.error('Error deleting account:', e);
                    }
                },

                async toggleStudioAccount(email) {
                    try {
                        const res = await fetch(`/api/studio-accounts/${encodeURIComponent(email)}/toggle`, {
                            method: 'PUT'
                        });
                        const data = await res.json();
                        if (data.success) {
                            await this.fetchStudioAccounts();
                        }
                    } catch (e) {
                        console.error('Error toggling account:', e);
                    }
                },

                filteredStudioEmails(query) {
                    if (!query || !query.trim()) return this.studioEmails;
                    const q = query.trim().toLowerCase();
                    return this.studioEmails.filter(email => email.toLowerCase().includes(q));
                },

                async fetchRecordings(page = 1) {
                    this.isLoading = true;
                    this.pagination.page = page;
                    try {
                        const params = new URLSearchParams({
                            page: this.pagination.page,
                            limit: this.pagination.limit,
                            email: this.filters.email,
                            worker: this.filters.worker,
                            status: this.filters.status,
                            search: this.filters.search
                        });
                        if (this.filters.startDate) params.append('startDate', this.filters.startDate);
                        if (this.filters.endDate) params.append('endDate', this.filters.endDate);

                        const res = await fetch(`/api/studio-recordings?${params.toString()}`);
                        if (res.ok) {
                            const data = await res.json();
                            this.recordings = data.recordings || [];
                            this.pagination.total = data.total || 0;
                            this.pagination.totalPages = data.totalPages || 1;
                            if (data.lastSyncedAt) {
                                this.lastSyncedAt = data.lastSyncedAt;
                            }
                            if (data.stats) {
                                this.stats = { ...this.stats, ...data.stats };
                            }
                        }
                    } catch (e) {
                        console.error('Error fetching recordings:', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                applyFilters(page = 1) {
                    this.selectedIds = [];
                    this.fetchRecordings(page);
                },

                resetFilters() {
                    this.filters = {
                        email: 'all',
                        worker: 'all',
                        status: 'all',
                        search: '',
                        datePreset: 'all',
                        startDate: '',
                        endDate: ''
                    };
                    this.applyFilters(1);
                },

                applyDatePreset() {
                    const now = new Date();
                    const witaString = new Intl.DateTimeFormat('en-CA', {
                        timeZone: 'Asia/Makassar',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    }).format(now);

                    const toYMD = (d) => {
                        const year = d.getFullYear();
                        const month = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    const today = new Date(witaString);

                    if (this.filters.datePreset === 'all') {
                        this.filters.startDate = '';
                        this.filters.endDate = '';
                    } else if (this.filters.datePreset === 'today') {
                        this.filters.startDate = toYMD(today);
                        this.filters.endDate = toYMD(today);
                    } else if (this.filters.datePreset === 'yesterday') {
                        const y = new Date(today);
                        y.setDate(y.getDate() - 1);
                        this.filters.startDate = toYMD(y);
                        this.filters.endDate = toYMD(y);
                    } else if (this.filters.datePreset === 'last7') {
                        const past = new Date(today);
                        past.setDate(past.getDate() - 6);
                        this.filters.startDate = toYMD(past);
                        this.filters.endDate = toYMD(today);
                    } else if (this.filters.datePreset === 'last30') {
                        const past = new Date(today);
                        past.setDate(past.getDate() - 29);
                        this.filters.startDate = toYMD(past);
                        this.filters.endDate = toYMD(today);
                    } else if (this.filters.datePreset === 'thisMonth') {
                        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                        this.filters.startDate = toYMD(firstDay);
                        this.filters.endDate = toYMD(today);
                    } else if (this.filters.datePreset === 'custom') {
                        return;
                    }
                    this.applyFilters(1);
                },

                toggleSelectAll() {
                    if (this.isAllSelected) {
                        const pageIds = new Set(this.recordings.map(r => String(r.record_id)));
                        this.selectedIds = this.selectedIds.filter(id => !pageIds.has(String(id)));
                    } else {
                        const set = new Set([...this.selectedIds.map(String), ...this.recordings.map(r => String(r.record_id))]);
                        this.selectedIds = Array.from(set);
                    }
                },

                toggleRowSelection(recordId, index, event) {
                    const target = String(recordId);
                    if (event && event.shiftKey && this.lastSelectedIndex !== null) {
                        const start = Math.min(this.lastSelectedIndex, index);
                        const end = Math.max(this.lastSelectedIndex, index);
                        const rangeIds = this.recordings.slice(start, end + 1).map(r => String(r.record_id));
                        const set = new Set([...this.selectedIds.map(String), ...rangeIds]);
                        this.selectedIds = Array.from(set);
                    } else {
                        if (this.isRowSelected(recordId)) {
                            this.selectedIds = this.selectedIds.map(String).filter(id => id !== target);
                        } else {
                            this.selectedIds = [...this.selectedIds.map(String), target];
                        }
                        this.lastSelectedIndex = index;
                    }
                },

                handleRowClick(event, recordId, index) {
                    this.toggleRowSelection(recordId, index, event);
                },

                async applyFastAssign(workerName) {
                    if (!this.selectedIds.length) return;
                    try {
                        const contributor = this.contributors.find(c => (c.name || c.nama || "").toLowerCase() === workerName.toLowerCase());
                        const res = await fetch('/api/studio-recordings/batch-assign', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                record_ids: this.selectedIds,
                                worker_name: workerName,
                                contributor_id: contributor ? contributor.contributor_id : null
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.recordings.forEach(r => {
                                if (this.selectedIds.includes(r.record_id)) {
                                    r.assigned_worker_name = workerName;
                                    r.assigned_contributor_id = contributor ? contributor.contributor_id : null;
                                }
                            });
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Ditugaskan',
                                text: `${this.selectedIds.length} rekaman ditugaskan ke ${workerName}`,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            this.selectedIds = [];
                            this.refreshStatsOnly();
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: err.message });
                    }
                },

                async applyFastStatus(status) {
                    if (!this.selectedIds.length) return;
                    try {
                        const res = await fetch('/api/studio-recordings/batch-assign', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                record_ids: this.selectedIds,
                                payment_status: status
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.recordings.forEach(r => {
                                if (this.selectedIds.includes(r.record_id)) {
                                    r.manual_payment_status = status;
                                }
                            });
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Diperbarui',
                                text: `${this.selectedIds.length} rekaman diubah statusnya menjadi ${status}`,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            this.selectedIds = [];
                            this.refreshStatsOnly();
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: err.message });
                    }
                },

                async assignWorker(recordId, workerName) {
                    try {
                        const contributor = workerName ? this.contributors.find(c => c.name.toLowerCase() === workerName.toLowerCase()) : null;
                        const res = await fetch('/api/studio-recordings/batch-assign', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                record_ids: [recordId],
                                worker_name: workerName || '',
                                contributor_id: contributor ? contributor.contributor_id : null
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            const rec = this.recordings.find(r => r.record_id === recordId);
                            if (rec) {
                                rec.assigned_worker_name = workerName;
                                rec.assigned_contributor_id = contributor ? contributor.contributor_id : null;
                            }
                            this.refreshStatsOnly();
                        }
                    } catch (err) {
                        console.error('Error assigning worker:', err);
                    }
                },

                async cyclePaymentStatus(recordId, currentStatus) {
                    let nextStatus = 'Paid';
                    if (currentStatus === 'Paid') nextStatus = 'Belum Rekap';
                    else if (currentStatus === 'Belum Rekap') nextStatus = 'Paid';
                    else nextStatus = 'Paid';

                    try {
                        const res = await fetch('/api/studio-recordings/batch-assign', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                record_ids: [recordId],
                                payment_status: nextStatus
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            const rec = this.recordings.find(r => r.record_id === recordId);
                            if (rec) {
                                rec.manual_payment_status = nextStatus;
                            }
                            this.refreshStatsOnly();
                        }
                    } catch (err) {
                        console.error('Error cycling payment status:', err);
                    }
                },

                async openQuickAssignAccountModal() {
                    const accName = this.filters.email.split('@')[0];
                    const { value: workerName } = await Swal.fire({
                        title: `Tandai Akun ${accName}`,
                        text: `Pilih kontributor untuk SEMUA rekaman kosong di akun ${this.filters.email}:`,
                        input: 'select',
                        inputOptions: this.contributors.reduce((acc, c) => {
                            acc[c.name] = c.name;
                            return acc;
                        }, {}),
                        inputPlaceholder: '-- Pilih Pekerja --',
                        showCancelButton: true,
                        confirmButtonText: 'Terapkan ke Akun Ini',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#00B090'
                    });

                    if (workerName) {
                        this.executeQuickAssignAccount(workerName);
                    }
                },

                async executeQuickAssignAccount(workerName) {
                    try {
                        Swal.fire({
                            title: 'Menerapkan...',
                            text: 'Menandai seluruh rekaman akun ini...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const contributor = this.contributors.find(c => (c.name || c.nama || "").toLowerCase() === workerName.toLowerCase());
                        const res = await fetch(`/api/studio-recordings?email=${encodeURIComponent(this.filters.email)}&worker=unassigned&limit=1000`);
                        const data = await res.json();
                        const unassignedIds = (data.recordings || []).map(r => r.record_id);

                        if (!unassignedIds.length) {
                            Swal.fire({ icon: 'info', title: 'Selesai', text: 'Tidak ada rekaman kosong di akun ini.' });
                            return;
                        }

                        const updateRes = await fetch('/api/studio-recordings/batch-assign', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                record_ids: unassignedIds,
                                worker_name: workerName,
                                contributor_id: contributor ? contributor.contributor_id : null
                            })
                        });
                        const updateData = await updateRes.json();

                        if (updateData.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: `${updateData.updated} rekaman berhasil ditugaskan ke ${workerName}.`
                            });
                            this.applyFilters(1);
                        }
                    } catch (e) {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: e.message });
                    }
                },

                async refreshStatsOnly() {
                    try {
                        const params = new URLSearchParams({ email: this.filters.email, limit: 1 });
                        const res = await fetch(`/api/studio-recordings?${params.toString()}`);
                        if (res.ok) {
                            const data = await res.json();
                            if (data.stats) this.stats = { ...this.stats, ...data.stats };
                        }
                    } catch (e) {}
                },

                async triggerSyncAtlas() {
                    this.isSyncing = true;
                    this.botProgress = {
                        isRunning: true,
                        stage: 'starting',
                        currentAccount: 'Memulai proses...',
                        currentIndex: 0,
                        totalAccounts: this.studioEmails.length,
                        percent: 2,
                        message: 'Memulai bot AIKu Studio...',
                        recordsCount: 0,
                        recent_logs: ['[00:00:00] Memulai bot AIKu Studio...']
                    };
                    // Open the Walking Robot progress modal so user sees it right away!
                    this.showConsoleModal = true;

                    try {
                        const res = await fetch('/api/studio-recordings/sync-now', { method: 'POST' });
                        const data = await res.json();
                        this.startLiveSyncPolling();
                    } catch (err) {
                        this.isSyncing = false;
                        this.botProgress.isRunning = false;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Gagal Menjalankan Bot', text: err.message });
                        }
                    }
                },

                
                getQCStatus(r) {
                    if (!r) return 'pending';
                    const b = parseFloat(r.billable_min) || 0;
                    const rej = parseFloat(r.rejected_min) || 0;
                    if (b > 0) return 'approved';
                    if (rej > 0) return 'rejected';
                    return 'pending';
                },

                getQCLabel(r) {
                    const st = this.getQCStatus(r);
                    if (st === 'approved') return 'Accepted';
                    if (st === 'rejected') return 'Canceled';
                    return 'Pending';
                },

                formatProfileDate(dtStr) {
                    if (!dtStr) return '-';
                    try {
                        const d = new Date(dtStr);
                        const now = new Date();
                        
                        const timeStr = new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d);

                        const dateWita = new Intl.DateTimeFormat('en-CA', {
                            timeZone: 'Asia/Makassar',
                            year: 'numeric', month: '2-digit', day: '2-digit'
                        }).format(d);

                        const todayWita = new Intl.DateTimeFormat('en-CA', {
                            timeZone: 'Asia/Makassar',
                            year: 'numeric', month: '2-digit', day: '2-digit'
                        }).format(now);

                        const yDate = new Date(now);
                        yDate.setDate(yDate.getDate() - 1);
                        const yesterdayWita = new Intl.DateTimeFormat('en-CA', {
                            timeZone: 'Asia/Makassar',
                            year: 'numeric', month: '2-digit', day: '2-digit'
                        }).format(yDate);

                        if (dateWita === todayWita) {
                            return 'Today at ' + timeStr + ' WITA';
                        } else if (dateWita === yesterdayWita) {
                            return 'Yesterday at ' + timeStr + ' WITA';
                        } else {
                            const dateStr = new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Makassar',
                                day: 'numeric', month: 'short'
                            }).format(d);
                            return dateStr + ' at ' + timeStr + ' WITA';
                        }
                    } catch (e) {
                        return dtStr;
                    }
                },

                
                togglePopover(id) {
                    this.activePopover = this.activePopover === id ? null : id;
                },

                getPendingMin(rec) {
                    if (!rec) return 0;
                    if (rec.pending_min && rec.pending_min > 0) return rec.pending_min;
                    if (rec.processing_status === 'processing') return rec.duration_min || 0;
                    if ((!rec.billable_min || rec.billable_min === 0) && (!rec.rejected_min || rec.rejected_min === 0) && (rec.duration_min || 0) > 0) {
                        return rec.duration_min || 0;
                    }
                    return 0;
                },

                formatWita(recorded_at, recorded_utc) {
                    let d = null;
                    if (recorded_at) {
                        let s = String(recorded_at).trim();
                        if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(\.\d+)?$/.test(s)) {
                            s = s.replace(' ', 'T') + 'Z';
                        }
                        const parsed = new Date(s);
                        if (!isNaN(parsed.getTime())) {
                            d = parsed;
                        }
                    }
                    if ((!d || isNaN(d.getTime())) && recorded_utc) {
                        let s = String(recorded_utc).trim();
                        if (s.endsWith(' WITA')) {
                            s = s.replace(/ WITA$/, ' +0800');
                        } else if (s.endsWith(' UTC')) {
                            s = s.replace(/ UTC$/, ' +0000');
                        }
                        const parsed = new Date(s);
                        if (!isNaN(parsed.getTime())) {
                            d = parsed;
                        }
                    }
                    if (!d || isNaN(d.getTime())) {
                        return recorded_utc || recorded_at || '-';
                    }
                    try {
                        const fmt = new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d);
                        return fmt.replace('.', ':') + ' WITA';
                    } catch (e) {
                        return recorded_utc || recorded_at || '-';
                    }
                },

                getWorkerShortName(name) {
                    if (!name) return 'Belum Ditandai';
                    const parts = name.split(' ');
                    if (parts[0].toLowerCase() === 'martina') return 'Bu Tina';
                    if (parts[0].toLowerCase() === 'muhamad' && parts[1]) return parts[1];
                    if (parts[0].toLowerCase() === 'muhammad' && parts[1]) return parts[1];
                    return parts[0];
                },

                getWorkerInitials(name) {
                    if (!name) return '?';
                    const parts = name.split(' ');
                    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
                    return name.substring(0, 2).toUpperCase();
                },

                getWorkerStyle(name) {
                    const n = (name || '').toLowerCase();
                    if (n.includes('aufa')) return 'bg-violet-50 text-violet-700 border-violet-200';
                    if (n.includes('zikri')) return 'bg-blue-50 text-blue-700 border-blue-200';
                    if (n.includes('ghina')) return 'bg-rose-50 text-rose-700 border-rose-200';
                    if (n.includes('rio')) return 'bg-cyan-50 text-cyan-700 border-cyan-200';
                    if (n.includes('rahmat')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (n.includes('yoga') || n.includes('diyoga')) return 'bg-amber-50 text-amber-700 border-amber-200';
                    if (n.includes('tina') || n.includes('tilopha')) return 'bg-purple-50 text-purple-700 border-purple-200';
                    if (n.includes('fahri')) return 'bg-indigo-50 text-indigo-700 border-indigo-200';
                    if (n.includes('aida')) return 'bg-pink-50 text-pink-700 border-pink-200';
                    if (n.includes('maryam') || n.includes('mariyam')) return 'bg-teal-50 text-teal-700 border-teal-200';
                    return 'bg-slate-100 text-slate-700 border-slate-200';
                },

                getWorkerAvatarStyle(name) {
                    const n = (name || '').toLowerCase();
                    if (n.includes('aufa')) return 'bg-violet-200 text-violet-800';
                    if (n.includes('zikri')) return 'bg-blue-200 text-blue-800';
                    if (n.includes('ghina')) return 'bg-rose-200 text-rose-800';
                    if (n.includes('rio')) return 'bg-cyan-200 text-cyan-800';
                    if (n.includes('rahmat')) return 'bg-emerald-200 text-emerald-800';
                    if (n.includes('yoga') || n.includes('diyoga')) return 'bg-amber-200 text-amber-800';
                    if (n.includes('tina') || n.includes('tilopha')) return 'bg-purple-200 text-purple-800';
                    if (n.includes('fahri')) return 'bg-indigo-200 text-indigo-800';
                    if (n.includes('aida')) return 'bg-pink-200 text-pink-800';
                    if (n.includes('maryam') || n.includes('mariyam')) return 'bg-teal-200 text-teal-800';
                    return 'bg-slate-200 text-slate-700';
                },

                formatWITA(dateStr) {
                    if (!dateStr) return '-';
                    try {
                        const d = new Date(dateStr);
                        return new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d) + ' WITA';
                    } catch (e) {
                        return dateStr;
                    }
                },

                formatDuration(minutes) {
                    if (!minutes || isNaN(minutes)) return '0m';
                    const num = parseFloat(minutes);
                    const hours = Math.floor(num / 60);
                    const mins = Math.round(num % 60);
                    if (hours > 0) {
                        return `${hours}h ${mins}m`;
                    }
                    return `${mins}m`;
                }

            }
        }
    </script>
</body>
</html>
