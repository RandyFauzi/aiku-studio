<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Portal Kontributor' }} — AIKu Studio</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-[#F8FAFC] font-sans text-slate-800 antialiased selection:bg-emerald-100 selection:text-emerald-900 relative" 
      x-data="attendancePortalData()">

    <!-- LIGHT GREEN AMBIENT TOP GRADIENT (Clean Light Green Gradient Palette) -->
    <div class="fixed top-0 inset-x-0 h-[420px] bg-gradient-to-b from-emerald-100/60 via-teal-50/30 to-transparent pointer-events-none -z-10"></div>
    <div class="fixed top-0 right-0 w-80 h-80 bg-emerald-200/20 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <div class="flex min-h-screen">

        <!-- ========================================================= -->
        <!-- DESKTOP LEFT SIDEBAR NAVIGATION                           -->
        <!-- ========================================================= -->
        <aside class="hidden md:flex w-64 border-r border-slate-200/80 bg-white min-h-screen sticky top-0 flex-col justify-between z-30 shrink-0 shadow-2xs">
            
            <!-- Top Branding & Navigation -->
            <div class="p-5 space-y-6">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#1C1C1E] text-white flex items-center justify-center font-black text-sm shadow-xs">
                        AI
                    </div>
                    <div>
                        <span class="font-black text-slate-900 text-base tracking-tight block">AIKu Studio</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Portal Kontributor</span>
                        </span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-1.5">
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Menu Utama</p>

                    <!-- Link 1: Utama / Presensi -->
                    @php($isHome = request()->is('me') || request()->is('me/'))
                    <a href="/me" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs transition duration-150 {{ $isHome ? 'bg-[#1C1C1E] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-semibold' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ $isHome ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Utama & Absensi</span>
                        </div>
                        <template x-if="!todayAttendance">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        </template>
                    </a>

                    <!-- Link 2: Laporan Kerja -->
                    @php($isReport = request()->is('me/laporan*'))
                    <a href="/me/laporan" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs transition duration-150 {{ $isReport ? 'bg-[#1C1C1E] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-semibold' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ $isReport ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Laporan Harian</span>
                        </div>
                        <template x-if="todayReportSubmitted === false">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        </template>
                    </a>

                    <!-- Link 3: Jadwal Shift -->
                    @php($isSchedule = request()->is('me/jadwal*'))
                    <a href="/me/jadwal" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs transition duration-150 {{ $isSchedule ? 'bg-[#1C1C1E] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-semibold' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ $isSchedule ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Jadwal Shift</span>
                        </div>
                    </a>

                    <!-- Link 4: Slip Gaji -->
                    @php($isSlip = request()->is('me/slip*'))
                    <a href="/me/slip" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs transition duration-150 {{ $isSlip ? 'bg-[#1C1C1E] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-semibold' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ $isSlip ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Slip Gaji</span>
                        </div>
                        <template x-if="slips.length > 0">
                            <span class="text-[10px] font-black px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800" x-text="slips.length"></span>
                        </template>
                    </a>

                    <!-- Link 5: Profil Akun -->
                    @php($isProfile = request()->is('me/profil*') || request()->is('me/profile*'))
                    <a href="/me/profil" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs transition duration-150 {{ $isProfile ? 'bg-[#1C1C1E] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-semibold' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ $isProfile ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Profil Akun</span>
                        </div>
                    </a>
                </nav>

                <!-- Sidebar Focus Timer Mini Widget -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Timer Rekaman</span>
                        <span class="w-2 h-2 rounded-full" :class="timer.isRunning ? 'bg-emerald-500 animate-ping' : 'bg-slate-300'"></span>
                    </div>

                    <div class="flex items-baseline justify-between">
                        <span class="text-xl font-black font-mono text-slate-900" x-text="timerFormatted"></span>
                        <button type="button" 
                                @click="toggleTimer()" 
                                class="px-2.5 py-1 rounded-xl text-xs font-black transition"
                                :class="timer.isRunning ? 'bg-amber-100 text-amber-900' : 'bg-[#1C1C1E] text-white hover:bg-black'">
                            <span x-text="timer.isRunning ? 'Jeda' : 'Mulai'"></span>
                        </button>
                    </div>

                    <button type="button" 
                            @click="openTimerModal()" 
                            class="w-full py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-[11px] font-bold text-slate-700 transition text-center shadow-2xs">
                        Buka Timer Lengkap ↗
                    </button>
                </div>
            </div>

            <!-- Bottom User Card & Logout -->
            <div class="p-4 border-t border-slate-100 space-y-3 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-black flex items-center justify-center text-sm shadow-2xs shrink-0 ring-2 ring-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-extrabold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="w-full py-2 rounded-xl border border-slate-200 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 text-xs font-bold text-slate-600 transition flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar Akun</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ========================================================= -->
        <!-- MAIN CONTENT WRAPPER                                      -->
        <!-- ========================================================= -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- TOP HEADER (MOBILE & TABLET / DESKTOP SUBHEADER) -->
            <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-200/60 shadow-2xs">
                <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    
                    <!-- MOBILE HEADER -->
                    <div class="flex items-center justify-between w-full md:hidden">
                        <!-- User Avatar & Sapaan -->
                        <div class="flex items-center gap-2.5">
                            <a href="/me/profil" class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-black flex items-center justify-center text-xs shadow-xs shrink-0 ring-2 ring-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </a>
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 leading-none">AIKu Studio</p>
                                <h2 class="text-xs font-extrabold text-slate-900 leading-tight mt-0.5 truncate max-w-[130px]">{{ auth()->user()->name }}</h2>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <!-- Quick Timer Button (Mobile Header) -->
                            <button type="button" 
                                    @click="openTimerModal()" 
                                    class="h-9 px-2.5 rounded-full border transition flex items-center gap-1.5 shadow-2xs"
                                    :class="timer.isRunning 
                                        ? 'bg-emerald-500 text-slate-950 border-emerald-400 font-black shadow-emerald-500/20' 
                                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                                    title="Timer Rekaman">
                                <svg class="w-3.5 h-3.5" :class="timer.isRunning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-xs font-mono font-bold" x-show="timer.isRunning" x-text="timerFormatted"></span>
                            </button>

                            <!-- Quick Scan Barcode Button (+) -->
                            <button type="button" 
                                    @click="openScanner()" 
                                    :disabled="checking || loading || !!todayAttendance"
                                    class="w-9 h-9 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-xs hover:scale-105 active:scale-95 transition disabled:opacity-50"
                                    title="Scan Barcode WiFi">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </button>

                            <!-- Mobile Menu Toggle Button (Opens mobile slide-over) -->
                            <button type="button" 
                                    @click="mobileSidebarOpen = !mobileSidebarOpen" 
                                    class="w-9 h-9 rounded-full bg-white border border-slate-200 text-slate-700 flex items-center justify-center shadow-2xs hover:bg-slate-50 active:scale-95 transition"
                                    title="Menu Sidebar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- DESKTOP TOP BAR CONTENT -->
                    <div class="hidden md:flex items-center justify-between w-full">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400">Portal Kontributor</span>
                            <h2 class="text-base font-extrabold text-slate-900 leading-tight">{{ $pageTitle ?? 'Dashboard' }}</h2>
                        </div>

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

                            <!-- Scan Barcode Quick Button -->
                            <button type="button" 
                                    @click="openScanner()" 
                                    :disabled="checking || loading || !!todayAttendance"
                                    class="min-h-[38px] px-3.5 py-1.5 rounded-2xl bg-[#1C1C1E] text-white font-bold text-xs shadow-xs hover:bg-black transition flex items-center gap-2 disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <span x-text="todayAttendance ? 'Sudah Presensi' : 'Scan Barcode WiFi'"></span>
                            </button>
                        </div>
                    </div>

                </div>
            </header>

            <!-- MAIN BODY CONTENT -->
            <main x-cloak class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-7 pb-36 md:pb-12 space-y-6 w-full">
                
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

                <!-- ACTIVE TIMER FLOATING MINI-BANNER (When running) -->
                <div x-show="timer.isRunning" 
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

                <!-- PAGE CONTENT INJECTED HERE -->
                @yield('content')

            </main>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- MOBILE SLIDE-OVER SIDEBAR DRAWER                          -->
    <!-- ========================================================= -->
    <div x-show="mobileSidebarOpen" x-cloak 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex md:hidden"
         @keydown.escape.window="mobileSidebarOpen = false">
        
        <!-- Backdrop Overlay -->
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" @click="mobileSidebarOpen = false"></div>

        <!-- Slide Drawer Panel -->
        <div class="relative w-72 max-w-[80%] bg-white h-full flex flex-col justify-between p-5 shadow-2xl z-10 space-y-6 overflow-y-auto"
             x-transition:enter="transform transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            
            <div class="space-y-6">
                <!-- Drawer Top Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#1C1C1E] text-white flex items-center justify-center font-black text-xs">
                            AI
                        </div>
                        <span class="font-black text-slate-900 text-sm">AIKu Studio</span>
                    </div>
                    <button type="button" @click="mobileSidebarOpen = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold">
                        ✕
                    </button>
                </div>

                <!-- Drawer Links -->
                <nav class="space-y-1.5">
                    <a href="/me" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-bold {{ request()->is('me') || request()->is('me/') ? 'bg-[#1C1C1E] text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Utama & Presensi</span>
                    </a>
                    <a href="/me/laporan" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-bold {{ request()->is('me/laporan*') ? 'bg-[#1C1C1E] text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Laporan Harian</span>
                    </a>
                    <a href="/me/jadwal" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-bold {{ request()->is('me/jadwal*') ? 'bg-[#1C1C1E] text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Jadwal Shift</span>
                    </a>
                    <a href="/me/slip" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-bold {{ request()->is('me/slip*') ? 'bg-[#1C1C1E] text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Slip Gaji</span>
                    </a>
                    <a href="/me/profil" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-bold {{ request()->is('me/profil*') || request()->is('me/profile*') ? 'bg-[#1C1C1E] text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Profil Akun</span>
                    </a>
                </nav>
            </div>

            <!-- Drawer Bottom User & Logout -->
            <div class="pt-4 border-t border-slate-100 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-black flex items-center justify-center text-xs">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-black text-slate-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-rose-600 hover:bg-rose-50 transition flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar Akun</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- FLOATING DOCK BOTTOM NAVIGATION BAR (MATCHING DRIBBBLE)   -->
    <!-- ========================================================= -->
    <nav class="md:hidden fixed bottom-5 inset-x-4 max-w-sm sm:max-w-md mx-auto z-40 bg-white/95 backdrop-blur-xl border border-slate-200/80 shadow-[0_16px_36px_rgba(0,0,0,0.12)] rounded-[32px] px-3 py-2 flex items-center justify-between">
        
        <!-- Tab 1: Utama / Home -->
        @php($isHomeNav = request()->is('me') || request()->is('me/'))
        <a href="/me" 
           class="transition-all duration-200 flex flex-col items-center justify-center min-w-[56px] {{ $isHomeNav ? 'bg-[#1C1C1E] text-white rounded-[20px] px-3 py-2 shadow-sm' : 'text-slate-400 hover:text-slate-700 py-2 px-1.5' }}">
            <svg class="w-5 h-5 shrink-0 {{ $isHomeNav ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] leading-tight mt-1 {{ $isHomeNav ? 'font-bold text-white' : 'font-medium text-slate-400' }}">Home</span>
        </a>

        <!-- Tab 2: Laporan / Projects -->
        @php($isReportNav = request()->is('me/laporan*'))
        <a href="/me/laporan" 
           class="transition-all duration-200 flex flex-col items-center justify-center min-w-[56px] {{ $isReportNav ? 'bg-[#1C1C1E] text-white rounded-[20px] px-3 py-2 shadow-sm' : 'text-slate-400 hover:text-slate-700 py-2 px-1.5' }}">
            <svg class="w-5 h-5 shrink-0 {{ $isReportNav ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-[10px] leading-tight mt-1 {{ $isReportNav ? 'font-bold text-white' : 'font-medium text-slate-400' }}">Laporan</span>
        </a>

        <!-- Tab 3: Jadwal / Calendar -->
        @php($isScheduleNav = request()->is('me/jadwal*'))
        <a href="/me/jadwal" 
           class="transition-all duration-200 flex flex-col items-center justify-center min-w-[56px] {{ $isScheduleNav ? 'bg-[#1C1C1E] text-white rounded-[20px] px-3 py-2 shadow-sm' : 'text-slate-400 hover:text-slate-700 py-2 px-1.5' }}">
            <svg class="w-5 h-5 shrink-0 {{ $isScheduleNav ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-[10px] leading-tight mt-1 {{ $isScheduleNav ? 'font-bold text-white' : 'font-medium text-slate-400' }}">Jadwal</span>
        </a>

        <!-- Tab 4: Slip Gaji -->
        @php($isSlipNav = request()->is('me/slip*'))
        <a href="/me/slip" 
           class="transition-all duration-200 flex flex-col items-center justify-center min-w-[56px] {{ $isSlipNav ? 'bg-[#1C1C1E] text-white rounded-[20px] px-3 py-2 shadow-sm' : 'text-slate-400 hover:text-slate-700 py-2 px-1.5' }}">
            <svg class="w-5 h-5 shrink-0 {{ $isSlipNav ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="text-[10px] leading-tight mt-1 {{ $isSlipNav ? 'font-bold text-white' : 'font-medium text-slate-400' }}">Slip</span>
        </a>

        <!-- Tab 5: Profil -->
        @php($isProfileNav = request()->is('me/profil*') || request()->is('me/profile*'))
        <a href="/me/profil" 
           class="transition-all duration-200 flex flex-col items-center justify-center min-w-[56px] {{ $isProfileNav ? 'bg-[#1C1C1E] text-white rounded-[20px] px-3 py-2 shadow-sm' : 'text-slate-400 hover:text-slate-700 py-2 px-1.5' }}">
            <svg class="w-5 h-5 shrink-0 {{ $isProfileNav ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-[10px] leading-tight mt-1 {{ $isProfileNav ? 'font-bold text-white' : 'font-medium text-slate-400' }}">Profil</span>
        </a>

    </nav>

    <!-- ========================================================= -->
    <!-- CAMERA SCANNER MODAL FOR CONTRIBUTOR                      -->
    <!-- ========================================================= -->
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
    <!-- TIMER FOCUS MODAL (Accessible from any page via Header)   -->
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
                            class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60 shadow-2xs"
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
                        class="px-4 py-2.5 rounded-2xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Reset</span>
                </button>
                <button type="button" 
                        @click="closeTimerModal()" 
                        class="flex-1 py-2.5 rounded-2xl bg-[#1C1C1E] text-white text-xs font-black hover:bg-black transition text-center shadow-xs">
                    Tutup
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
