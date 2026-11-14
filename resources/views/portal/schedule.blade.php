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
                    class="h-10 px-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold transition shadow-2xs flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <span>Salin Format WA</span>
            </button>

            <button type="button" 
                    @click="resetPositions()" 
                    class="h-10 px-3.5 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-slate-800 text-xs font-semibold transition shadow-2xs cursor-pointer" 
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
        <button type="button" @click="copied=false" class="text-emerald-700 hover:text-emerald-900 font-bold ml-2 cursor-pointer">✕</button>
    </div>

    <!-- ========================================================================= -->
    <!-- WIDGET FOKUS JADWAL WORKER HARI INI & BESOK (PALING ATAS)                 -->
    <!-- ========================================================================= -->
    <div class="rounded-3xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/50 to-emerald-50/30 p-4 sm:p-6 shadow-xs space-y-4">
        <!-- Header Widget: Judul & Anggota Selector -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-100 text-[#00875A] font-bold text-sm shadow-2xs">
                    🎯
                </span>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Fokus Jadwal Shift Saya</h2>
                    <p class="text-[11px] text-slate-500 font-medium">Jadwal tugas rekaman studio Anda untuk hari ini dan besok (WITA).</p>
                </div>
            </div>

            <!-- Selector Anggota (Default otomatis akun login) -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="text-[11px] text-slate-400 font-semibold shrink-0">Anggota:</span>
                <div class="relative">
                    <select x-model="selectedWorker" 
                            @change="onWorkerChange()"
                            class="bg-white border border-slate-200/90 text-slate-800 text-xs font-bold rounded-xl px-3 py-1.5 pr-8 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs cursor-pointer appearance-none">
                        <template x-for="name in allWorkerNames" :key="name">
                            <option :value="name" x-text="name + (isCurrentUser(name) ? ' (Saya)' : '')"></option>
                        </template>
                    </select>
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2 Highlight Cards Grid: Hari Ini & Besok -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            
            <!-- 1. KARTU HARI INI -->
            <div class="rounded-2xl border p-4 sm:p-5 transition-all shadow-2xs relative overflow-hidden"
                 :class="todaySchedule.isOff 
                    ? 'border-amber-200 bg-amber-50/70' 
                    : (todaySchedule.shift 
                        ? 'border-emerald-300/80 bg-gradient-to-br from-white to-emerald-50/70 ring-1 ring-emerald-200/50' 
                        : 'border-slate-200 bg-white')">
                
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider"
                         :class="todaySchedule.isOff ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900'">
                        <span class="w-2 h-2 rounded-full" :class="todaySchedule.isOff ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse'"></span>
                        <span>HARI INI</span>
                    </div>
                    <span class="text-xs font-bold text-slate-600" x-text="todayDateLabel"></span>
                </div>

                <!-- Jika BERTUGAS HARI INI -->
                <template x-if="!todaySchedule.isOff && todaySchedule.shift">
                    <div class="space-y-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <div>
                                <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider" x-text="todaySchedule.shift.shift || todaySchedule.shift.role"></div>
                                <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-mono mt-0.5" x-text="todaySchedule.shift.time"></div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-[#EBF7F2] text-[#00875A] border border-[#B3E8D5] shrink-0">
                                WITA
                            </span>
                        </div>

                        <!-- Badges Rincian -->
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100/90 text-slate-700 font-semibold border border-slate-200/60 text-[11px]">
                                📱 1 Alat · 6 HP
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/70 text-[11px]">
                                🎯 Target 6 Jam Video
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-800 font-medium border border-blue-200/60 text-[11px]">
                                ⏱️ 90 Menit (1j Rekam + 30m Prep)
                            </span>
                        </div>

                        <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-slate-400 font-medium" x-text="'Pola: ' + todaySchedule.pattern"></span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-md">
                                ✓ Siap Bertugas
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Jika LIBUR (Senin atau Libur Mingguan Rutin) -->
                <template x-if="todaySchedule.isOff">
                    <div class="py-2.5 space-y-1.5">
                        <div class="flex items-center gap-2 text-amber-900 font-extrabold text-base">
                            <span class="text-xl">☕</span>
                            <span x-text="todaySchedule.reason || 'Libur Studio (Off)'"></span>
                        </div>
                        <p class="text-xs text-amber-800 font-medium leading-relaxed" x-text="todaySchedule.description || 'Hari ini Anda tidak ada jadwal rekaman studio. Selamat beristirahat!'">
                        </p>
                    </div>
                </template>

                <!-- Jika TIDAK ADA SHIFT TERJADWAL -->
                <template x-if="!todaySchedule.isOff && !todaySchedule.shift">
                    <div class="py-2.5 space-y-1.5">
                        <div class="flex items-center gap-2 text-slate-700 font-bold text-base">
                            <span class="text-xl">🏖️</span>
                            <span>Tidak Ada Shift Hari Ini</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">
                            Nama Anda tidak terdaftar pada giliran shift operasional hari ini (<span x-text="todaySchedule.pattern"></span>).
                        </p>
                    </div>
                </template>
            </div>

            <!-- 2. KARTU BESOK -->
            <div class="rounded-2xl border p-4 sm:p-5 transition-all shadow-2xs relative overflow-hidden"
                 :class="tomorrowSchedule.isOff 
                    ? 'border-amber-200 bg-amber-50/70' 
                    : (tomorrowSchedule.shift 
                        ? 'border-blue-200/90 bg-gradient-to-br from-white to-blue-50/50 ring-1 ring-blue-100' 
                        : 'border-slate-200 bg-white')">
                
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider"
                         :class="tomorrowSchedule.isOff ? 'bg-amber-100 text-amber-900' : 'bg-blue-100 text-blue-900'">
                        <span class="w-2 h-2 rounded-full" :class="tomorrowSchedule.isOff ? 'bg-amber-500' : 'bg-blue-500'"></span>
                        <span>BESOK</span>
                    </div>
                    <span class="text-xs font-bold text-slate-600" x-text="tomorrowDateLabel"></span>
                </div>

                <!-- Jika BERTUGAS BESOK -->
                <template x-if="!tomorrowSchedule.isOff && tomorrowSchedule.shift">
                    <div class="space-y-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <div>
                                <div class="text-[11px] font-bold text-blue-700 uppercase tracking-wider" x-text="tomorrowSchedule.shift.shift || tomorrowSchedule.shift.role"></div>
                                <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-mono mt-0.5" x-text="tomorrowSchedule.shift.time"></div>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-[#EFF6FF] text-[#1D4ED8] border border-[#BFDBFE] shrink-0">
                                WITA
                            </span>
                        </div>

                        <!-- Badges Rincian -->
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100/90 text-slate-700 font-semibold border border-slate-200/60 text-[11px]">
                                📱 1 Alat · 6 HP
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-800 font-bold border border-blue-200/70 text-[11px]">
                                🎯 Target 6 Jam Video
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 text-slate-600 font-medium border border-slate-200/60 text-[11px]">
                                ⏱️ 90 Menit (1j Rekam + 30m Prep)
                            </span>
                        </div>

                        <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-slate-400 font-medium" x-text="'Pola: ' + tomorrowSchedule.pattern"></span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                                📅 Terjadwal Besok
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Jika BESOK LIBUR -->
                <template x-if="tomorrowSchedule.isOff">
                    <div class="py-2.5 space-y-1.5">
                        <div class="flex items-center gap-2 text-amber-900 font-extrabold text-base">
                            <span class="text-xl">☕</span>
                            <span x-text="tomorrowSchedule.reason || 'Besok: Libur Studio (Off)'"></span>
                        </div>
                        <p class="text-xs text-amber-800 font-medium leading-relaxed" x-text="tomorrowSchedule.description || 'Besok Anda tidak ada jadwal rekaman studio. Selamat beristirahat!'">
                        </p>
                    </div>
                </template>

                <!-- Jika TIDAK ADA SHIFT BESOK -->
                <template x-if="!tomorrowSchedule.isOff && !tomorrowSchedule.shift">
                    <div class="py-2.5 space-y-1.5">
                        <div class="flex items-center gap-2 text-slate-700 font-bold text-base">
                            <span class="text-xl">🏖️</span>
                            <span>Tidak Ada Shift Besok</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">
                            Nama Anda tidak terdaftar pada giliran shift operasional besok (<span x-text="tomorrowSchedule.pattern"></span>).
                        </p>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- SCHEDULE TABS SWITCHER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-1.5 rounded-2xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-1.5 flex-wrap">
            <!-- Tab Matriks Mingguan -->
            <button type="button" 
                    @click="scheduleTab = 'matriks'" 
                    :class="scheduleTab === 'matriks' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer">
                <span>📅 Matriks Mingguan</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="scheduleTab === 'matriks' ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-600'">
                    10 Sesi + Mid
                </span>
            </button>

            <!-- Tab Jadwal Harian -->
            <button type="button" 
                    @click="scheduleTab = 'harian'" 
                    :class="scheduleTab === 'harian' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer">
                <span>🕒 Jadwal Harian</span>
            </button>

            <!-- Tab Libur & SOP -->
            <button type="button" 
                    @click="scheduleTab = 'libur_sop'" 
                    :class="scheduleTab === 'libur_sop' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer">
                <span>📋 Libur & SOP Studio</span>
            </button>

            <!-- Tab Kalender & Detail Sesi -->
            <button type="button" 
                    @click="scheduleTab = 'kalender'" 
                    :class="scheduleTab === 'kalender' 
                        ? 'bg-[#174c39] text-white shadow-xs font-bold' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer">
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
    <!-- VIEW 1: MATRIKS JADWAL MINGGUAN STUDIO LENGKAP                            -->
    <!-- ========================================================================= -->
    <div x-show="scheduleTab === 'matriks'" class="space-y-6">

        <!-- OPERATIONAL PARAMETERS SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <!-- Format Sesi -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Format Sesi</span>
                    <span class="text-base">⏱️</span>
                </div>
                <div class="mt-2 text-base font-extrabold text-slate-900">90 Menit / sesi</div>
                <div class="mt-0.5 text-xs text-slate-500 font-medium">1 Jam Rekaman + 30m Prep/Cas</div>
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
                <div class="mt-2 text-base font-extrabold text-slate-900 font-mono tracking-tight">
                    08:00 – 23:00 WITA
                </div>
                <div class="mt-0.5 text-xs text-slate-500 font-medium">
                    10 Sesi Harian + Sesi Midnight
                </div>
            </div>

            <!-- Target Video -->
            <div class="rounded-2xl border border-emerald-200/90 bg-emerald-50/60 p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Target Output Video</span>
                    <span class="text-base">🎯</span>
                </div>
                <div class="mt-2 text-base font-extrabold text-emerald-900">
                    6 Jam Video / orang
                </div>
                <div class="mt-0.5 text-xs text-emerald-700 font-semibold">Total 60 Jam Video Target / hari</div>
            </div>
        </div>

        <!-- MAIN SCHEDULE MATRIX TABLE -->
        <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white shadow-xs">
            <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📊</span>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
                            Matriks Jadwal Operasional 7 Hari AIKu Studio
                        </h2>
                        <p class="text-[11px] text-slate-500">
                            Nama yang berwarna hijau adalah giliran tugas Anda (<strong class="text-emerald-700" x-text="selectedWorker"></strong>).
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 text-[11px]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Jadwal Saya
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-rose-50 text-rose-800 font-bold border border-rose-200 text-[11px]">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Libur Studio
                    </span>
                </div>
            </div>

            <!-- RESPONSIVE TABLE -->
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[940px]">
                    <thead>
                        <tr class="bg-slate-100/90 border-b border-slate-200 text-[11px] font-black text-slate-700 uppercase tracking-wider">
                            <th class="py-3 px-3 text-center w-36">Jadwal / Jam</th>
                            <th class="py-3 px-2.5 text-center">Selasa</th>
                            <th class="py-3 px-2.5 text-center">Rabu</th>
                            <th class="py-3 px-2.5 text-center">Kamis</th>
                            <th class="py-3 px-2.5 text-center">Jumat</th>
                            <th class="py-3 px-2.5 text-center">Sabtu</th>
                            <th class="py-3 px-2.5 text-center">Minggu</th>
                            <th class="py-3 px-2.5 text-center text-rose-700 bg-rose-50/60 w-24">Senin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                        <!-- 10 DAYTIME SESSIONS -->
                        <template x-for="session in (scheduleData.sessions || [])" :key="session.id">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-3 text-center bg-slate-50/40 border-r border-slate-100">
                                    <div class="font-mono text-[11px] font-black text-slate-900" x-text="session.time"></div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider" x-text="session.shift"></div>
                                </td>

                                <template x-for="dayKey in ['selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']" :key="dayKey">
                                    <td class="py-2.5 px-2 text-center align-middle"
                                        :class="isWorkerMatch(session.days[dayKey]) ? 'bg-emerald-50/80' : ''">
                                        <div class="py-1 px-1.5 rounded-xl transition text-center"
                                             :class="isWorkerMatch(session.days[dayKey]) 
                                                ? 'bg-emerald-100/90 text-emerald-900 font-extrabold border border-emerald-300 shadow-2xs' 
                                                : 'text-slate-800 font-semibold'">
                                            <span x-text="getShortName(session.days[dayKey])"></span>
                                            <template x-if="isWorkerMatch(session.days[dayKey])">
                                                <span class="block text-[9px] font-black uppercase tracking-wider text-emerald-700 mt-0.5">
                                                    (Saya)
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                </template>

                                <!-- Senin LIBUR -->
                                <td class="py-2.5 px-2 text-center align-middle bg-rose-50/30 border-l border-rose-100">
                                    <span class="inline-block px-2 py-0.5 rounded-lg bg-rose-100 text-rose-800 font-black text-[10px] border border-rose-200">
                                        LIBUR
                                    </span>
                                </td>
                            </tr>
                        </template>

                        <!-- MIDNIGHT HEADER -->
                        <tr class="bg-indigo-900 text-white text-[11px] font-black uppercase tracking-wider">
                            <td colspan="8" class="py-2 px-4 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <span>🌙</span>
                                    <span>SESI MIDNIGHT (23:00 – SUBUH WITA)</span>
                                </span>
                                <span class="text-[10px] font-semibold opacity-90">1 Alat · 6 HP · Target 6 Jam Video</span>
                            </td>
                        </tr>

                        <!-- MIDNIGHT ROWS -->
                        <template x-for="mid in (scheduleData.midnight || [])" :key="mid.id">
                            <tr class="bg-indigo-50/20 hover:bg-indigo-50/40 transition">
                                <td class="py-3 px-3 text-center bg-indigo-50/40 border-r border-indigo-100">
                                    <div class="font-mono text-[11px] font-black text-indigo-950" x-text="mid.time"></div>
                                    <div class="text-[10px] text-indigo-600 font-bold uppercase tracking-wider" x-text="mid.role"></div>
                                </td>

                                <template x-for="dayKey in ['selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']" :key="dayKey">
                                    <td class="py-2.5 px-2 text-center align-middle"
                                        :class="isWorkerMatch(mid.days[dayKey]) ? 'bg-emerald-50/80' : ''">
                                        <div class="py-1 px-1.5 rounded-xl transition text-center"
                                             :class="isWorkerMatch(mid.days[dayKey]) 
                                                ? 'bg-emerald-100/90 text-emerald-900 font-extrabold border border-emerald-300 shadow-2xs' 
                                                : 'text-indigo-900 font-semibold'">
                                            <span x-text="getShortName(mid.days[dayKey])"></span>
                                            <template x-if="isWorkerMatch(mid.days[dayKey])">
                                                <span class="block text-[9px] font-black uppercase tracking-wider text-emerald-700 mt-0.5">
                                                    (Saya)
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                </template>

                                <!-- Senin LIBUR -->
                                <td class="py-2.5 px-2 text-center align-middle bg-rose-50/30 border-l border-rose-100">
                                    <span class="inline-block px-2 py-0.5 rounded-lg bg-rose-100 text-rose-800 font-black text-[10px] border border-rose-200">
                                        LIBUR
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2 INFO CARDS: JADWAL LIBUR & SOP STUDIO -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- CARD 1: JADWAL LIBUR MINGGUAN -->
            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs space-y-3.5">
                <div class="flex items-center gap-2 text-slate-900 font-black text-sm sm:text-base border-b border-slate-100 pb-3">
                    <span class="text-lg">🏖️</span>
                    <span>Jadwal Libur Mingguan per Anggota</span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-700">Selasa</span>
                        <span class="font-extrabold text-slate-900">Nuga, Aida</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-700">Rabu</span>
                        <span class="font-extrabold text-slate-900">Fai, Aida</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-700">Kamis</span>
                        <span class="font-extrabold text-slate-900">Fahri, Aida</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-700">Jumat</span>
                        <span class="font-extrabold text-slate-900">Yoga</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-700">Sabtu</span>
                        <span class="font-extrabold text-slate-900">Rahmat</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-bold text-slate-700">Minggu</span>
                        <span class="font-extrabold text-slate-900">Maryam, Ghina</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-rose-50 border border-rose-200">
                        <span class="font-black text-rose-800">Senin</span>
                        <span class="font-black text-rose-900">SEMUA ANGGOTA (Libur Bersama Studio)</span>
                    </div>
                </div>
            </div>

            <!-- CARD 2: CATATAN & SOP STUDIO -->
            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs space-y-3.5">
                <div class="flex items-center gap-2 text-slate-900 font-black text-sm sm:text-base border-b border-slate-100 pb-3">
                    <span class="text-lg">📌</span>
                    <span>Aturan & Catatan Operasional Studio</span>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">1</span>
                        <div>
                            <strong class="text-slate-900">Kalo jamnya sudah lewat, Harus Stop</strong>
                            <p class="text-slate-500 mt-0.5 text-[11px]">Disiplin waktu sesi agar giliran anggota berikutnya tidak tertunda.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">2</span>
                        <div>
                            <strong class="text-slate-900">Selesaikan Sesi 1 jam baru Istirahat</strong>
                            <p class="text-slate-500 mt-0.5 text-[11px]">Fokus penuh pada jam perekaman sebelum mengambil jeda.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">3</span>
                        <div>
                            <strong class="text-slate-900">Setelah selesai, Wajib cas dan Upload</strong>
                            <p class="text-slate-500 mt-0.5 text-[11px]">Kembalikan perangkat dalam keadaan baterai terisi dan video terupload.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">4</span>
                        <div>
                            <strong class="text-slate-900">Task Harus Sesuai dengan List yang ditentukan</strong>
                            <p class="text-slate-500 mt-0.5 text-[11px]">Kerjakan skrip dan instruksi sesuai antrean tugas resmi studio.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 2: JADWAL HARIAN (FILTER PER HARI DENGAN TAMPILAN KARTU)              -->
    <!-- ========================================================================= -->
    <div x-show="scheduleTab === 'harian'" class="space-y-5">
        
        <!-- Day Selector Pills -->
        <div class="flex items-center gap-1.5 flex-wrap bg-white p-2 rounded-2xl border border-slate-200/90 shadow-2xs">
            <span class="text-xs font-bold text-slate-400 px-2 shrink-0">Pilih Hari:</span>
            <template x-for="d in ['selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu', 'senin']" :key="d">
                <button type="button" 
                        @click="selectedDayFilter = d" 
                        :class="selectedDayFilter === d 
                            ? 'bg-[#174c39] text-white shadow-xs font-extrabold' 
                            : (d === 'senin' ? 'bg-rose-50 text-rose-800 hover:bg-rose-100 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold')"
                        class="px-3.5 py-1.5 rounded-xl text-xs capitalize transition cursor-pointer">
                    <span x-text="d"></span>
                    <template x-if="d === 'senin'">
                        <span class="text-[10px] opacity-80">(Libur)</span>
                    </template>
                </button>
            </template>
        </div>

        <!-- Jika HARI SENIN (LIBUR STUDIO) -->
        <div x-show="selectedDayFilter === 'senin'" class="rounded-3xl border border-rose-200 bg-rose-50/70 p-6 text-center space-y-2">
            <span class="text-4xl block">☕</span>
            <h3 class="text-lg font-black text-rose-900">Hari Senin: Libur Studio (Off)</h3>
            <p class="text-xs text-rose-800 max-w-md mx-auto leading-relaxed">
                Studio libur operasional setiap hari Senin. Tidak ada sesi perekaman video. Semua anggota beristirahat untuk persiapan jadwal sepekan ke depan.
            </p>
        </div>

        <!-- Jika HARI KERJA (SELASA - MINGGU) -->
        <div x-show="selectedDayFilter !== 'senin'" class="space-y-4">
            
            <div class="flex items-center justify-between text-xs px-1">
                <div>
                    <span class="font-bold text-slate-700 capitalize" x-text="'Daftar Sesi Hari ' + selectedDayFilter"></span>
                    <span class="text-slate-400">· 10 Sesi Siang + 2 Sesi Midnight</span>
                </div>
                <div class="text-emerald-800 font-bold" x-show="getDayOffNames(selectedDayFilter).length">
                    🏖️ Libur: <span x-text="getDayOffNames(selectedDayFilter).join(', ')"></span>
                </div>
            </div>

            <!-- List Shift Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <template x-for="s in (scheduleData.sessions || [])" :key="s.id">
                    <div class="rounded-2xl border p-4 transition shadow-2xs space-y-2 relative"
                         :class="isWorkerMatch(s.days[selectedDayFilter]) 
                            ? 'border-emerald-300 bg-emerald-50/70 ring-1 ring-emerald-200' 
                            : 'border-slate-200 bg-white'">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase tracking-wider"
                                  :class="isWorkerMatch(s.days[selectedDayFilter]) ? 'text-emerald-800' : 'text-slate-400'"
                                  x-text="s.shift"></span>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg"
                                  :class="isWorkerMatch(s.days[selectedDayFilter]) ? 'bg-emerald-200/80 text-emerald-900' : 'bg-slate-100 text-slate-700'"
                                  x-text="s.time"></span>
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0"
                                      :class="isWorkerMatch(s.days[selectedDayFilter]) ? 'bg-emerald-700' : ''"
                                      x-text="getInitials(s.days[selectedDayFilter] || '?')"></span>
                                <div>
                                    <h4 class="font-extrabold text-xs text-slate-900" x-text="s.days[selectedDayFilter] || '— Kosong —'"></h4>
                                    <p class="text-[10px] text-slate-400">1 Alat · 6 HP · Target 6 Jam</p>
                                </div>
                            </div>
                            <template x-if="isWorkerMatch(s.days[selectedDayFilter])">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Saya
                                </span>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- 2 MIDNIGHT CARDS -->
                <template x-for="m in (scheduleData.midnight || [])" :key="m.id">
                    <div class="rounded-2xl border p-4 transition shadow-2xs space-y-2 relative border-indigo-200 bg-indigo-50/50"
                         :class="isWorkerMatch(m.days[selectedDayFilter]) ? 'ring-2 ring-emerald-400' : ''">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase tracking-wider text-indigo-700 flex items-center gap-1">
                                <span>🌙</span> <span x-text="m.role"></span>
                            </span>
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg bg-indigo-100 text-indigo-900"
                                  x-text="m.time"></span>
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-full bg-indigo-900 text-white font-bold text-xs flex items-center justify-center shrink-0"
                                      x-text="getInitials(m.days[selectedDayFilter] || '?')"></span>
                                <div>
                                    <h4 class="font-extrabold text-xs text-slate-900" x-text="m.days[selectedDayFilter] || '— Kosong —'"></h4>
                                    <p class="text-[10px] text-indigo-600 font-medium">Sesi Midnight Studio</p>
                                </div>
                            </div>
                            <template x-if="isWorkerMatch(m.days[selectedDayFilter])">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Saya
                                </span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 3: RINCIAN JADWAL LIBUR & SOP STUDIO                                 -->
    <!-- ========================================================================= -->
    <div x-show="scheduleTab === 'libur_sop'" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Table Libur Mingguan -->
            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                <div class="flex items-center gap-2 text-slate-900 font-extrabold text-base border-b border-slate-100 pb-3">
                    <span class="text-xl">🏖️</span>
                    <span>Jadwal Libur Mingguan Kontributor</span>
                </div>
                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-3 flex items-center justify-between">
                        <span class="font-bold text-slate-700 w-24">Selasa</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Muhammad Nugraha Dwiputra</span>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Aida</span>
                        </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <span class="font-bold text-slate-700 w-24">Rabu</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Fai</span>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Aida</span>
                        </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <span class="font-bold text-slate-700 w-24">Kamis</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Fahri</span>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Aida</span>
                        </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <span class="font-bold text-slate-700 w-24">Jumat</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Muhammad Diyoga</span>
                        </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <span class="font-bold text-slate-700 w-24">Sabtu</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Rahmatullah</span>
                        </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <span class="font-bold text-slate-700 w-24">Minggu</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Siti Mariyam</span>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-800">Ghina Mawaddah</span>
                        </div>
                    </div>
                    <div class="py-3 flex items-center justify-between bg-rose-50/50 px-2 rounded-xl">
                        <span class="font-black text-rose-800 w-24">Senin</span>
                        <span class="px-2.5 py-1 rounded-xl bg-rose-100 font-black text-rose-900 border border-rose-200">
                            SEMUA ANGGOTA (Libur Bersama Studio)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Catatan SOP Studio -->
            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs space-y-4">
                <div class="flex items-center gap-2 text-slate-900 font-extrabold text-base border-b border-slate-100 pb-3">
                    <span class="text-xl">📋</span>
                    <span>SOP & Aturan Operasional Studio</span>
                </div>
                <div class="space-y-3 text-xs leading-relaxed">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-black text-xs flex items-center justify-center shrink-0">1</span>
                        <div>
                            <strong class="text-sm font-bold text-slate-900">Kalo jamnya sudah lewat, Harus Stop</strong>
                            <p class="text-slate-500 mt-1">Jangan melebihi waktu sesi yang sudah ditentukan agar tidak memotong durasi persiapan shift berikutnya.</p>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-black text-xs flex items-center justify-center shrink-0">2</span>
                        <div>
                            <strong class="text-sm font-bold text-slate-900">Selesaikan Sesi 1 jam baru Istirahat</strong>
                            <p class="text-slate-500 mt-1">Lakukan rekaman secara berkelanjutan selama 60 menit sebelum mengambil waktu jeda atau perpindahan.</p>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-black text-xs flex items-center justify-center shrink-0">3</span>
                        <div>
                            <strong class="text-sm font-bold text-slate-900">Setelah selesai, Wajib cas dan Upload</strong>
                            <p class="text-slate-500 mt-1">Seluruh 6 unit HP wajib disambungkan ke charger dan hasil rekaman di-upload ke sistem sesuai SOP.</p>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-black text-xs flex items-center justify-center shrink-0">4</span>
                        <div>
                            <strong class="text-sm font-bold text-slate-900">Task Harus Sesuai dengan List yang ditentukan</strong>
                            <p class="text-slate-500 mt-1">Pastikan video yang direkam mengikuti daftar task yang telah dibagikan admin untuk shift tersebut.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 4: KALENDER BULANAN & SESI SAYA (PORTAL CALENDAR)                    -->
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
                        class="rounded-2xl p-2.5 flex flex-col items-center justify-between gap-2 border transition shadow-2xs cursor-pointer"
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
                                        class="min-h-[72px] w-full p-2 text-left sm:min-h-[84px] sm:p-2.5 transition rounded-xl cursor-pointer">
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

<!-- JAVASCRIPT LOGIC FOR SCHEDULE TABLE & SWAPPING -->
<script>
function scheduleManager() {
    return {
        scheduleTab: 'matriks',
        selectedDayFilter: 'selasa',
        copied: false,
        toastMessage: '',
        currentUser: @json(auth()->check() ? auth()->user()->name : ''),
        selectedWorker: '',

        scheduleData: {
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
        },

        allWorkerNames: [
            "Aufa Anugraha",
            "Muhamad Zikri Fadhilah",
            "Muhammad Diyoga",
            "Rahmatullah",
            "Ghina Mawaddah",
            "Siti Mariyam",
            "Afif Anshari",
            "Sasa",
            "Fai",
            "Fahri",
            "Muhammad Nugraha Dwiputra",
            "Aida",
            "Muhammad Rio Hadi"
        ],

        async initSchedule() {
            // Deteksi hari aktif WITA untuk tab harian
            try {
                const now = new Date();
                const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
                const witaDate = new Date(utc + (3600000 * 8));
                const dayIndex = witaDate.getDay();
                const dayKeys = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
                this.selectedDayFilter = dayKeys[dayIndex] || 'selasa';
            } catch (e) {
                this.selectedDayFilter = 'selasa';
            }

            // Muat jadwal resmi dari server
            try {
                const res = await fetch('/api/shifts/weekly-schedule', { headers: { Accept: 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.schedule) {
                        this.scheduleData = data.schedule;
                    }
                }
            } catch (e) {
                console.warn('Gagal memuat jadwal dari server, menggunakan data default:', e);
            }

            // Inisialisasi selectedWorker (akun login -> simpanan lokal -> worker pertama)
            try {
                const savedWorker = localStorage.getItem('aiku_schedule_selected_worker');
                if (savedWorker && this.allWorkerNames.includes(savedWorker)) {
                    this.selectedWorker = savedWorker;
                } else if (this.currentUser) {
                    const cur = this.currentUser.toLowerCase().trim();
                    const matched = this.allWorkerNames.find(n => 
                        n.toLowerCase().trim() === cur || 
                        n.toLowerCase().includes(cur) || 
                        cur.includes(n.toLowerCase())
                    );
                    this.selectedWorker = matched || (this.allWorkerNames[0] || '');
                } else {
                    this.selectedWorker = this.allWorkerNames[0] || '';
                }
            } catch (e) {
                this.selectedWorker = this.allWorkerNames[0] || '';
            }
        },

        onWorkerChange() {
            try {
                if (this.selectedWorker) {
                    localStorage.setItem('aiku_schedule_selected_worker', this.selectedWorker);
                }
            } catch (e) {}
        },

        isCurrentUser(name) {
            if (!this.currentUser || !name) return false;
            const cur = this.currentUser.toLowerCase().trim();
            const n = name.toLowerCase().trim();
            return cur === n || cur.includes(n) || n.includes(cur);
        },

        isWorkerMatch(name) {
            if (!name) return false;
            const target = (this.selectedWorker || this.currentUser || '').toLowerCase().trim();
            if (!target) return false;
            const n = name.toLowerCase().trim();
            return target === n || target.includes(n) || n.includes(target);
        },

        getShortName(fullName) {
            if (!fullName) return '—';
            const map = {
                'Aufa Anugraha': 'Aufa',
                'Muhamad Zikri Fadhilah': 'Zikri',
                'Muhammad Diyoga': 'Yoga',
                'Rahmatullah': 'Rahmat',
                'Ghina Mawaddah': 'Ghina',
                'Siti Mariyam': 'Maryam',
                'Afif Anshari': 'Afif',
                'Sasa': 'Sasa',
                'Fai': 'Fai',
                'Fahri': 'Fahri',
                'Muhammad Nugraha Dwiputra': 'Nuga',
                'Aida': 'Aida',
                'Muhammad Rio Hadi': 'Rio'
            };
            return map[fullName] || fullName;
        },

        getInitials(name) {
            if (!name) return '?';
            const parts = name.trim().split(/\s+/);
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        },

        getDayOffNames(dayKey) {
            if (!this.scheduleData.days_off || !this.scheduleData.days_off[dayKey]) return [];
            return this.scheduleData.days_off[dayKey];
        },

        getWitaDate(offsetDays = 0) {
            const now = new Date();
            const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
            const witaDate = new Date(utc + (3600000 * 8));
            if (offsetDays !== 0) {
                witaDate.setDate(witaDate.getDate() + offsetDays);
            }
            return witaDate;
        },

        formatDateLabel(dateObj) {
            try {
                return new Intl.DateTimeFormat('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }).format(dateObj);
            } catch (e) {
                return dateObj.toLocaleDateString();
            }
        },

        get todayDateLabel() {
            return this.formatDateLabel(this.getWitaDate(0));
        },

        get tomorrowDateLabel() {
            return this.formatDateLabel(this.getWitaDate(1));
        },

        getScheduleForDate(offsetDays = 0) {
            const dateObj = this.getWitaDate(offsetDays);
            const dayOfWeek = dateObj.getDay(); // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu
            const dayKeys = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
            const dayLabels = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const dayKey = dayKeys[dayOfWeek];
            const dayLabel = dayLabels[dayOfWeek];

            // 1. Hari Senin: Libur Studio (Off)
            if (dayOfWeek === 1) {
                return {
                    isOff: true,
                    shift: null,
                    reason: 'Libur Studio (Off)',
                    description: 'Hari Senin studio libur operasional mingguan. Tidak ada jadwal perekaman video. Selamat beristirahat!',
                    pattern: 'Senin (Libur Bersama Studio)'
                };
            }

            if (!this.selectedWorker) {
                return { isOff: false, shift: null, pattern: dayLabel };
            }

            // 2. Cek apakah giliran libur rutin mingguan
            const daysOff = this.scheduleData.days_off?.[dayKey] || [];
            const isOffToday = daysOff.some(name => this.isWorkerMatch(name));
            if (isOffToday) {
                return {
                    isOff: true,
                    shift: null,
                    reason: 'Jadwal Libur Rutin (' + dayLabel + ')',
                    description: 'Hari ini adalah giliran libur mingguan Anda sesuai jadwal operasional. Selamat beristirahat!',
                    pattern: 'Libur Rutin (' + dayLabel + ')'
                };
            }

            // 3. Cek di 10 Sesi Harian
            const sessions = this.scheduleData.sessions || [];
            for (const s of sessions) {
                if (this.isWorkerMatch(s.days?.[dayKey])) {
                    return {
                        isOff: false,
                        shift: {
                            shift: s.shift,
                            time: s.time,
                            detail: '90 Menit (1j Rekam + 30m Prep)',
                            target: '6 Jam Video',
                            hp: '6 HP',
                            alat: '1 Alat'
                        },
                        pattern: s.shift + ' (' + dayLabel + ')'
                    };
                }
            }

            // 4. Cek di Sesi Midnight
            const midnight = this.scheduleData.midnight || [];
            for (const m of midnight) {
                if (this.isWorkerMatch(m.days?.[dayKey])) {
                    return {
                        isOff: false,
                        shift: {
                            shift: m.role,
                            time: m.time,
                            detail: 'Sesi Midnight (23:00 – Subuh)',
                            target: '6 Jam Video',
                            hp: '6 HP',
                            alat: '1 Alat'
                        },
                        pattern: m.role + ' (' + dayLabel + ')'
                    };
                }
            }

            return {
                isOff: false,
                shift: null,
                pattern: 'Tidak Ada Jadwal (' + dayLabel + ')'
            };
        },

        get todaySchedule() {
            return this.getScheduleForDate(0);
        },

        get tomorrowSchedule() {
            return this.getScheduleForDate(1);
        },

        resetPositions() {
            this.initSchedule();
            this.showToast('Jadwal operasional disinkronkan kembali.');
        },

        copyWhatsApp() {
            let text = "JADWAL SHIFT OPERASIONAL AIKU STUDIO\n";
            text += "* Format Sesi: 90 Menit per sesi (1 Jam Rekaman + 30m Prep)\n";
            text += "* Pengaturan: 1 Alat · 6 HP per orang\n";
            text += "* Jam Operasional: 08:00 – 23:00 WITA (10 Sesi) + Midnight\n";
            text += "* Output Video: 6 Jam Video/orang/sesi\n\n";

            text += "========================================\n";
            text += "ATURAN / NOTE OPERASIONAL:\n";
            (this.scheduleData.notes || []).forEach((note, i) => {
                text += `${i + 1}. ${note}\n`;
            });
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
            (this.scheduleData.sessions || []).forEach(s => {
                text += `• ${s.shift} (${s.time}):\n`;
                text += `  Sel: ${this.getShortName(s.days.selasa)} | Rab: ${this.getShortName(s.days.rabu)} | Kam: ${this.getShortName(s.days.kamis)} | Jum: ${this.getShortName(s.days.jumat)} | Sab: ${this.getShortName(s.days.sabtu)} | Min: ${this.getShortName(s.days.minggu)}\n`;
            });

            text += "\nSESI MIDNIGHT (23:00 – Subuh):\n";
            (this.scheduleData.midnight || []).forEach(m => {
                text += `• ${m.role} (${m.time}):\n`;
                text += `  Sel-Min: ${this.getShortName(m.days.selasa)}\n`;
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
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3200);
        }
    };
}
</script>
@endsection
