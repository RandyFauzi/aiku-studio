@extends('portal.layout', ['currentPage' => 'laporan', 'pageTitle' => 'Laporan Kerja Harian'])

@section('content')
<div class="space-y-6">

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

    <!-- Form Input Laporan Kerja -->
    @include('work-reports.form')

</div>
@endsection
