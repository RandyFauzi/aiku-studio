<header class="sticky top-0 z-20 flex min-h-[80px] shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen=!sidebarOpen" aria-label="Buka menu" class="h-11 w-11 rounded-lg text-slate-600 hover:bg-slate-100 transition">
            <svg class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-semibold text-slate-700">Laporan kerja harian</span>
    </div>
    <div class="flex items-center gap-2">
        <a href="/recordings" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Semua Rekaman ↗</span>
        </a>
        <a href="/contributors" class="text-xs sm:text-sm font-semibold text-brand-700 hover:text-brand-800 transition">Teams & Kontributor →</a>
    </div>
</header>

<section x-data="adminWorkReportsData()" class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-700">Aktivitas tim</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 tracking-tight">Laporan Kerja & Assist Pencocokan</h1>
            <p class="mt-1 text-xs sm:text-sm text-slate-500">
                Pencocokan rekaman bot berbasis akun HP, tanggal WITA, dan durasi menit yang dilaporkan kontributor.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openCreateModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-2xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Buat Laporan</span>
            </button>
            <button type="button" @click="load()" :disabled="loading || assistLoading" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 shadow-2xs transition">
                <svg class="w-3.5 h-3.5 text-slate-500" :class="(loading || assistLoading) ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Muat Ulang Data</span>
            </button>
        </div>
    </div>

    <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"></p>

    <!-- Filter Bar Modern AIKu Style -->
    <div class="flex flex-col lg:flex-row lg:items-end gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-2xs">
        
        <!-- 1. KONTROL TANGGAL (WITA) DENGAN FLOATING CALENDAR POPOVER -->
        <div class="w-full lg:w-auto space-y-1.5" @click.outside="calendarOpen = false">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                    <span>📅</span>
                    <span>Pilih Tanggal (WITA)</span>
                </span>
                <span x-show="isTodaySelected()" class="text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/70">
                    Hari Ini
                </span>
            </div>

            <!-- Button Group Navigator Tanggal -->
            <div class="relative flex items-center gap-1.5 bg-slate-50 p-1 rounded-2xl border border-slate-200/80">
                <!-- Tombol Hari Sebelumnya -->
                <button type="button" 
                        @click="changeDateOffset(-1)" 
                        class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-slate-800 hover:shadow-2xs transition cursor-pointer" 
                        title="Hari Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <!-- Tombol Pintas Hari Ini -->
                <button type="button" 
                        @click="setDateToToday()" 
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap"
                        :class="isTodaySelected() ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200/60'">
                    Hari Ini
                </button>

                <!-- Tombol Trigger Utama Tanggal (Membuka Floating Calendar Popover) -->
                <button type="button" 
                        @click="calendarOpen = !calendarOpen" 
                        class="flex-1 sm:flex-none flex items-center justify-between gap-2.5 px-3.5 py-1.5 rounded-xl bg-white hover:bg-emerald-50/50 border text-slate-800 text-xs sm:text-sm font-bold shadow-2xs transition cursor-pointer"
                        :class="calendarOpen ? 'border-emerald-500 ring-2 ring-emerald-200' : 'border-slate-200/90'">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="whitespace-nowrap" x-text="formatSelectedDate()"></span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200" 
                         :class="calendarOpen ? 'rotate-180 text-emerald-600' : ''" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Tombol Hari Berikutnya -->
                <button type="button" 
                        @click="changeDateOffset(1)" 
                        class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-slate-800 hover:shadow-2xs transition cursor-pointer" 
                        title="Hari Berikutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

                <!-- ======================================================== -->
                <!-- FLOATING CALENDAR POPOVER (ALA AIKU STUDIO / TIKET.COM)  -->
                <!-- ======================================================== -->
                <div x-show="calendarOpen" 
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                     class="absolute top-full left-0 mt-2 z-50 bg-white border border-slate-200/90 rounded-3xl shadow-2xl p-4 sm:p-5 w-[310px] select-none"
                     x-cloak>
                    
                    <!-- Header Popover: Title & Close Button -->
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <span class="text-xs font-extrabold text-slate-800 tracking-tight">Pilih Tanggal Laporan</span>
                        </div>
                        <button type="button" @click="calendarOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition" title="Tutup">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Presets Cepat Bar -->
                    <div class="flex items-center gap-1.5 pb-3 mb-3 border-b border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-0.5">Pintas:</span>
                        <button type="button" @click="setDateToToday()" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer" :class="isTodaySelected() ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'">Hari Ini</button>
                        <button type="button" @click="setDateToYesterday()" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer">Kemarin</button>
                        <button type="button" @click="changeDateOffset(-2)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer">H-2</button>
                    </div>

                    <!-- Month Switcher Header -->
                    <template x-if="calendarMonth">
                        <div>
                            <div class="flex items-center justify-between mb-3 px-1">
                                <button type="button" 
                                        @click="calendarPrevMonth()" 
                                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition cursor-pointer" 
                                        title="Bulan Sebelumnya">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="text-xs sm:text-sm font-extrabold text-slate-800" x-text="calendarMonth.title"></span>
                                <button type="button" 
                                        @click="calendarNextMonth()" 
                                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition cursor-pointer" 
                                        title="Bulan Berikutnya">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>

                            <!-- Indonesian Days of Week -->
                            <div class="grid grid-cols-7 mb-1.5 text-center">
                                <span class="text-[11px] font-bold text-rose-500">Min</span>
                                <span class="text-[11px] font-semibold text-slate-400">Sen</span>
                                <span class="text-[11px] font-semibold text-slate-400">Sel</span>
                                <span class="text-[11px] font-semibold text-slate-400">Rab</span>
                                <span class="text-[11px] font-semibold text-slate-400">Kam</span>
                                <span class="text-[11px] font-semibold text-slate-400">Jum</span>
                                <span class="text-[11px] font-semibold text-slate-400">Sab</span>
                            </div>

                            <!-- Calendar Days Grid -->
                            <div class="grid grid-cols-7 gap-y-1">
                                <template x-for="item in calendarMonth.days" :key="item.dateStr">
                                    <div class="flex items-center justify-center p-0.5">
                                        <button type="button"
                                                @click="selectDate(item.dateStr)"
                                                class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-semibold transition-all cursor-pointer"
                                                :class="{
                                                    'bg-emerald-600 text-white font-bold shadow-xs scale-105 ring-2 ring-emerald-300': item.dateStr === date,
                                                    'border border-emerald-500 text-emerald-700 font-bold bg-emerald-50/50': item.dateStr === today() && item.dateStr !== date,
                                                    'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800': item.isCurrentMonth && item.dateStr !== date && item.dateStr !== today(),
                                                    'text-slate-300 hover:bg-slate-50': !item.isCurrentMonth && item.dateStr !== date
                                                }"
                                                x-text="item.day">
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- 2. CARI NAMA KONTRIBUTOR -->
        <div class="w-full lg:flex-1 space-y-1.5">
            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <span>🔍</span>
                <span>Cari Nama Kontributor</span>
            </span>
            <div class="relative">
                <input type="search" 
                       x-model="search" 
                       placeholder="Ketik nama kontributor…" 
                       class="h-11 w-full rounded-2xl border border-slate-200/90 bg-slate-50/60 pl-10 pr-9 text-xs sm:text-sm font-medium focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <button type="button" 
                        x-show="search" 
                        @click="search = ''" 
                        class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-200 transition cursor-pointer"
                        title="Hapus pencarian">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500">Pekerja Melapor</p>
            <p class="mt-2 text-2xl font-black text-slate-900" x-text="new Set(rows.map(r => r.contributor_id)).size + ' Orang (' + rows.length + ' Sesi)'"></p>
            <p class="text-[11px] text-slate-400 mt-0.5" x-text="dateLabel(date)"></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500">Total Durasi Dilaporkan</p>
            <p class="mt-2 text-2xl font-black text-emerald-800" x-text="total + ' Menit'"></p>
            <p class="text-[11px] text-slate-400 mt-0.5" x-text="'Setara ' + duration(total)"></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500">Metode Verifikasi</p>
            <p class="mt-2 text-base font-extrabold text-slate-900">Semi-Otomatis (Assist)</p>
            <p class="text-[11px] text-emerald-700 font-semibold mt-0.5">Admin memegang kendali penugasan</p>
        </div>
    </div>

    <!-- Reports Table with Expandable Assist Drawer -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-slate-900" x-text="'Daftar Laporan ' + dateLabel(date)"></h2>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan laporan harian. Buka Detail untuk melihat email, task, dan riwayat pengiriman.</p>
            </div>
            <span class="text-xs font-semibold text-slate-400" x-text="filtered.length + ' laporan'"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="block w-full text-left text-sm md:table" data-report-summary>
                <thead class="hidden bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 md:table-header-group">
                    <tr>
                        <th class="p-4">Kontributor</th>
                        <th class="p-4">Total Menit</th>
                        <th class="p-4">HP Digunakan</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <template x-for="report in filtered" :key="report.id">
                    <tbody class="block md:table-row-group">
                        <tr class="grid grid-cols-2 border-t border-slate-100 transition md:table-row"
                            :class="expandedReportId === report.id ? 'bg-emerald-50/20' : 'hover:bg-slate-50/70'">
                            <td class="col-span-2 block px-4 pt-4 pb-2 align-middle md:table-cell md:py-4">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-bold text-slate-900" x-text="report.contributor_name"></p>
                                    <span x-show="report.shift_label" class="px-2 py-0.5 rounded-md text-[11px] font-black bg-emerald-100 text-emerald-950 border border-emerald-300" x-text="report.shift_label"></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500" :title="reportTimestamp(report.created_at)" x-text="'Dikirim ' + (reportTimestamp(report.created_at).match(/\d{2}:\d{2} WITA$/)?.[0] || '—') + (report.revision > 1 ? ' · Direvisi' : '')"></p>
                            </td>
                            <td class="block px-4 py-2 align-middle md:table-cell md:py-4">
                                <span class="mb-1 block text-xs text-slate-500 md:hidden">Total durasi</span>
                                <span class="font-mono text-base font-bold text-emerald-900" x-text="report.total_duration_minutes"></span>
                                <span class="text-xs text-slate-500">menit</span>
                            </td>
                            <td class="block min-w-0 px-4 py-2 align-middle md:table-cell md:py-4">
                                <span class="mb-1 block text-xs text-slate-500 md:hidden">HP digunakan</span>
                                <p class="text-xs leading-5 text-slate-700 md:hidden" x-text="(report.phones?.length || 0) + ' HP · ' + (report.phones || []).slice(0, 2).map(pkey => phoneOptions[pkey] || pkey).join(', ') + (report.phones?.length > 2 ? ' +' + (report.phones.length - 2) : '')"></p>
                                <div class="hidden flex-wrap gap-1.5 md:flex">
                                    <template x-for="pkey in (report.phones || []).slice(0, 3)" :key="pkey">
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700">
                                            <span x-text="phoneOptions[pkey] || pkey"></span>
                                            <span x-show="report.phone_durations?.[pkey]" class="font-mono text-slate-500" x-text="(report.phone_durations?.[pkey] || '') + 'm'"></span>
                                        </span>
                                    </template>
                                    <button type="button" x-show="report.phones?.length > 3" @click="openDetailModal(report)" class="min-h-11 px-2 text-xs font-semibold text-emerald-800" x-text="'+' + (report.phones.length - 3) + ' HP'"></button>
                                </div>
                            </td>
                            <td class="col-span-2 block px-4 pt-2 pb-4 text-right align-middle md:table-cell md:py-4">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <button type="button" @click="openDetailModal(report)" class="min-h-11 rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detail</button>
                                    <button type="button" @click="toggleExpandReport(report.id)" class="min-h-11 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-xs font-semibold text-emerald-900" :aria-expanded="expandedReportId === report.id" x-text="expandedReportId === report.id ? 'Tutup Assist' : 'Assist'"></button>
                                </div>
                            </td>
                        </tr>

                        <!-- EXPANDED ASSIST DRAWER ROW -->
                        <tr x-show="expandedReportId === report.id" class="block border-t border-slate-200/80 bg-slate-50/90 md:table-row">
                            <td colspan="4" class="block p-4 sm:p-6 md:table-cell">
                                <div class="space-y-4">
                                    <!-- Header Assist Drawer -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/70">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <h3 class="text-sm font-extrabold text-slate-900">
                                                    Assist Pencocokan: <span class="text-emerald-700" x-text="report.contributor_name"></span>
                                                </h3>
                                                <span x-show="report.shift_label" class="rounded-md bg-slate-200/80 px-2 py-0.5 text-[11px] font-bold text-slate-700" x-text="report.shift_label"></span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                Mencocokkan durasi HP laporan pekerja dengan rekaman bot otomatis pada hari yang sama.
                                            </p>
                                        </div>

                                        <!-- Header Right Actions & Quick Assign All -->
                                        <div class="flex items-center gap-2.5">
                                            <!-- Batch Assign All candidates from all phones -->
                                            <template x-if="getAssistSummary(report.id).totalCandidates > 0">
                                                <button type="button"
                                                        @click="assignCandidates(report.contributor_id, report.contributor_name, getAssistSummary(report.id).allCandidateIds)"
                                                        :disabled="assigning"
                                                        class="min-h-9 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span x-text="'Tandai Semua (' + getAssistSummary(report.id).totalCandidates + ' Rekaman)'"></span>
                                                </button>
                                            </template>

                                            <button type="button" @click="reloadAssistOnly()" :disabled="assistLoading" class="min-h-9 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 flex items-center gap-1.5 shadow-2xs transition">
                                                <svg class="w-3.5 h-3.5 text-slate-500" :class="assistLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                <span>Segarkan</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Assist Phones Cards Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <template x-for="pMatch in (getAssistForReport(report.id)?.phones || [])" :key="pMatch.phone_key">
                                            <div x-data="{ showTasks: false }"
                                                 class="rounded-2xl border p-4.5 bg-white shadow-xs space-y-3 transition"
                                                 :class="{
                                                     'border-emerald-300 ring-2 ring-emerald-400/20 bg-emerald-50/15': pMatch.unassigned_count > 0,
                                                     'border-emerald-200 bg-emerald-50/10': pMatch.status === 'fully_verified',
                                                     'border-slate-200/80 bg-slate-50/60 opacity-80 hover:opacity-100': pMatch.status === 'no_bot_data' && pMatch.already_assigned_count === 0,
                                                     'border-slate-200 bg-white': pMatch.status !== 'fully_verified' && pMatch.unassigned_count === 0 && pMatch.status !== 'no_bot_data'
                                                 }">
                                                
                                                <!-- Card Header: Device Name & Status Badge -->
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                            </div>
                                                            <span class="text-xs font-black text-slate-900 tracking-tight" x-text="'HP ' + pMatch.phone_label"></span>
                                                        </div>

                                                        <!-- Task info -->
                                                        <div class="mt-1.5 flex flex-wrap items-center gap-1 text-xs">
                                                            <span class="text-slate-400 text-[11px] font-semibold">Task:</span>
                                                            <template x-if="pMatch.reported_tasks && pMatch.reported_tasks.length > 0">
                                                                <div class="flex flex-wrap gap-1">
                                                                    <template x-for="tk in pMatch.reported_tasks" :key="tk">
                                                                        <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-medium" x-text="tk"></span>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <template x-if="!pMatch.reported_tasks || !pMatch.reported_tasks.length">
                                                                <span class="text-slate-400 italic text-[11px]">Tanpa rincian task</span>
                                                            </template>
                                                        </div>

                                                        <!-- Email accounts -->
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            <template x-if="pMatch.emails && pMatch.emails.length > 0">
                                                                <div class="flex flex-wrap gap-1">
                                                                    <template x-for="em in pMatch.emails" :key="em">
                                                                        <span class="inline-block px-1.5 py-0.5 rounded bg-slate-50 border border-slate-200/80 font-mono text-[10px] text-slate-500 break-all" x-text="em"></span>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <template x-if="!pMatch.emails || !pMatch.emails.length">
                                                                <span class="text-[11px] text-amber-700">Belum ada akun di HP ini</span>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <!-- Status Badge -->
                                                    <div class="shrink-0 text-right">
                                                        <template x-if="pMatch.status === 'fully_verified'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                                                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                                Terverifikasi Penuh
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'exact_match'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                                                Sangat Cocok (±15m)
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'close_match'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                                                                Mendekati (±45m)
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'over_bot'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200 shadow-2xs">
                                                                <span x-text="'Bot +' + pMatch.difference_minutes + 'm'"></span>
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'under_bot'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                                                                <span x-text="'Bot ' + pMatch.difference_minutes + 'm'"></span>
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'no_bot_data'">
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium bg-slate-100 text-slate-500">
                                                                Belum Ada Data Bot
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'personal_phone'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                                HP Sendiri
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'no_device_account'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                                                Email HP Belum Terdaftar
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Comparison Numbers Box -->
                                                <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                                    <div>
                                                        <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Laporan Pekerja</span>
                                                        <div class="mt-0.5 flex items-baseline gap-1">
                                                            <strong class="font-mono text-slate-800 text-base font-black" x-text="pMatch.reported_minutes"></strong>
                                                            <span class="text-slate-500 text-[11px] font-semibold">menit</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Rekaman Bot</span>
                                                        <div class="mt-0.5 flex items-baseline gap-1">
                                                            <strong class="font-mono text-emerald-700 text-base font-black" x-text="(pMatch.unassigned_minutes + pMatch.already_assigned_minutes).toFixed(1)"></strong>
                                                            <span class="text-slate-500 text-[11px] font-semibold">m</span>
                                                            <span class="text-[11px] text-slate-400 font-normal" x-text="'(' + (pMatch.unassigned_count + pMatch.already_assigned_count) + ' task)'"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons Row -->
                                                <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
                                                    <!-- 1-Click Assign Button -->
                                                    <template x-if="pMatch.candidate_record_ids && pMatch.candidate_record_ids.length > 0">
                                                        <button type="button" 
                                                                @click="assignCandidates(report.contributor_id, report.contributor_name, pMatch.candidate_record_ids)" 
                                                                :disabled="assigning"
                                                                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                                                            <svg class="w-3.5 h-3.5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                            <span>Tandai</span>
                                                            <span x-text="pMatch.candidate_record_ids.length + ' Rekaman ke ' + report.contributor_name"></span>
                                                        </button>
                                                    </template>

                                                    <!-- Revert / Unassign Button if already assigned -->
                                                    <template x-if="pMatch.my_record_ids && pMatch.my_record_ids.length > 0">
                                                        <button type="button" 
                                                                @click="unassignRecords(pMatch.my_record_ids)" 
                                                                :disabled="assigning"
                                                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 text-xs font-semibold text-slate-600 transition cursor-pointer">
                                                            <span x-text="'Lepas Penugasan (' + pMatch.my_record_ids.length + ' task)'"></span>
                                                        </button>
                                                    </template>

                                                    <!-- Task preview toggle button -->
                                                    <template x-if="pMatch.tasks && pMatch.tasks.length > 0">
                                                        <button type="button"
                                                                @click="showTasks = !showTasks"
                                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 hover:text-slate-800 py-1 transition cursor-pointer">
                                                            <span x-text="showTasks ? 'Tutup daftar task' : ('Lihat ' + pMatch.tasks.length + ' task rekaman')"></span>
                                                            <svg class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="showTasks ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>
                                                    </template>
                                                </div>

                                                <!-- Tasks Preview Modern List (Toggled via showTasks) -->
                                                <template x-if="pMatch.tasks && pMatch.tasks.length > 0">
                                                    <div x-show="showTasks" 
                                                         x-transition:enter="transition ease-out duration-150"
                                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                                         x-transition:enter-end="opacity-100 translate-y-0"
                                                         class="pt-2 border-t border-slate-100 text-xs space-y-1.5">
                                                        <div class="max-h-52 overflow-y-auto space-y-1.5 custom-scroll pr-1">
                                                            <template x-for="t in pMatch.tasks" :key="t.record_id">
                                                                <div class="flex items-center justify-between p-2 rounded-xl border text-[11px] transition"
                                                                     :class="t.is_assigned_to_me ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50/70 border-slate-100'">
                                                                    <div class="min-w-0 pr-2">
                                                                        <p class="font-bold text-slate-800 truncate" x-text="t.task_title"></p>
                                                                        <div class="flex items-center gap-2 mt-0.5">
                                                                            <span class="text-slate-500 font-mono text-[10px]" x-text="t.recorded_wita || t.recorded_utc"></span>
                                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider"
                                                                                  :class="{
                                                                                      'bg-emerald-100 text-emerald-800': t.processing_status === 'approved',
                                                                                      'bg-rose-100 text-rose-800': t.processing_status === 'rejected',
                                                                                      'bg-amber-100 text-amber-800': t.processing_status === 'pending'
                                                                                  }"
                                                                                  x-text="t.processing_status"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-right shrink-0 flex items-center gap-2">
                                                                        <div>
                                                                            <span class="font-mono font-bold block" :class="t.is_assigned_to_me ? 'text-emerald-700' : 'text-slate-700'" x-text="t.duration_min + 'm'"></span>
                                                                            <span class="block text-[10px]" :class="t.is_assigned_to_me ? 'text-emerald-600 font-bold' : 'text-slate-400'" x-text="t.is_assigned_to_me ? 'Ditandai' : 'Belum Ditandai'"></span>
                                                                        </div>
                                                                        <!-- Single task assign button -->
                                                                        <template x-if="!t.is_assigned_to_me">
                                                                            <button type="button"
                                                                                    @click="assignCandidates(report.contributor_id, report.contributor_name, [t.record_id])"
                                                                                    :disabled="assigning"
                                                                                    class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] shadow-2xs transition">
                                                                                Tandai
                                                                            </button>
                                                                        </template>
                                                                        <template x-if="t.is_assigned_to_me">
                                                                            <button type="button"
                                                                                    @click="unassignRecords([t.record_id])"
                                                                                    :disabled="assigning"
                                                                                    class="px-2 py-1 rounded-lg bg-white border border-slate-200 hover:bg-rose-50 text-rose-600 font-semibold text-[10px] transition">
                                                                                Lepas
                                                                            </button>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        <p x-show="loading" class="p-8 text-center text-sm text-slate-500">Memuat laporan kerja…</p>
        <p x-show="!loading && !filtered.length" class="p-8 text-center text-sm text-slate-500">Belum ada laporan kerja pada tanggal ini.</p>
    </div>

    <!-- Details are kept out of the compact report table. -->
    <div x-show="detailModalOpen" x-cloak @keydown.escape.window="closeDetailModal()" @click.self="closeDetailModal()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-3 sm:p-5">
        <section role="dialog" aria-modal="true" aria-labelledby="report-detail-title" class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
            <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <h3 id="report-detail-title" class="text-lg font-bold text-slate-900">Detail laporan kerja</h3>
                    <p class="mt-1 break-words text-sm font-semibold text-slate-700" x-text="selectedReport?.contributor_name"></p>
                    <p class="mt-1 text-xs text-slate-500" x-text="selectedReport ? dateLabel(selectedReport.work_date) : ''"></p>
                </div>
                <button type="button" @click="closeDetailModal()" class="min-h-11 shrink-0 rounded-lg px-3 text-sm text-slate-600" aria-label="Tutup detail laporan">Tutup</button>
            </header>
            <div class="min-h-0 overflow-y-auto p-5 sm:p-6 space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-emerald-50 p-4">
                    <div><p class="text-xs text-emerald-800">Durasi dilaporkan</p><p class="mt-1 text-2xl font-bold text-emerald-950"><span x-text="selectedReport?.total_duration_minutes || 0"></span> <span class="text-sm font-medium">menit</span></p></div>
                    <p class="text-xs text-emerald-900" x-text="(selectedReport?.phones?.length || 0) + ' HP · Revisi #' + (selectedReport?.revision || 1)"></p>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Dikirim:</dt><dd class="mt-1 font-medium text-slate-800" x-text="reportTimestamp(selectedReport?.created_at)"></dd></div>
                    <div><dt class="text-xs text-slate-500">Diperbarui:</dt><dd class="mt-1 font-medium text-slate-800" x-text="selectedReport?.revision > 1 ? reportTimestamp(selectedReport.updated_at) : 'Belum ada revisi'"></dd></div>
                </dl>
                <div class="space-y-3">
                    <h4 class="text-sm font-bold text-slate-900">Rincian per HP</h4>
                    <template x-for="pkey in (selectedReport?.phones || [])" :key="pkey">
                        <article class="rounded-2xl border border-slate-200 p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3"><h5 class="text-sm font-bold text-slate-900" x-text="formatPhoneName(pkey)"></h5><span class="shrink-0 text-sm font-semibold text-emerald-900" x-text="selectedReport?.phone_durations?.[pkey] ? selectedReport.phone_durations[pkey] + ' menit' : 'Tidak dirinci'"></span></div>
                            <div class="text-sm"><p class="text-xs text-slate-500" x-text="isPhoneEmailOverridden(selectedReport, pkey) ? 'Email pada laporan' : (pkey === 'sendiri' ? 'Email pribadi (opsional)' : 'Email master HP saat ini')"></p><p class="mt-1 break-all text-slate-800" x-text="getReportPhoneEmail(selectedReport, pkey) || 'Belum diisi'"></p><p x-show="pkey !== 'sendiri' && !isPhoneEmailOverridden(selectedReport, pkey)" class="mt-1 text-xs text-slate-500">Referensi master HP; pastikan akun yang digunakan saat bekerja.</p></div>
                            <div class="border-t border-slate-100 pt-3"><p class="mb-2 text-xs text-slate-500">Task yang dikerjakan</p><div class="flex flex-wrap gap-2"><template x-for="task in (selectedReport?.phone_tasks?.[pkey] || [])" :key="task"><span class="max-w-full break-words rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm text-slate-700" x-text="task"></span></template><p x-show="!selectedReport?.phone_tasks?.[pkey]?.length" class="text-sm text-slate-400">Task belum dicatat pada laporan ini.</p></div></div>
                        </article>
                    </template>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <h4 class="text-sm font-semibold text-amber-950">Verifikasi Daily Record</h4>
                    <p class="mt-1 text-xs leading-5 text-amber-900">Gunakan setelah bukti rekaman diperiksa jika data bot belum tersedia. Verifikasi ini tidak mengubah QA atau penghasilan.</p>
                    <button type="button" @click="verifyRecordStreak()" :disabled="verifyingStreak || !selectedReport || !!streakReviewMessage" class="mt-3 min-h-11 rounded-lg border border-amber-300 bg-white px-4 text-xs font-bold disabled:opacity-40" x-text="verifyingStreak ? 'Memverifikasi…' : 'Verifikasi Daily Record'"></button>
                    <p x-show="streakReviewMessage" x-text="streakReviewMessage" role="status" class="mt-2 text-xs text-amber-900"></p>
                    <p x-show="streakReviewError" x-text="streakReviewError" role="alert" class="mt-2 text-xs text-red-700"></p>
                </div>
            </div>
            <footer class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 sm:px-6">
                <button type="button" @click="const r = selectedReport; confirmDeleteReport(r).then(() => { if (!rows.some(row => row.id === r.id)) closeDetailModal(); })" :disabled="deletingId === selectedReport?.id" class="min-h-11 rounded-lg px-3 text-xs font-semibold text-red-700 disabled:opacity-40">Hapus laporan</button>
                <button type="button" @click="const r = selectedReport; closeDetailModal(); openEditModal(r);" class="min-h-11 rounded-lg bg-emerald-700 px-4 text-xs font-bold text-white">Edit Laporan Ini</button>
            </footer>
        </section>
    </div>

    <!-- EDIT REPORT MODAL (POP-UP) -->
    <div x-show="editModalOpen" x-cloak 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
         @keydown.escape.window="closeEditModal()">
        
        <div class="bg-white rounded-[28px] border border-slate-200 w-full max-w-lg overflow-hidden shadow-2xl flex flex-col"
             @click.outside="closeEditModal()">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-sm sm:text-base">Edit Laporan Kerja</h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span x-text="editingReport?.contributor_name"></span> · <span x-text="dateLabel(editingReport?.work_date)"></span>
                    </p>
                </div>
                <button type="button" @click="closeEditModal()" class="w-8 h-8 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center transition">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                <!-- Live Accumulation Total Box -->
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Total Akumulasi Durasi</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="font-mono text-2xl font-black text-emerald-950" x-text="calcEditTotal() + ' Menit'"></span>
                            <span class="text-xs text-emerald-700 font-semibold" x-text="'(' + duration(calcEditTotal()) + ')'"></span>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 bg-white px-3 py-1 rounded-full border border-emerald-200 shadow-2xs"
                          x-text="editForm.phones.length + ' HP Dipilih'"></span>
                </div>

                <!-- Sesi / Shift Selector -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-3 space-y-2">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">
                        Sesi / Shift Kerja
                    </label>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" @click="editForm.shift_label = 'Shift 1 (Pagi)'"
                                :class="editForm.shift_label === 'Shift 1 (Pagi)' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer">Shift 1 (Pagi)</button>
                        <button type="button" @click="editForm.shift_label = 'Shift 2 (Siang)'"
                                :class="editForm.shift_label === 'Shift 2 (Siang)' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer">Shift 2 (Siang)</button>
                        <button type="button" @click="editForm.shift_label = 'Shift 3 (Malam)'"
                                :class="editForm.shift_label === 'Shift 3 (Malam)' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer">Shift 3 (Malam)</button>
                        <button type="button" @click="editForm.shift_label = 'Lembur'"
                                :class="editForm.shift_label === 'Lembur' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer">Lembur</button>
                        <input type="text" x-model.trim="editForm.shift_label" placeholder="Nama shift..."
                               class="h-8 flex-1 min-w-[110px] rounded-xl border border-slate-300 px-2.5 text-xs font-bold text-slate-800 bg-white focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <!-- Phone List Selection & Durations -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Pilih HP & Atur Menit Durasi:</label>
                    <div class="grid grid-cols-1 gap-2.5">
                        <template x-for="(label, key) in phoneOptions" :key="key">
                            <div class="rounded-2xl border p-3 transition flex flex-col justify-between"
                                 :class="editForm.phones.includes(key) ? 'bg-emerald-50/40 border-emerald-300' : 'bg-slate-50 border-slate-200'">
                                
                                <!-- Toggle Row -->
                                <button type="button" 
                                        @click="toggleEditPhone(key)" 
                                        class="flex items-center justify-between w-full text-left">
                                    <div class="flex items-center gap-2">
                                        <div class="w-4 h-4 rounded-md border flex items-center justify-center transition text-[10px]"
                                             :class="editForm.phones.includes(key) ? 'bg-emerald-600 border-emerald-600 text-white font-bold' : 'border-slate-300 bg-white'">
                                            <span x-show="editForm.phones.includes(key)">✓</span>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-slate-800" x-text="'HP ' + label"></span>
                                            <template x-if="getPhoneInfo(key)?.active_account_name">
                                                <span class="block text-[10px] text-emerald-800 font-mono font-bold" x-text="'Akun: ' + getPhoneInfo(key).active_account_name"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <span x-show="editForm.phones.includes(key)" class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">Aktif</span>
                                </button>

                                <!-- Duration & Email Inputs (visible when selected) -->
                                <div x-show="editForm.phones.includes(key)" class="mt-2.5 pt-2 border-t border-slate-200/60 space-y-2.5">
                                    <!-- Duration Input -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Durasi Menit:</label>
                                        <div class="flex items-center gap-1.5">
                                            <input type="number" 
                                                   x-model.number="editForm.phone_durations[key]" 
                                                   min="1" 
                                                   max="1440" 
                                                   placeholder="0"
                                                   class="w-full h-8 px-2.5 rounded-lg border border-slate-200 bg-white font-mono text-xs font-bold text-slate-800 focus:border-emerald-500 focus:outline-none">
                                            <span class="text-xs text-slate-500 font-semibold shrink-0">menit</span>
                                        </div>
                                        <!-- Quick Chips -->
                                        <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                            <button type="button" @click="editForm.phone_durations[key] = 60" class="px-1.5 py-0.5 rounded-md bg-white hover:bg-emerald-100 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">60m</button>
                                            <button type="button" @click="editForm.phone_durations[key] = 120" class="px-1.5 py-0.5 rounded-md bg-white hover:bg-emerald-100 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">120m</button>
                                            <button type="button" @click="editForm.phone_durations[key] = 180" class="px-1.5 py-0.5 rounded-md bg-white hover:bg-emerald-100 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">180m</button>
                                            <button type="button" @click="editForm.phone_durations[key] = 240" class="px-1.5 py-0.5 rounded-md bg-white hover:bg-emerald-100 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">240m</button>
                                        </div>
                                    </div>

                                    <!-- Tasks per phone; optional email only for personal phones. -->
                                    @include('work-reports.task-picker', ['taskMap' => 'editForm.phone_tasks', 'phoneList' => 'editForm.phones'])
                                <template x-if="key === 'sendiri'">
                                    <label class="block border-t border-slate-200/50 pt-3 text-xs font-semibold text-slate-600">Email HP Sendiri (opsional)
                                        <input type="email" x-model.trim="editForm.phone_emails[key]" maxlength="100" placeholder="nama@email.com"
                                               class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-base text-slate-800 focus:border-emerald-500 focus:outline-none">
                                        <span class="mt-2 block text-xs font-normal leading-5 text-slate-500">Jika diisi, email dipakai untuk mencocokkan rekaman bot pada tanggal laporan. Tidak dihubungkan ke akun master HP studio.</span>
                                    </label>
                                </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="editError" x-text="editError" class="p-3 rounded-xl bg-red-50 text-red-700 text-xs"></div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" @click="closeEditModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="button" 
                        @click="saveEditReport()" 
                        :disabled="savingEdit || calcEditTotal() <= 0" 
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                    <span x-text="savingEdit ? 'Menyimpan…' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Create Report Modal -->
    <div x-show="createModalOpen" 
         x-transition.opacity 
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div class="bg-white rounded-[28px] border border-slate-200 w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col"
             @click.outside="closeCreateModal()">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-sm sm:text-base">Buat Laporan Kerja</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Input rekaman menit pengerjaan HP atas nama kontributor</p>
                </div>
                <button type="button" @click="closeCreateModal()" class="w-8 h-8 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center transition cursor-pointer" aria-label="Tutup form">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                <!-- Contributor & Date Selection Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">
                            Pilih Kontributor <span class="text-red-500">*</span>
                        </label>
                        <select x-model="createForm.contributor_id" 
                                class="w-full h-11 px-3.5 rounded-2xl border-2 border-slate-200 bg-white text-xs sm:text-sm font-bold text-slate-900 focus:border-emerald-500 focus:outline-none">
                            <option value="">-- Pilih Kontributor --</option>
                            <template x-for="c in contributorsList" :key="c.id">
                                <option :value="c.id" x-text="c.nama + (c.email ? ' (' + c.email + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">
                            Tanggal Kerja (WITA) <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               x-model="createForm.work_date" 
                               class="w-full h-11 px-3.5 rounded-2xl border-2 border-slate-200 bg-white text-xs sm:text-sm font-bold text-slate-900 focus:border-emerald-500 focus:outline-none font-mono">
                    </div>
                </div>

                <!-- Sesi / Shift Selector -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-3.5 space-y-2">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">
                        Sesi / Shift Kerja
                    </label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" @click="createForm.shift_label = 'Shift 1 (Pagi)'"
                                :class="createForm.shift_label === 'Shift 1 (Pagi)' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-xl text-xs font-bold border transition cursor-pointer">Shift 1 (Pagi)</button>
                        <button type="button" @click="createForm.shift_label = 'Shift 2 (Siang)'"
                                :class="createForm.shift_label === 'Shift 2 (Siang)' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-xl text-xs font-bold border transition cursor-pointer">Shift 2 (Siang)</button>
                        <button type="button" @click="createForm.shift_label = 'Shift 3 (Malam)'"
                                :class="createForm.shift_label === 'Shift 3 (Malam)' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-xl text-xs font-bold border transition cursor-pointer">Shift 3 (Malam)</button>
                        <button type="button" @click="createForm.shift_label = 'Lembur'"
                                :class="createForm.shift_label === 'Lembur' ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200'"
                                class="px-2.5 py-1 rounded-xl text-xs font-bold border transition cursor-pointer">Lembur</button>
                        <input type="text" x-model.trim="createForm.shift_label" placeholder="Atau ketik nama shift..."
                               class="h-8 flex-1 min-w-[130px] rounded-xl border border-slate-300 px-3 text-xs font-bold text-slate-800 bg-white focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <!-- Section Label -->
                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-700">Pilih HP & Durasi Menit</span>
                    <span class="text-xs font-bold"
                          :class="createForm.phones.length ? 'text-emerald-900 bg-emerald-100 px-3 py-1 rounded-full font-extrabold border border-emerald-200' : 'text-slate-500'"
                          x-text="createForm.phones.length ? createForm.phones.length + ' HP Dipilih' : 'Pilih HP di bawah'"></span>
                </div>

                <!-- Dynamic Phones Grid -->
                <div class="grid grid-cols-1 gap-3">
                    <template x-for="(label, key) in phoneOptions" :key="key">
                        <div :class="createForm.phones.includes(key) ? 'border-2 border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20 shadow-xs' : 'border border-slate-200 hover:border-slate-300 bg-white shadow-2xs'"
                             class="rounded-3xl p-3.5 transition flex flex-col justify-between">

                            <!-- Toggle Header -->
                            <button type="button" class="flex flex-wrap gap-2 items-center justify-between w-full cursor-pointer select-none" 
                                    @click="toggleCreatePhone(key)">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-md border flex items-center justify-center transition"
                                         :class="createForm.phones.includes(key) ? 'bg-emerald-600 border-emerald-600 text-white font-black' : 'border-slate-300 bg-white'">
                                        <svg x-show="createForm.phones.includes(key)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-xs sm:text-sm font-black text-slate-900" x-text="'HP ' + label"></span>
                                        <template x-if="getPhoneInfo(key)?.active_account_name">
                                            <span class="block text-[10px] text-emerald-800 font-mono font-bold" x-text="'Akun: ' + getPhoneInfo(key).active_account_name"></span>
                                        </template>
                                    </div>
                                </div>
                                <span x-show="createForm.phones.includes(key)" class="text-[10px] font-black text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Dipakai
                                </span>
                            </button>

                            <!-- Duration & Email Input Section (Expands when selected) -->
                            <div x-show="createForm.phones.includes(key)" x-transition class="mt-3 pt-3 border-t border-slate-200 space-y-2.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 mb-1">Durasi Menit:</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number"
                                               min="1"
                                               max="1440"
                                               step="1"
                                               inputmode="numeric"
                                               placeholder="120"
                                               :value="createForm.phone_durations[key] || ''"
                                               @input="setCreateMinutes(key, $event.target.value)"
                                               class="w-full bg-white border-2 border-slate-300 rounded-xl px-2.5 py-1.5 text-xs sm:text-sm font-black text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none font-mono">
                                        <span class="text-xs font-bold text-slate-600 shrink-0">menit</span>
                                    </div>

                                    <!-- Fast Increment Pill Chips -->
                                    <div class="flex items-center gap-1 flex-wrap mt-1.5">
                                        <button type="button" @click="setCreateMinutes(key, 60)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">60m</button>
                                        <button type="button" @click="setCreateMinutes(key, 120)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">120m</button>
                                        <button type="button" @click="setCreateMinutes(key, 180)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">180m</button>
                                        <button type="button" @click="setCreateMinutes(key, 240)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">240m</button>
                                    </div>
                                </div>

                                <!-- Tasks per phone; optional email only for personal phones. -->
                                @include('work-reports.task-picker', ['taskMap' => 'createForm.phone_tasks', 'phoneList' => 'createForm.phones'])
                                <template x-if="key === 'sendiri'">
                                    <label class="block border-t border-slate-200/50 pt-3 text-xs font-semibold text-slate-600">Email HP Sendiri (opsional)
                                        <input type="email" x-model.trim="createForm.phone_emails[key]" maxlength="100" placeholder="nama@email.com"
                                               class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-base text-slate-800 focus:border-emerald-500 focus:outline-none">
                                        <span class="mt-2 block text-xs font-normal leading-5 text-slate-500">Jika diisi, email dipakai untuk mencocokkan rekaman bot pada tanggal laporan. Tidak dihubungkan ke akun master HP studio.</span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Dynamic Live Total Accumulation Banner (Lime & Mint Design) -->
                <div class="rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-2 border-lime-300/90 shadow-xs relative overflow-hidden"
                     style="background: linear-gradient(115deg, #dcfce7 0%, #d9f99d 55%, #bef264 100%);">
                    
                    <div class="relative z-10">
                        <span class="text-[10px] font-black uppercase tracking-wider text-[#1e3c16] block">
                            Akumulasi Durasi Laporan
                        </span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-2xl sm:text-3xl font-black text-[#0f240c] font-mono tracking-tight" x-text="calcCreateTotal()"></span>
                            <span class="text-xs sm:text-sm font-black text-[#0f240c]">Menit</span>
                            <span class="text-xs font-extrabold text-[#2d4e22]" x-text="'(' + duration(calcCreateTotal()) + ')'"></span>
                        </div>
                    </div>

                    <div class="relative z-10">
                        <span x-show="calcCreateTotal() <= 0 || !createForm.phones.length" 
                              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-slate-900 text-lime-300 shadow-xs border border-slate-800">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            Pilih HP & Isi Menit
                        </span>
                        <span x-show="calcCreateTotal() > 0 && createForm.phones.length" 
                              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-[#14532d] text-emerald-100 shadow-xs border border-emerald-800">
                            <svg class="w-3.5 h-3.5 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Laporan Siap Disimpan
                        </span>
                    </div>
                </div>

                <!-- Error Message Alert -->
                <div x-show="createError" x-text="createError" class="p-3.5 rounded-2xl bg-red-50 text-red-700 text-xs font-bold border border-red-200"></div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" @click="closeCreateModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    Batal
                </button>
                <button type="button" 
                        @click="saveCreateReport()" 
                        :disabled="savingCreate || calcCreateTotal() <= 0 || !createForm.contributor_id || !createForm.work_date" 
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                    <span x-text="savingCreate ? 'Menyimpan…' : 'Simpan Laporan Kerja'"></span>
                </button>
            </div>
        </div>
    </div>
</section>
