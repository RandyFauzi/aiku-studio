@extends('portal.layout', ['currentPage' => 'jadwal', 'pageTitle' => 'Jadwal Shift'])

@section('content')
<div x-data="scheduleManager()" x-init="initSchedule()" class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-100 text-[#00875A] font-bold text-base shadow-2xs">
                    📅
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Halaman Jadwal Shift</h1>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Jadwal operasional rekaman studio harian, rincian sesi kerja, target video, dan pertukaran posisi shift.
            </p>
        </div>

        <!-- Quick Actions (Copy WA & Reset) -->
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" 
                    @click="copyWhatsApp()" 
                    class="h-10 px-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold transition shadow-2xs flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <span>Salin Format WA</span>
            </button>

            <button type="button" 
                    @click="resetPositions()" 
                    class="h-10 px-3.5 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-slate-800 text-xs font-semibold transition shadow-2xs" 
                    title="Kembalikan susunan shift ke posisi bawaan">
                ↺ Reset Susunan
            </button>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div x-show="copied" 
         x-cloak 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="rounded-2xl border border-emerald-300 bg-emerald-50/95 text-emerald-900 px-4 py-3 text-xs font-bold flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span x-text="toastMessage"></span>
        </div>
        <button type="button" @click="copied=false" class="text-emerald-700 hover:text-emerald-900 font-bold ml-2">✕</button>
    </div>

    <!-- SCHEDULE TABS SWITCHER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-1.5 rounded-2xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-1.5 flex-wrap">
            <!-- Tab Selasa - Kamis -->
            <button type="button" 
                    @click="scheduleTab = 'selasa_kamis'" 
                    :class="scheduleTab === 'selasa_kamis' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition">
                <span>Selasa – Kamis</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="scheduleTab === 'selasa_kamis' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-600'">
                    12 Shift
                </span>
            </button>

            <!-- Tab Jum'at - Minggu -->
            <button type="button" 
                    @click="scheduleTab = 'jumat_minggu'" 
                    :class="scheduleTab === 'jumat_minggu' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition">
                <span>Jum'at – Minggu</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="scheduleTab === 'jumat_minggu' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-600'">
                    13 Shift
                </span>
            </button>

            <!-- Tab Kalender & Detail Sesi -->
            <button type="button" 
                    @click="scheduleTab = 'kalender'" 
                    :class="scheduleTab === 'kalender' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Kalender Bulanan</span>
            </button>
        </div>

        <!-- Studio Day Off Pill -->
        <div class="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200/80 text-[11px] font-semibold text-amber-800 flex items-center gap-1.5 self-start sm:self-auto">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            <span>Senin: Libur Studio (Off)</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 1 & 2: TABEL JADWAL LENGKAP (SELASA - KAMIS & JUM'AT - MINGGU)       -->
    <!-- ========================================================================= -->
    <div x-show="scheduleTab === 'selasa_kamis' || scheduleTab === 'jumat_minggu'" class="space-y-6">

        <!-- OPERATIONAL PARAMETERS SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <!-- Format Sesi -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Format Sesi</span>
                    <span class="text-base">⏱️</span>
                </div>
                <div class="mt-2 text-base font-extrabold text-slate-900">90 Menit / sesi</div>
                <div class="mt-0.5 text-xs text-slate-500 font-medium">1 Jam Rekaman + 30m Prep</div>
            </div>

            <!-- Pengaturan -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pengaturan</span>
                    <span class="text-base">📱</span>
                </div>
                <div class="mt-2 text-base font-extrabold text-slate-900">1 Alat · 6 HP</div>
                <div class="mt-0.5 text-xs text-slate-500 font-medium">Per orang setiap shift</div>
            </div>

            <!-- Jam Operasional -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jam Operasional</span>
                    <span class="text-base">🕒</span>
                </div>
                <div class="mt-2 text-base font-extrabold text-slate-900 font-mono tracking-tight"
                     x-text="scheduleTab === 'selasa_kamis' ? '08:00 – 02:00 (+1h)' : '08:00 – 03:30 (+1h)'">
                </div>
                <div class="mt-0.5 text-xs text-slate-500 font-medium"
                     x-text="scheduleTab === 'selasa_kamis' ? 'WITA · 12 Sesi Berkelanjutan' : 'WITA · 13 Sesi Berkelanjutan'">
                </div>
            </div>

            <!-- Target Video -->
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/60 p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Output Video Target</span>
                    <span class="text-base">🎯</span>
                </div>
                <div class="mt-2 text-base font-extrabold text-emerald-900"
                     x-text="scheduleTab === 'selasa_kamis' ? 'Total 72 Jam Video/hari' : 'Total 78 Jam Video/hari'">
                </div>
                <div class="mt-0.5 text-xs text-emerald-700 font-semibold">6 Jam Video / orang (Target Sesi)</div>
            </div>
        </div>

        <!-- MAIN SCHEDULE TABLE CARD -->
        <div class="rounded-[28px] border border-slate-200/80 bg-white p-5 sm:p-7 shadow-xs">
            
            <!-- Table Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🎥</span>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight"
                        x-text="scheduleTab === 'selasa_kamis' ? 'Tabel Jadwal Lengkap (12 Orang)' : 'Tabel Jadwal Lengkap (13 Orang)'">
                    </h2>
                </div>

                <div class="text-xs text-slate-400 font-medium">
                    Gunakan tombol <span class="font-bold text-slate-600">▲</span> dan <span class="font-bold text-slate-600">▼</span> pada kolom <strong class="text-slate-700">Tukar Posisi</strong> untuk memindahkan giliran shift.
                </div>
            </div>

            <!-- RESPONSIVE TABLE WRAPPER -->
            <div class="overflow-x-auto mt-4 custom-scrollbar rounded-2xl border border-slate-100">
                <table class="w-full text-left border-collapse">
                    <!-- TABLE COLUMNS HEADER -->
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-3 text-center text-slate-400 w-12">NO</th>
                            <th class="py-3.5 px-4 min-w-[200px]">NAMA ANGGOTA</th>
                            <th class="py-3.5 px-3 min-w-[110px]">SHIFT / SESI</th>
                            <th class="py-3.5 px-3 text-center min-w-[120px]">ALAT</th>
                            <th class="py-3.5 px-3 text-center min-w-[170px]">JAM SESI LENGKAP</th>
                            <th class="py-3.5 px-3 text-center min-w-[150px]">RINCIAN WAKTU</th>
                            <th class="py-3.5 px-3 text-center min-w-[100px]">HP AKTIF</th>
                            <th class="py-3.5 px-3 text-center min-w-[120px]">TARGET VIDEO</th>
                            <th class="py-3.5 px-3 text-center min-w-[100px] leading-tight">
                                TUKAR<br>POSISI
                            </th>
                        </tr>
                    </thead>

                    <!-- TABLE BODY ROWS -->
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                        <template x-for="(row, index) in (scheduleTab === 'selasa_kamis' ? rowsSelasaKamis : rowsJumatMinggu)" :key="row.shift + '-' + row.time">
                            <tr class="hover:bg-slate-50/70 transition duration-150"
                                :class="currentUser && row.name.toLowerCase().includes(currentUser.toLowerCase()) ? 'bg-emerald-50/40' : ''">
                                
                                <!-- 1. NO -->
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800" x-text="row.no"></td>

                                <!-- 2. NAMA ANGGOTA -->
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span x-text="row.name"></span>
                                        <template x-if="currentUser && row.name.toLowerCase().includes(currentUser.toLowerCase())">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Kamu
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                <!-- 3. SHIFT / SESI -->
                                <td class="py-3.5 px-3 font-bold text-slate-900" x-text="row.shift"></td>

                                <!-- 4. ALAT -->
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-[#EBF7F2] text-[#00875A] border border-[#B3E8D5] shadow-2xs">
                                        Alat Tunggal
                                    </span>
                                </td>

                                <!-- 5. JAM SESI LENGKAP -->
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-xl text-xs font-semibold bg-[#E6F9F5] text-[#0F766E] border border-[#99F6E4] font-mono tracking-tight shadow-2xs"
                                          x-text="row.time">
                                    </span>
                                </td>

                                <!-- 6. RINCIAN WAKTU -->
                                <td class="py-3.5 px-3 text-center text-slate-500 font-medium" x-text="row.detail"></td>

                                <!-- 7. HP AKTIF -->
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl text-[11px] font-bold bg-[#EFF6FF] text-[#1D4ED8] border border-[#BFDBFE] shadow-2xs">
                                        6 HP
                                    </span>
                                </td>

                                <!-- 8. TARGET VIDEO -->
                                <td class="py-3.5 px-3 text-center font-bold text-[#00875A]" x-text="row.target"></td>

                                <!-- 9. TUKAR POSISI BUTTONS -->
                                <td class="py-3.5 px-3 text-center">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <!-- UP BUTTON -->
                                        <button type="button" 
                                                @click="swap(index, index - 1)" 
                                                :disabled="index === 0"
                                                class="w-6 h-6 rounded-lg border border-slate-200/90 bg-white hover:bg-slate-100 disabled:opacity-20 disabled:cursor-not-allowed flex items-center justify-center text-[10px] text-slate-500 hover:text-slate-900 transition shadow-2xs"
                                                title="Tukar posisi dengan shift di atasnya">
                                            ▲
                                        </button>

                                        <!-- DOWN BUTTON -->
                                        <button type="button" 
                                                @click="swap(index, index + 1)" 
                                                :disabled="index === (scheduleTab === 'selasa_kamis' ? rowsSelasaKamis.length - 1 : rowsJumatMinggu.length - 1)"
                                                class="w-6 h-6 rounded-lg border border-slate-200/90 bg-white hover:bg-slate-100 disabled:opacity-20 disabled:cursor-not-allowed flex items-center justify-center text-[10px] text-slate-500 hover:text-slate-900 transition shadow-2xs"
                                                title="Tukar posisi dengan shift di bawahnya">
                                            ▼
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer Notes -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-400 gap-2">
                <div>
                    Pola shift ini berlaku secara konsisten sepanjang periode kerja aktif studio.
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1 text-slate-600 font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Zona Waktu WITA (UTC+8)
                    </span>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 3: KALENDER BULANAN & SESI SAYA (ORIGINAL PORTAL CALENDAR)           -->
    <!-- ========================================================================= -->
    <div x-show="scheduleTab === 'kalender'" class="space-y-6">

        <!-- Title & Month Switcher -->
        <div class="flex items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900">Kalender Shift Pribadi & Kehadiran</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Jadwal periode cut-off studio, revisi harian, dan izin/sakit dari admin</p>
            </div>
            <input type="month" aria-label="Bulan jadwal shift" x-model="month" @change="load()" :disabled="loading" class="h-9 rounded-2xl border border-slate-200 px-3 text-xs font-bold text-slate-800 bg-white shadow-2xs">
        </div>

        <!-- HORIZONTAL 7-DAY STRIP -->
        <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
            <template x-for="d in weekDays" :key="d.date">
                <button type="button"
                        @click="selectedDate = d.date"
                        class="rounded-2xl p-2.5 flex flex-col items-center justify-between gap-2 border transition shadow-2xs"
                        :class="selectedDate === d.date
                            ? 'bg-white border-slate-900 ring-2 ring-slate-900/10'
                            : 'bg-white border-slate-100 hover:border-slate-300'">
                    <span class="text-[11px] font-semibold uppercase"
                          :class="selectedDate === d.date ? 'text-slate-900' : 'text-slate-400'"
                          x-text="d.name"></span>

                    <!-- Date Number -->
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition"
                          :class="selectedDate === d.date
                              ? 'bg-[#174c39] text-white shadow-xs'
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
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Kalender Bulanan</span>
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
                                        :class="selectedDate===cell.date ? 'bg-[#174c39] text-white shadow-sm' : 'hover:bg-slate-50'"
                                        class="min-h-[72px] w-full p-2 text-left sm:min-h-[84px] sm:p-2.5 transition rounded-xl">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold"
                                              :class="cell.date === today && selectedDate !== cell.date ? 'w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px]' : ''"
                                              x-text="Number(cell.date?.slice(-2))"></span>
                                        <span x-show="cell.attendance" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    </div>

                                    <span class="mt-1.5 block text-[9px] sm:text-[10px] leading-tight font-semibold"
                                          :class="selectedDate === cell.date ? 'text-slate-300' : (cell.attendance ? 'text-emerald-700' : (cell.sessions?.length ? 'text-slate-700' : 'text-slate-400'))"
                                          x-text="cell.attendance ? '✓ Masuk' : (['izin','sakit'].includes(cell.status) ? (cell.status==='izin'?'Izin':'Sakit') : (cell.sessions?.length ? cell.sessions.length + ' shift' : 'Libur'))"></span>
                                </button>
                                <span x-show="!cell.date" class="block min-h-[72px] bg-slate-50/40 sm:min-h-[84px]"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right: Selected Date Session Timeline -->
            <div class="lg:col-span-5 space-y-3">
                <div class="rounded-[28px] border border-slate-100 bg-white p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Detail Sesi</span>
                        <span x-show="selectedDate === today" class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Hari Ini</span>
                    </div>

                    <h3 class="text-base font-semibold text-slate-900" x-text="dateLabel(selectedDate)"></h3>
                    <p class="text-xs text-slate-500" x-text="selectedDay.custom?'Revisi khusus dari admin':'Mengikuti pola periode dan hari libur'"></p>
                    <p x-show="['izin','sakit'].includes(selectedDay.status)" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-900" x-text="selectedDay.status==='izin'?'Izin — tidak dijadwalkan bekerja':'Sakit — tidak dijadwalkan bekerja'"></p>
                    <p x-show="selectedDay.note" class="break-words rounded-xl bg-slate-50 p-3 text-sm text-slate-600" x-text="selectedDay.note"></p>
                    <p x-show="selectedDay.sessions.length" class="text-xs font-semibold text-emerald-800" x-text="new Intl.NumberFormat('id-ID',{maximumFractionDigits:2}).format(selectedDay.work_hours||0)+' jam kerja'"></p>

                    <!-- Sessions List -->
                    <div class="space-y-2.5">
                        <template x-for="session in selectedDay.sessions" :key="session.id">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="text-center w-12 shrink-0">
                                        <span class="text-xs font-semibold text-slate-900 block font-mono" x-text="session.start"></span>
                                        <span class="text-[10px] font-bold text-slate-400 block font-mono" x-text="session.end+(session.end_at?.slice(0,10)>selectedDate?' +1 hari':'')"></span>
                                    </div>
                                    <div class="h-6 w-px bg-slate-200"></div>
                                    <div>
                                        <p class="font-semibold text-xs text-slate-900" x-text="'Shift ' + session.id"></p>
                                        <p class="text-[11px] text-emerald-700 font-bold" x-text="session.tool"></p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-700 bg-slate-200/80 px-2 py-0.5 rounded-lg">Terjadwal</span>
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

</div>

<!-- JAVASCRIPT LOGIC FOR SCHEDULE TABLE & POSITION SWAPPING -->
<script>
function scheduleManager() {
    return {
        scheduleTab: 'selasa_kamis',
        copied: false,
        toastMessage: '',
        currentUser: @json(auth()->check() ? auth()->user()->name : ''),
        
        defaultSelasaKamis: [
            { no: 1, name: 'Aufa Anugraha', shift: 'Shift 1', alat: 'Alat Tunggal', time: '08:00 – 09:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 2, name: 'Muhamad Zikri Fadhilah', shift: 'Shift 2', alat: 'Alat Tunggal', time: '09:30 – 11:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 3, name: 'Rahmatullah', shift: 'Shift 3', alat: 'Alat Tunggal', time: '11:00 – 12:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 4, name: 'Ghina Mawaddah', shift: 'Shift 4', alat: 'Alat Tunggal', time: '12:30 – 14:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 5, name: 'Muhammad Diyoga', shift: 'Shift 5', alat: 'Alat Tunggal', time: '14:00 – 15:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 6, name: 'Siti Mariyam', shift: 'Shift 6', alat: 'Alat Tunggal', time: '15:30 – 17:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 7, name: 'Fai', shift: 'Shift 7', alat: 'Alat Tunggal', time: '17:00 – 18:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 8, name: 'Sasa', shift: 'Shift 8', alat: 'Alat Tunggal', time: '18:30 – 20:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 9, name: 'Muhammad Nugraha Dwiputra', shift: 'Shift 9', alat: 'Alat Tunggal', time: '20:00 – 21:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 10, name: 'Fahri', shift: 'Shift 10', alat: 'Alat Tunggal', time: '21:30 – 23:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 11, name: 'Afif Anshari', shift: 'Shift 11', alat: 'Alat Tunggal', time: '23:00 – 00:30 (+1h)', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 12, name: 'Muhammad Rio Hadi', shift: 'Shift 12', alat: 'Alat Tunggal', time: '00:30 (+1h) – 02:00 (+1h)', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' }
        ],

        defaultJumatMinggu: [
            { no: 1, name: 'Aufa Anugraha', shift: 'Shift 1', alat: 'Alat Tunggal', time: '08:00 – 09:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 2, name: 'Muhamad Zikri Fadhilah', shift: 'Shift 2', alat: 'Alat Tunggal', time: '09:30 – 11:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 3, name: 'Rahmatullah', shift: 'Shift 3', alat: 'Alat Tunggal', time: '11:00 – 12:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 4, name: 'Ghina Mawaddah', shift: 'Shift 4', alat: 'Alat Tunggal', time: '12:30 – 14:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 5, name: 'Muhammad Diyoga', shift: 'Shift 5', alat: 'Alat Tunggal', time: '14:00 – 15:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 6, name: 'Siti Mariyam', shift: 'Shift 6', alat: 'Alat Tunggal', time: '15:30 – 17:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 7, name: 'Fai', shift: 'Shift 7', alat: 'Alat Tunggal', time: '17:00 – 18:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 8, name: 'Aida', shift: 'Shift 8', alat: 'Alat Tunggal', time: '18:30 – 20:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 9, name: 'Sasa', shift: 'Shift 9', alat: 'Alat Tunggal', time: '20:00 – 21:30', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 10, name: 'Fahri', shift: 'Shift 10', alat: 'Alat Tunggal', time: '21:30 – 23:00', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 11, name: 'Muhammad Nugraha Dwiputra', shift: 'Shift 11', alat: 'Alat Tunggal', time: '23:00 – 00:30 (+1h)', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 12, name: 'Afif Anshari', shift: 'Shift 12', alat: 'Alat Tunggal', time: '00:30 (+1h) – 02:00 (+1h)', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' },
            { no: 13, name: 'Muhammad Rio Hadi', shift: 'Shift 13', alat: 'Alat Tunggal', time: '02:00 (+1h) – 03:30 (+1h)', detail: '1j Rekam + 30m Prep', hp: '6 HP', target: '6 Jam Video' }
        ],

        rowsSelasaKamis: [],
        rowsJumatMinggu: [],

        initSchedule() {
            // Auto detect active day in WITA
            try {
                const dayName = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Makassar', weekday: 'short' }).format(new Date());
                if (['Fri', 'Sat', 'Sun'].includes(dayName)) {
                    this.scheduleTab = 'jumat_minggu';
                } else {
                    this.scheduleTab = 'selasa_kamis';
                }
            } catch (e) {
                this.scheduleTab = 'selasa_kamis';
            }

            // Load preserved swaps from localStorage
            try {
                const savedSK = localStorage.getItem('aiku_schedule_selasa_kamis_v2');
                this.rowsSelasaKamis = savedSK ? JSON.parse(savedSK) : JSON.parse(JSON.stringify(this.defaultSelasaKamis));
                
                const savedJM = localStorage.getItem('aiku_schedule_jumat_minggu_v2');
                this.rowsJumatMinggu = savedJM ? JSON.parse(savedJM) : JSON.parse(JSON.stringify(this.defaultJumatMinggu));
            } catch (e) {
                this.rowsSelasaKamis = JSON.parse(JSON.stringify(this.defaultSelasaKamis));
                this.rowsJumatMinggu = JSON.parse(JSON.stringify(this.defaultJumatMinggu));
            }
        },

        swap(index1, index2) {
            const list = this.scheduleTab === 'selasa_kamis' ? this.rowsSelasaKamis : this.rowsJumatMinggu;
            if (index1 < 0 || index1 >= list.length || index2 < 0 || index2 >= list.length) return;

            // Swap names between positions
            const tempName = list[index1].name;
            list[index1].name = list[index2].name;
            list[index2].name = tempName;

            this.saveStorage();
        },

        saveStorage() {
            try {
                if (this.scheduleTab === 'selasa_kamis') {
                    localStorage.setItem('aiku_schedule_selasa_kamis_v2', JSON.stringify(this.rowsSelasaKamis));
                } else {
                    localStorage.setItem('aiku_schedule_jumat_minggu_v2', JSON.stringify(this.rowsJumatMinggu));
                }
            } catch (e) {}
        },

        resetPositions() {
            if (this.scheduleTab === 'selasa_kamis') {
                this.rowsSelasaKamis = JSON.parse(JSON.stringify(this.defaultSelasaKamis));
                try { localStorage.removeItem('aiku_schedule_selasa_kamis_v2'); } catch (e) {}
            } else {
                this.rowsJumatMinggu = JSON.parse(JSON.stringify(this.defaultJumatMinggu));
                try { localStorage.removeItem('aiku_schedule_jumat_minggu_v2'); } catch (e) {}
            }
            this.showToast('Susunan posisi dikembalikan ke jadwal awal!');
        },

        copyWhatsApp() {
            let text = '';
            if (this.scheduleTab === 'selasa_kamis') {
                text = "JADWAL SHIFT (Selasa-Kamis)\n" +
                    "* Format Sesi: 90 Menit per sesi (1 Jam Rekaman)\n" +
                    "* Pengaturan: 1 Alat · 6 HP per orang\n" +
                    "* Jam Operasional: 08:00 – 02:00 (+1h) WITA (12 Sesi)\n" +
                    "* Output Video: 6 Jam Video/orang → Total 72 Jam Video/hari TARGET\n\n";
                this.rowsSelasaKamis.forEach(row => {
                    text += `${row.shift} (${row.time})\n  - ${row.name}\n\n`;
                });
            } else {
                text = "JADWAL SHIFT (Jum'at - Minggu)\n" +
                    "* Format Sesi: 90 Menit per sesi (1 Jam Rekaman)\n" +
                    "* Pengaturan: 1 Alat · 6 HP per orang\n" +
                    "* Jam Operasional: 08:00 – 03:30 (+1h) WITA (13 Sesi)\n" +
                    "* Output Video: 6 Jam Video/orang → Total 78 Jam Video/hari TARGET\n\n";
                this.rowsJumatMinggu.forEach(row => {
                    text += `${row.shift} (${row.time})\n  - ${row.name}\n\n`;
                });
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text.trim()).then(() => {
                    this.showToast('Format teks WhatsApp berhasil disalin!');
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
                this.showToast('Format teks WhatsApp berhasil disalin!');
            } catch (err) {
                this.showToast('Gagal menyalin teks secara otomatis.');
            }
            document.body.removeChild(ta);
        },

        showToast(msg) {
            this.toastMessage = msg;
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3200);
        }
    };
}
</script>
@endsection
