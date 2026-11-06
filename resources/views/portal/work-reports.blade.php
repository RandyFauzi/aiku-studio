@extends('portal.layout', ['currentPage' => 'laporan', 'pageTitle' => 'Laporan Kerja Harian'])

@section('content')
<div class="space-y-6"
     x-data="Object.assign(dailyWorkReportData(), { reportFormOpen: false })"
     @report-status-updated.window="if ($event.detail && $event.detail.submitted) { reportFormOpen = false; }"
     @close-report-form.window="reportFormOpen = false; $nextTick(() => $refs.reportLauncher?.focus())">

    <!-- Subheader Title -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Laporan Kerja Harian</h1>
            <p class="text-xs sm:text-sm text-slate-600 font-semibold mt-0.5">Catat menit pemakaian HP untuk setiap sesi kerja hari ini</p>
        </div>
        <a href="/me" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
    </div>

    <!-- ========================================== -->
    <!-- CARD 1: STATUS & BUAT LAPORAN HARI INI     -->
    <!-- ========================================== -->
    <section x-show="!reportFormOpen" class="rounded-[32px] border border-slate-200/90 bg-white p-6 sm:p-7 shadow-2xs space-y-5 transition">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center transition"
                     :class="todayReports.length > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'">
                    @include('portal.icon', ['name' => 'report'])
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">Laporan Kerja Hari Ini</h2>
                    <p class="text-xs text-slate-500 font-mono font-medium mt-0.5" x-text="dateLabel(date)"></p>
                </div>
            </div>
            <div>
                <span x-show="loading" class="rounded-full px-3 py-1 text-xs font-bold bg-slate-100 text-slate-500">
                    Memuat…
                </span>
                <span x-show="!loading && todayReports.length > 0" class="rounded-full px-3.5 py-1 text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="todayReports.length + ' Sesi Laporan Tersimpan'"></span>
                </span>
                <span x-show="!loading && todayReports.length === 0" class="rounded-full px-3 py-1 text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                    Belum Ada Laporan
                </span>
            </div>
        </div>

        <!-- Akumulasi Total Hari Ini (jika ada laporan) -->
        <template x-if="!loading && todayReports.length > 0">
            <div class="space-y-4">
                <div class="rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-lime-300 bg-gradient-to-r from-emerald-50 via-lime-50 to-emerald-50">
                    <div>
                        <span class="text-[11px] font-black uppercase tracking-wider text-emerald-950 block">Akumulasi Durasi Hari Ini</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-2xl sm:text-3xl font-black text-slate-950 font-mono" x-text="todayTotalMinutes + ' Menit'"></span>
                            <span class="text-xs font-bold text-emerald-900" x-text="'(' + duration(todayTotalMinutes) + ') • ' + todayReports.length + ' Sesi'"></span>
                        </div>
                    </div>
                    <button type="button"
                            @click="openNewReport()"
                            class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-black shadow-xs transition flex items-center justify-center gap-2 cursor-pointer shrink-0">
                        @include('portal.icon', ['name' => 'plus'])
                        <span>+ Tambah Laporan Shift / Sesi Lain</span>
                    </button>
                </div>

                <!-- Daftar Sesi Laporan Hari Ini -->
                <div class="space-y-2.5">
                    <template x-for="(rep, idx) in todayReports" :key="rep.id">
                        <div class="rounded-2xl bg-white border border-slate-200/90 p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-emerald-300 hover:bg-slate-50/50 transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-black px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-900 border border-emerald-200"
                                          x-text="rep.shift_label || ('Sesi #' + (idx + 1))"></span>
                                    <span class="text-[11px] font-semibold text-slate-500 font-mono"
                                          x-text="'Dikirim ' + (reportTimestamp(rep.created_at).match(/\d{2}:\d{2} WITA$/)?.[0] || '—')"></span>
                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"
                                          x-text="'Revisi #' + rep.revision"></span>
                                </div>
                                <p class="text-xs text-slate-900 font-mono font-bold"
                                   x-text="rep.total_duration_minutes + ' menit (' + duration(rep.total_duration_minutes) + ') • ' + (rep.phones ? rep.phones.length : 0) + ' HP Digunakan'"></p>
                                <p class="text-[11px] text-slate-600 font-medium"
                                   x-text="phoneDurationsText(rep)"></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button"
                                        @click="editReport(rep)"
                                        class="px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-emerald-50 hover:border-emerald-300 text-slate-700 hover:text-emerald-900 text-xs font-bold transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Ubah Sesi Ini</span>
                                </button>
                                <button type="button"
                                        @click="deleteReport(rep)"
                                        title="Hapus sesi laporan ini"
                                        class="p-2 rounded-xl bg-white border border-slate-200 hover:bg-rose-50 hover:border-rose-300 text-slate-400 hover:text-rose-600 transition shadow-2xs cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Aksi jika belum dikirim hari ini -->
        <template x-if="!loading && todayReports.length === 0">
            <div class="space-y-4 pt-1">
                <p class="text-xs sm:text-sm text-slate-600">Catat durasi kerja dan HP yang kamu gunakan setelah selesai sesi kerja hari ini. Kamu bisa membuat laporan lebih dari sekali jika lanjut shift berikutnya.</p>
                <button type="button" 
                        x-ref="reportLauncher" 
                        @click="openNewReport()"
                        class="portal-primary-button w-full sm:w-auto cursor-pointer">
                    @include('portal.icon', ['name' => 'plus'])
                    <span>Buat Laporan Hari Ini</span>
                </button>
            </div>
        </template>
    </section>

    <!-- ========================================== -->
    <!-- FORM INPUT LAPORAN (Muncul Saat Dibuka)    -->
    <!-- ========================================== -->
    <div id="daily-report-entry" x-ref="reportForm" tabindex="-1" x-show="reportFormOpen" x-cloak>
        @include('work-reports.form')
    </div>

    <!-- ========================================== -->
    <!-- CARD 2: RIWAYAT LAPORAN KERJA              -->
    <!-- ========================================== -->
    <section class="rounded-[32px] border border-slate-200/90 bg-white p-6 sm:p-7 shadow-2xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">Riwayat Laporan Kerja</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Daftar laporan kerja yang telah Anda laporkan</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label for="history-month-input" class="text-xs font-bold text-slate-500 sr-only">Pilih Bulan</label>
                <input id="history-month-input"
                       type="month" 
                       aria-label="Bulan riwayat laporan" 
                       x-model="month" 
                       @change="load(false)" 
                       :disabled="loading || saving" 
                       class="h-10 rounded-xl border border-slate-200 px-3.5 text-xs font-bold text-slate-800 bg-slate-50/80 shadow-2xs focus:border-emerald-500 focus:bg-white focus:outline-none transition">
            </div>
        </div>

        <div class="space-y-3">
            <div x-show="loading" class="py-8 text-center text-xs font-bold text-slate-400">
                Memuat riwayat laporan…
            </div>

            <template x-if="!loading && reports.length > 0">
                <div class="space-y-2.5">
                    <template x-for="report in reports" :key="report.id">
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 shadow-2xs hover:bg-slate-50 hover:border-slate-300 transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-xs sm:text-sm font-extrabold text-slate-900" x-text="dateLabel(report.work_date)"></p>
                                    <span x-show="report.shift_label" class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-900 border border-emerald-200" x-text="report.shift_label"></span>
                                </div>
                                <p class="text-[11px] text-slate-600 font-mono font-medium" x-text="phoneDurationsText(report)"></p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs sm:text-sm font-black text-emerald-800 font-mono block" x-text="report.total_duration_minutes + ' menit'"></span>
                                <span class="text-[11px] font-bold text-slate-500 block" x-text="duration(report.total_duration_minutes)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!loading && (!reports || reports.length === 0)">
                <div class="py-10 text-center space-y-2">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 font-bold">Belum ada laporan pada bulan ini</p>
                    <p class="text-xs text-slate-400">Laporan yang kamu kirimkan akan muncul di sini secara otomatis.</p>
                </div>
            </template>
        </div>
    </section>

</div>
@endsection
