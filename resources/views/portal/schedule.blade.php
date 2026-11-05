@extends('portal.layout', ['currentPage' => 'jadwal', 'pageTitle' => 'Jadwal Shift'])

@section('content')
<div class="space-y-6">

    <!-- Title & Month Switcher -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Jadwal Shift</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Kalender sesi kerja bulanan dan shift aktif</p>
        </div>
        <input type="month" aria-label="Bulan jadwal shift" x-model="month" @change="load()" :disabled="loading" class="h-9 rounded-2xl border border-slate-200 px-3 text-xs font-bold text-slate-800 bg-white shadow-2xs">
    </div>

    <!-- HORIZONTAL 7-DAY STRIP (EXACT SCREEN 2 LAYOUT) -->
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

                <!-- Date Number (Solid black circle when selected, exactly like Screen 2) -->
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
                                      x-text="cell.attendance ? '✓ Masuk' : (cell.sessions?.length ? cell.sessions.length + ' sesi' : 'Libur')"></span>
                            </button>
                            <span x-show="!cell.date" class="block min-h-[72px] bg-slate-50/40 sm:min-h-[84px]"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right: Selected Date Session Timeline (Screen 2 Timeline Style) -->
        <div class="lg:col-span-5 space-y-3">
            <div class="rounded-[28px] border border-slate-100 bg-white p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Detail Sesi</span>
                    <span x-show="selectedDate === today" class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Hari Ini</span>
                </div>

                <h3 class="text-base font-semibold text-slate-900" x-text="dateLabel(selectedDate)"></h3>

                <!-- Sessions List -->
                <div class="space-y-2.5">
                    <template x-for="session in selectedDay.sessions" :key="session.id">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="text-center w-12 shrink-0">
                                    <span class="text-xs font-semibold text-slate-900 block font-mono" x-text="session.start"></span>
                                    <span class="text-[10px] font-bold text-slate-400 block font-mono" x-text="session.end"></span>
                                </div>
                                <div class="h-6 w-px bg-slate-200"></div>
                                <div>
                                    <p class="font-semibold text-xs text-slate-900" x-text="'Sesi ' + session.id"></p>
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
@endsection
