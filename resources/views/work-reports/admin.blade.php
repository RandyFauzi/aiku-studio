<header class="flex min-h-[80px] items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen=!sidebarOpen" aria-label="Buka menu" class="h-11 w-11 rounded-lg text-slate-600 hover:bg-slate-100 transition">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-semibold text-slate-700">Laporan kerja harian</span>
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
        <a href="/contributors" class="text-xs sm:text-sm font-semibold text-brand-700 hover:text-brand-800 transition">Teams & Kontributor →</a>
    </div>
</header>

<section x-data="adminWorkReportsData()" class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">Aktivitas tim</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Laporan kerja harian</h1>
            <p class="mt-2 text-sm text-slate-500">Rekap durasi dan HP yang dilaporkan kontributor dari akun masing-masing.</p>
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
                    Kontributor wajib terhubung ke WiFi Studio untuk absen dengan scan barcode. Unduh barcode poster resmi untuk dicetak dan ditempel di dinding studio.
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
        <button type="button" @click="load()" :disabled="loading" class="w-full sm:w-auto sm:mt-auto h-11 rounded-lg border border-brand-200 px-4 text-sm font-semibold text-brand-700 hover:bg-brand-50 transition">
            Muat ulang
        </button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs text-slate-500">Laporan diterima pada tanggal ini</p>
            <p class="mt-2 text-2xl font-bold" x-text="rows.length+' orang'"></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs text-slate-500">Total durasi dilaporkan</p>
            <p class="mt-2 text-2xl font-bold text-brand-700" x-text="duration(total)"></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="p-5">
            <h2 class="font-bold" x-text="dateLabel(date)"></h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[540px] text-left text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500">
                    <tr>
                        <th class="p-4">Nama kontributor</th>
                        <th class="p-4">Total durasi</th>
                        <th class="p-4">HP yang dipakai</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="report in filtered" :key="report.id">
                        <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition">
                            <td class="p-4 font-semibold" x-text="report.contributor_name"></td>
                            <td class="p-4 font-semibold text-brand-700" x-text="duration(report.total_duration_minutes)"></td>
                            <td class="p-4" x-text="phoneNames(report.phones)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p x-show="loading" class="p-8 text-center text-sm text-slate-500">Memuat laporan…</p>
        <p x-show="!loading&&!filtered.length" class="p-8 text-center text-sm text-slate-500">Belum ada laporan yang cocok pada tanggal ini.</p>
    </div>
</section>

@include('attendance.barcode-modal')
