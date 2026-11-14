<!-- ========================================================================= -->
<!-- JADWAL SHIFT OPERASIONAL STUDIO TERBARU (10 SESI + MIDNIGHT + LIBUR)    -->
<!-- ========================================================================= -->
<div x-data="weeklyMatrixAdmin()" x-init="init()" class="space-y-6">

    <!-- TOP HEADER & CONTROLS -->
    <div class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-100 text-[#00875A] font-bold text-base shadow-2xs">
                    📅
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">Jadwal Shift Operasional Studio (Terbaru)</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Matriks 10 sesi kerja harian (08:00 – 23:00 WITA) + Sesi Midnight, jadwal libur rutin, dan aturan operasional studio.
                    </p>
                </div>
            </div>

            <!-- Quick Badges Specs -->
            <div class="flex items-center gap-2 flex-wrap mt-3 text-xs">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700 font-semibold border border-slate-200/80 text-[11px]">
                    ⏱️ Format Sesi: 90 Menit (1j Rekam + 30m Prep/Cas)
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700 font-semibold border border-slate-200/80 text-[11px]">
                    📱 Pengaturan: 1 Alat · 6 HP / orang
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/80 text-[11px]">
                    🎯 Target: 6 Jam Video / orang
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-800 font-semibold border border-indigo-200/80 text-[11px]">
                    🌙 Midnight: 23:00 – Subuh
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-50 text-rose-800 font-semibold border border-rose-200/80 text-[11px]">
                    ☕ Senin: Libur Studio (Off)
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap w-full lg:w-auto justify-end">
            <!-- Salin Format WA -->
            <button type="button" 
                    @click="copyWhatsApp()" 
                    class="h-10 px-3.5 rounded-xl bg-white border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <span>Salin Format WA</span>
            </button>

            <!-- Reset -->
            <button type="button" 
                    @click="resetToDefault()" 
                    class="h-10 px-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition shadow-2xs cursor-pointer"
                    title="Kembalikan ke susunan standar awal">
                ↺ Reset Default
            </button>

            <!-- Simpan Perubahan -->
            <button type="button" 
                    @click="saveToServer()" 
                    :disabled="saving"
                    class="h-10 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span x-text="saving ? 'Menyimpan…' : 'Simpan ke Server'"></span>
            </button>
        </div>
    </div>

    <!-- TOAST -->
    <div x-show="toastMessage" 
         x-cloak 
         x-transition 
         class="rounded-2xl border border-emerald-300 bg-emerald-50 text-emerald-900 px-4 py-3 text-xs font-bold flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span x-text="toastMessage"></span>
        </div>
        <button type="button" @click="toastMessage = ''" class="text-emerald-700 hover:text-emerald-900 font-bold ml-2 cursor-pointer">✕</button>
    </div>

    <!-- MAIN MATRIX TABLE (RESPONSIVE TABLE) -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-xs">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm sm:text-base">Tabel Matriks Shift Mingguan</h3>
                <p class="text-xs text-slate-500">Klik nama pada sel tabel untuk mengubah penugasan pekerja pada sesi dan hari tersebut.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-bold text-slate-600">10 Sesi Operasional + 2 Sesi Midnight</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[980px]">
                <thead>
                    <tr class="bg-slate-100/80 border-b border-slate-200 text-xs font-black text-slate-700 uppercase tracking-wider">
                        <th class="py-3.5 px-4 w-40 text-center">Jadwal / Jam</th>
                        <th class="py-3.5 px-3 text-center">Selasa</th>
                        <th class="py-3.5 px-3 text-center">Rabu</th>
                        <th class="py-3.5 px-3 text-center">Kamis</th>
                        <th class="py-3.5 px-3 text-center">Jumat</th>
                        <th class="py-3.5 px-3 text-center">Sabtu</th>
                        <th class="py-3.5 px-3 text-center">Minggu</th>
                        <th class="py-3.5 px-3 text-center text-rose-700 bg-rose-50/60 w-28">Senin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <!-- SESI 1 s/d 10 -->
                    <template x-for="(session, sIndex) in scheduleData.sessions" :key="session.id">
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Kolom Jam & Sesi -->
                            <td class="py-3 px-4 font-bold text-slate-900 bg-slate-50/40 border-r border-slate-100 text-center">
                                <div class="font-mono text-[11px] font-black text-emerald-800" x-text="session.time"></div>
                                <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider" x-text="session.shift"></div>
                            </td>

                            <!-- Selasa s/d Minggu -->
                            <template x-for="dayKey in ['selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']" :key="dayKey">
                                <td class="py-2.5 px-2 text-center align-middle">
                                    <div class="relative group">
                                        <select :value="session.days[dayKey]" 
                                                @change="updateCell(sIndex, dayKey, $event.target.value)"
                                                class="w-full text-center py-1.5 px-2 rounded-xl text-xs font-bold border transition cursor-pointer appearance-none focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs"
                                                :class="session.days[dayKey] ? 'bg-white hover:bg-emerald-50/40 text-slate-800 border-slate-200/90' : 'bg-slate-50 text-slate-400 border-dashed border-slate-200'">
                                            <option value="">— Kosong —</option>
                                            <template x-for="w in allWorkers" :key="w.name">
                                                <option :value="w.name" :selected="session.days[dayKey] === w.name" x-text="w.short + ' (' + w.name + ')'"></option>
                                            </template>
                                        </select>
                                    </div>
                                </td>
                            </template>

                            <!-- Kolom Senin (LIBUR) -->
                            <td class="py-2.5 px-2 text-center align-middle bg-rose-50/40 border-l border-rose-100">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-rose-100/90 text-rose-800 font-black text-[11px] border border-rose-200">
                                    LIBUR
                                </span>
                            </td>
                        </tr>
                    </template>

                    <!-- BARIS PEMISAH MIDNIGHT -->
                    <tr class="bg-indigo-900 text-white text-xs font-black tracking-wider uppercase">
                        <td colspan="8" class="py-2 px-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span>🌙 Sesi Midnight (23:00 – Subuh WITA)</span>
                                <span class="text-[10px] text-indigo-200 font-normal">Operasional dini hari tim studio</span>
                            </div>
                            <span class="text-[10px] text-indigo-200">2 Petugas Standby</span>
                        </td>
                    </tr>

                    <!-- MIDNIGHT ROWS (Rio & Afif) -->
                    <template x-for="(mid, mIndex) in scheduleData.midnight" :key="mid.id">
                        <tr class="bg-indigo-50/30 hover:bg-indigo-50/60 transition border-t border-indigo-100">
                            <!-- Kolom Label Midnight -->
                            <td class="py-3 px-4 font-bold text-indigo-950 bg-indigo-50/50 border-r border-indigo-100 text-center">
                                <div class="font-mono text-[11px] font-black text-indigo-900" x-text="mid.time"></div>
                                <div class="text-[10px] text-indigo-600 font-bold uppercase tracking-wider" x-text="mid.role"></div>
                            </td>

                            <!-- Selasa s/d Minggu -->
                            <template x-for="dayKey in ['selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']" :key="dayKey">
                                <td class="py-2.5 px-2 text-center align-middle">
                                    <div class="relative group">
                                        <select :value="mid.days[dayKey]" 
                                                @change="updateMidnightCell(mIndex, dayKey, $event.target.value)"
                                                class="w-full text-center py-1.5 px-2 rounded-xl text-xs font-bold border transition cursor-pointer appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-2xs bg-white text-indigo-950 border-indigo-200/90 hover:bg-indigo-50">
                                            <option value="">— Kosong —</option>
                                            <template x-for="w in allWorkers" :key="w.name">
                                                <option :value="w.name" :selected="mid.days[dayKey] === w.name" x-text="w.short + ' (' + w.name + ')'"></option>
                                            </template>
                                        </select>
                                    </div>
                                </td>
                            </template>

                            <!-- Kolom Senin (LIBUR) -->
                            <td class="py-2.5 px-2 text-center align-middle bg-rose-50/40 border-l border-rose-100">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-rose-100/90 text-rose-800 font-black text-[11px] border border-rose-200">
                                    LIBUR
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2 COLUMN GRID: JADWAL LIBUR & ATURAN OPERASIONAL -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- CARD 1: JADWAL LIBUR ANGGOTA -->
        <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-amber-100 text-amber-900 font-bold text-xs">
                        🏖️
                    </span>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Jadwal Libur Rutin Anggota</h3>
                        <p class="text-[11px] text-slate-500">Hari libur mingguan yang telah ditetapkan per pekerja.</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200/80">
                    Roster Rotasi
                </span>
            </div>

            <!-- List Hari Libur -->
            <div class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex items-center justify-between">
                    <span class="font-bold text-slate-800 w-24">Selasa</span>
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Nuga (Muhammad Nugraha Dwiputra)</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Aida</span>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="font-bold text-slate-800 w-24">Rabu</span>
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Fai</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Aida</span>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="font-bold text-slate-800 w-24">Kamis</span>
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Fahri</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Aida</span>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="font-bold text-slate-800 w-24">Jumat</span>
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Yoga (Muhammad Diyoga)</span>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="font-bold text-slate-800 w-24">Sabtu</span>
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Rahmat (Rahmatullah)</span>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="font-bold text-slate-800 w-24">Minggu</span>
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Maryam (Siti Mariyam)</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 font-bold">Ghina (Ghina Mawaddah)</span>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between bg-rose-50/50 px-2 rounded-xl">
                    <span class="font-bold text-rose-900 w-24">Senin</span>
                    <span class="px-2.5 py-0.5 rounded-lg bg-rose-100 text-rose-800 font-black">Semua Anggota (Libur Studio)</span>
                </div>
            </div>
        </div>

        <!-- CARD 2: CATATAN & ATURAN OPERASIONAL -->
        <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-blue-100 text-blue-900 font-bold text-xs">
                        📌
                    </span>
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Catatan Operasional Studio</h3>
                        <p class="text-[11px] text-slate-500">Aturan wajib yang harus dipatuhi oleh seluruh anggota shift.</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold text-blue-800 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200/80">
                    SOP Wajib
                </span>
            </div>

            <!-- Notes List -->
            <div class="space-y-3 text-xs leading-relaxed">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-amber-950">
                    <span class="text-base leading-none">🛑</span>
                    <div>
                        <strong class="font-extrabold block">Kalo jamnya sudah lewat, Harus Stop</strong>
                        <p class="text-[11px] text-amber-900 mt-0.5">Jangan melanjutkan rekaman melampaui batas jam shift agar tidak mengganggu sesi rekan berikutnya.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-emerald-950">
                    <span class="text-base leading-none">⏱️</span>
                    <div>
                        <strong class="font-extrabold block">Selesaikan Sesi 1 jam baru Istirahat</strong>
                        <p class="text-[11px] text-emerald-900 mt-0.5">Waktu 90 menit terdiri dari 60 menit rekam aktif, sisa 30 menit untuk persiapan, cas HP, dan istirahat.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-blue-50/70 border border-blue-200/80 text-blue-950">
                    <span class="text-base leading-none">⚡</span>
                    <div>
                        <strong class="font-extrabold block">Setelah selesai, Wajib cas dan Upload</strong>
                        <p class="text-[11px] text-blue-900 mt-0.5">Pastikan 6 HP kembali terisi daya dengan aman dan video hasil rekaman telah terunggah ke Google Drive.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/70 border border-purple-200/80 text-purple-950">
                    <span class="text-base leading-none">📋</span>
                    <div>
                        <strong class="font-extrabold block">Task Harus Sesuai dengan List yang ditentukan</strong>
                        <p class="text-[11px] text-purple-900 mt-0.5">Perekaman video wajib mengikuti antrean judul dan ID task yang telah ditentukan oleh admin.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- LOGIC JAVASCRIPT MATRIX ADMIN -->
<script>
function weeklyMatrixAdmin() {
    return {
        saving: false,
        toastMessage: '',
        
        allWorkers: [
            { name: 'Aufa Anugraha', short: 'Aufa' },
            { name: 'Muhamad Zikri Fadhilah', short: 'Zikri' },
            { name: 'Muhammad Diyoga', short: 'Yoga' },
            { name: 'Rahmatullah', short: 'Rahmat' },
            { name: 'Ghina Mawaddah', short: 'Ghina' },
            { name: 'Siti Mariyam', short: 'Maryam' },
            { name: 'Afif Anshari', short: 'Afif' },
            { name: 'Sasa', short: 'Sasa' },
            { name: 'Fai', short: 'Fai' },
            { name: 'Fahri', short: 'Fahri' },
            { name: 'Muhammad Nugraha Dwiputra', short: 'Nuga' },
            { name: 'Aida', short: 'Aida' },
            { name: 'Muhammad Rio Hadi', short: 'Rio' }
        ],

        scheduleData: {
            sessions: [],
            midnight: []
        },

        async init() {
            await this.loadSchedule();
        },

        async loadSchedule() {
            try {
                const res = await fetch('/api/shifts/weekly-schedule');
                if (res.ok) {
                    const data = await res.json();
                    if (data.schedule && data.schedule.sessions) {
                        this.scheduleData = data.schedule;
                        return;
                    }
                }
            } catch (e) {}

            // Fallback default
            this.resetToDefault();
        },

        updateCell(sIndex, dayKey, newName) {
            if (this.scheduleData.sessions[sIndex]) {
                this.scheduleData.sessions[sIndex].days[dayKey] = newName || null;
            }
        },

        updateMidnightCell(mIndex, dayKey, newName) {
            if (this.scheduleData.midnight[mIndex]) {
                this.scheduleData.midnight[mIndex].days[dayKey] = newName || null;
            }
        },

        async saveToServer() {
            this.saving = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/shifts/weekly-schedule', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify({
                        schedule: this.scheduleData
                    })
                });

                if (res.ok) {
                    this.showToast('Jadwal operasional mingguan berhasil disimpan ke server!');
                } else {
                    throw new Error('Gagal menyimpan jadwal ke server.');
                }
            } catch (e) {
                this.showToast('Gagal menyimpan perubahan ke server: ' + e.message);
            } finally {
                this.saving = false;
            }
        },

        resetToDefault() {
            this.scheduleData = {
                operational_info: {
                    session_duration: "90 Menit per sesi (1 Jam Rekaman + 30 Menit Prep/Cas)",
                    device_setup: "1 Alat · 6 HP per orang",
                    day_hours: "08:00 – 23:00 WITA (10 Sesi)",
                    midnight_hours: "23:00 – Subuh WITA (Midnight)",
                    target_video: "6 Jam Video / orang per sesi"
                },
                notes: [
                    "Kalo jamnya sudah lewat, Harus Stop",
                    "Selesaikan Sesi 1 jam baru Istirahat",
                    "Setelah selesai, Wajib cas dan Upload",
                    "Task Harus Sesuai dengan List yang ditentukan"
                ],
                days_off: {
                    selasa: ["Muhammad Nugraha Dwiputra", "Aida"],
                    rabu: ["Fai", "Aida"],
                    kamis: ["Fahri", "Aida"],
                    jumat: ["Muhammad Diyoga"],
                    sabtu: ["Rahmatullah"],
                    minggu: ["Siti Mariyam", "Ghina Mawaddah"],
                    senin: ["Semua Anggota (Libur Studio)"]
                },
                sessions: [
                    { id: 1, shift: "Shift 1", time: "08:00 – 09:30", days: { selasa: "Aufa Anugraha", rabu: "Aufa Anugraha", kamis: "Aufa Anugraha", jumat: "Aufa Anugraha", sabtu: "Aufa Anugraha", minggu: "Aufa Anugraha", senin: null } },
                    { id: 2, shift: "Shift 2", time: "09:31 – 11:00", days: { selasa: "Muhamad Zikri Fadhilah", rabu: "Muhamad Zikri Fadhilah", kamis: "Muhamad Zikri Fadhilah", jumat: "Muhamad Zikri Fadhilah", sabtu: "Muhamad Zikri Fadhilah", minggu: "Muhamad Zikri Fadhilah", senin: null } },
                    { id: 3, shift: "Shift 3", time: "11:01 – 12:30", days: { selasa: "Muhammad Diyoga", rabu: "Muhammad Diyoga", kamis: "Muhammad Diyoga", jumat: "Aida", sabtu: "Aida", minggu: "Aida", senin: null } },
                    { id: 4, shift: "Shift 4", time: "12:31 – 14:00", days: { selasa: "Rahmatullah", rabu: "Rahmatullah", kamis: "Rahmatullah", jumat: "Rahmatullah", sabtu: "Muhammad Diyoga", minggu: "Muhammad Diyoga", senin: null } },
                    { id: 5, shift: "Shift 5", time: "14:01 – 15:30", days: { selasa: "Ghina Mawaddah", rabu: "Ghina Mawaddah", kamis: "Ghina Mawaddah", jumat: "Siti Mariyam", sabtu: "Siti Mariyam", minggu: "Rahmatullah", senin: null } },
                    { id: 6, shift: "Shift 6", time: "15:31 – 17:00", days: { selasa: "Siti Mariyam", rabu: "Siti Mariyam", kamis: "Siti Mariyam", jumat: "Ghina Mawaddah", sabtu: "Ghina Mawaddah", minggu: "Sasa", senin: null } },
                    { id: 7, shift: "Shift 7", time: "17:01 – 18:30", days: { selasa: "Afif Anshari", rabu: "Afif Anshari", kamis: "Afif Anshari", jumat: "Sasa", sabtu: "Sasa", minggu: "Muhammad Nugraha Dwiputra", senin: null } },
                    { id: 8, shift: "Shift 8", time: "18:31 – 20:00", days: { selasa: "Sasa", rabu: "Sasa", kamis: "Sasa", jumat: "Muhammad Nugraha Dwiputra", sabtu: "Muhammad Nugraha Dwiputra", minggu: "Fahri", senin: null } },
                    { id: 9, shift: "Shift 9", time: "20:01 – 21:30", days: { selasa: "Fai", rabu: "Muhammad Nugraha Dwiputra", kamis: "Muhammad Nugraha Dwiputra", jumat: "Fai", sabtu: "Fahri", minggu: "Muhammad Rio Hadi", senin: null } },
                    { id: 10, shift: "Shift 10", time: "21:31 – 23:00", days: { selasa: "Fahri", rabu: "Fahri", kamis: "Fai", jumat: "Fahri", sabtu: "Fai", minggu: "Afif Anshari", senin: null } }
                ],
                midnight: [
                    { id: "mid-1", role: "Midnight 1", time: "23:00 – Subuh", days: { selasa: "Muhammad Rio Hadi", rabu: "Muhammad Rio Hadi", kamis: "Muhammad Rio Hadi", jumat: "Muhammad Rio Hadi", sabtu: "Muhammad Rio Hadi", minggu: "Muhammad Rio Hadi", senin: null } },
                    { id: "mid-2", role: "Midnight 2", time: "23:00 – Subuh", days: { selasa: "Afif Anshari", rabu: "Afif Anshari", kamis: "Afif Anshari", jumat: "Afif Anshari", sabtu: "Afif Anshari", minggu: "Afif Anshari", senin: null } }
                ]
            };
            this.showToast('Jadwal dikembalikan ke susunan bawaan.');
        },

        copyWhatsApp() {
            let text = "JADWAL SHIFT OPERASIONAL AIKU STUDIO\n";
            text += "* Format Sesi: 90 Menit per sesi (1 Jam Rekaman + 30m Prep)\n";
            text += "* Pengaturan: 1 Alat · 6 HP per orang\n";
            text += "* Jam Operasional: 08:00 – 23:00 WITA (10 Sesi) + Midnight\n";
            text += "* Output Video: 6 Jam Video/orang/sesi\n\n";

            text += "========================================\n";
            text += "ATURAN / NOTE OPERASIONAL:\n";
            text += "1. Kalo jamnya sudah lewat, Harus Stop\n";
            text += "2. Selesaikan Sesi 1 jam baru Istirahat\n";
            text += "3. Setelah selesai, Wajib cas dan Upload\n";
            text += "4. Task Harus Sesuai dengan List yang ditentukan\n";
            text += "========================================\n\n";

            text += "JADWAL LIBUR MINGGUAN:\n";
            text += "- Selasa: Nuga, Aida\n";
            text += "- Rabu: Fai, Aida\n";
            text += "- Kamis: Fahri, Aida\n";
            text += "- Jumat: Yoga\n";
            text += "- Sabtu: Rahmat\n";
            text += "- Minggu: Maryam, Ghina\n";
            text += "- Senin: LIBUR BERSAMA STUDIO\n\n";

            text += "SESI SHIFT HARIAN (Selasa - Minggu):\n";
            this.scheduleData.sessions.forEach(s => {
                text += `• ${s.shift} (${s.time}):\n`;
                text += `  Sel: ${s.days.selasa || '-'} | Rab: ${s.days.rabu || '-'} | Kam: ${s.days.kamis || '-'} | Jum: ${s.days.jumat || '-'} | Sab: ${s.days.sabtu || '-'} | Min: ${s.days.minggu || '-'}\n`;
            });

            text += "\nSESI MIDNIGHT (23:00 – Subuh):\n";
            this.scheduleData.midnight.forEach(m => {
                text += `• ${m.role} (${m.time}):\n`;
                text += `  Sel-Min: ${m.days.selasa || '-'}\n`;
            });

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text.trim()).then(() => {
                    this.showToast('Jadwal format WhatsApp berhasil disalin!');
                }).catch(() => {
                    this.fallbackCopy(text.trim());
                });
            } else {
                this.fallbackCopy(text.trim());
            }
        },

        fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                this.showToast('Jadwal format WhatsApp berhasil disalin!');
            } catch (err) {
                this.showToast('Gagal menyalin teks secara otomatis.');
            }
            document.body.removeChild(ta);
        },

        showToast(msg) {
            this.toastMessage = msg;
            setTimeout(() => { this.toastMessage = ''; }, 3200);
        }
    };
}
</script>
