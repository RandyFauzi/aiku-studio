@extends('portal.layout', ['currentPage' => 'home', 'pageTitle' => 'Utama & Presensi'])

@section('content')
<div class="space-y-6">

    <!-- Bold Catchy Hero Headline -->
    <div class="pt-1">
        <h1 class="text-[28px] sm:text-[34px] font-black text-slate-900 tracking-tight leading-[1.15]">
            Maksimalkan<br><span class="text-slate-400">Sesi Kerjamu</span>
        </h1>
    </div>

    <!-- DARK CONTRAST HERO CARD ("TODAY'S PROGRESS" - EXACT SCREEN 1 MATCH) -->
    <section class="rounded-[32px] bg-[#161618] text-white p-5 sm:p-7 shadow-2xl relative overflow-hidden">
        <!-- Top Row: Card Title & Date Pill -->
        <div class="flex items-center justify-between gap-3 pb-4 border-b border-white/10">
            <span class="text-xs font-black uppercase tracking-wider text-slate-400">Absensi & Target Hari Ini</span>
            <span class="text-xs font-bold px-3 py-1 rounded-full bg-white/10 text-slate-200 border border-white/10" x-text="dateLabel(today)"></span>
        </div>

        <!-- Middle Row: Circular Ring Gauge & Stats Column (Exact Screen 1 proportions) -->
        <div class="py-5 flex items-center justify-between gap-6 sm:gap-10">
            
            <!-- Circular SVG Ring Gauge (Screen 1 Style) -->
            <div class="relative w-24 h-24 sm:w-28 sm:h-28 shrink-0 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-white/10" stroke-width="3.5" stroke="currentColor" fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path :class="todayAttendance ? 'text-emerald-400' : 'text-amber-400'" 
                          stroke-dasharray="100, 100" 
                          :stroke-dashoffset="todayAttendance ? 0 : 75" 
                          stroke-linecap="round" 
                          stroke-width="3.5" 
                          stroke="currentColor" 
                          fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <template x-if="todayAttendance">
                        <div>
                            <span class="text-xl sm:text-2xl font-black text-emerald-400 block leading-none">100%</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mt-0.5">Hadir</span>
                        </div>
                    </template>
                    <template x-if="!todayAttendance">
                        <div>
                            <span class="text-lg sm:text-xl font-black text-amber-400 block leading-none">0%</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mt-0.5">Absen</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- 3 Stacked Metric Rows (Matching Screen 1 layout exactly) -->
            <div class="flex-1 space-y-3">
                <!-- Stat 1: Total Sesi Hari Ini -->
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-slate-300 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-white leading-tight font-mono" x-text="todaySchedule.sessions ? todaySchedule.sessions.length : 0"></p>
                        <p class="text-[10px] font-semibold text-slate-400 leading-tight">Total Sesi</p>
                    </div>
                </div>

                <!-- Stat 2: Kehadiran -->
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black leading-tight" :class="todayAttendance ? 'text-emerald-400' : 'text-amber-400'" x-text="todayAttendance ? '1 / 1' : '0 / 1'"></p>
                        <p class="text-[10px] font-semibold text-slate-400 leading-tight">Presensi Hadir</p>
                    </div>
                </div>

                <!-- Stat 3: Laporan HP -->
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-slate-300 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-black leading-tight" :class="todayReportSubmitted ? 'text-emerald-400' : 'text-slate-300'" x-text="todayReportSubmitted ? 'Terkirim' : 'Pending'"></p>
                        <p class="text-[10px] font-semibold text-slate-400 leading-tight">Laporan Harian</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Bottom Row: Direct Action Buttons (Light Green Gradient Accent) -->
        <div class="pt-4 border-t border-white/10 flex flex-wrap items-center gap-2.5">
            <!-- Scan Barcode Button -->
            <button type="button" 
                    @click="openScanner()" 
                    :disabled="checking || loading || !!todayAttendance" 
                    class="flex-1 min-h-[48px] rounded-2xl bg-gradient-to-r from-emerald-400 via-teal-400 to-emerald-500 hover:brightness-105 active:scale-98 px-4 py-2.5 text-xs font-black text-slate-950 shadow-md shadow-emerald-950/20 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span x-text="todayAttendance ? 'Presensi Masuk Berhasil' : 'Scan Barcode WiFi'"></span>
            </button>

            <!-- GPS Button -->
            <button type="button" 
                    @click="checkIn()" 
                    :disabled="checking || loading || !!todayAttendance" 
                    class="min-h-[48px] rounded-2xl bg-white/10 hover:bg-white/20 active:scale-98 px-4 py-2.5 text-xs font-bold text-white transition border border-white/10 flex items-center justify-center gap-1.5 disabled:opacity-50">
                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-text="checking ? 'Membaca…' : 'GPS'"></span>
            </button>
        </div>

        <!-- Minimalist Verification Strip (if attended) -->
        <template x-if="todayAttendance">
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-300">
                <div class="flex items-center gap-1.5">
                    <span class="text-emerald-400 font-bold">✓ Jam Masuk:</span>
                    <span class="font-mono text-white font-bold" x-text="time(todayAttendance.checked_in_at)"></span>
                    <span class="text-slate-400 ml-1" x-text="todayAttendance.checkin_method === 'qr_wifi' ? '(WiFi Studio)' : '(GPS)'"></span>
                </div>
                <template x-if="todayAttendance.latitude && Number(todayAttendance.latitude) !== 0">
                    <a :href="mapUrl(todayAttendance)" target="_blank" rel="noopener noreferrer" class="font-bold text-emerald-400 hover:underline flex items-center gap-0.5">
                        <span>Peta</span>
                        <span>↗</span>
                    </a>
                </template>
            </div>
        </template>
    </section>

    <!-- ========================================================= -->
    <!-- FOCUS & RECORDING TIMER CARD (MATCHING USER REFERENCE)    -->
    <!-- ========================================================= -->
    <section class="rounded-[32px] bg-white p-6 sm:p-7 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
        <!-- Top Card Header -->
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Focus</h3>
                    <span class="w-2.5 h-2.5 rounded-full" :class="timer.isRunning ? 'bg-emerald-500 animate-ping' : 'bg-slate-300'"></span>
                </div>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Waktu rekaman teratur. Hasil maksimal.</p>
            </div>

            <!-- Header Quick Controls (Reset & Test Audio) -->
            <div class="flex items-center gap-1.5">
                <!-- Test Audio Button -->
                <button type="button" 
                        @click="testAlarmSound()" 
                        class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60 shadow-2xs"
                        title="Tes Bunyi Notifikasi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                </button>
                <!-- Reset Button -->
                <button type="button" 
                        @click="resetTimer()" 
                        class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60 shadow-2xs"
                        title="Reset Timer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        <!-- Circular Progress Ring (Exact Match with Reference Image) -->
        <div class="py-2 flex flex-col items-center justify-center">
            <div class="relative w-56 h-56 sm:w-64 sm:h-64 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                    <!-- Background ring track (light mint pastel) -->
                    <path class="text-emerald-100" stroke-width="2.6" stroke="currentColor" fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <!-- Active progress ring (vibrant green) -->
                    <path class="text-emerald-500 transition-all duration-300" 
                          stroke-dasharray="100, 100" 
                          :stroke-dashoffset="timerStrokeDashoffset" 
                          stroke-linecap="round" 
                          stroke-width="2.6" 
                          stroke="currentColor" 
                          fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>

                <!-- Center Info: Focus Time, Large Countdown, and Play/Pause Button -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-1">Focus Time</span>
                    <span class="text-4xl sm:text-5xl font-black font-mono text-slate-900 tracking-tight block select-none" x-text="timerFormatted"></span>

                    <!-- Big Circular Black Play/Pause Button (Matching Reference) -->
                    <div class="mt-3.5">
                        <button type="button" 
                                @click="toggleTimer()" 
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition"
                                :title="timer.isRunning ? 'Jeda Timer' : 'Mulai Timer'">
                            <!-- Play Icon -->
                            <svg x-show="!timer.isRunning" class="w-5 h-5 sm:w-6 sm:h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <!-- Pause Icon -->
                            <svg x-show="timer.isRunning" class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Presets & Manual Duration Controls (User Explicit Requirement: 15-20-30 & Manual) -->
        <div class="space-y-3 pt-2 border-t border-slate-100">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Pilihan Durasi Sesi</span>
                <span class="text-xs font-bold text-slate-600" x-text="timer.durationMinutes + ' Menit'"></span>
            </div>

            <!-- Preset Chips Row (15, 20, 30) -->
            <div class="grid grid-cols-3 gap-2">
                <button type="button" 
                        @click="setTimerPreset(15)"
                        class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                        :class="timer.selectedPreset === 15 
                            ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                    15 Menit
                </button>
                <button type="button" 
                        @click="setTimerPreset(20)"
                        class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                        :class="timer.selectedPreset === 20 
                            ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                    20 Menit
                </button>
                <button type="button" 
                        @click="setTimerPreset(30)"
                        class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                        :class="timer.selectedPreset === 30 
                            ? 'bg-[#1C1C1E] text-white border-[#1C1C1E] shadow-sm' 
                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                    30 Menit
                </button>
            </div>

            <!-- Manual Duration Setting -->
            <div class="flex items-center gap-2 pt-1">
                <div class="relative flex-1">
                    <input type="number" 
                           min="1" 
                           max="720" 
                           x-model="timer.customMinutesInput" 
                           placeholder="Atur menit manual (cth: 25, 45, 60)..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="button" 
                        @click="setTimerManual(timer.customMinutesInput)" 
                        :disabled="!timer.customMinutesInput || timer.customMinutesInput <= 0" 
                        class="px-4 py-2 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100 text-xs font-black transition disabled:opacity-40">
                    Terapkan
                </button>
            </div>
        </div>

        <!-- Today's Focus Routine (Matching Bottom List in Reference Image) -->
        <div class="space-y-2.5 pt-2">
            <span class="text-xs font-black uppercase tracking-wider text-slate-400 block">Today's Focus</span>
            
            <div class="space-y-2">
                <!-- Routine 1 -->
                <div @click="setTimerPreset(20)" 
                     class="p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer shadow-2xs hover:border-emerald-300"
                     :class="timer.selectedPreset === 20 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-white border-slate-100'">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <span class="text-xs font-black text-slate-900">Rekaman Sesi Utama</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400">20 min</span>
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                              :class="timer.selectedPreset === 20 ? 'bg-emerald-600 text-white' : 'border border-slate-300'">
                            <svg x-show="timer.selectedPreset === 20" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </div>
                </div>

                <!-- Routine 2 -->
                <div @click="setTimerPreset(5)" 
                     class="p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer shadow-2xs hover:border-emerald-300"
                     :class="timer.selectedPreset === 5 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-white border-slate-100'">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        </div>
                        <span class="text-xs font-black text-slate-900">Istirahat Pendek (Short Break)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400">5 min</span>
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                              :class="timer.selectedPreset === 5 ? 'bg-emerald-600 text-white' : 'border border-slate-300'">
                            <svg x-show="timer.selectedPreset === 5" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </div>
                </div>

                <!-- Routine 3 -->
                <div @click="setTimerPreset(15)" 
                     class="p-3.5 rounded-2xl border transition flex items-center justify-between cursor-pointer shadow-2xs hover:border-emerald-300"
                     :class="timer.selectedPreset === 15 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-white border-slate-100'">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-xs font-black text-slate-900">Review & Kirim Laporan</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400">15 min</span>
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                              :class="timer.selectedPreset === 15 ? 'bg-emerald-600 text-white' : 'border border-slate-300'">
                            <svg x-show="timer.selectedPreset === 15" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TODAY'S TASKS / SESSIONS SECTION (MATCHING SCREEN 1 & 2) -->
    <section class="space-y-3.5">
        <!-- Section Header with View All -->
        <div class="flex items-center justify-between">
            <h3 class="text-base font-black text-slate-900 tracking-tight">Jadwal Sesi Hari Ini</h3>
            <a href="/me/jadwal" class="text-xs font-bold text-slate-400 hover:text-slate-800 transition">
                Lihat Semua ↗
            </a>
        </div>

        <!-- Horizontal Filter Pills (Matching Screen 1) -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <div class="rounded-full bg-white border border-slate-200/80 px-3 py-1 text-xs font-extrabold text-slate-800 flex items-center gap-1.5 shadow-2xs shrink-0">
                <span class="w-4 h-4 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center text-[10px]" x-text="todaySchedule.sessions ? todaySchedule.sessions.length : 0"></span>
                <span>Sesi Hari Ini</span>
            </div>
            <div class="rounded-full bg-emerald-50 border border-emerald-200/80 px-3 py-1 text-xs font-extrabold text-emerald-800 flex items-center gap-1.5 shrink-0">
                <span class="w-1.5 h-1.5 rounded-full" :class="todayAttendance ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                <span x-text="todayAttendance ? 'Presensi Masuk' : 'Belum Presensi'"></span>
            </div>
            <div class="rounded-full bg-white border border-slate-200/80 px-3 py-1 text-xs font-extrabold text-slate-600 flex items-center gap-1.5 shrink-0">
                <span>WiFi Studio</span>
            </div>
        </div>

        <!-- Task Cards List (Screen 1 "Design Landing Page" Card Style) -->
        <div class="space-y-3">
            <template x-if="todaySchedule.sessions && todaySchedule.sessions.length > 0">
                <div class="space-y-3">
                    <template x-for="session in todaySchedule.sessions" :key="session.id">
                        <div class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.02)] hover:border-emerald-300 transition flex flex-col gap-3">
                            <!-- Top Row: Priority Pill & Angled Arrow Circle (Matching Screen 1) -->
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>High Priority</span>
                                </span>
                                <a href="/me/jadwal" class="w-7 h-7 rounded-full bg-slate-50 border border-slate-100 hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-900 transition" title="Lihat Sesi">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 17L17 7m0 0H7m10 0v10"/></svg>
                                </a>
                            </div>

                            <!-- Middle: Task Title -->
                            <div>
                                <h4 class="text-base font-black text-slate-900" x-text="'Sesi ' + session.id + ' — Rekaman Studio'"></h4>
                            </div>

                            <!-- Bottom: Time & Avatars Stack (Matching Screen 1 Bottom Row) -->
                            <div class="pt-1 flex items-center justify-between text-xs text-slate-500">
                                <div class="flex items-center gap-1.5 font-mono font-bold text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span x-text="session.start + ' – ' + session.end + ' WITA'"></span>
                                </div>
                                
                                <!-- Avatars Stack -->
                                <div class="flex items-center -space-x-1.5">
                                    <div class="w-6 h-6 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-[8px] font-black text-white shadow-2xs" x-text="session.tool ? session.tool.substring(0,2).toUpperCase() : 'HP'"></div>
                                    <div class="w-6 h-6 rounded-full bg-teal-500 border-2 border-white flex items-center justify-center text-[8px] font-black text-white shadow-2xs">ST</div>
                                    <div class="w-6 h-6 rounded-full bg-[#1C1C1E] border-2 border-white flex items-center justify-center text-[7px] font-black text-white shadow-2xs">+2</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!todaySchedule.sessions || todaySchedule.sessions.length === 0">
                <div class="p-8 text-center rounded-[28px] bg-white border border-slate-100 shadow-2xs">
                    <p class="text-sm font-black text-slate-800">Tidak ada jadwal sesi rekaman hari ini.</p>
                    <p class="text-xs text-slate-400 mt-1">Hari ini libur atau belum ada shift terdaftar.</p>
                </div>
            </template>
        </div>
    </section>

    <!-- SHORTCUT CARD: LAPORAN KERJA HARIAN -->
    <a href="/me/laporan" class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs flex items-center justify-between gap-3 hover:border-emerald-300 transition block">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-800 font-bold flex items-center justify-center shrink-0 border border-emerald-100">
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h4 class="text-sm font-black text-slate-900">Laporan Kerja Harian</h4>
                <p class="text-[11px] font-medium text-slate-400 mt-0.5">Input durasi menit per HP yang digunakan</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <span class="text-xs font-extrabold text-emerald-700">Isi Sekarang</span>
            <span class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 text-xs font-bold">→</span>
        </div>
    </a>

</div>
@endsection
