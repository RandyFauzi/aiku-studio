@extends('portal.layout', ['currentPage' => 'laporan', 'pageTitle' => 'Laporan Kerja Harian'])

@section('content')
<div class="space-y-6" x-data="{ reportFormOpen: false }"
     @close-report-form="reportFormOpen = false; $nextTick(() => $refs.reportLauncher.focus())">

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

    <section x-show="!reportFormOpen" class="rounded-[32px] border border-slate-200 bg-white p-6 sm:p-8 space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">Laporan hari ini</h2>
        <p class="text-sm text-slate-600">Catat durasi kerja dan HP yang kamu gunakan setelah selesai bekerja.</p>
        <button type="button" x-ref="reportLauncher" @click="reportFormOpen = true; $nextTick(() => $refs.reportForm.focus())"
                :aria-expanded="reportFormOpen" aria-controls="daily-report-entry" class="portal-primary-button">
            @include('portal.icon', ['name' => 'plus'])
            <span x-text="todayReportSubmitted ? 'Lihat atau ubah laporan hari ini' : 'Buat laporan hari ini'"></span>
        </button>
    </section>
    <div id="daily-report-entry" x-ref="reportForm" tabindex="-1" x-show="reportFormOpen" x-cloak>
        @include('work-reports.form')
    </div>

</div>
@endsection
