<!-- MATRIKS JADWAL SHIFT KALENDER (ROSTER VIEW) -->
<div class="space-y-6">

    <!-- TOP CONTROL BAR -->
    <div class="bg-white border border-slate-200/90 rounded-3xl p-5 shadow-xs flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-emerald-50 text-[#009B7E]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
                <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Matriks Kalender Shift</h2>
                <span class="hidden sm:inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600" x-text="rosterEmployees.length + ' Anggota'"></span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Klik pada sel nama & tanggal untuk mengubah shift. Jadwal Senin otomatis libur sesuai aturan studio.
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap w-full lg:w-auto justify-between lg:justify-end">
            <!-- Mode Switch: Mingguan vs Bulanan -->
            <div class="flex items-center bg-slate-100 rounded-xl p-1 border border-slate-200/60 shadow-2xs">
                <button type="button" 
                        @click="rosterViewMode = 'weekly'; setRosterWeekToToday()" 
                        :class="rosterViewMode === 'weekly' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-500 hover:text-slate-800 font-semibold'" 
                        class="px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    <span>Perminggu</span>
                </button>
                <button type="button" 
                        @click="rosterViewMode = 'monthly'" 
                        :class="rosterViewMode === 'monthly' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-500 hover:text-slate-800 font-semibold'" 
                        class="px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Per Siklus Cut-off</span>
                </button>
            </div>

            <!-- Weekly Navigator Controls -->
            <div x-show="rosterViewMode === 'weekly'" class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/80 rounded-xl p-1">
                <button type="button" 
                        @click="moveRosterWeek(-1)" 
                        :disabled="rosterWeekIndex <= 0" 
                        class="p-1 rounded-lg text-slate-600 hover:bg-white disabled:opacity-30 disabled:pointer-events-none transition" 
                        title="Minggu Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <span class="text-xs font-bold text-slate-800 px-2 select-none" x-text="activeWeekLabel"></span>
                <button type="button" 
                        @click="moveRosterWeek(1)" 
                        :disabled="rosterWeekIndex >= rosterWeeks.length - 1" 
                        class="p-1 rounded-lg text-slate-600 hover:bg-white disabled:opacity-30 disabled:pointer-events-none transition" 
                        title="Minggu Berikutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </button>
                <button type="button" 
                        @click="setRosterWeekToToday()" 
                        class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-[#009B7E] text-[11px] font-bold transition ml-1" 
                        title="Lompat ke Minggu Ini">
                    Minggu Ini
                </button>
            </div>

            <!-- Export Actions -->
            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="exportRosterCsv()" 
                        class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-1.5" 
                        title="Unduh jadwal format CSV / Excel">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span class="hidden sm:inline">Export Excel</span>
                </button>
                <button type="button" 
                        onclick="window.print()" 
                        class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-1.5" 
                        title="Cetak format kertas / PDF">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span class="hidden sm:inline">Cetak</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN ROSTER TABLE CONTAINER -->
    <div class="bg-white border border-slate-200/90 rounded-3xl overflow-hidden shadow-xs relative">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full border-collapse text-xs select-none">
                <!-- TABLE HEADER -->
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200/80">
                        <!-- Sticky No Column -->
                        <th class="w-12 py-3 px-2 text-center text-slate-400 font-bold uppercase tracking-wider sticky left-0 z-20 bg-slate-50/95 border-r border-slate-200/70">
                            No
                        </th>
                        <!-- Sticky Name Column -->
                        <th class="w-48 py-3 px-3 text-left text-slate-700 font-bold uppercase tracking-wider sticky left-12 z-20 bg-slate-50/95 border-r border-slate-200 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.05)]">
                            Nama Kontributor
                        </th>
                        <!-- Date Columns -->
                        <template x-for="date in activeRosterDates" :key="'th-' + date">
                            <th class="py-2.5 px-1.5 text-center transition-colors border-r border-slate-100"
                                :class="isDateDayOff(date) ? 'bg-rose-500 text-white font-extrabold' : (date === today ? 'bg-emerald-50/80 text-emerald-950 font-extrabold' : 'text-slate-700 font-semibold')"
                                :style="rosterViewMode === 'weekly' ? 'min-width: 105px;' : 'min-width: 68px;'">
                                <div class="text-[11px] font-mono tracking-tight" x-text="formatDateHeader(date)"></div>
                                <div class="text-[10px] mt-0.5 uppercase tracking-wide opacity-90" x-text="formatDayName(date)"></div>
                                <template x-if="date === today && !isDateDayOff(date)">
                                    <div class="inline-block mt-0.5 px-1 rounded bg-[#00B090] text-white text-[9px] font-bold">Kini</div>
                                </template>
                            </th>
                        </template>
                    </tr>
                </thead>

                <!-- TABLE BODY (EMPLOYEE ROWS) -->
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(emp, idx) in rosterEmployees" :key="emp.contributor_id || emp.name">
                        <tr class="hover:bg-slate-50/80 transition group">
                            <!-- Sticky No Cell -->
                            <td class="w-12 py-3 px-2 text-center text-slate-400 font-semibold sticky left-0 z-10 bg-white group-hover:bg-slate-50/95 border-r border-slate-100" x-text="idx + 1"></td>
                            
                            <!-- Sticky Name Cell -->
                            <td class="w-48 py-3 px-3 text-left font-bold text-slate-900 sticky left-12 z-10 bg-white group-hover:bg-slate-50/95 border-r border-slate-200 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.05)]">
                                <div class="flex items-center gap-2.5 truncate">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-100/70 text-[#007F67] flex items-center justify-center text-[10px] font-extrabold shrink-0" x-text="emp.initials"></span>
                                    <span class="truncate" :title="emp.name" x-text="emp.name"></span>
                                </div>
                            </td>

                            <!-- Dynamic Date Shift Cells -->
                            <template x-for="date in activeRosterDates" :key="'cell-' + (emp.contributor_id || emp.name) + '-' + date">
                                <td @click.stop="openRosterEdit(emp, date)"
                                    title="Klik untuk ubah shift"
                                    class="py-2.5 px-1.5 text-center cursor-pointer transition-all border-r border-slate-100/80 hover:ring-2 hover:ring-inset hover:ring-[#00B090]/50"
                                    :class="isDateDayOff(date) ? 'bg-rose-50/40 hover:bg-rose-100/50' : (date === today ? 'bg-emerald-50/20 hover:bg-emerald-50/50' : 'hover:bg-slate-100/70')">
                                    
                                    <div class="flex items-center justify-center">
                                        <!-- Cell Info Rendering -->
                                        <template x-if="getRosterCellInfo(emp, date).type === 'libur'">
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-md text-[11px] font-extrabold bg-rose-500 text-white shadow-2xs">
                                                Libur
                                            </span>
                                        </template>

                                        <template x-if="getRosterCellInfo(emp, date).type === 'izin'">
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-md text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                Izin
                                            </span>
                                        </template>

                                        <template x-if="getRosterCellInfo(emp, date).type === 'sakit'">
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-md text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-300">
                                                Sakit
                                            </span>
                                        </template>

                                        <template x-if="getRosterCellInfo(emp, date).type === 'multi'">
                                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-200 tracking-tight shadow-2xs group-hover:scale-105 transition">
                                                <span x-text="getRosterCellInfo(emp, date).text"></span>
                                            </span>
                                        </template>

                                        <template x-if="getRosterCellInfo(emp, date).type === 'shift'">
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-md text-xs font-bold transition group-hover:scale-105 shadow-2xs"
                                                  :class="{
                                                      'bg-blue-50 text-blue-700 border border-blue-200': getRosterCellInfo(emp, date).text === '1',
                                                      'bg-teal-50 text-teal-700 border border-teal-200': getRosterCellInfo(emp, date).text === '2',
                                                      'bg-emerald-50 text-emerald-800 border border-emerald-200 font-extrabold': getRosterCellInfo(emp, date).text === '3',
                                                      'bg-amber-50 text-amber-800 border border-amber-200': getRosterCellInfo(emp, date).text === '4',
                                                      'bg-purple-50 text-purple-700 border border-purple-200': getRosterCellInfo(emp, date).text === '5',
                                                      'bg-slate-100 text-slate-700': !['1','2','3','4','5'].includes(getRosterCellInfo(emp, date).text)
                                                  }">
                                                <span x-text="getRosterCellInfo(emp, date).text"></span>
                                            </span>
                                        </template>

                                        <template x-if="getRosterCellInfo(emp, date).type === 'normal' && !isDateDayOff(date)">
                                            <span class="text-slate-300 font-semibold group-hover:text-slate-500">-</span>
                                        </template>
                                    </div>

                                    <!-- Custom Revision Dot Indicator -->
                                    <template x-if="getRosterCellInfo(emp, date).hasCustomRevision">
                                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 mx-auto mt-1" title="Ada revisi khusus tanggal ini"></div>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>

                <!-- TABLE FOOTER: LIVE CAPACITY PER SHIFT -->
                <tfoot class="bg-slate-50/95 border-t-2 border-slate-200 font-semibold">
                    <template x-for="s in shifts" :key="'footer-shift-' + s.id">
                        <tr class="text-[11px] border-b border-slate-100">
                            <td class="w-12 py-1.5 px-2 text-center text-slate-400 sticky left-0 z-20 bg-slate-50 border-r border-slate-200/70">
                                <span class="font-bold text-[10px]" x-text="'S' + s.id"></span>
                            </td>
                            <td class="w-48 py-1.5 px-3 text-left font-bold text-slate-700 sticky left-12 z-20 bg-slate-50 border-r border-slate-200 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.05)]">
                                <span x-text="'Kuota Shift ' + s.id"></span>
                            </td>
                            <template x-for="date in activeRosterDates" :key="'foot-s-' + s.id + '-' + date">
                                <td class="py-1.5 px-1 text-center border-r border-slate-100 font-mono"
                                    :class="isDateDayOff(date) ? 'bg-rose-50/60 text-rose-400' : (getShiftCountForDate(s.id, date) === 3 ? 'text-emerald-700 font-bold' : (getShiftCountForDate(s.id, date) === 0 ? 'text-amber-600 font-bold' : 'text-slate-600'))">
                                    <span x-show="!isDateDayOff(date)" x-text="getShiftCountForDate(s.id, date) + ' / 3'"></span>
                                    <span x-show="isDateDayOff(date)" class="text-[10px]">-</span>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- SHIFT LEGEND / KETERANGAN CARD (PERSIS SEPERTI FORMAT SPREADSHEET) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <div class="p-5 rounded-3xl bg-white border border-slate-200/90 shadow-2xs space-y-3">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Keterangan Jam Kerja Shift Studio (WITA)</span>
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <template x-for="s in shifts" :key="'legend-' + s.id">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="font-extrabold text-slate-800" x-text="'Shift ' + s.id"></span>
                        <span class="font-mono text-slate-600 font-semibold" x-text="s.start + ' – ' + s.end + ' WITA'"></span>
                    </div>
                </template>
                <div x-show="config?.day_off && config.day_off !== 'none'" class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800">
                    <span class="font-bold capitalize" x-text="config?.day_off === 'monday' ? 'Senin' : config?.day_off"></span>
                    <span class="font-extrabold text-rose-700">Libur Rutin Mingguan</span>
                </div>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-gradient-to-br from-white to-emerald-50/30 border border-emerald-100 shadow-2xs space-y-2 text-xs text-slate-600">
            <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#00B090]"></span>
                <span>Panduan Cepat Pengaturan</span>
            </h4>
            <p class="leading-relaxed">
                • <strong>Klik pada sel:</strong> Membuka jendela pengaturan shift pekerja untuk tanggal tersebut. Anda dapat memilih 1 shift atau multi-shift (misal `1-2`).
            </p>
            <p class="leading-relaxed">
                • <strong>Terapkan ke Semua Hari:</strong> Centang opsi di dalam jendela edit untuk menerapkan shift tersebut secara otomatis ke seluruh hari kerja aktif dalam periode (sangat cocok untuk pekerja dengan jam rutin).
            </p>
            <p class="leading-relaxed">
                • <strong>Titik Biru / Ungu:</strong> Menandakan tanggal tersebut memiliki penyesuaian harian khusus yang berbeda dari pola dasar.
            </p>
        </div>
    </div>

    <!-- QUICK EDIT MODAL / POPOVER FOR ROSTER CELL -->
    <template x-if="rosterEditCell">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs select-none"
             @click="closeRosterEdit()"
             @keydown.escape.window="closeRosterEdit()"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.stop
                 class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-lg p-6 space-y-4 text-slate-800 animate-in fade-in zoom-in-95 duration-150">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-emerald-100/70 text-[#007F67] flex items-center justify-center text-xs font-black shrink-0" x-text="rosterEditCell.emp.initials"></span>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight" x-text="rosterEditCell.personName"></h3>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-slate-500 font-mono" x-text="dateLabel(rosterEditCell.date)"></span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span class="text-[11px] font-bold" 
                                      :class="rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length ? 'text-[#009B7E]' : 'text-slate-500'" 
                                      x-text="rosterEditCell.status === 'scheduled' ? (rosterEditCell.sessions.length ? 'Saat ini: Shift ' + rosterEditCell.sessions.join('-') : 'Belum ada shift') : 'Status: ' + rosterEditCell.status"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="closeRosterEdit()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Scope Switcher: Hanya Hari Ini vs Sepanjang Periode -->
                <div class="space-y-1">
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Terapkan Perubahan Untuk:</label>
                    <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100/80 rounded-2xl border border-slate-200/60 text-xs font-bold">
                        <button type="button" 
                                @click="rosterEditCell.applyToAll = false" 
                                :class="!rosterEditCell.applyToAll ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                                class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :class="!rosterEditCell.applyToAll ? 'bg-[#00B090]' : 'bg-slate-300'"></span>
                            <span x-text="'Hanya Tanggal Ini (' + formatDateHeader(rosterEditCell.date) + ')'"></span>
                        </button>
                        <button type="button" 
                                @click="rosterEditCell.applyToAll = true" 
                                :class="rosterEditCell.applyToAll ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                                class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :class="rosterEditCell.applyToAll ? 'bg-indigo-600' : 'bg-slate-300'"></span>
                            <span>Sepanjang Periode (Rutin)</span>
                        </button>
                    </div>
                </div>

                <!-- 1-Click Shift Selector (1, 2, 3, 4, 5) -->
                <div class="space-y-2 pt-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black text-slate-800">Klik 1x untuk Langsung Ubah Shift:</label>
                        <span x-show="saving" class="text-xs font-bold text-[#009B7E] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Menyimpan...</span>
                        </span>
                    </div>

                    <!-- Single Shifts 1 to 5 Buttons -->
                    <div class="grid grid-cols-5 gap-2">
                        <template x-for="s in shifts" :key="'quick-btn-' + s.id">
                            <button type="button" 
                                    @click="quickSetShift([s.id])" 
                                    :disabled="saving"
                                    class="p-2.5 rounded-2xl border text-center transition flex flex-col items-center justify-center gap-1 hover:scale-105 active:scale-95 shadow-2xs group"
                                    :class="{
                                        'bg-blue-50 border-blue-400 text-blue-900 ring-2 ring-blue-400/30 font-black': rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length === 1 && rosterEditCell.sessions[0] === s.id && s.id === 1,
                                        'bg-teal-50 border-teal-400 text-teal-900 ring-2 ring-teal-400/30 font-black': rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length === 1 && rosterEditCell.sessions[0] === s.id && s.id === 2,
                                        'bg-emerald-50 border-emerald-400 text-emerald-950 ring-2 ring-emerald-400/30 font-black': rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length === 1 && rosterEditCell.sessions[0] === s.id && s.id === 3,
                                        'bg-amber-50 border-amber-400 text-amber-900 ring-2 ring-amber-400/30 font-black': rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length === 1 && rosterEditCell.sessions[0] === s.id && s.id === 4,
                                        'bg-purple-50 border-purple-400 text-purple-900 ring-2 ring-purple-400/30 font-black': rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length === 1 && rosterEditCell.sessions[0] === s.id && s.id === 5,
                                        'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300': !(rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.length === 1 && rosterEditCell.sessions[0] === s.id)
                                    }">
                                <span class="text-sm font-black" x-text="'Shift ' + s.id"></span>
                                <span class="text-[9px] font-mono opacity-75" x-text="s.start"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Common Combination & Status Options -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                        <button type="button" 
                                @click="quickSetShift([1, 2])"
                                :disabled="saving"
                                class="p-2.5 rounded-xl border text-xs font-black transition text-center hover:scale-105 active:scale-95 shadow-2xs"
                                :class="rosterEditCell.status === 'scheduled' && rosterEditCell.sessions.join('-') === '1-2' ? 'bg-indigo-600 text-white border-indigo-700 ring-2 ring-indigo-300' : 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'">
                            Shift 1-2
                        </button>
                        
                        <button type="button" 
                                @click="quickSetShift([], 'libur')"
                                :disabled="saving"
                                class="p-2.5 rounded-xl border text-xs font-black transition text-center hover:scale-105 active:scale-95 shadow-2xs"
                                :class="rosterEditCell.status === 'libur' ? 'bg-rose-600 text-white border-rose-700 ring-2 ring-rose-300' : 'bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100'">
                            Libur
                        </button>

                        <button type="button" 
                                @click="quickSetShift([], 'izin')"
                                :disabled="saving"
                                class="p-2.5 rounded-xl border text-xs font-black transition text-center hover:scale-105 active:scale-95 shadow-2xs"
                                :class="rosterEditCell.status === 'izin' ? 'bg-amber-600 text-white border-amber-700 ring-2 ring-amber-300' : 'bg-amber-50 border-amber-200 text-amber-800 hover:bg-amber-100'">
                            Izin
                        </button>

                        <button type="button" 
                                @click="quickSetShift([], 'sakit')"
                                :disabled="saving"
                                class="p-2.5 rounded-xl border text-xs font-black transition text-center hover:scale-105 active:scale-95 shadow-2xs"
                                :class="rosterEditCell.status === 'sakit' ? 'bg-purple-600 text-white border-purple-700 ring-2 ring-purple-300' : 'bg-purple-50 border-purple-200 text-purple-700 hover:bg-purple-100'">
                            Sakit
                        </button>
                    </div>
                </div>

                <!-- Custom Multi-Shift Accordion for Advanced Combinations -->
                <details class="group pt-2 border-t border-slate-100">
                    <summary class="text-xs font-bold text-slate-500 hover:text-slate-800 cursor-pointer flex items-center justify-between py-1">
                        <span>Atur kombinasi shift custom manual...</span>
                        <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <div class="mt-2 space-y-3 p-3 bg-slate-50 rounded-2xl border border-slate-200/80">
                        <div class="grid grid-cols-5 gap-1.5">
                            <template x-for="s in shifts" :key="'custom-cb-' + s.id">
                                <label class="p-2 rounded-xl border text-center cursor-pointer transition flex flex-col items-center justify-center gap-1"
                                       :class="rosterEditCell.sessions.includes(s.id) ? 'bg-emerald-50 border-[#00B090] text-emerald-900 font-bold' : 'bg-white border-slate-200 text-slate-600'">
                                    <input type="checkbox" :checked="rosterEditCell.sessions.includes(s.id)" @change="toggleRosterEditSession(s.id)" class="sr-only">
                                    <span class="text-xs font-black" x-text="'S' + s.id"></span>
                                    <span x-show="rosterEditCell.sessions.includes(s.id)" class="text-[10px] text-[#00B090] font-black">✓ Terpilih</span>
                                </label>
                            </template>
                        </div>
                        <div class="flex items-center justify-end">
                            <button type="button" 
                                    @click="saveRosterEdit()" 
                                    :disabled="saving"
                                    class="px-4 py-2 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan Kombinasi Manual'"></span>
                            </button>
                        </div>
                    </div>
                </details>

                <!-- Feedback Error if any -->
                <template x-if="error">
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold flex items-center justify-between">
                        <span x-text="error"></span>
                        <button type="button" @click="error=''" class="text-rose-500 hover:text-rose-800 font-bold">×</button>
                    </div>
                </template>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <button type="button" 
                            @click="quickSetShift([], 'scheduled')" 
                            :disabled="saving"
                            class="text-xs font-semibold text-slate-400 hover:text-rose-600 underline transition">
                        Kosongkan Jadwal (-)
                    </button>
                    <button type="button" 
                            @click="closeRosterEdit()" 
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
