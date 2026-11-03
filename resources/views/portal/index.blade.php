<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jadwal & Absensi — AIKu Studio</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800" x-data="attendancePortalData()">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-5 sm:px-6">
            <a href="/me" class="font-extrabold text-brand-700">AIKu Studio</a>
            <div class="flex items-center gap-4">
                <span class="hidden text-sm text-slate-500 sm:block">{{ auth()->user()->email }}</span>
                <form method="post" action="/logout">
                    @csrf
                    <button class="min-h-[44px] rounded-lg border border-slate-200 px-4 text-sm">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main x-cloak class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 sm:py-10">
        <section>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">Portal kontributor</p>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Halo, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Lihat jadwalmu, catat kehadiran, dan kirim laporan kerja harian.</p>
        </section>

        <p x-show="error" x-text="error" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"></p>

        <!-- ATTENDANCE CARD -->
        <section class="rounded-2xl border border-brand-100 bg-white p-5 sm:p-7 shadow-2xs">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Absensi Hari Ini</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">WiFi Studio</span>
                    </div>
                    <h2 class="mt-1.5 text-lg font-bold" x-text="dateLabel(today)"></h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500">Scan Barcode Studio yang terpasang di dinding saat terhubung ke WiFi kantor, atau gunakan GPS.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- SCAN BARCODE BUTTON (PRIMARY) -->
                    <button type="button" 
                            @click="openScanner()" 
                            :disabled="checking || loading || !!todayAttendance" 
                            class="min-h-[48px] shrink-0 inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-xs transition disabled:opacity-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span x-text="todayAttendance ? 'Sudah Masuk' : 'Scan Barcode WiFi'"></span>
                    </button>

                    <!-- GPS FALLBACK BUTTON -->
                    <button type="button" 
                            @click="checkIn()" 
                            :disabled="checking || loading || !!todayAttendance" 
                            class="min-h-[48px] shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 transition disabled:opacity-50"
                            title="Absensi menggunakan koordinat GPS HP">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="checking ? 'Membaca…' : (todayAttendance ? 'Hadir' : 'GPS')"></span>
                    </button>
                </div>
            </div>

            <p x-show="message" x-text="message" role="status" aria-live="polite" class="mt-4 text-sm font-medium text-brand-700"></p>

            <template x-if="todayAttendance">
                <div>
                    <!-- Case 1: Checked in via QR Code + WiFi -->
                    <template x-if="todayAttendance.checkin_method === 'qr_wifi'">
                        <div class="mt-5 grid gap-4 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 p-4 text-sm sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold text-emerald-800/80">Jam Masuk (WITA)</p>
                                <p class="mt-1 text-base font-extrabold text-emerald-950" x-text="time(todayAttendance.checked_in_at)"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-emerald-800/80">Metode & Router</p>
                                <p class="mt-1 font-bold text-emerald-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                    <span>Barcode WiFi Studio</span>
                                </p>
                                <p class="mt-0.5 text-xs text-emerald-700 font-mono" x-text="'IP: ' + (todayAttendance.client_ip || 'Terverifikasi')"></p>
                            </div>
                            <div class="flex items-center sm:justify-end">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    ✓ Terverifikasi WiFi Kantor
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Case 2: Checked in via GPS Coordinates -->
                    <template x-if="todayAttendance.checkin_method !== 'qr_wifi'">
                        <div class="mt-5 grid gap-4 rounded-2xl bg-brand-50/80 border border-brand-200/80 p-4 text-sm sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold text-brand-800/80">Jam Masuk (WITA)</p>
                                <p class="mt-1 text-base font-extrabold text-slate-900" x-text="time(todayAttendance.checked_in_at)"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-brand-800/80">Koordinat GPS</p>
                                <p class="mt-1 break-all font-mono text-xs font-medium" x-text="todayAttendance.latitude+', '+todayAttendance.longitude"></p>
                                <p class="mt-0.5 text-xs text-slate-500" x-text="'Akurasi ±'+Math.round(todayAttendance.accuracy_m)+' meter'"></p>
                            </div>
                            <div class="flex items-center sm:justify-end">
                                <a :href="mapUrl(todayAttendance)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-brand-200 text-brand-700 hover:bg-brand-50 transition">
                                    <span>Lihat lokasi di peta</span>
                                    <span>↗</span>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </section>

        @include('work-reports.form')

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 p-5 sm:p-6">
                <div>
                    <h2 class="text-lg font-bold">Kalender jadwalmu</h2>
                    <p class="mt-1 text-xs text-slate-500">Pilih tanggal untuk melihat sesi dan alat.</p>
                </div>
                <label>
                    <span class="sr-only">Bulan jadwal</span>
                    <input type="month" x-model="month" @change="load()" :disabled="loading" class="h-11 rounded-lg border border-slate-200 px-3 text-sm">
                </label>
            </div>
            <p x-show="loading" class="p-4 text-sm text-slate-500">Memuat jadwal…</p>
            <div class="grid grid-cols-7 bg-slate-50">
                <template x-for="day in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']">
                    <div class="py-3 text-center text-xs font-semibold text-slate-500" x-text="day"></div>
                </template>
            </div>
            <div class="grid grid-cols-7">
                <template x-for="cell in calendarCells" :key="cell.key">
                    <div class="min-w-0 border-t border-r border-slate-100">
                        <button type="button" x-show="cell.date" @click="selectedDate=cell.date" :aria-label="cell.date+(cell.attendance?', sudah masuk':'')" :aria-pressed="selectedDate===cell.date" :class="selectedDate===cell.date?'bg-brand-50 ring-2 ring-inset ring-brand-500':''" class="min-h-[88px] w-full p-2 text-left sm:min-h-[112px] sm:p-3">
                            <span class="text-sm font-bold" x-text="Number(cell.date?.slice(-2))"></span>
                            <span class="mt-2 block text-[10px] sm:text-xs" :class="cell.attendance?'font-semibold text-brand-700':'text-slate-500'" x-text="cell.attendance?'Masuk':(cell.sessions?.length?cell.sessions.length+' sesi':'Libur')"></span>
                            <span x-show="cell.custom" class="mt-1 hidden text-[10px] text-indigo-600 sm:block">Khusus</span>
                        </button>
                        <span x-show="!cell.date" class="block min-h-[88px] bg-slate-50 sm:min-h-[112px]"></span>
                    </div>
                </template>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
            <h2 class="font-bold" x-text="dateLabel(selectedDate)"></h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <template x-for="session in selectedDay.sessions" :key="session.id">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="font-semibold" x-text="'Sesi '+session.id+' · '+session.tool"></p>
                        <p class="mt-2 text-sm text-slate-500" x-text="session.start+'–'+session.end+' WITA'"></p>
                    </div>
                </template>
            </div>
            <p x-show="!selectedDay.sessions.length" class="mt-3 text-sm text-slate-500">Tidak ada sesi yang dijadwalkan untukmu.</p>
            <template x-if="selectedAttendance">
                <div class="mt-4 border-t border-slate-100 pt-4 text-sm">
                    <p>Masuk: <strong x-text="time(selectedAttendance.checked_in_at)"></strong></p>
                    <template x-if="selectedAttendance.checkin_method === 'qr_wifi'">
                        <p class="mt-2 text-emerald-700 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            <span>Terverifikasi Scan Barcode WiFi Studio</span>
                        </p>
                    </template>
                    <template x-if="selectedAttendance.checkin_method !== 'qr_wifi' && selectedAttendance.latitude">
                        <div>
                            <p class="mt-2 break-all text-slate-500" x-text="'GPS '+selectedAttendance.latitude+', '+selectedAttendance.longitude+' · akurasi ±'+Math.round(selectedAttendance.accuracy_m)+' m'"></p>
                            <a :href="mapUrl(selectedAttendance)" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block text-brand-700 underline">Lihat lokasi ↗</a>
                        </div>
                    </template>
                </div>
            </template>
        </section>

        <p class="text-xs leading-5 text-slate-500">
            Absensi dicatat sekali sehari dan tidak memerlukan checkout. Scan barcode poster di dinding studio dengan HP Anda yang terhubung ke WiFi kantor.
        </p>
    </main>

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
