<header class="sticky top-0 z-20 flex min-h-[80px] shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen=!sidebarOpen" aria-label="Buka menu" class="h-11 w-11 rounded-lg text-slate-600 hover:bg-slate-100 transition">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-semibold text-slate-700">Absensi kontributor</span>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
        <a href="/attendance/barcode/poster" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span class="hidden sm:inline">Cetak Poster A4 ↗</span>
            <span class="sm:hidden">Poster</span>
        </a>
        <a href="/shifts" class="text-xs sm:text-sm font-semibold text-brand-700 hover:text-brand-800 transition">Atur jadwal →</a>
    </div>
</header>

<section class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

    <!-- DEDICATED LIVE QR CODE SECTION DIRECTLY ON ATTENDANCE PAGE -->
    <div x-data="liveQrCard()" x-init="init('attendance-page-qr-canvas')" class="rounded-3xl border-2 border-emerald-500/30 bg-gradient-to-br from-white via-emerald-50/40 to-teal-50/50 p-5 sm:p-7 shadow-sm">
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
                        <canvas id="attendance-page-qr-canvas" class="w-full h-full"></canvas>
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
                <div class="space-y-3 pt-1">
                    <!-- If WiFi is NOT set yet -->
                    <template x-if="!config.wifi_ips || config.wifi_ips.length === 0">
                        <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-left">
                            <div class="text-xs">
                                <span class="font-extrabold flex items-center gap-1.5 text-amber-900">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    <span>WiFi Kantor Belum Dikunci!</span>
                                </span>
                                <span class="block text-amber-800 text-[11px] mt-0.5">
                                    HP pekerja tidak akan bisa absen sampai IP WiFi Kantor didaftarkan. IP Admin saat ini: <strong class="font-mono" x-text="currentIp"></strong>.
                                </span>
                            </div>
                            <button type="button" @click="setOfficeIpNow()" :disabled="loading" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shrink-0 transition shadow-xs flex items-center gap-1">
                                <span>Kunci IP Ini Sebagai WiFi Kantor</span>
                            </button>
                        </div>
                    </template>

                    <!-- If WiFi IS set -->
                    <template x-if="config.wifi_ips && config.wifi_ips.length > 0">
                        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-emerald-200 text-xs text-slate-700 shadow-2xs">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-slate-400">WiFi Terdaftar:</span>
                                <strong class="font-mono text-emerald-900" x-text="config.wifi_ips.join(', ')"></strong>
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-700 shadow-2xs">
                                <span class="text-slate-400">IP Anda:</span>
                                <strong class="font-mono" :class="config.wifi_ips.includes(currentIp) ? 'text-emerald-700' : 'text-slate-700'" x-text="currentIp || '—'"></strong>
                            </div>

                            <button type="button" @click="$dispatch('open-barcode-modal', { tab: 'wifi' })" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Kelola WiFi & Reset Barcode</span>
                            </button>
                        </div>
                    </template>
                </div>

            </div>

        </div>
    </div>

    <!-- REKAP TABLE ATTENDANCE -->
    <div x-data="adminAttendanceData()" class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">Kehadiran tim</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Rekap Absensi Masuk Harian</h1>
            <p class="mt-2 text-sm text-slate-500">Rekap kehadiran masuk kontributor via Scan Barcode WiFi Studio atau GPS. Waktu WITA.</p>
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
            <div class="flex items-center gap-2 w-full sm:w-auto sm:mt-auto">
                <button type="button" @click="load()" class="flex-1 sm:flex-none h-11 rounded-lg border border-brand-200 px-4 text-sm font-semibold text-brand-700 hover:bg-brand-50 transition cursor-pointer">
                    Muat ulang
                </button>
                <button type="button" @click="openManualModal()" class="flex-1 sm:flex-none h-11 inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 text-sm font-bold text-white shadow-xs transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Absensi Manual</span>
                </button>
            </div>
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
                                        <div>
                                            <template x-if="row.latitude && Number(row.latitude) !== 0">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                                    <span>WiFi Studio + GPS</span>
                                                </span>
                                            </template>
                                            <template x-if="!row.latitude || Number(row.latitude) === 0">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                                    <span>WiFi Studio</span>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="row.checkin_method === 'manual'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200/60 shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span>Manual Admin</span>
                                        </span>
                                    </template>
                                    <template x-if="row.checkin_method !== 'qr_wifi' && row.checkin_method !== 'manual'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>GPS Lokasi</span>
                                        </span>
                                    </template>
                                </td>
                                <td class="p-4">
                                    <template x-if="row.checkin_method === 'manual'">
                                        <div>
                                            <p class="font-semibold text-xs text-indigo-900" x-text="row.client_ip || 'Manual Admin'"></p>
                                            <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Diverifikasi oleh Admin</p>
                                        </div>
                                    </template>
                                    <template x-if="row.checkin_method === 'qr_wifi'">
                                        <div>
                                            <p class="font-mono text-xs font-semibold text-slate-700" x-text="'IP: ' + (row.client_ip || 'Terverifikasi')"></p>
                                            <template x-if="row.latitude && Number(row.latitude) !== 0">
                                                <div class="mt-1">
                                                    <p class="font-mono text-[11px] text-slate-600" x-text="'GPS: ' + row.latitude + ', ' + row.longitude"></p>
                                                    <p class="text-[10px] text-slate-400" x-text="row.accuracy_m ? ('±' + Math.round(row.accuracy_m) + ' meter') : ''"></p>
                                                </div>
                                            </template>
                                            <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Router WiFi Kantor</p>
                                        </div>
                                    </template>
                                    <template x-if="row.checkin_method !== 'qr_wifi' && row.checkin_method !== 'manual'">
                                        <div>
                                            <p class="font-mono text-xs text-slate-700" x-text="row.latitude ? (row.latitude+', '+row.longitude) : '—'"></p>
                                            <p class="mt-0.5 text-xs text-slate-500" x-text="row.accuracy_m ? ('±'+Math.round(row.accuracy_m)+' meter') : ''"></p>
                                        </div>
                                    </template>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <template x-if="row.latitude && Number(row.latitude) !== 0">
                                            <a :href="mapUrl(row)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 font-bold text-xs transition shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Lihat peta ↗</span>
                                            </a>
                                        </template>
                                        <template x-if="!row.latitude || Number(row.latitude) === 0">
                                            <span class="text-xs text-slate-400">Tanpa GPS</span>
                                        </template>
                                        
                                        <!-- Tombol Hapus Absensi untuk Admin -->
                                        <button type="button" 
                                                @click="deleteAttendance(row)" 
                                                title="Hapus data absensi ini"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="loading" class="p-8 text-center text-sm text-slate-500">Memuat absensi…</p>
            <p x-show="!loading&&!filtered.length" class="p-8 text-center text-sm text-slate-500">Belum ada absensi yang cocok pada tanggal ini.</p>
        </div>

        <!-- MODAL TAMBAH ABSENSI MANUAL -->
        <div x-show="showManualModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
             @keydown.escape.window="closeManualModal()">
            
            <div class="bg-white rounded-[28px] border border-slate-200 w-full max-w-lg overflow-hidden shadow-2xl flex flex-col"
                 @click.outside="closeManualModal()">
                
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Tambah Absensi Manual</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Bantu absensi kontributor yang mengalami kendala QR atau GPS.</p>
                    </div>
                    <button type="button" @click="closeManualModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form @submit.prevent="saveManualAttendance()" class="p-6 space-y-4">
                    <!-- Error Alert -->
                    <div x-show="manualError" x-cloak class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                        <span x-text="manualError"></span>
                    </div>

                    <!-- Pilih Kontributor -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pilih Kontributor <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="manualForm.contributor_id" 
                                required
                                class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition cursor-pointer">
                            <option value="">-- Pilih Kontributor --</option>
                            <template x-for="c in contributors" :key="c.id || c.contributor_id">
                                <option :value="c.id || c.contributor_id" x-text="(c.nama || c.name) + (c.shift ? ' (' + c.shift + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Tanggal Kerja & Jam Masuk (Grid 2 kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Tanggal Kerja <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" 
                                   x-model="manualForm.work_date" 
                                   required
                                   class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Jam Masuk (WITA) <span class="text-rose-500">*</span>
                            </label>
                            <input type="time" 
                                   step="1"
                                   x-model="manualForm.time" 
                                   required
                                   class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm font-mono font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition">
                        </div>
                    </div>

                    <!-- Catatan / Keterangan Alasan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Catatan / Alasan Manual <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <input type="text" 
                               x-model="manualForm.notes" 
                               placeholder="Contoh: Kendala scan QR WiFi / GPS HP bermasalah" 
                               maxlength="200"
                               class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition">
                    </div>

                    <!-- Info Box -->
                    <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl flex items-start gap-2.5 text-xs text-amber-800">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="leading-relaxed">
                            Absensi manual akan langsung tercatat sebagai hadir dan terverifikasi untuk tanggal kerja terkait. Pastikan kontributor benar-benar hadir.
                        </p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" 
                                @click="closeManualModal()"
                                :disabled="manualSaving"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer disabled:opacity-50">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="manualSaving"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition cursor-pointer disabled:opacity-50">
                            <span x-show="manualSaving" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span x-text="manualSaving ? 'Menyimpan...' : 'Simpan Absensi'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>

@include('attendance.barcode-modal')
