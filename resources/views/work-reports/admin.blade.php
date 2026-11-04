<header class="flex min-h-[80px] items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
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
            <button type="button" @click="load()" :disabled="loading || assistLoading" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 shadow-2xs transition">
                <svg class="w-3.5 h-3.5 text-slate-500" :class="(loading || assistLoading) ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Muat Ulang Data</span>
            </button>
        </div>
    </div>

    <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"></p>

    <!-- Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs">
        <label class="w-full sm:w-auto text-xs font-bold text-slate-700">
            Pilih Tanggal (WITA)
            <input type="date" x-model="date" @change="load()" class="mt-2 block h-11 w-full sm:w-auto rounded-xl border border-slate-200 px-3.5 text-sm font-semibold focus:border-emerald-500 focus:outline-none">
        </label>
        <label class="w-full sm:flex-1 text-xs font-bold text-slate-700">
            Cari Nama Kontributor
            <input type="search" x-model="search" placeholder="Ketik nama kontributor…" class="mt-2 block h-11 w-full rounded-xl border border-slate-200 px-3.5 text-sm font-normal focus:border-emerald-500 focus:outline-none">
        </label>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500">Pekerja Melapor</p>
            <p class="mt-2 text-2xl font-black text-slate-900" x-text="rows.length + ' Orang'"></p>
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
                <p class="text-xs text-slate-500 mt-0.5">Klik tombol Assist pada baris pekerja untuk mencocokkan rekaman bot.</p>
            </div>
            <span class="text-xs font-semibold text-slate-400" x-text="filtered.length + ' laporan'"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-4">Kontributor</th>
                        <th class="p-4">Total Menit</th>
                        <th class="p-4">Rincian Menit per HP</th>
                        <th class="p-4 text-right">Assist Rekaman Bot</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="report in filtered" :key="report.id">
                        <tr class="border-t border-slate-100 transition" 
                            :class="expandedReportId === report.id ? 'bg-emerald-50/20' : 'hover:bg-slate-50/70'">
                            
                            <!-- Contributor Info -->
                            <td class="p-4">
                                <p class="font-extrabold text-slate-900" x-text="report.contributor_name"></p>
                                <p class="text-[11px] text-slate-400" x-text="'Rev: #' + (report.revision || 1)"></p>
                            </td>

                            <!-- Total Minutes -->
                            <td class="p-4">
                                <span class="font-mono text-base font-black text-emerald-900" x-text="report.total_duration_minutes + ' m'"></span>
                                <span class="block text-[11px] text-slate-400" x-text="duration(report.total_duration_minutes)"></span>
                            </td>

                            <!-- Per-Phone Minutes -->
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-if="report.phone_durations && Object.keys(report.phone_durations).length > 0">
                                        <template x-for="(mins, pkey) in report.phone_durations" :key="pkey">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                                <span class="text-slate-500 font-semibold" x-text="'HP ' + (options[pkey] || pkey) + ':'"></span>
                                                <span class="font-mono text-emerald-800" x-text="mins + 'm'"></span>
                                            </span>
                                        </template>
                                    </template>
                                    <template x-if="!report.phone_durations || Object.keys(report.phone_durations).length === 0">
                                        <span class="text-xs text-slate-500 font-medium" x-text="phoneNames(report.phones)"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- Assist Matching Trigger Button -->
                            <td class="p-4 text-right">
                                <button type="button" 
                                        @click="toggleExpandReport(report.id)" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-2xs"
                                        :class="expandedReportId === report.id 
                                            ? 'bg-slate-900 text-white' 
                                            : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    <span x-text="expandedReportId === report.id ? 'Tutup Assist' : 'Assist Pencocokan'"></span>
                                </button>
                            </td>
                        </tr>

                        <!-- EXPANDED ASSIST DRAWER ROW -->
                        <tr x-show="expandedReportId === report.id" class="border-t border-slate-200/60 bg-slate-50/80">
                            <td colspan="4" class="p-4 sm:p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                                <span>Assist Pencocokan Rekaman:</span>
                                                <span class="text-emerald-700" x-text="report.contributor_name"></span>
                                            </h3>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                Menampilkan kandidat rekaman bot yang cocok dengan akun email HP pekerja pada tanggal ini.
                                            </p>
                                        </div>
                                        <button type="button" @click="reloadAssistOnly()" :disabled="assistLoading" class="text-xs font-semibold text-brand-700 hover:text-brand-800 flex items-center gap-1">
                                            <svg class="w-3 h-3" :class="assistLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Segarkan</span>
                                        </button>
                                    </div>

                                    <!-- Assist Phones Cards -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <template x-for="pMatch in (getAssistForReport(report.id)?.phones || [])" :key="pMatch.phone_key">
                                            <div class="rounded-2xl border p-4.5 bg-white shadow-2xs space-y-3"
                                                 :class="pMatch.status === 'fully_verified' ? 'border-emerald-300 ring-1 ring-emerald-300' : 'border-slate-200'">
                                                
                                                <!-- Card Header -->
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <span class="text-xs font-extrabold text-slate-800" x-text="'HP ' + pMatch.phone_label"></span>
                                                        <p class="text-[11px] text-slate-400 font-mono" x-text="pMatch.emails.length ? pMatch.emails.join(', ') : 'Belum ada akun di HP ini'"></p>
                                                    </div>

                                                    <!-- Status Badge -->
                                                    <div>
                                                        <template x-if="pMatch.status === 'fully_verified'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                                                ✓ Terverifikasi Penuh
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'exact_match'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                                Sangat Cocok (±15m)
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'close_match'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                                Mendekati (±45m)
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'no_bot_data'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-600">
                                                                Belum Ada Data Bot
                                                            </span>
                                                        </template>
                                                        <template x-if="pMatch.status === 'no_device_account'">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-red-50 text-red-700">
                                                                Email HP Belum Terdaftar
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Comparison Numbers Box -->
                                                <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                                    <div>
                                                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Laporan Pekerja:</span>
                                                        <strong class="font-mono text-slate-800 text-sm" x-text="pMatch.reported_minutes + ' menit'"></strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Rekaman Bot Siap Ditandai:</span>
                                                        <strong class="font-mono text-emerald-700 text-sm" x-text="pMatch.unassigned_count + ' task (' + pMatch.unassigned_minutes + 'm)'"></strong>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                                    <!-- 1-Click Assign Button -->
                                                    <template x-if="pMatch.candidate_record_ids && pMatch.candidate_record_ids.length > 0">
                                                        <button type="button" 
                                                                @click="assignCandidates(report.contributor_id, report.contributor_name, pMatch.candidate_record_ids)" 
                                                                :disabled="assigning"
                                                                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 disabled:opacity-50">
                                                            <span>✓ Tandai</span>
                                                            <span x-text="pMatch.candidate_record_ids.length + ' Rekaman ke ' + report.contributor_name"></span>
                                                        </button>
                                                    </template>

                                                    <!-- Revert / Unassign Button if already assigned -->
                                                    <template x-if="pMatch.my_record_ids && pMatch.my_record_ids.length > 0">
                                                        <button type="button" 
                                                                @click="unassignRecords(pMatch.my_record_ids)" 
                                                                :disabled="assigning"
                                                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-red-50 hover:text-red-700 text-xs font-semibold text-slate-600 transition">
                                                            <span x-text="'Lepas Penugasan (' + pMatch.my_record_ids.length + ' task)'"></span>
                                                        </button>
                                                    </template>
                                                </div>

                                                <!-- Tasks Preview Accordion -->
                                                <template x-if="pMatch.tasks && pMatch.tasks.length > 0">
                                                    <details class="pt-2 border-t border-slate-100 text-xs">
                                                        <summary class="cursor-pointer font-bold text-slate-500 hover:text-slate-700 text-[11px]">
                                                            Lihat daftar <span x-text="pMatch.tasks.length"></span> task rekaman...
                                                        </summary>
                                                        <div class="mt-2 space-y-1.5 max-h-44 overflow-y-auto pr-1">
                                                            <template x-for="t in pMatch.tasks" :key="t.record_id">
                                                                <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50/70 border border-slate-100 text-[11px]">
                                                                    <div class="min-w-0 pr-2">
                                                                        <p class="font-bold text-slate-800 truncate" x-text="t.task_title"></p>
                                                                        <p class="text-slate-400 font-mono text-[10px]" x-text="t.recorded_utc || t.record_id"></p>
                                                                    </div>
                                                                    <div class="text-right shrink-0">
                                                                        <span class="font-mono font-bold" :class="t.is_assigned_to_me ? 'text-emerald-700' : 'text-slate-700'" x-text="t.duration_min + 'm'"></span>
                                                                        <span class="block text-[10px]" :class="t.is_assigned_to_me ? 'text-emerald-600 font-bold' : 'text-slate-400'" x-text="t.is_assigned_to_me ? 'Terverifikasi' : 'Belum Ditandai'"></span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </details>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <p x-show="loading" class="p-8 text-center text-sm text-slate-500">Memuat laporan kerja…</p>
        <p x-show="!loading && !filtered.length" class="p-8 text-center text-sm text-slate-500">Belum ada laporan kerja pada tanggal ini.</p>
    </div>
</section>
