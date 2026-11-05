@include('portal.shell')

    <div x-show="scannerOpen" x-cloak
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs"
         @keydown.escape.window="closeScanner()">

        <div class="bg-white rounded-[32px] border border-slate-200 w-full max-w-sm overflow-hidden shadow-2xl flex flex-col"
             @click.outside="closeScanner()">

            <div class="px-5 py-4 bg-[#174c39] text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <h3 class="font-bold text-sm">Scan Barcode Studio</h3>
                </div>
                <button type="button" @click="closeScanner()" aria-label="Tutup kamera" class="w-8 h-8 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center transition">
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
                    <p class="text-[11px] text-slate-500 mt-1">Pastikan HP terhubung ke <strong>WiFi Kantor</strong>.</p>
                </div>
                <button type="button" @click="closeScanner()" class="w-full py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 transition">
                    Tutup Kamera
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TIMER FOCUS MODAL (Accessible from any page via Header)   -->
    <!-- ========================================================= -->
    <div x-show="timer.modalOpen" x-cloak
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @keydown.escape.window="closeTimerModal()">

        <div class="bg-white rounded-[32px] border border-slate-200 w-full max-w-sm sm:max-w-md overflow-hidden shadow-2xl p-6 sm:p-7 space-y-5"
             @click.outside="closeTimerModal()">

            <!-- Modal Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Focus Timer</h3>
                        <span class="w-2.5 h-2.5 rounded-full" :class="timer.isRunning ? 'bg-emerald-500 animate-ping' : 'bg-slate-300'"></span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Waktu rekaman teratur. Hasil maksimal.</p>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button"
                            @click="testAlarmSound()"
                            class="w-9 h-9 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition border border-slate-200/60 shadow-2xs"
                            title="Tes Bunyi Notifikasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                    </button>
                    <button type="button"
                            @click="closeTimerModal()"
                            class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition text-xs font-bold">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Circular Progress Ring (Exact Match with Reference Image) -->
            <div class="py-2 flex flex-col items-center justify-center">
                <div class="relative w-56 h-56 flex items-center justify-center">
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
                        <span class="text-4xl font-black font-mono text-slate-900 tracking-tight block select-none" x-text="timerFormatted"></span>

                        <!-- Big Circular Black Play/Pause Button -->
                        <div class="mt-3.5">
                            <button type="button"
                                    @click="toggleTimer()"
                                    class="w-13 h-13 rounded-full bg-[#174c39] text-white flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition"
                                    :title="timer.isRunning ? 'Jeda Timer' : 'Mulai Timer'">
                                <svg x-show="!timer.isRunning" class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <svg x-show="timer.isRunning" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Presets & Manual Duration Controls (15-20-30 & Manual) -->
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
                                ? 'bg-[#174c39] text-white border-[#1C1C1E] shadow-sm'
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                            15 Menit
                    </button>
                    <button type="button"
                            @click="setTimerPreset(20)"
                            class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                            :class="timer.selectedPreset === 20
                                ? 'bg-[#174c39] text-white border-[#1C1C1E] shadow-sm'
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                            20 Menit
                    </button>
                    <button type="button"
                            @click="setTimerPreset(30)"
                            class="py-2.5 px-3 rounded-2xl text-xs font-black transition border text-center shadow-2xs"
                            :class="timer.selectedPreset === 30
                                ? 'bg-[#174c39] text-white border-[#1C1C1E] shadow-sm'
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
                               placeholder="Menit manual (cth: 25, 45, 60)..."
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

            <!-- Footer Controls -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-2">
                <button type="button"
                        @click="resetTimer()"
                        class="px-4 py-2.5 rounded-2xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Reset</span>
                </button>
                <button type="button"
                        @click="closeTimerModal()"
                        class="flex-1 py-2.5 rounded-2xl bg-[#174c39] text-white text-xs font-black hover:bg-black transition text-center shadow-xs">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- WAKTU HABIS! ALARM NOTIFICATION ALERT (SHUFFLE + LOOP 3X) -->
    <!-- ========================================================= -->
    <div x-show="timer.showAlarmAlert" x-cloak
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md">

        <div class="bg-white rounded-[32px] border-2 border-emerald-400 w-full max-w-sm overflow-hidden shadow-2xl p-6 sm:p-7 text-center space-y-5 animate-pulse">

            <!-- Animated Alarm Icon -->
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-800 mx-auto flex items-center justify-center shadow-inner">
                <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <!-- Title & Status -->
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 mb-2">
                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                    <span>Waktu Habis</span>
                </span>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">Waktu Rekaman Selesai!</h3>
                <p class="text-xs text-slate-500 mt-1">Audio notifikasi sedang berputar acak (loop 3x).</p>
            </div>

            <!-- Direct Actions -->
            <div class="space-y-2.5 pt-1">
                <!-- STOP ALARM BUTTON (MOST PROMINENT) -->
                <button type="button"
                        @click="stopAlarm(); timer.showAlarmAlert = false"
                        class="w-full py-4 rounded-2xl bg-rose-600 hover:bg-rose-700 active:scale-98 text-white font-black text-sm shadow-xl shadow-rose-600/30 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                    <span>Matikan Suara Alarm</span>
                </button>

                <!-- RESTART NEW SESSION -->
                <button type="button"
                        @click="stopAlarm(); timer.showAlarmAlert = false; resetTimer(); startTimer();"
                        class="w-full py-3 rounded-2xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:brightness-105 active:scale-98 text-slate-950 font-black text-xs shadow-md shadow-emerald-950/10 transition">
                    Mulai Sesi Baru Lagi
                </button>

                <!-- CLOSE BUTTON -->
                <button type="button"
                        @click="stopAlarm(); timer.showAlarmAlert = false"
                        class="w-full py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</body>
</html>
