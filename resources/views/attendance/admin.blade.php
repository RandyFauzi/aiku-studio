<header class="flex min-h-[80px] items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen=!sidebarOpen" aria-label="Buka menu" class="h-11 w-11 rounded-lg text-slate-600 hover:bg-slate-100 transition">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-semibold text-slate-700">Absensi kontributor</span>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
        <button type="button" @click="$dispatch('open-barcode-modal', { tab: 'qr' })" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-xs sm:text-sm font-bold text-emerald-800 transition">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span class="hidden sm:inline">Barcode Absensi & WiFi</span>
            <span class="sm:hidden">Barcode</span>
        </button>
        <a href="/attendance/barcode/poster" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span class="hidden sm:inline">Cetak Poster A4 ↗</span>
            <span class="sm:hidden">Poster</span>
        </a>
        <a href="/shifts" class="text-xs sm:text-sm font-semibold text-brand-700 hover:text-brand-800 transition">Atur jadwal →</a>
    </div>
</header>

<section class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6" x-data="adminAttendanceData()">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">Kehadiran tim</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Absensi masuk</h1>
            <p class="mt-2 text-sm text-slate-500">Rekap kehadiran masuk kontributor via Scan Barcode WiFi Studio atau GPS. Waktu WITA.</p>
        </div>
    </div>

    <!-- BANNER BARCODE ABSENSI WIFI KANTOR (OPSI 1) -->
    <div class="rounded-2xl border border-emerald-200/90 bg-gradient-to-r from-emerald-50/90 via-teal-50/80 to-emerald-50/60 p-4 sm:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xs">
        <div class="flex items-start sm:items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base">Barcode Absensi Studio & WiFi Kantor</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Sistem Aktif</span>
                </div>
                <p class="text-xs text-slate-600 mt-1 max-w-2xl leading-relaxed">
                    Kontributor wajib terhubung ke WiFi Studio untuk absen dengan scan barcode. Unduh barcode poster resmi untuk dicetak dan ditempel di studio.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto shrink-0">
            <a href="/attendance/barcode/poster" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Poster A4</span>
            </a>
            <button type="button" @click="$dispatch('open-barcode-modal', { tab: 'qr' })" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-2xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download Barcode</span>
            </button>
            <button type="button" @click="$dispatch('open-barcode-modal', { tab: 'wifi' })" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs sm:text-sm font-semibold transition" title="Konfigurasi IP WiFi Kantor">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                <span>Atur WiFi Kantor</span>
            </button>
        </div>
    </div>

    <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"></p>

    <div class="flex flex-col sm:flex-row gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <label class="w-full sm:w-auto text-xs font-semibold text-slate-600">
            Tanggal
            <input type="date" x-model="date" @change="load()" class="mt-2 block h-11 w-full sm:w-auto rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="w-full sm:flex-1 text-xs font-semibold text-slate-600">
            Cari nama
            <input type="search" x-model="search" placeholder="Nama kontributor…" class="mt-2 block h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <button type="button" @click="load()" class="w-full sm:w-auto sm:mt-auto h-11 rounded-lg border border-brand-200 px-4 text-sm font-semibold text-brand-700 hover:bg-brand-50 transition">
            Muat ulang
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="p-5">
            <h2 class="font-bold text-slate-900" x-text="dateLabel(date)"></h2>
            <p class="mt-1 text-sm text-slate-500" x-text="rows.length+' orang sudah masuk'"></p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500">
                    <tr>
                        <th class="p-4">Nama kontributor</th>
                        <th class="p-4">Jam masuk · WITA</th>
                        <th class="p-4">Metode Absensi</th>
                        <th class="p-4">Detail Lokasi / Jaringan</th>
                        <th class="p-4">Aksi / Peta</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in filtered" :key="row.id">
                        <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition">
                            <td class="p-4 font-semibold text-slate-900" x-text="row.contributor_name"></td>
                            <td class="p-4 tabular-nums" x-text="time(row.checked_in_at)"></td>
                            <td class="p-4">
                                <template x-if="row.checkin_method === 'qr_wifi'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                        <span>WiFi Studio</span>
                                    </span>
                                </template>
                                <template x-if="row.checkin_method !== 'qr_wifi'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800">
                                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>GPS Lokasi</span>
                                    </span>
                                </template>
                            </td>
                            <td class="p-4">
                                <template x-if="row.checkin_method === 'qr_wifi'">
                                    <div>
                                        <p class="font-mono text-xs font-semibold text-slate-700" x-text="'IP: ' + (row.client_ip || 'Terverifikasi')"></p>
                                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Router WiFi Kantor</p>
                                    </div>
                                </template>
                                <template x-if="row.checkin_method !== 'qr_wifi'">
                                    <div>
                                        <p class="font-mono text-xs text-slate-700" x-text="row.latitude ? (row.latitude+', '+row.longitude) : '—'"></p>
                                        <p class="mt-0.5 text-xs text-slate-500" x-text="row.accuracy_m ? ('±'+Math.round(row.accuracy_m)+' meter') : ''"></p>
                                    </div>
                                </template>
                            </td>
                            <td class="p-4">
                                <template x-if="row.checkin_method === 'qr_wifi'">
                                    <span class="text-xs font-medium text-slate-400">Scan Barcode</span>
                                </template>
                                <template x-if="row.checkin_method !== 'qr_wifi' && row.latitude">
                                    <a :href="mapUrl(row)" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 underline text-xs">
                                        Lihat peta ↗
                                    </a>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p x-show="loading" class="p-8 text-center text-sm text-slate-500">Memuat absensi…</p>
        <p x-show="!loading&&!filtered.length" class="p-8 text-center text-sm text-slate-500">Belum ada absensi yang cocok pada tanggal ini.</p>
    </div>
</section>

@include('attendance.barcode-modal')
