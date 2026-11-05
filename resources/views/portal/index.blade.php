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
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased selection:bg-emerald-100 selection:text-emerald-900" x-data="attendancePortalData()">

    <!-- TOP HEADER -->
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur-md shadow-2xs">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            
            <!-- MOBILE TOP HEADER (md:hidden) -->
            <div class="flex items-center justify-between w-full md:hidden">
                <a href="/me" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-sm shadow-xs">
                        AI
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-black text-slate-900 text-base tracking-tight">AIKu Studio</span>
                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Portal</span>
                        </div>
                    </div>
                </a>

                <!-- User profile pill quick access -->
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="setTab('akun')" 
                            class="flex items-center gap-2 py-1 px-2.5 rounded-xl border border-slate-200 bg-slate-50/80 hover:bg-slate-100 transition text-left">
                        <div class="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="text-xs font-semibold text-slate-700 max-w-[90px] truncate">{{ auth()->user()->name }}</span>
                    </button>
                </div>
            </div>

            <!-- DESKTOP TOP HEADER (hidden md:flex) -->
            <div class="hidden md:flex items-center justify-between w-full">
                <!-- Brand & Subtitle -->
                <div class="flex items-center gap-3">
                    <a href="/me" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-base shadow-xs group-hover:scale-105 transition">
                            AI
                        </div>
                        <div>
                            <span class="font-black text-slate-900 text-lg tracking-tight">AIKu Studio</span>
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-700">Portal Kontributor</span>
                        </div>
                    </a>
                </div>

                <!-- DESKTOP MODERN SEGMENTED NAVIGATION TABS -->
                <nav class="flex items-center gap-1.5 p-1 rounded-2xl bg-slate-100 border border-slate-200/90 shadow-inner">
                    <!-- Tab 1: Absensi -->
                    <button type="button" 
                            @click="setTab('absensi')" 
                            :class="activeTab === 'absensi' ? 'bg-white text-emerald-800 shadow-xs font-bold border border-slate-200/70' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="relative min-h-[38px] px-4 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>Absensi</span>
                        <template x-if="!todayAttendance">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse" title="Belum Absen Hari Ini"></span>
                        </template>
                        <template x-if="todayAttendance">
                            <span class="w-2 h-2 rounded-full bg-emerald-500" title="Sudah Absen"></span>
                        </template>
                    </button>

                    <!-- Tab 2: Laporan -->
                    <button type="button" 
                            @click="setTab('laporan')" 
                            :class="activeTab === 'laporan' ? 'bg-white text-emerald-800 shadow-xs font-bold border border-slate-200/70' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="relative min-h-[38px] px-4 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Laporan Kerja</span>
                        <template x-if="todayReportSubmitted === false">
                            <span class="w-2 h-2 rounded-full bg-amber-500" title="Belum Kirim Laporan"></span>
                        </template>
                        <template x-if="todayReportSubmitted === true">
                            <span class="w-2 h-2 rounded-full bg-emerald-500" title="Laporan Sudah Dikirim"></span>
                        </template>
                    </button>

                    <!-- Tab 3: Jadwal -->
                    <button type="button" 
                            @click="setTab('jadwal')" 
                            :class="activeTab === 'jadwal' ? 'bg-white text-emerald-800 shadow-xs font-bold border border-slate-200/70' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="min-h-[38px] px-4 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Kalender Jadwal</span>
                    </button>

                    <!-- Tab 4: Akun / Profil -->
                    <button type="button" 
                            @click="setTab('akun')" 
                            :class="activeTab === 'akun' ? 'bg-white text-emerald-800 shadow-xs font-bold border border-slate-200/70' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                            class="min-h-[38px] px-4 py-1.5 rounded-xl text-xs transition duration-150 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Profil & Rekap</span>
                    </button>
                </nav>

                <!-- Desktop User & Logout -->
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-xs font-extrabold text-slate-800 leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] font-medium text-slate-400 leading-tight">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="post" action="/logout">
                        @csrf
                        <button class="min-h-[38px] rounded-xl border border-slate-200 px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </header>

    <!-- MAIN BODY CONTAINER -->
    <main x-cloak class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8 pb-32 md:pb-12 space-y-6">

        <!-- GLOBAL ERROR ALERT -->
        <div x-show="error" 
             x-transition 
             class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 flex items-start justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="error"></span>
            </div>
            <button type="button" @click="error = ''" class="text-red-400 hover:text-red-700 text-sm font-bold">✕</button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: ABSENSI HARI INI                    -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'absensi'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6">

            <!-- Hero Banner -->
            <section class="rounded-3xl bg-gradient-to-br from-emerald-900 via-teal-900 to-slate-900 text-white p-6 sm:p-8 shadow-md relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-emerald-300 border border-white/10 backdrop-blur-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Presensi Kehadiran</span>
                        </div>
                        <h1 class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-white">
                            Halo, {{ auth()->user()->name }}
                        </h1>
                        <p class="mt-1.5 text-xs sm:text-sm text-emerald-100/80 max-w-xl">
                            Catat kehadiranmu menggunakan Barcode WiFi Studio saat sampai di kantor atau gunakan GPS cadangan.
                        </p>
                    </div>
                    <div class="shrink-0 bg-white/10 border border-white/10 rounded-2xl p-4 text-center sm:text-right backdrop-blur-xs">
                        <p class="text-[11px] font-semibold text-emerald-200 uppercase tracking-wider">Tanggal Hari Ini (WITA)</p>
                        <p class="mt-0.5 text-sm sm:text-base font-extrabold text-white" x-text="dateLabel(today)"></p>
                    </div>
                </div>
            </section>

            <!-- ATTENDANCE MAIN CARD -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-7 shadow-xs">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider text-emerald-700">Absensi Hari Ini</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                WiFi Studio
                            </span>
                        </div>
                        <h2 class="mt-1.5 text-xl font-extrabold text-slate-900" x-text="dateLabel(today)"></h2>
                        <p class="mt-1 text-xs sm:text-sm text-slate-500">
                            Scan Barcode Studio yang terpasang di dinding saat terhubung ke WiFi kantor, atau gunakan GPS.
                        </p>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- SCAN BARCODE BUTTON (PRIMARY) -->
                        <button type="button" 
                                @click="openScanner()" 
                                :disabled="checking || loading || !!todayAttendance" 
                                class="min-h-[48px] shrink-0 inline-flex items-center gap-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-98 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            <span x-text="todayAttendance ? 'Sudah Masuk' : 'Scan Barcode WiFi'"></span>
                        </button>

                        <!-- GPS FALLBACK BUTTON -->
                        <button type="button" 
                                @click="checkIn()" 
                                :disabled="checking || loading || !!todayAttendance" 
                                class="min-h-[48px] shrink-0 inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 active:scale-98 px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-700 transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Absensi menggunakan koordinat GPS HP">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span x-text="checking ? 'Membaca…' : (todayAttendance ? 'Hadir (GPS)' : 'GPS')"></span>
                        </button>
                    </div>
                </div>

                <p x-show="message" x-text="message" role="status" aria-live="polite" class="mt-4 text-sm font-semibold text-emerald-700"></p>

                <!-- CHECK-IN RESULT CARD -->
                <template x-if="todayAttendance">
                    <div class="mt-5">
                        <!-- Case 1: Checked in via QR Code + WiFi -->
                        <template x-if="todayAttendance.checkin_method === 'qr_wifi'">
                            <div class="grid gap-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 p-5 text-sm sm:grid-cols-3">
                                <div>
                                    <p class="text-xs font-bold text-emerald-800">Jam Masuk (WITA)</p>
                                    <p class="mt-1 text-xl font-black text-emerald-950 font-mono" x-text="time(todayAttendance.checked_in_at)"></p>
                                    <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-200/70 text-emerald-900 border border-emerald-300">
                                        ✓ Terverifikasi WiFi Studio
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-800">Metode & Jaringan</p>
                                    <p class="mt-1 font-bold text-emerald-950 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                        <span>Barcode WiFi Studio</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-emerald-800 font-mono" x-text="'IP: ' + (todayAttendance.client_ip || 'Terverifikasi')"></p>

                                    <template x-if="todayAttendance.latitude && Number(todayAttendance.latitude) !== 0">
                                        <div class="mt-2 pt-2 border-t border-emerald-200/80">
                                            <p class="text-[11px] font-bold text-emerald-800">Koordinat GPS:</p>
                                            <p class="font-mono text-[11px] text-emerald-950" x-text="todayAttendance.latitude + ', ' + todayAttendance.longitude"></p>
                                            <p class="text-[10px] text-emerald-700" x-text="todayAttendance.accuracy_m ? ('Akurasi ±' + Math.round(todayAttendance.accuracy_m) + ' meter') : ''"></p>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center sm:justify-end">
                                    <template x-if="todayAttendance.latitude && Number(todayAttendance.latitude) !== 0">
                                        <a :href="mapUrl(todayAttendance)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-50 transition shadow-2xs">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>Lihat lokasi di peta ↗</span>
                                        </a>
                                    </template>
                                    <template x-if="!todayAttendance.latitude || Number(todayAttendance.latitude) === 0">
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-200/70 text-emerald-900 border border-emerald-300">
                                            ✓ Presensi Sah
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Case 2: Checked in via GPS Coordinates -->
                        <template x-if="todayAttendance.checkin_method !== 'qr_wifi'">
                            <div class="grid gap-4 rounded-2xl bg-brand-50/80 border border-brand-200/80 p-5 text-sm sm:grid-cols-3">
                                <div>
                                    <p class="text-xs font-bold text-brand-800">Jam Masuk (WITA)</p>
                                    <p class="mt-1 text-xl font-black text-slate-900 font-mono" x-text="time(todayAttendance.checked_in_at)"></p>
                                    <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-brand-100 text-brand-900 border border-brand-200">
                                        ✓ Presensi GPS Cadangan
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-brand-800">Koordinat GPS</p>
                                    <p class="mt-1 break-all font-mono text-xs font-medium text-slate-800" x-text="todayAttendance.latitude + ', ' + todayAttendance.longitude"></p>
                                    <p class="mt-0.5 text-xs text-slate-500" x-text="'Akurasi ±' + Math.round(todayAttendance.accuracy_m) + ' meter'"></p>
                                </div>
                                <div class="flex items-center sm:justify-end">
                                    <a :href="mapUrl(todayAttendance)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl text-xs font-bold bg-white border border-brand-200 text-brand-700 hover:bg-brand-50 transition shadow-2xs">
                                        <span>Lihat lokasi di peta</span>
                                        <span>↗</span>
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- If not checked in yet -->
                <template x-if="!todayAttendance">
                    <div class="mt-4 rounded-2xl bg-amber-50/80 border border-amber-200 p-4 text-xs text-amber-900 flex items-center gap-3">
                        <span class="text-lg">💡</span>
                        <p class="leading-relaxed">
                            <strong>Kamu belum melakukan presensi hari ini.</strong> Hubungkan HP ke WiFi Studio, lalu tekan tombol <strong>Scan Barcode WiFi</strong> di atas untuk memindai poster barcode di dinding studio.
                        </p>
                    </div>
                </template>
            </section>

            <!-- TODAY'S SCHEDULE SNAPSHOT -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-7 shadow-xs">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Jadwal Sesi Hari Ini</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Shift</span>
                        </div>
                        <h3 class="mt-1 text-base sm:text-lg font-extrabold text-slate-900" x-text="dateLabel(today)"></h3>
                    </div>
                    <button type="button" @click="setTab('jadwal')" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center gap-1">
                        <span>Lihat Semua Kalender</span>
                        <span>→</span>
                    </button>
                </div>

                <div class="mt-4">
                    <template x-if="todaySchedule.sessions && todaySchedule.sessions.length > 0">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="session in todaySchedule.sessions" :key="session.id">
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 transition hover:border-emerald-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="font-black text-slate-900 text-sm" x-text="'Sesi ' + session.id"></span>
                                        </div>
                                        <span class="text-xs font-bold text-emerald-800 bg-emerald-100/90 px-2.5 py-1 rounded-xl" x-text="session.tool"></span>
                                    </div>
                                    <div class="mt-2.5 flex items-center justify-between text-xs font-semibold text-slate-600">
                                        <span class="text-slate-400">Jam Kerja:</span>
                                        <span class="font-mono font-bold text-slate-800" x-text="session.start + ' – ' + session.end + ' WITA'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!todaySchedule.sessions || todaySchedule.sessions.length === 0">
                        <div class="text-center py-8 text-slate-500 text-sm bg-slate-50/80 rounded-2xl border border-dashed border-slate-200">
                            <span class="text-2xl block mb-1">☕</span>
                            <p class="font-bold text-slate-700">Tidak ada jadwal sesi rekaman untukmu hari ini.</p>
                            <p class="text-xs text-slate-400 mt-1">Hari ini libur atau tidak ada shift yang dijadwalkan.</p>
                        </div>
                    </template>
                </div>
            </section>

            <!-- QUICK ACTION: GO TO WORK REPORT -->
            <section class="rounded-3xl bg-gradient-to-r from-emerald-600 to-teal-700 p-5 sm:p-7 text-white shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-black text-lg">Sudah selesai rekaman hari ini?</h3>
                    <p class="text-xs sm:text-sm text-emerald-100 mt-1">
                        Kirim Laporan Kerja Harian untuk mencatat total menit pengerjaan pada tiap HP yang kamu gunakan.
                    </p>
                </div>
                <button type="button" 
                        @click="setTab('laporan')" 
                        class="shrink-0 px-5 py-3 rounded-2xl bg-white text-emerald-800 hover:bg-emerald-50 active:scale-98 text-xs sm:text-sm font-black shadow-xs transition flex items-center gap-2">
                    <span>Buka Laporan Kerja</span>
                    <span>→</span>
                </button>
            </section>

            <!-- PANDUAN CEPAT ABSENSI -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 text-xs text-slate-500">
                <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px] mb-2">Panduan Singkat</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-slate-600">
                    <div class="flex items-start gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0">1</span>
                        <p class="leading-relaxed"><strong>Hubungkan WiFi Studio:</strong> Pastikan HP Anda tersambung ke jaringan WiFi kantor AIKu Studio.</p>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0">2</span>
                        <p class="leading-relaxed"><strong>Scan Poster Barcode:</strong> Klik Scan Barcode dan arahkan kamera HP ke poster di dinding studio.</p>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0">3</span>
                        <p class="leading-relaxed"><strong>Selesai:</strong> Absensi masuk tercatat otomatis. Tidak perlu checkout saat pulang kerja.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: LAPORAN KERJA HARIAN                -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'laporan'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6">

            <!-- Subheader banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Input Durasi Menit</p>
                    <h2 class="text-2xl font-black text-slate-900 mt-0.5">Laporan Kerja Harian</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Centang HP yang kamu gunakan hari ini dan masukkan durasi pengerjaan dalam satuan menit.</p>
                </div>
                <div class="shrink-0">
                    <button type="button" @click="setTab('absensi')" class="text-xs font-bold text-slate-500 hover:text-emerald-700 transition flex items-center gap-1">
                        <span>← Kembali ke Absensi</span>
                    </button>
                </div>
            </div>

            <!-- INCLUDED WORK REPORT FORM -->
            @include('work-reports.form')
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: KALENDER JADWAL & SHIFT             -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'jadwal'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6">

            <!-- Subheader banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Jadwal Kerja Bulanan</p>
                    <h2 class="text-2xl font-black text-slate-900 mt-0.5">Kalender Jadwal & Shift</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Pilih tanggal pada kalender untuk melihat rincian sesi dan alat kerja.</p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-3 py-1.5 shadow-2xs">
                        <span class="text-xs font-bold text-slate-500">Bulan:</span>
                        <input type="month" x-model="month" @change="load()" :disabled="loading" class="text-xs sm:text-sm font-bold text-slate-800 bg-transparent focus:outline-none">
                    </label>
                </div>
            </div>

            <!-- RESPONSIVE 2-COLUMN LAYOUT ON DESKTOP -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- LEFT COLUMN: CALENDAR GRID (lg:col-span-7) -->
                <div class="lg:col-span-7">
                    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
                        <div class="flex items-center justify-between p-4 sm:p-5 border-b border-slate-100">
                            <span class="text-sm font-extrabold text-slate-800">Kalender Bulanan</span>
                            <span x-show="loading" class="text-xs text-slate-400">Memuat jadwal…</span>
                        </div>

                        <!-- Day Names -->
                        <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-100 text-center">
                            <template x-for="day in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']">
                                <div class="py-2.5 text-center text-xs font-bold text-slate-500" x-text="day"></div>
                            </template>
                        </div>

                        <!-- Calendar Cells -->
                        <div class="grid grid-cols-7">
                            <template x-for="cell in calendarCells" :key="cell.key">
                                <div class="min-w-0 border-t border-r border-slate-100 relative">
                                    <button type="button" 
                                            x-show="cell.date" 
                                            @click="selectedDate=cell.date" 
                                            :aria-label="cell.date+(cell.attendance?', sudah masuk':'')" 
                                            :aria-pressed="selectedDate===cell.date" 
                                            :class="selectedDate===cell.date ? 'bg-emerald-50/80 ring-2 ring-inset ring-emerald-500' : 'hover:bg-slate-50/80'" 
                                            class="min-h-[80px] w-full p-2 text-left sm:min-h-[96px] sm:p-2.5 transition">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold text-slate-800" 
                                                  :class="cell.date === today ? 'w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-black' : ''"
                                                  x-text="Number(cell.date?.slice(-2))"></span>
                                            <span x-show="cell.attendance" class="w-2 h-2 rounded-full bg-emerald-500" title="Hadir"></span>
                                        </div>

                                        <span class="mt-2 block text-[10px] sm:text-xs leading-tight font-semibold" 
                                              :class="cell.attendance ? 'text-emerald-700' : (cell.sessions?.length ? 'text-slate-700' : 'text-slate-400')" 
                                              x-text="cell.attendance ? '✓ Masuk' : (cell.sessions?.length ? cell.sessions.length + ' sesi' : 'Libur')"></span>
                                        
                                        <span x-show="cell.custom" class="mt-1 hidden text-[9px] font-bold text-indigo-600 sm:block">Khusus</span>
                                    </button>
                                    <span x-show="!cell.date" class="block min-h-[80px] bg-slate-50/50 sm:min-h-[96px]"></span>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>

                <!-- RIGHT COLUMN: SELECTED DAY DETAIL (lg:col-span-5) -->
                <div class="lg:col-span-5 lg:sticky lg:top-24 space-y-4">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Detail Tanggal</span>
                            <span x-show="selectedDate === today" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Hari Ini</span>
                        </div>

                        <h3 class="mt-2 text-base sm:text-lg font-black text-slate-900" x-text="dateLabel(selectedDate)"></h3>

                        <!-- Session List -->
                        <div class="mt-4 space-y-2.5">
                            <template x-for="session in selectedDay.sessions" :key="session.id">
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 transition">
                                    <div class="flex items-center justify-between">
                                        <p class="font-extrabold text-sm text-slate-900" x-text="'Sesi ' + session.id"></p>
                                        <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-lg" x-text="session.tool"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500 font-semibold" x-text="session.start + ' – ' + session.end + ' WITA'"></p>
                                </div>
                            </template>

                            <p x-show="!selectedDay.sessions.length" class="py-6 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                Tidak ada sesi yang dijadwalkan pada tanggal ini.
                            </p>
                        </div>

                        <!-- Attendance log for selected date -->
                        <template x-if="selectedAttendance">
                            <div class="mt-5 border-t border-slate-100 pt-4 text-xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-500">Status Kehadiran:</span>
                                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200">✓ Hadir</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-500">Jam Masuk:</span>
                                    <strong class="font-mono text-slate-900" x-text="time(selectedAttendance.checked_in_at)"></strong>
                                </div>
                                <template x-if="selectedAttendance.checkin_method === 'qr_wifi'">
                                    <p class="text-emerald-700 font-semibold flex items-center gap-1.5 pt-1">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Terverifikasi WiFi Studio</span>
                                    </p>
                                </template>
                                <template x-if="selectedAttendance.latitude && Number(selectedAttendance.latitude) !== 0">
                                    <div class="pt-1">
                                        <p class="text-[11px] text-slate-500 font-mono" x-text="'GPS: ' + selectedAttendance.latitude + ', ' + selectedAttendance.longitude"></p>
                                        <a :href="mapUrl(selectedAttendance)" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:underline">
                                            <span>Lihat lokasi di peta</span>
                                            <span>↗</span>
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </section>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: AKUN & PROFIL                       -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'akun'" 
             x-transition:enter="transition ease-out duration-150" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6">

            <!-- Subheader banner -->
            <div class="pb-1">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Akun & Ringkasan</p>
                <h2 class="text-2xl font-black text-slate-900 mt-0.5">Profil Saya</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Informasi kontributor, ringkasan performa bulan ini, dan pengaturan akun.</p>
            </div>

            <!-- Profile Card -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-xl flex items-center justify-center shadow-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-xl font-extrabold text-slate-900">{{ auth()->user()->name }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                Kontributor Aktif
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">{{ auth()->user()->email }}</p>
                        <p class="text-[11px] text-slate-400 mt-2">Terdaftar di AIKu Studio Production Portal</p>
                    </div>
                </div>

                <!-- Monthly Stats -->
                <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kehadiran Bulan Ini</p>
                        <p class="mt-1 text-2xl font-black text-emerald-700 font-mono" x-text="totalMonthAttendances + ' Hari'"></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sesi Terjadwal</p>
                        <p class="mt-1 text-2xl font-black text-slate-800 font-mono" x-text="totalMonthSessions + ' Sesi'"></p>
                    </div>
                    <div class="col-span-2 sm:col-span-1 rounded-2xl bg-slate-50 p-4 border border-slate-100">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Zona Waktu Studio</p>
                        <p class="mt-1 text-sm font-extrabold text-slate-800">WITA (Asia/Makassar)</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">UTC +08:00</p>
                    </div>
                </div>
            </section>

            <!-- Ketentuan Studio Card -->
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs text-xs text-slate-600 space-y-3">
                <h4 class="font-extrabold text-sm text-slate-800">Ketentuan & Kebijakan Studio</h4>
                <ul class="space-y-2 list-disc list-inside leading-relaxed text-slate-500">
                    <li><strong>WiFi Kantor:</strong> Absensi menggunakan Barcode hanya sah apabila HP terhubung ke jaringan WiFi resmi AIKu Studio.</li>
                    <li><strong>Absensi 1x Sehari:</strong> Presensi dicatat satu kali per hari kerja saat tiba di studio dan tidak memerlukan proses checkout saat selesai.</li>
                    <li><strong>Laporan Harian:</strong> Setiap kontributor wajib mengisi Laporan Kerja Harian setelah menyelesaikan sesi rekaman sesuai menit riil masing-masing HP.</li>
                </ul>
            </section>

            <!-- Logout Section -->
            <section class="rounded-3xl border border-red-100 bg-red-50/50 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h4 class="font-bold text-sm text-red-950">Keluar dari Akun</h4>
                    <p class="text-xs text-red-700/80 mt-0.5">Akhiri sesi portal kontributor pada perangkat ini.</p>
                </div>
                <form method="post" action="/logout">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-2xl bg-red-600 hover:bg-red-700 active:scale-98 text-white font-bold text-xs transition duration-150 shadow-xs flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar Sekarang</span>
                    </button>
                </form>
            </section>
        </div>

    </main>

    <!-- ========================================== -->
    <!-- MODERN BOTTOM NAVIGATION BAR (MOBILE ONLY) -->
    <!-- ========================================== -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_24px_rgba(0,0,0,0.06)] px-2 pt-1.5 pb-[calc(0.65rem+env(safe-area-inset-bottom,0px))]">
        <div class="grid grid-cols-4 items-center max-w-md mx-auto">
            
            <!-- Tab 1: Absensi -->
            <button type="button" 
                    @click="setTab('absensi')" 
                    class="relative flex flex-col items-center justify-center py-1 px-1 rounded-2xl transition duration-150"
                    :class="activeTab === 'absensi' ? 'text-emerald-700 font-bold' : 'text-slate-400 hover:text-slate-600 font-medium'">
                <div class="relative p-1 rounded-xl transition duration-150"
                     :class="activeTab === 'absensi' ? 'bg-emerald-50 text-emerald-600 scale-105' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <!-- Status dot indicator -->
                    <template x-if="!todayAttendance">
                        <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-amber-500 ring-2 ring-white animate-pulse"></span>
                    </template>
                    <template x-if="todayAttendance">
                        <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </template>
                </div>
                <span class="text-[11px] leading-tight mt-0.5">Absensi</span>
            </button>

            <!-- Tab 2: Laporan -->
            <button type="button" 
                    @click="setTab('laporan')" 
                    class="relative flex flex-col items-center justify-center py-1 px-1 rounded-2xl transition duration-150"
                    :class="activeTab === 'laporan' ? 'text-emerald-700 font-bold' : 'text-slate-400 hover:text-slate-600 font-medium'">
                <div class="relative p-1 rounded-xl transition duration-150"
                     :class="activeTab === 'laporan' ? 'bg-emerald-50 text-emerald-600 scale-105' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <!-- Status dot indicator -->
                    <template x-if="todayReportSubmitted === false">
                        <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-amber-500 ring-2 ring-white"></span>
                    </template>
                    <template x-if="todayReportSubmitted === true">
                        <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </template>
                </div>
                <span class="text-[11px] leading-tight mt-0.5">Laporan</span>
            </button>

            <!-- Tab 3: Jadwal -->
            <button type="button" 
                    @click="setTab('jadwal')" 
                    class="relative flex flex-col items-center justify-center py-1 px-1 rounded-2xl transition duration-150"
                    :class="activeTab === 'jadwal' ? 'text-emerald-700 font-bold' : 'text-slate-400 hover:text-slate-600 font-medium'">
                <div class="relative p-1 rounded-xl transition duration-150"
                     :class="activeTab === 'jadwal' ? 'bg-emerald-50 text-emerald-600 scale-105' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-[11px] leading-tight mt-0.5">Jadwal</span>
            </button>

            <!-- Tab 4: Akun / Profil -->
            <button type="button" 
                    @click="setTab('akun')" 
                    class="relative flex flex-col items-center justify-center py-1 px-1 rounded-2xl transition duration-150"
                    :class="activeTab === 'akun' ? 'text-emerald-700 font-bold' : 'text-slate-400 hover:text-slate-600 font-medium'">
                <div class="relative p-1 rounded-xl transition duration-150"
                     :class="activeTab === 'akun' ? 'bg-emerald-50 text-emerald-600 scale-105' : ''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <span class="text-[11px] leading-tight mt-0.5">Profil</span>
            </button>

        </div>
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
        
        <div class="bg-white rounded-3xl border border-slate-200 w-full max-w-sm overflow-hidden shadow-2xl flex flex-col"
             @click.outside="closeScanner()">
            
            <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <h3 class="font-bold text-sm">Scan Barcode Studio</h3>
                </div>
                <button type="button" @click="closeScanner()" class="w-8 h-8 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center transition">
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
                    <p class="text-[11px] text-slate-500 mt-1">Pastikan HP Anda sudah terhubung ke <strong>WiFi Kantor</strong>.</p>
                </div>
                <button type="button" @click="closeScanner()" class="w-full py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition">
                    Tutup Kamera
                </button>
            </div>
        </div>
    </div>

</body>
</html>
