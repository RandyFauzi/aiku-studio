<!-- Barcode Absensi & WiFi Kantor Admin Modal -->
<div x-data="barcodeWifiModal()" x-cloak>
    <!-- Modal Backdrop & Container -->
    <div x-show="open" 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         @keydown.escape.window="closeModal()">

        <!-- Dialog Box -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @click.outside="closeModal()"
             class="bg-white rounded-3xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[92vh]">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-4 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#009B7E] flex items-center justify-center shrink-0 border border-emerald-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900">Barcode Absensi & WiFi Kantor</h2>
                        <p class="text-xs text-slate-500">Opsi 1: Poster Barcode Resmi & Verifikasi Router WiFi Studio</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Tab Navigation -->
            <div class="px-6 pt-3 border-b border-slate-100 flex items-center gap-2">
                <button type="button" 
                        @click="tab = 'qr'; $nextTick(() => renderQrCode())"
                        :class="tab === 'qr' ? 'border-[#009B7E] text-[#009B7E] font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                        class="px-4 py-2.5 text-xs sm:text-sm border-b-2 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Unduh Barcode & Poster</span>
                </button>
                <button type="button" 
                        @click="tab = 'wifi'"
                        :class="tab === 'wifi' ? 'border-[#009B7E] text-[#009B7E] font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                        class="px-4 py-2.5 text-xs sm:text-sm border-b-2 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                    <span>Pengaturan WiFi Kantor</span>
                    <span x-show="config.wifi_ips && config.wifi_ips.length" 
                          x-text="config.wifi_ips.length" 
                          class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-[#009B7E] font-bold"></span>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-6">

                <!-- Loading State -->
                <div x-show="loading" class="py-12 text-center text-slate-500 text-sm">
                    <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full mb-2"></div>
                    <p>Memuat konfigurasi Barcode & WiFi…</p>
                </div>

                <!-- TAB 1: BARCODE & POSTER -->
                <div x-show="!loading && tab === 'qr'" class="space-y-6">
                    
                    <div class="flex flex-col sm:flex-row items-center gap-6 bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80">
                        <!-- Canvas Container -->
                        <div class="shrink-0 p-3 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center">
                            <canvas id="admin-barcode-canvas" class="w-48 h-48 sm:w-52 sm:h-52"></canvas>
                            <span class="mt-2 text-[10px] font-mono text-slate-400 font-semibold" x-text="'TOKEN: ' + (config.qr_secret ? config.qr_secret.slice(0, 14) + '…' : '')"></span>
                        </div>

                        <!-- Info & Action Buttons -->
                        <div class="flex-1 space-y-3 text-center sm:text-left">
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-sm sm:text-base">Poster Barcode Siap Cetak & Pasang</h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                    Cetak poster A4 resmi untuk ditempel di pintu/dinding studio, atau unduh gambar Barcode dalam format PNG dan SVG untuk dicetak mandiri.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-1 justify-center sm:justify-start">
                                <!-- Print Poster Link -->
                                <a href="/attendance/barcode/poster" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>Cetak Poster A4 ↗</span>
                                </a>

                                <!-- Download PNG -->
                                <button type="button" @click="downloadPng()" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs sm:text-sm font-semibold shadow-2xs transition">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>Unduh PNG</span>
                                </button>

                                <!-- Download SVG -->
                                <button type="button" @click="downloadSvg()" class="inline-flex items-center gap-1.5 px-3 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs sm:text-sm font-semibold shadow-2xs transition">
                                    <span>Unduh SVG</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Direct QR URL Field -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Link Absensi Langsung (Encoded QR):</label>
                        <div class="flex items-center gap-2">
                            <input type="text" :value="qrUrl" readonly class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-600 select-all focus:outline-none">
                            <button type="button" @click="copyQrUrl()" class="shrink-0 px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition flex items-center gap-1.5">
                                <template x-if="!copied">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                        <span>Salin URL</span>
                                    </span>
                                </template>
                                <template x-if="copied">
                                    <span class="text-emerald-700 font-bold flex items-center gap-1">
                                        ✓ Tersalin!
                                    </span>
                                </template>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400">Kontributor dapat langsung scan dengan kamera bawaan HP (iPhone Camera / Android Lens) atau melalui Portal AIKu.</p>
                    </div>

                    <!-- Regenerate QR Danger Zone -->
                    <div class="border-t border-slate-100 pt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-slate-700">Perbarui / Reset Barcode</p>
                            <p class="text-[11px] text-slate-400">Gunakan jika barcode lama bocor atau ingin mengganti poster barcode fisik yang baru.</p>
                        </div>
                        <button type="button" @click="saveSettings(true)" :disabled="saving" class="shrink-0 px-3 py-2 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition disabled:opacity-50">
                            Regenerasi Barcode Baru
                        </button>
                    </div>

                </div>

                <!-- TAB 2: PENGATURAN WIFI KANTOR -->
                <div x-show="!loading && tab === 'wifi'" class="space-y-6">

                    <!-- WiFi Check Toggle -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-slate-900">Validasi WiFi Kantor (Wajib)</p>
                            <p class="text-xs text-slate-500 mt-0.5">Hanya izinkan absensi bila IP perangkat kontributor cocok dengan IP router WiFi kantor.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" x-model="config.wifi_check_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Current Detected IP Banner -->
                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>IP Jaringan Anda Saat Ini (Admin):</span>
                            </p>
                            <p class="font-mono text-sm font-extrabold text-emerald-950" x-text="currentIp || 'Mendeteksi IP…'"></p>
                            <p class="text-[11px] text-emerald-700">Jika Anda membuka halaman ini lewat WiFi kantor, IP di atas adalah IP Public router kantor Anda.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="addCurrentIp()" class="shrink-0 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                                + Tambahkan IP Ini
                            </button>
                            <button type="button" @click="addSubnetDynamic()" class="shrink-0 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-900 text-white rounded-xl text-xs font-bold shadow-xs transition" title="Daftarkan seluruh subnet /16 WiFi dinamis IndiHome/Telkom">
                                + Subnet Dinamis (*.*)
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic ISP / WiFi Guidance Note -->
                    <div class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200/80 text-[11px] text-amber-900 space-y-1">
                        <p class="font-bold flex items-center gap-1.5 text-amber-950">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Tips IP Dinamis ISP (IndiHome / FirstMedia):</span>
                        </p>
                        <p class="text-amber-800 leading-relaxed">
                            ISP IndiHome merotasi IP publik secara berkala saat modem restart. Sistem kini mendukung wildcard (contoh: <code class="font-mono font-bold bg-amber-100/70 px-1 py-0.5 rounded text-amber-900">140.213.*.*</code>) dan CIDR (<code class="font-mono font-bold bg-amber-100/70 px-1 py-0.5 rounded text-amber-900">140.213.0.0/16</code>) agar absensi tetap lancar. Anda juga dapat menyetel <strong>Titik Koordinat Studio (GPS)</strong> di bawah sebagai verifikasi cadangan jika koneksi HP beralih ke data seluler.
                        </p>
                    </div>

                    <!-- Whitelisted IPs List -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Daftar IP WiFi Kantor Resmi:
                            </label>
                            <span class="text-xs text-slate-400" x-text="(config.wifi_ips ? config.wifi_ips.length : 0) + ' IP terdaftar'"></span>
                        </div>

                        <!-- Manual Add Form -->
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="newIpInput" @keydown.enter.prevent="addManualIp()" placeholder="Masukkan IP (140.213.10.103), CIDR (140.213.0.0/16), atau Wildcard (140.213.*.*)" class="flex-1 bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs sm:text-sm font-mono focus:border-emerald-500 focus:outline-none">
                            <button type="button" @click="addManualIp()" class="shrink-0 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs sm:text-sm font-semibold transition">
                                Tambah IP
                            </button>
                        </div>

                        <!-- IP Chips / Table -->
                        <div class="space-y-2 mt-2">
                            <template x-if="!config.wifi_ips || config.wifi_ips.length === 0">
                                <div class="p-4 rounded-xl border border-dashed border-slate-300 text-center text-xs text-slate-400">
                                    Belum ada IP WiFi yang didaftarkan. Klik tombol "+ Tambahkan IP Ini" di atas bila Anda sedang terhubung ke WiFi kantor.
                                </div>
                            </template>

                            <template x-for="(ip, idx) in config.wifi_ips" :key="ip + idx">
                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100/70 transition">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        <span class="font-mono text-xs sm:text-sm font-semibold text-slate-800" x-text="ip"></span>
                                        <span x-show="ip === currentIp" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">IP Saat Ini</span>
                                    </div>
                                    <button type="button" @click="removeIp(idx)" class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 rounded-lg hover:bg-red-50 transition">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Studio Name Input for Poster -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Nama Studio / Kantor (Tampil di Poster):</label>
                        <input type="text" x-model="config.studio_name" placeholder="AIKu Studio" class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs sm:text-sm focus:border-emerald-500 focus:outline-none">
                    </div>

                    <!-- Geolocation / Geofence Section -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Titik Koordinat Studio (GPS & Geofence Radius)
                                </label>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Setiap kontributor yang scan barcode otomatis dicatat posisi GPS-nya. Jika koordinat studio diisi, sistem memvalidasi batas radius fisik.
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" @click="setOfficialStudioLocation()" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition">
                                    Reset ke Studio AIKu
                                </button>
                                <button type="button" @click="captureAdminCoordinates()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Ambil GPS Saya</span>
                                </button>
                            </div>
                        </div>

                        <!-- Studio Address & Location Banner -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-800">Alamat Studio AIKu:</p>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed" x-text="config.studio_address || 'Jl. Hikmah Banua VI No.144, Pemurus Luar, Kec. Banjarmasin Tim, Kota Banjarmasin, Kalimantan Selatan 70654'"></p>
                                </div>
                                <a :href="'https://maps.google.com/?q=' + (config.studio_lat || -3.3445020) + ',' + (config.studio_lng || 114.6325970)" target="_blank" class="shrink-0 text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center gap-1">
                                    <span>Buka Maps ↗</span>
                                </a>
                            </div>
                            <div class="flex items-center gap-2 pt-1 border-t border-slate-200/60 text-[11px]">
                                <span class="text-slate-500">Koordinat Resmi:</span>
                                <code class="font-mono font-bold text-slate-700">-3.3445020, 114.6325970</code>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Latitude</label>
                                <input type="number" step="any" x-model="config.studio_lat" placeholder="-3.3445020" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Longitude</label>
                                <input type="number" step="any" x-model="config.studio_lng" placeholder="114.6325970" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Radius Toleransi (Meter)</label>
                                <input type="number" min="20" max="5000" x-model="config.geofence_radius_m" placeholder="150" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="pt-3">
                        <button type="button" @click="saveSettings(false)" :disabled="saving" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-50">
                            <span x-show="saving" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                            <span x-text="saving ? 'Menyimpan Pengaturan…' : 'Simpan Pengaturan WiFi'"></span>
                        </button>
                    </div>

                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs text-slate-500">
                <span>AIKu Studio Attendance System</span>
                <button type="button" @click="closeModal()" class="font-semibold text-slate-600 hover:text-slate-800 transition">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
