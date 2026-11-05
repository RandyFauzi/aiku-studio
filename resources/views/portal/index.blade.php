<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Kontributor — AIKu Studio</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-[#F8FAFC] font-sans text-slate-800 antialiased selection:bg-emerald-100 selection:text-emerald-900 relative" x-data="attendancePortalData()">

    <!-- LIGHT GREEN AMBIENT TOP GRADIENT (Clean Light Green Gradient Palette) -->
    <div class="fixed top-0 inset-x-0 h-[420px] bg-gradient-to-b from-emerald-100/60 via-teal-50/30 to-transparent pointer-events-none -z-10"></div>
    <div class="fixed top-0 right-0 w-80 h-80 bg-emerald-200/20 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <!-- TOP APP HEADER -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/60 shadow-2xs">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            
            <!-- MOBILE TOP HEADER (Screen 1 & 2 Style) -->
            <div class="flex items-center justify-between w-full md:hidden">
                <!-- User Avatar & Greeting -->
                <div class="flex items-center gap-3">
                    <button type="button" @click="setTab('akun')" class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-black flex items-center justify-center text-sm shadow-xs shrink-0 ring-2 ring-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </button>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 leading-none">Selamat Datang,</p>
                        <h2 class="text-sm font-extrabold text-slate-900 leading-tight mt-0.5 truncate max-w-[150px]">{{ auth()->user()->name }}</h2>
                    </div>
                </div>

                <!-- Circular Action Buttons (Matching Dribbble Screen 1) -->
                <div class="flex items-center gap-2">
                    <!-- Quick Timer Button (Mobile Header) -->
                    <button type="button" 
                            @click="openTimerModal()" 
                            class="h-10 px-2.5 rounded-full border transition flex items-center gap-1.5 shadow-2xs"
                            :class="timer.isRunning 
                                ? 'bg-emerald-500 text-slate-950 border-emerald-400 font-black shadow-emerald-500/20' 
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            title="Timer Rekaman">
                        <svg class="w-4 h-4" :class="timer.isRunning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-mono font-bold" x-show="timer.isRunning" x-text="timerFormatted"></span>
                    </button>

                    <!-- Quick Scan Barcode Button (+) -->
                    <button type="button" 
                            @click="openScanner()" 
                            :disabled="checking || loading || !!todayAttendance"
                            class="w-10 h-10 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-xs hover:scale-105 active:scale-95 transition disabled:opacity-50"
                            title="Scan Barcode WiFi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>

                    <!-- Account Button -->
                    <button type="button" 
                            @click="setTab('akun')" 
                            class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-700 flex items-center justify-center shadow-2xs hover:bg-slate-50 active:scale-95 transition"
                            title="Profil">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </button>
                </div>
            </div>

            <!-- DESKTOP TOP HEADER (hidden md:flex) -->
            <div class="hidden md:flex items-center justify-between w-full">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <a href="/me" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-2xl bg-[#1C1C1E] text-white flex items-center justify-center font-black text-sm shadow-xs group-hover:scale-105 transition">
                            AI
                        </div>
                        <div>
                            <span class="font-black text-slate-900 text-lg tracking-tight">AIKu Studio</span>
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-700">Contributor Portal</span>
                        </div>
                    </a>
                </div>

                <!-- DESKTOP MODERN SEGMENTED NAVIGATION TABS -->
                <nav class="flex items-center gap-1 p-1 rounded-2xl bg-slate-100 border border-slate-200/80 shadow-inner">
                    <!-- Tab 1: Absensi -->
                    <button type="button" 
                            @click="setTab('absensi')" 
                            :class="activeTab === 'absensi' ? 'bg-[#1C1C1E] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="relative min-h-[38px] px-3.5 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-1.5">
                        <svg class="w-4 h-4" :class="activeTab === 'absensi' ? 'text-white' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Utama</span>
                        <template x-if="!todayAttendance">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        </template>
                    </button>

                    <!-- Tab 2: Laporan -->
                    <button type="button" 
                            @click="setTab('laporan')" 
                            :class="activeTab === 'laporan' ? 'bg-[#18181B] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="relative min-h-[38px] px-3.5 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-1.5">
                        <svg class="w-4 h-4" :class="activeTab === 'laporan' ? 'text-white' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Laporan</span>
                        <template x-if="todayReportSubmitted === false">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        </template>
                    </button>

                    <!-- Tab 3: Jadwal -->
                    <button type="button" 
                            @click="setTab('jadwal')" 
                            :class="activeTab === 'jadwal' ? 'bg-[#18181B] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="min-h-[38px] px-3.5 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-1.5">
                        <svg class="w-4 h-4" :class="activeTab === 'jadwal' ? 'text-white' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Jadwal</span>
                    </button>

                    <!-- Tab 4: Slip Gaji -->
                    <button type="button" 
                            @click="setTab('slip')" 
                            :class="activeTab === 'slip' ? 'bg-[#18181B] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="relative min-h-[38px] px-3.5 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-1.5">
                        <svg class="w-4 h-4" :class="activeTab === 'slip' ? 'text-white' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Slip Gaji</span>
                        <template x-if="slips.length > 0">
                            <span class="text-[10px] font-black px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800" x-text="slips.length"></span>
                        </template>
                    </button>

                    <!-- Tab 5: Profil -->
                    <button type="button" 
                            @click="setTab('akun')" 
                            :class="activeTab === 'akun' ? 'bg-[#18181B] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="min-h-[38px] px-3.5 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-1.5">
                        <svg class="w-4 h-4" :class="activeTab === 'akun' ? 'text-white' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Profil</span>
                    </button>
                </nav>

                <!-- Desktop User & Logout -->
                <div class="flex items-center gap-3">
                    <!-- Desktop Timer Button -->
                    <button type="button" 
                            @click="openTimerModal()" 
                            class="min-h-[38px] px-3.5 py-1.5 rounded-2xl border transition flex items-center gap-2 shadow-2xs text-xs font-bold"
                            :class="timer.isRunning 
                                ? 'bg-emerald-500 text-slate-950 border-emerald-400 font-black shadow-sm shadow-emerald-500/20' 
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                        <svg class="w-4 h-4" :class="timer.isRunning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="timer.isRunning ? ('Sesi: ' + timerFormatted) : 'Timer Rekaman'"></span>
                    </button>

                    <div class="text-right">
                        <p class="text-xs font-extrabold text-slate-800 leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] font-medium text-slate-400 leading-tight">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="post" action="/logout">
                        @csrf
                        <button class="min-h-[38px] rounded-2xl border border-slate-200 px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </header>

    <!-- MAIN BODY CONTAINER -->
    <main x-cloak class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-7 pb-36 md:pb-16 space-y-6">

        <!-- GLOBAL ERROR ALERT -->
        <div x-show="error" 
             x-transition 
             class="rounded-2xl border border-red-200 bg-red-50 p-3.5 text-xs font-semibold text-red-700 flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-red-500 font-bold">✕</span>
                <span x-text="error"></span>
            </div>
            <button type="button" @click="error = ''" class="text-red-400 hover:text-red-700 text-xs font-bold">Tutup</button>
        </div>

        <!-- ACTIVE TIMER FLOATING MINI-BANNER (When on other tabs) -->
        <div x-show="timer.isRunning && activeTab !== 'absensi'" 
             x-transition 
             class="rounded-2xl border border-emerald-400/40 bg-[#161618] text-white p-3.5 flex items-center justify-between gap-3 shadow-xl">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-xs font-bold text-slate-300">Timer Sesi Rekaman:</span>
                <span class="font-mono font-black text-sm text-emerald-400 tracking-wider" x-text="timerFormatted"></span>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" @click="toggleTimer()" class="px-2.5 py-1 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-white transition" x-text="timer.isRunning ? 'Jeda' : 'Lanjut'"></button>
                <button type="button" @click="openTimerModal()" class="px-3 py-1 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-400 text-slate-950 text-xs font-black transition hover:brightness-105 shadow-2xs">Buka Timer ↗</button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: ABSENSI & HOME (DRIBBBLE SCREEN 1) -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'absensi'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-5">

            <!-- Bold Catchy Hero Headline (Matching "Let's Make Today Productive") -->
            <div class="pt-1">
                <h1 class="text-[28px] sm:text-[34px] font-black text-slate-900 tracking-tight leading-[1.15]">
                    Maksimalkan<br><span class="text-slate-400">Sesi Kerjamu</span>
                </h1>
            </div>

            <!-- DARK CONTRAST HERO CARD ("TODAY'S PROGRESS" - EXACT SCREEN 1 MATCH) -->
            <section class="rounded-[32px] bg-[#161618] text-white p-5 sm:p-7 shadow-2xl relative overflow-hidden">
                <!-- Top Row: Card Title & Date Pill -->
                <div class="flex items-center justify-between gap-3 pb-4 border-b border-white/10">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400">Today's Progress</span>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-white/10 text-slate-200 border border-white/10" x-text="dateLabel(today)"></span>
                </div>

                <!-- Middle Row: Circular Ring Gauge & Stats Column (Exact Screen 1 proportions) -->
                <div class="py-5 flex items-center justify-between gap-6 sm:gap-10">
                    
                    <!-- Circular SVG Ring Gauge (Screen 1 Style) -->
                    <div class="relative w-24 h-24 sm:w-28 sm:h-28 shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-white/10" stroke-width="3.5" stroke="currentColor" fill="none"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path :class="todayAttendance ? 'text-emerald-400' : 'text-amber-400'" 
                                  stroke-dasharray="100, 100" 
                                  :stroke-dashoffset="todayAttendance ? 0 : 75" 
                                  stroke-linecap="round" 
                                  stroke-width="3.5" 
                                  stroke="currentColor" 
                                  fill="none"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <template x-if="todayAttendance">
                                <div>
                                    <span class="text-xl sm:text-2xl font-black text-emerald-400 block leading-none">100%</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mt-0.5">Hadir</span>
                                </div>
                            </template>
                            <template x-if="!todayAttendance">
                                <div>
                                    <span class="text-lg sm:text-xl font-black text-amber-400 block leading-none">0%</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mt-0.5">Absen</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 3 Stacked Metric Rows (Matching Screen 1 layout exactly) -->
                    <div class="flex-1 space-y-3">
                        <!-- Stat 1: Total Sesi Hari Ini -->
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-slate-300 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-white leading-tight font-mono" x-text="todaySchedule.sessions ? todaySchedule.sessions.length : 0"></p>
                                <p class="text-[10px] font-semibold text-slate-400 leading-tight">Total Sesi</p>
                            </div>
                        </div>

                        <!-- Stat 2: Kehadiran -->
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black leading-tight" :class="todayAttendance ? 'text-emerald-400' : 'text-amber-400'" x-text="todayAttendance ? '1 / 1' : '0 / 1'"></p>
                                <p class="text-[10px] font-semibold text-slate-400 leading-tight">Presensi Hadir</p>
                            </div>
                        </div>

                        <!-- Stat 3: Laporan HP -->
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-slate-300 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black leading-tight" :class="todayReportSubmitted ? 'text-emerald-400' : 'text-slate-300'" x-text="todayReportSubmitted ? 'Terkirim' : 'Pending'"></p>
                                <p class="text-[10px] font-semibold text-slate-400 leading-tight">Laporan Harian</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Row: Direct Action Buttons (Light Green Gradient Accent) -->
                <div class="pt-4 border-t border-white/10 flex flex-wrap items-center gap-2.5">
                    <!-- Scan Barcode Button -->
                    <button type="button" 
                            @click="openScanner()" 
                            :disabled="checking || loading || !!todayAttendance" 
                            class="flex-1 min-h-[48px] rounded-2xl bg-gradient-to-r from-emerald-400 via-teal-400 to-emerald-500 hover:brightness-105 active:scale-98 px-4 py-2.5 text-xs font-black text-slate-950 shadow-md shadow-emerald-950/20 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span x-text="todayAttendance ? 'Presensi Masuk Berhasil' : 'Scan Barcode WiFi'"></span>
                    </button>

                    <!-- GPS Button -->
                    <button type="button" 
                            @click="checkIn()" 
                            :disabled="checking || loading || !!todayAttendance" 
                            class="min-h-[48px] rounded-2xl bg-white/10 hover:bg-white/20 active:scale-98 px-4 py-2.5 text-xs font-bold text-white transition border border-white/10 flex items-center justify-center gap-1.5 disabled:opacity-50">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="checking ? 'Membaca…' : 'GPS'"></span>
                    </button>
                </div>
                    <button type="button" 
                            @click="checkIn()" 
                            :disabled="checking || loading || !!todayAttendance" 
                            class="min-h-[48px] rounded-2xl bg-white/10 hover:bg-white/20 active:scale-98 px-4 py-2.5 text-xs font-bold text-white transition border border-white/10 flex items-center justify-center gap-1.5 disabled:opacity-50">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="checking ? 'Membaca…' : 'GPS'"></span>
                    </button>
                </div>

                <!-- Minimalist Verification Strip (if attended) -->
                <template x-if="todayAttendance">
                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-300">
                        <div class="flex items-center gap-1.5">
                            <span class="text-emerald-400 font-bold">✓ Jam Masuk:</span>
                            <span class="font-mono text-white font-bold" x-text="time(todayAttendance.checked_in_at)"></span>
                            <span class="text-slate-400 ml-1" x-text="todayAttendance.checkin_method === 'qr_wifi' ? '(WiFi Studio)' : '(GPS)'"></span>
                        </div>
                        <template x-if="todayAttendance.latitude && Number(todayAttendance.latitude) !== 0">
                            <a :href="mapUrl(todayAttendance)" target="_blank" rel="noopener noreferrer" class="font-bold text-emerald-400 hover:underline flex items-center gap-0.5">
                                <span>Peta</span>
                                <span>↗</span>
                            </a>
                        </template>
                    </div>
                </template>
            </section>

            <!-- ========================================================= -->
            <!-- FOCUS & RECORDING TIMER CARD (MATCHING USER REFERENCE)    -->
            <!-- ========================================================= -->
            <section class="rounded-[32px] bg-white p-6 sm:p-7 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
                <!-- Top Card Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Focus</h3>
                            <span class="w-2.5 h-2.5 rounded-full" :class="timer.isRunning ? 'bg-emerald-500 animate-ping' : 'bg-slate-300'"></span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Waktu rekaman teratur. Hasil maksimal.</p>
                    </div>

                    <!-- Header Quick Controls (Reset & Test Audio) -->
                    <div class="flex items-center gap-1.5">
                        <!-- Test Audio Button -->
                        <button type="button" 
                                @click="testAlarmSound()" 
                                class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60 shadow-2xs"
                                title="Tes Bunyi Notifikasi">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                        </button>
                        <!-- Reset Button -->
                        <button type="button" 
                                @click="resetTimer()" 
                                class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60 shadow-2xs"
                                title="Reset Timer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Circular Progress Ring (Exact Match with Reference Image) -->
                <div class="py-2 flex flex-col items-center justify-center">
                    <div class="relative w-56 h-56 sm:w-64 sm:h-64 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <!-- Background ring track (light mint pastel) -->
                            <path class="text-emerald-100" stroke-width="2.6" stroke="currentColor" fill="none"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <!-- Active progress ring (vibrant green) -->
                            <path class="text-emerald-500 transition-all duration-300" 
                                  stroke-dasharray="100, 100" 
                                  :stroke-dashoffset="timerStrokeDashoffset" 
                                  stroke-linecap="round" 
                                  stroke-width="2.6" 
                                  stroke="currentColor" 
                                  fill="none"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>

                        <!-- Center Info: Focus Time, Large Countdown, and Play/Pause Button -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-1">Focus Time</span>
                            <span class="text-4xl sm:text-5xl font-black font-mono text-slate-900 tracking-tight block select-none" x-text="timerFormatted"></span>

                            <!-- Big Circular Black Play/Pause Button (Matching Reference) -->
                            <div class="mt-3.5">
                                <button type="button" 
                                        @click="toggleTimer()" 
                                        class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition"
                                        :title="timer.isRunning ? 'Jeda Timer' : 'Mulai Timer'">
                                    <!-- Play Icon -->
                                    <svg x-show="!timer.isRunning" class="w-5 h-5 sm:w-6 sm:h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    <!-- Pause Icon -->
                                    <svg x-show="timer.isRunning" class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Presets & Manual Duration Controls (User Explicit Requirement: 15-20-30 & Manual) -->
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Pilihan Durasi Sesi</span>
                        <span class="text-xs font-bold text-slate-600" x-text="timer.durationMinutes + ' Menit'"></span>
                    </div>

                    <!-- Preset Chips Row (15, 20, 30) -->
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" 
                                @click="setTimerPreset(15)"
                                class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                                :class="timer.selectedPreset === 15 
                                    ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                            15 Menit
                        </button>
                        <button type="button" 
                                @click="setTimerPreset(20)"
                                class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                                :class="timer.selectedPreset === 20 
                                    ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                            20 Menit
                        </button>
                        <button type="button" 
                                @click="setTimerPreset(30)"
                                class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                                :class="timer.selectedPreset === 30 
                                    ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                            30 Menit
                        </button>
                    </div>

                    <!-- Manual Duration Setting -->
                    <div class="flex items-center gap-2 pt-1">
                        <div class="relative flex-1">
                            <input type="number" 
                                   min="1" 
                                   max="720" 
                                   x-model="timer.customMinutesInput" 
                                   placeholder="Atur menit manual (cth: 25, 45, 60)..." 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <button type="button" 
                                @click="setTimerManual(timer.customMinutesInput)" 
                                :disabled="!timer.customMinutesInput || timer.customMinutesInput <= 0" 
                                class="px-4 py-2 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100 text-xs font-black transition disabled:opacity-40">
                            Terapkan
                        </button>
                    </div>
                </div>

                <!-- Today's Focus Routine (Matching Bottom List in Reference Image) -->
                <div class="space-y-2.5 pt-2">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 block">Today's Focus</span>
                    
                    <div class="space-y-2">
                        <!-- Routine 1 -->
                        <div @click="setTimerPreset(20)" 
                             class="p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer shadow-2xs hover:border-emerald-300"
                             :class="timer.selectedPreset === 20 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-white border-slate-100'">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                </div>
                                <span class="text-xs font-black text-slate-900">Rekaman Sesi Utama</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-slate-400">20 min</span>
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                                      :class="timer.selectedPreset === 20 ? 'bg-emerald-600 text-white' : 'border border-slate-300'">
                                    <svg x-show="timer.selectedPreset === 20" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </div>
                        </div>

                        <!-- Routine 2 -->
                        <div @click="setTimerPreset(5)" 
                             class="p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer shadow-2xs hover:border-emerald-300"
                             :class="timer.selectedPreset === 5 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-white border-slate-100'">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                </div>
                                <span class="text-xs font-black text-slate-900">Istirahat Pendek (Short Break)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-slate-400">5 min</span>
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                                      :class="timer.selectedPreset === 5 ? 'bg-emerald-600 text-white' : 'border border-slate-300'">
                                    <svg x-show="timer.selectedPreset === 5" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </div>
                        </div>

                        <!-- Routine 3 -->
                        <div @click="setTimerPreset(15)" 
                             class="p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer shadow-2xs hover:border-emerald-300"
                             :class="timer.selectedPreset === 15 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-white border-slate-100'">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="text-xs font-black text-slate-900">Review & Kirim Laporan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-slate-400">15 min</span>
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                                      :class="timer.selectedPreset === 15 ? 'bg-emerald-600 text-white' : 'border border-slate-300'">
                                    <svg x-show="timer.selectedPreset === 15" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TODAY'S TASKS / SESSIONS SECTION (MATCHING SCREEN 1 & 2) -->
            <section class="space-y-3.5">
                <!-- Section Header with View All -->
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Jadwal Sesi Hari Ini</h3>
                    <button type="button" @click="setTab('jadwal')" class="text-xs font-bold text-slate-400 hover:text-slate-800 transition">
                        Lihat Semua ↗
                    </button>
                </div>

                <!-- Horizontal Filter Pills (Matching Screen 1) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <div class="rounded-full bg-white border border-slate-200/80 px-3 py-1 text-xs font-extrabold text-slate-800 flex items-center gap-1.5 shadow-2xs shrink-0">
                        <span class="w-4 h-4 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center text-[10px]" x-text="todaySchedule.sessions ? todaySchedule.sessions.length : 0"></span>
                        <span>Sesi Hari Ini</span>
                    </div>
                    <div class="rounded-full bg-emerald-50 border border-emerald-200/80 px-3 py-1 text-xs font-extrabold text-emerald-800 flex items-center gap-1.5 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full" :class="todayAttendance ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                        <span x-text="todayAttendance ? 'Presensi Masuk' : 'Belum Presensi'"></span>
                    </div>
                    <div class="rounded-full bg-white border border-slate-200/80 px-3 py-1 text-xs font-extrabold text-slate-600 flex items-center gap-1.5 shrink-0">
                        <span>WiFi Studio</span>
                    </div>
                </div>

                <!-- Task Cards List (Screen 1 "Design Landing Page" Card Style) -->
                <div class="space-y-3">
                    <template x-if="todaySchedule.sessions && todaySchedule.sessions.length > 0">
                        <div class="space-y-3">
                            <template x-for="session in todaySchedule.sessions" :key="session.id">
                                <div class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.02)] hover:border-emerald-300 transition flex flex-col gap-3">
                                    <!-- Top Row: Priority Pill & Angled Arrow Circle (Matching Screen 1) -->
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>High Priority</span>
                                        </span>
                                        <button type="button" @click="setTab('jadwal')" class="w-7 h-7 rounded-full bg-slate-50 border border-slate-100 hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-900 transition" title="Lihat Sesi">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 17L17 7m0 0H7m10 0v10"/></svg>
                                        </button>
                                    </div>

                                    <!-- Middle: Task Title -->
                                    <div>
                                        <h4 class="text-base font-black text-slate-900" x-text="'Sesi ' + session.id + ' — Rekaman Studio'"></h4>
                                    </div>

                                    <!-- Bottom: Time & Avatars Stack (Matching Screen 1 Bottom Row) -->
                                    <div class="pt-1 flex items-center justify-between text-xs text-slate-500">
                                        <div class="flex items-center gap-1.5 font-mono font-bold text-slate-600">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="session.start + ' – ' + session.end + ' WITA'"></span>
                                        </div>
                                        
                                        <!-- Avatars Stack -->
                                        <div class="flex items-center -space-x-1.5">
                                            <div class="w-6 h-6 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-[8px] font-black text-white shadow-2xs" x-text="session.tool ? session.tool.substring(0,2).toUpperCase() : 'HP'"></div>
                                            <div class="w-6 h-6 rounded-full bg-teal-500 border-2 border-white flex items-center justify-center text-[8px] font-black text-white shadow-2xs">ST</div>
                                            <div class="w-6 h-6 rounded-full bg-[#1C1C1E] border-2 border-white flex items-center justify-center text-[7px] font-black text-white shadow-2xs">+2</div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!todaySchedule.sessions || todaySchedule.sessions.length === 0">
                        <div class="p-8 text-center rounded-[28px] bg-white border border-slate-100 shadow-2xs">
                            <p class="text-sm font-black text-slate-800">Tidak ada jadwal sesi rekaman hari ini.</p>
                            <p class="text-xs text-slate-400 mt-1">Hari ini libur atau belum ada shift terdaftar.</p>
                        </div>
                    </template>
                </div>
            </section>

            <!-- SHORTCUT CARD: LAPORAN KERJA HARIAN -->
            <section class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs flex items-center justify-between gap-3 cursor-pointer hover:border-emerald-300 transition" @click="setTab('laporan')">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-800 font-bold flex items-center justify-center shrink-0 border border-emerald-100">
                        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-900">Laporan Kerja Harian</h4>
                        <p class="text-[11px] font-medium text-slate-400 mt-0.5">Input durasi menit per HP yang digunakan</p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-extrabold text-emerald-700">Isi Sekarang</span>
                    <span class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 text-xs font-bold">→</span>
                </div>
            </section>

        </div>

        <!-- ========================================== -->
        <!-- TAB 2: LAPORAN KERJA (INPUT MENIT HP)     -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'laporan'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-5">
            @include('work-reports.form')
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: KALENDER JADWAL (SCREEN 2 MATCH)   -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'jadwal'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-5">

            <!-- Title & Month Switcher -->
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900">Jadwal Shift</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Kalender sesi kerja bulanan</p>
                </div>
                <input type="month" x-model="month" @change="load()" :disabled="loading" class="h-9 rounded-2xl border border-slate-200 px-3 text-xs font-bold text-slate-800 bg-white shadow-2xs">
            </div>

            <!-- HORIZONTAL 7-DAY STRIP (EXACT SCREEN 2 LAYOUT) -->
            <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
                <template x-for="d in weekDays" :key="d.date">
                    <button type="button" 
                            @click="selectedDate = d.date"
                            class="rounded-2xl p-2.5 flex flex-col items-center justify-between gap-2 border transition shadow-2xs"
                            :class="selectedDate === d.date 
                                ? 'bg-white border-slate-900 ring-2 ring-slate-900/10' 
                                : 'bg-white border-slate-100 hover:border-slate-300'">
                        <span class="text-[11px] font-extrabold uppercase" 
                              :class="selectedDate === d.date ? 'text-slate-900' : 'text-slate-400'" 
                              x-text="d.name"></span>
                        
                        <!-- Date Number (Solid black circle when selected, exactly like Screen 2) -->
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black transition"
                              :class="selectedDate === d.date 
                                  ? 'bg-[#1C1C1E] text-white shadow-xs' 
                                  : (d.isToday ? 'bg-emerald-100 text-emerald-800' : 'text-slate-800')"
                              x-text="d.dayNum"></span>

                        <span class="w-1.5 h-1.5 rounded-full" :class="d.hasSession ? 'bg-emerald-500' : 'bg-transparent'"></span>
                    </button>
                </template>
            </div>

            <!-- 2-Column Responsive Layout for Calendar & Day Details -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                
                <!-- Left: Full Monthly Calendar Grid -->
                <div class="lg:col-span-7">
                    <div class="overflow-hidden rounded-[28px] border border-slate-100 bg-white shadow-xs">
                        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400">Kalender Bulanan</span>
                            <span x-show="loading" class="text-xs text-slate-400">Memuat…</span>
                        </div>

                        <!-- Day Names -->
                        <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-100 text-center">
                            <template x-for="day in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']">
                                <div class="py-2.5 text-center text-xs font-bold text-slate-500" x-text="day"></div>
                            </template>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7">
                            <template x-for="cell in calendarCells" :key="cell.key">
                                <div class="min-w-0 border-t border-r border-slate-100 relative">
                                    <button type="button" 
                                            x-show="cell.date" 
                                            @click="selectedDate=cell.date" 
                                            :aria-pressed="selectedDate===cell.date" 
                                            :class="selectedDate===cell.date ? 'bg-[#1C1C1E] text-white shadow-sm' : 'hover:bg-slate-50'" 
                                            class="min-h-[72px] w-full p-2 text-left sm:min-h-[84px] sm:p-2.5 transition rounded-xl">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold" 
                                                  :class="cell.date === today && selectedDate !== cell.date ? 'w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px]' : ''"
                                                  x-text="Number(cell.date?.slice(-2))"></span>
                                            <span x-show="cell.attendance" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        </div>

                                        <span class="mt-1.5 block text-[9px] sm:text-[10px] leading-tight font-semibold" 
                                              :class="selectedDate === cell.date ? 'text-slate-300' : (cell.attendance ? 'text-emerald-700' : (cell.sessions?.length ? 'text-slate-700' : 'text-slate-400'))" 
                                              x-text="cell.attendance ? '✓ Masuk' : (cell.sessions?.length ? cell.sessions.length + ' sesi' : 'Libur')"></span>
                                    </button>
                                    <span x-show="!cell.date" class="block min-h-[72px] bg-slate-50/40 sm:min-h-[84px]"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right: Selected Date Session Timeline (Screen 2 Timeline Style) -->
                <div class="lg:col-span-5 space-y-3">
                    <div class="rounded-[28px] border border-slate-100 bg-white p-5 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400">Detail Sesi</span>
                            <span x-show="selectedDate === today" class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">Hari Ini</span>
                        </div>

                        <h3 class="text-base font-black text-slate-900" x-text="dateLabel(selectedDate)"></h3>

                        <!-- Sessions List -->
                        <div class="space-y-2.5">
                            <template x-for="session in selectedDay.sessions" :key="session.id">
                                <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="text-center w-12 shrink-0">
                                            <span class="text-xs font-black text-slate-900 block font-mono" x-text="session.start"></span>
                                            <span class="text-[10px] font-bold text-slate-400 block font-mono" x-text="session.end"></span>
                                        </div>
                                        <div class="h-6 w-px bg-slate-200"></div>
                                        <div>
                                            <p class="font-black text-xs text-slate-900" x-text="'Sesi ' + session.id"></p>
                                            <p class="text-[11px] text-emerald-700 font-bold" x-text="session.tool"></p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-700 bg-slate-200/80 px-2 py-0.5 rounded-lg">Terjadwal</span>
                                </div>
                            </template>

                            <p x-show="!selectedDay.sessions.length" class="py-6 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl">
                                Tidak ada jadwal sesi kerja pada tanggal ini.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: SLIP GAJI                          -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'slip'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-5">

            <!-- Subheader -->
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900">Slip Gaji</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Estimasi berjalan & arsip dokumen resmi</p>
                </div>
                <button type="button" @click="loadSlips()" :disabled="loadingSlips" class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    Segarkan
                </button>
            </div>

            <!-- LIVE CYCLE ESTIMATION CARD (Dark Hero Card) -->
            <section class="rounded-[32px] bg-[#1C1C1E] text-white p-5 sm:p-7 shadow-xl space-y-4">
                <div class="flex items-center justify-between gap-2 border-b border-white/10 pb-3">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">Periode Berjalan (Cut-Off 22 - 21)</span>
                    <span class="text-xs font-bold text-slate-300" x-text="currentPeriod?.name || 'Siklus Ini'"></span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Estimasi Take Home Pay</span>
                        <span class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono tracking-tight" x-text="formatRupiah(liveSlip?.total_thp || 0)"></span>
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-300">
                            <span>Approved: <strong class="text-white" x-text="(liveSlip?.total_approved_hours || 0) + ' Jam'"></strong></span>
                            <span>&bull;</span>
                            <span>Logged: <strong class="text-white" x-text="(liveSlip?.total_logged_hours || 0) + ' Jam'"></strong></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="openLiveSlip()" :disabled="!liveSlip" class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-black text-xs shadow-sm transition">
                            Buka Slip Live ↗
                        </button>
                        <button type="button" @click="downloadLiveSlipPdf()" :disabled="!liveSlip || downloadingSlipId === 'live'" class="px-3.5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition border border-white/10">
                            <span x-text="downloadingSlipId === 'live' ? 'PDF…' : 'PDF'"></span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- OFFICIAL SLIPS ARCHIVE -->
            <section class="space-y-3">
                <h3 class="text-sm font-black text-slate-900">Arsip Slip Resmi</h3>

                <div class="space-y-2.5">
                    <template x-for="slip in slips" :key="slip.id">
                        <div class="rounded-[24px] bg-white p-4 sm:p-5 border border-slate-100 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-emerald-200 transition">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-black text-slate-900" x-text="slip.period"></h4>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black"
                                          :class="slip.status === 'sudah' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                          x-text="slip.status === 'sudah' ? '✓ Sudah Ditransfer' : 'Menunggu Cair'">
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                    <span>Approved: <strong class="text-slate-800" x-text="slip.total_approved_hours + 'h'"></strong></span>
                                    <span class="text-emerald-700 font-bold" x-text="formatRupiah(slip.total_thp)"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click="openSlip(slip.id)" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition">
                                    Buka Slip ↗
                                </button>
                                <button type="button" @click="downloadSlipPdf(slip)" :disabled="downloadingSlipId === slip.id" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition">
                                    <span x-text="downloadingSlipId === slip.id ? '…' : 'PDF'"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="!loadingSlips && slips.length === 0">
                        <div class="p-8 text-center rounded-[28px] bg-white border border-slate-100 shadow-2xs">
                            <p class="text-xs font-extrabold text-slate-700">Belum ada slip resmi yang diterbitkan.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Slip resmi diterbitkan setiap tanggal 22 setelah cut-off.</p>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <!-- ========================================== -->
        <!-- TAB 5: AKUN & PROFIL                       -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'akun'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-5">

            <!-- Profile Info Card -->
            <section class="rounded-[32px] bg-white p-6 border border-slate-100 shadow-2xs flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-black text-xl flex items-center justify-center shadow-xs shrink-0 ring-4 ring-emerald-50">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">{{ auth()->user()->name }}</h3>
                    <p class="text-xs text-slate-400 font-medium">{{ auth()->user()->email }}</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 mt-1">
                        Kontributor Aktif
                    </span>
                </div>
            </section>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kehadiran Bulan Ini</span>
                    <span class="text-2xl font-black text-slate-900 font-mono block mt-1" x-text="totalMonthAttendances + ' Hari'"></span>
                </div>
                <div class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs cursor-pointer hover:border-emerald-300 transition" @click="setTab('slip')">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Arsip Slip Gaji</span>
                    <span class="text-2xl font-black text-emerald-700 font-mono block mt-1" x-text="slips.length + ' Dokumen'"></span>
                </div>
            </div>

            <!-- Logout Button -->
            <form method="post" action="/logout">
                @csrf
                <button type="submit" class="w-full py-4 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition border border-rose-200/60 shadow-2xs flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar dari Akun</span>
                </button>
            </form>
        </div>

    </main>

    <!-- ========================================================= -->
    <!-- FLOATING DOCK BOTTOM NAVIGATION BAR (MATCHING DRIBBBLE)   -->
    <!-- ========================================================= -->
    <nav class="md:hidden fixed bottom-5 inset-x-4 max-w-sm sm:max-w-md mx-auto z-40 bg-white/95 backdrop-blur-xl border border-slate-200/80 shadow-[0_16px_36px_rgba(0,0,0,0.12)] rounded-[32px] px-3 py-2 flex items-center justify-between">
        
        <!-- Tab 1: Utama / Home -->
        <button type="button" 
                @click="setTab('absensi')" 
                class="transition-all duration-200 flex flex-col items-center"
                :class="activeTab === 'absensi' 
                    ? 'bg-[#1C1C1E] text-white rounded-2xl px-3.5 py-1.5 shadow-sm scale-102' 
                    : 'text-slate-400 hover:text-slate-700 py-1.5 px-2'">
            <svg class="w-5 h-5" :class="activeTab === 'absensi' ? 'text-white' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] leading-none mt-0.5" :class="activeTab === 'absensi' ? 'font-bold text-white' : 'font-medium'">Home</span>
        </button>

        <!-- Tab 2: Laporan / Projects -->
        <button type="button" 
                @click="setTab('laporan')" 
                class="transition-all duration-200 flex flex-col items-center"
                :class="activeTab === 'laporan' 
                    ? 'bg-[#1C1C1E] text-white rounded-2xl px-3.5 py-1.5 shadow-sm scale-102' 
                    : 'text-slate-400 hover:text-slate-700 py-1.5 px-2'">
            <svg class="w-5 h-5" :class="activeTab === 'laporan' ? 'text-white' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-[10px] leading-none mt-0.5" :class="activeTab === 'laporan' ? 'font-bold text-white' : 'font-medium'">Laporan</span>
        </button>

        <!-- Tab 3: Jadwal / Calendar -->
        <button type="button" 
                @click="setTab('jadwal')" 
                class="transition-all duration-200 flex flex-col items-center"
                :class="activeTab === 'jadwal' 
                    ? 'bg-[#1C1C1E] text-white rounded-2xl px-3.5 py-1.5 shadow-sm scale-102' 
                    : 'text-slate-400 hover:text-slate-700 py-1.5 px-2'">
            <svg class="w-5 h-5" :class="activeTab === 'jadwal' ? 'text-white' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-[10px] leading-none mt-0.5" :class="activeTab === 'jadwal' ? 'font-bold text-white' : 'font-medium'">Jadwal</span>
        </button>

        <!-- Tab 4: Slip Gaji -->
        <button type="button" 
                @click="setTab('slip')" 
                class="transition-all duration-200 flex flex-col items-center"
                :class="activeTab === 'slip' 
                    ? 'bg-[#1C1C1E] text-white rounded-2xl px-3.5 py-1.5 shadow-sm scale-102' 
                    : 'text-slate-400 hover:text-slate-700 py-1.5 px-2'">
            <svg class="w-5 h-5" :class="activeTab === 'slip' ? 'text-white' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="text-[10px] leading-none mt-0.5" :class="activeTab === 'slip' ? 'font-bold text-white' : 'font-medium'">Slip</span>
        </button>

        <!-- Tab 5: Profil -->
        <button type="button" 
                @click="setTab('akun')" 
                class="transition-all duration-200 flex flex-col items-center"
                :class="activeTab === 'akun' 
                    ? 'bg-[#1C1C1E] text-white rounded-2xl px-3.5 py-1.5 shadow-sm scale-102' 
                    : 'text-slate-400 hover:text-slate-700 py-1.5 px-2'">
            <svg class="w-5 h-5" :class="activeTab === 'akun' ? 'text-white' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-[10px] leading-none mt-0.5" :class="activeTab === 'akun' ? 'font-bold text-white' : 'font-medium'">Profil</span>
        </button>

    </nav>

    <!-- CAMERA SCANNER MODAL FOR CONTRIBUTOR -->
    <div x-show="scannerOpen" x-cloak 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs"
         @keydown.escape.window="closeScanner()">
        
        <div class="bg-white rounded-[32px] border border-slate-200 w-full max-w-sm overflow-hidden shadow-2xl flex flex-col"
             @click.outside="closeScanner()">
            
            <div class="px-5 py-4 bg-[#1C1C1E] text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <h3 class="font-bold text-sm">Scan Barcode Studio</h3>
                </div>
                <button type="button" @click="closeScanner()" class="w-8 h-8 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center transition">
                    ✕
                </button>
            </div>

            <div class="p-4 bg-slate-950 flex flex-col items-center justify-center relative min-h-[280px]">
                <div id="portal-qr-reader" class="w-full max-w-[260px] rounded-2xl overflow-hidden bg-black border border-slate-800"></div>
                <div x-show="scannerStarting" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 text-slate-300 text-xs">
                    <div class="animate-spin w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full mb-3"></div>
                    <p>Mengaktifkan kamera HP…</p>
                </div>
            </div>

            <div class="p-5 text-center space-y-3 bg-white">
                <div>
                    <p class="text-xs font-bold text-slate-800">Arahkan kamera ke Poster Barcode di Studio</p>
                    <p class="text-[11px] text-slate-500 mt-1">Pastikan HP terhubung ke <strong>WiFi Kantor</strong>.</p>
                </div>
                <button type="button" @click="closeScanner()" class="w-full py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 transition">
                    Tutup Kamera
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TIMER FOCUS MODAL (Accessible from any tab via Header)    -->
    <!-- ========================================================= -->
    <div x-show="timer.modalOpen" x-cloak 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @keydown.escape.window="closeTimerModal()">
        
        <div class="bg-white rounded-[32px] border border-slate-200 w-full max-w-sm sm:max-w-md overflow-hidden shadow-2xl p-6 sm:p-7 space-y-5"
             @click.outside="closeTimerModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Focus Timer</h3>
                        <span class="w-2.5 h-2.5 rounded-full" :class="timer.isRunning ? 'bg-emerald-500 animate-ping' : 'bg-slate-300'"></span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Waktu rekaman teratur. Hasil maksimal.</p>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" 
                            @click="testAlarmSound()" 
                            class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60"
                            title="Tes Bunyi Notifikasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                    </button>
                    <button type="button" 
                            @click="closeTimerModal()" 
                            class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition text-xs font-bold">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Circular Progress Ring (Exact Match with Reference Image) -->
            <div class="py-2 flex flex-col items-center justify-center">
                <div class="relative w-56 h-56 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        <!-- Background ring track (light mint pastel) -->
                        <path class="text-emerald-100" stroke-width="2.6" stroke="currentColor" fill="none"
                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <!-- Active progress ring (vibrant green) -->
                        <path class="text-emerald-500 transition-all duration-300" 
                              stroke-dasharray="100, 100" 
                              :stroke-dashoffset="timerStrokeDashoffset" 
                              stroke-linecap="round" 
                              stroke-width="2.6" 
                              stroke="currentColor" 
                              fill="none"
                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>

                    <!-- Center Info: Focus Time, Large Countdown, and Play/Pause Button -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-1">Focus Time</span>
                        <span class="text-4xl font-black font-mono text-slate-900 tracking-tight block select-none" x-text="timerFormatted"></span>

                        <!-- Big Circular Black Play/Pause Button -->
                        <div class="mt-3.5">
                            <button type="button" 
                                    @click="toggleTimer()" 
                                    class="w-13 h-13 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition"
                                    :title="timer.isRunning ? 'Jeda Timer' : 'Mulai Timer'">
                                <svg x-show="!timer.isRunning" class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <svg x-show="timer.isRunning" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Presets & Manual Duration Controls (15-20-30 & Manual) -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400">Pilihan Durasi Sesi</span>
                    <span class="text-xs font-bold text-slate-600" x-text="timer.durationMinutes + ' Menit'"></span>
                </div>

                <!-- Preset Chips Row (15, 20, 30) -->
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" 
                            @click="setTimerPreset(15)"
                            class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                            :class="timer.selectedPreset === 15 
                                ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                        15 Menit
                    </button>
                    <button type="button" 
                            @click="setTimerPreset(20)"
                            class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                            :class="timer.selectedPreset === 20 
                                ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                        20 Menit
                    </button>
                    <button type="button" 
                            @click="setTimerPreset(30)"
                            class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                            :class="timer.selectedPreset === 30 
                                ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                        30 Menit
                    </button>
                </div>

                <!-- Manual Duration Setting -->
                <div class="flex items-center gap-2 pt-1">
                    <div class="relative flex-1">
                        <input type="number" 
                               min="1" 
                               max="720" 
                               x-model="timer.customMinutesInput" 
                               placeholder="Menit manual (cth: 25, 45, 60)..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none">
                    </div>
                    <button type="button" 
                            @click="setTimerManual(timer.customMinutesInput)" 
                            :disabled="!timer.customMinutesInput || timer.customMinutesInput <= 0" 
                            class="px-4 py-2 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100 text-xs font-black transition disabled:opacity-40">
                        Terapkan
                    </button>
                </div>
            </div>

            <!-- Footer Controls -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-2">
                <button type="button" 
                        @click="resetTimer()" 
                        class="px-4 py-2.5 rounded-2xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Reset</span>
                </button>
                <button type="button" 
                        @click="closeTimerModal()" 
                        class="flex-1 py-2.5 rounded-2xl bg-[#1C1C1E] text-white text-xs font-black hover:bg-black transition text-center">
                    Lanjutkan Pekerjaan
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- WAKTU HABIS! ALARM NOTIFICATION ALERT (SHUFFLE + LOOP 3X) -->
    <!-- ========================================================= -->
    <div x-show="timer.showAlarmAlert" x-cloak 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md">
        
        <div class="bg-white rounded-[32px] border-2 border-emerald-400 w-full max-w-sm overflow-hidden shadow-2xl p-6 sm:p-7 text-center space-y-5 animate-pulse">
            
            <!-- Animated Alarm Icon -->
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-800 mx-auto flex items-center justify-center shadow-inner">
                <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <!-- Title & Status -->
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 mb-2">
                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                    <span>Waktu Habis</span>
                </span>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">Waktu Rekaman Selesai!</h3>
                <p class="text-xs text-slate-500 mt-1">Audio notifikasi sedang berputar acak (loop 3x).</p>
            </div>

            <!-- Direct Actions -->
            <div class="space-y-2.5 pt-1">
                <!-- STOP ALARM BUTTON (MOST PROMINENT) -->
                <button type="button" 
                        @click="stopAlarm(); timer.showAlarmAlert = false" 
                        class="w-full py-4 rounded-2xl bg-rose-600 hover:bg-rose-700 active:scale-98 text-white font-black text-sm shadow-xl shadow-rose-600/30 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                    <span>Matikan Suara Alarm</span>
                </button>

                <!-- RESTART NEW SESSION -->
                <button type="button" 
                        @click="stopAlarm(); timer.showAlarmAlert = false; resetTimer(); startTimer();" 
                        class="w-full py-3 rounded-2xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:brightness-105 active:scale-98 text-slate-950 font-black text-xs shadow-md shadow-emerald-950/10 transition">
                    Mulai Sesi Baru Lagi
                </button>

                <!-- CLOSE BUTTON -->
                <button type="button" 
                        @click="stopAlarm(); timer.showAlarmAlert = false" 
                        class="w-full py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</body>
</html>
