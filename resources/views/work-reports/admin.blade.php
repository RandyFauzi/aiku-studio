<header class="flex min-h-[80px] items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen=!sidebarOpen" aria-label="Buka menu" class="h-11 w-11 rounded-lg text-slate-600 hover:bg-slate-100 transition">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-semibold text-slate-700">Laporan kerja harian</span>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
        <a href="/attendance/barcode/poster" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span class="hidden sm:inline">Cetak Poster A4 ↗</span>
            <span class="sm:hidden">Poster</span>
        </a>
        <a href="/contributors" class="text-xs sm:text-sm font-semibold text-brand-700 hover:text-brand-800 transition">Teams & Kontributor →</a>
    </div>
</header>

<section class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

    <!-- DEDICATED LIVE QR CODE SECTION DIRECTLY ON PAGE -->
    <div x-data="liveQrCard()" x-init="init('work-report-page-qr-canvas')" class="rounded-3xl border-2 border-emerald-500/30 bg-gradient-to-br from-white via-emerald-50/40 to-teal-50/50 p-5 sm:p-7 shadow-sm">
        <div class="flex flex-col lg:flex-row items-center lg:items-start gap-6 lg:gap-8">
            
            <!-- PHYSICAL LIVE QR CODE DISPLAY FRAME -->
            <div class="shrink-0 flex flex-col items-center">
                <div class="p-3.5 bg-white rounded-3xl border-2 border-slate-900 shadow-lg relative">
                    <!-- Frame corner decorative marks -->
                    <div class="absolute -top-1 -left-1 w-4 h-4 border-t-4 border-l-4 border-emerald-500 rounded-tl"></div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 border-t-4 border-r-4 border-emerald-500 rounded-tr"></div>
                    <div class="absolute -bottom-1 -left-1 w-4 h-4 border-b-4 border-l-4 border-emerald-500 rounded-bl"></div>
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-4 border-r-4 border-emerald-500 rounded-br"></div>

                    <div class="w-48 h-48 sm:w-52 sm:h-52 flex items-center justify-center bg-white">
                        <canvas id="work-report-page-qr-canvas" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- Direct Action Buttons below QR Code -->
                <div class="flex items-center gap-2 mt-3.5 w-full justify-center">
                    <button type="button" @click="downloadPng()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh PNG</span>
                    </button>
                    <button type="button" @click="downloadSvg()" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold shadow-2xs transition">
                        <span>SVG</span>
                    </button>
                    <a href="/attendance/barcode/poster" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition" title="Buka format poster resmi A4">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak A4 ↗</span>
                    </a>
                </div>
            </div>

            <!-- Explanation & WiFi Management Controls -->
            <div class="flex-1 space-y-4 text-center lg:text-left">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>QR CODE ABSENSI MASUK — KHUSUS ROUTER WIFI KANTOR</span>
                    </div>
                    <h2 class="mt-2 text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        Barcode Presensi AIKu Studio
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-2xl">
                        Arahkan kamera HP ke barcode di samping untuk absensi masuk. Sistem memastikan kontributor <strong>wajib terhubung ke WiFi Kantor</strong> (anti-titip absen). Jika kontributor menggunakan data seluler dari luar kantor, sistem akan otomatis menolak absensi.
                    </p>
                </div>

                <!-- Encoded Direct URL box -->
                <div class="bg-white/90 border border-slate-200/90 rounded-2xl p-3 max-w-xl text-left shadow-2xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Link URL Encoded Barcode:</span>
                    <div class="flex items-center gap-2">
                        <input type="text" :value="qrUrl" readonly class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-600 select-all focus:outline-none">
                        <button type="button" @click="copyQrUrl()" class="shrink-0 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 transition flex items-center gap-1.5">
                            <template x-if="!copied">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <span>Salin</span>
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="text-emerald-700 font-bold">✓ Tersalin</span>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Status Badges & WiFi Control Button -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5 pt-1">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 shadow-2xs">
                        <span class="text-slate-400">Router Whitelist:</span>
                        <strong class="font-bold text-slate-900" x-text="(config.wifi_ips ? config.wifi_ips.length : 0) + ' IP Kantor'"></strong>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 shadow-2xs">
                        <span class="text-slate-400">IP Jaringan Anda:</span>
                        <strong class="font-mono text-emerald-800" x-text="currentIp || '—'"></strong>
                    </div>

                    <button type="button" @click="$dispatch('open-barcode-modal', { tab: 'wifi' })" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Atur WiFi Kantor & Reset Barcode</span>
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- REKAP TABLE LAPORAN KERJA -->
    <div x-data="adminWorkReportsData()" class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">Aktivitas tim</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Laporan kerja harian</h1>
            <p class="mt-2 text-sm text-slate-500">Rekap durasi dan HP yang dilaporkan kontributor dari akun masing-masing.</p>
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
    </div>
</section>

@include('attendance.barcode-modal')
