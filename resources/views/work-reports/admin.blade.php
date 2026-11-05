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
                        <th class="p-4 text-right">Aksi & Assist</th>
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

                            <!-- Action Buttons: Edit, Delete, Assist -->
                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <!-- Edit Button (Pop-up) -->
                                    <button type="button" 
                                            @click="openEditModal(report)" 
                                            class="p-2 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-200 transition" 
                                            title="Edit Laporan (Pop-up)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" 
                                            @click="confirmDeleteReport(report)" 
                                            class="p-2 rounded-xl text-slate-500 hover:text-rose-700 hover:bg-rose-50 border border-slate-200 transition" 
                                            title="Hapus Laporan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>

                                    <!-- Assist Matching Trigger Button -->
                                    <button type="button" 
                                            @click="toggleExpandReport(report.id)" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-2xs"
                                            :class="expandedReportId === report.id 
                                                ? 'bg-slate-900 text-white' 
                                                : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        <span x-text="expandedReportId === report.id ? 'Tutup Assist' : 'Assist Pencocokan'"></span>
                                    </button>
                                </div>
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

                <!-- Phone List Selection & Durations -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Pilih HP & Atur Menit Durasi:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
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
                                        <span class="text-xs font-bold text-slate-800" x-text="'HP ' + label"></span>
                                    </div>
                                    <span x-show="editForm.phones.includes(key)" class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">Aktif</span>
                                </button>

                                <!-- Duration Input (visible when selected) -->
                                <div x-show="editForm.phones.includes(key)" class="mt-2.5 pt-2 border-t border-slate-200/60">
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" 
                                               x-model.number="editForm.phone_durations[key]" 
                                               min="1" 
                                               max="1440" 
                                               placeholder="0"
                                               class="w-full h-8 px-2.5 rounded-lg border border-slate-200 bg-white font-mono text-xs font-bold text-slate-800 focus:border-emerald-500 focus:outline-none">
                                        <span class="text-xs text-slate-500 font-semibold shrink-0">menit</span>
                                    </div>
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

                <!-- Section Label -->
                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-700">Pilih HP & Durasi Menit</span>
                    <span class="text-xs font-bold"
                          :class="createForm.phones.length ? 'text-emerald-900 bg-emerald-100 px-3 py-1 rounded-full font-extrabold border border-emerald-200' : 'text-slate-500'"
                          x-text="createForm.phones.length ? createForm.phones.length + ' HP Dipilih' : 'Pilih HP di bawah'"></span>
                </div>

                <!-- Dynamic Phones Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
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
                                    <span class="text-xs sm:text-sm font-black text-slate-900" x-text="'HP ' + label"></span>
                                </div>
                                <span x-show="createForm.phones.includes(key)" class="text-[10px] font-black text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Dipakai
                                </span>
                            </button>

                            <!-- Duration Input Section (Expands when selected) -->
                            <div x-show="createForm.phones.includes(key)" x-transition class="mt-3 pt-3 border-t border-slate-200 space-y-2">
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
                                <div class="flex items-center gap-1 flex-wrap">
                                    <button type="button" @click="setCreateMinutes(key, 60)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">60m</button>
                                    <button type="button" @click="setCreateMinutes(key, 120)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">120m</button>
                                    <button type="button" @click="setCreateMinutes(key, 180)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">180m</button>
                                    <button type="button" @click="setCreateMinutes(key, 240)" class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[10px] font-bold text-slate-700 border border-slate-200 transition cursor-pointer">240m</button>
                                </div>
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
