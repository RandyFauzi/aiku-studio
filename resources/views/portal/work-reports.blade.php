@extends('portal.layout', ['currentPage' => 'laporan', 'pageTitle' => 'Laporan Kerja Harian'])

@section('content')
<div class="space-y-6">

    <!-- Subheader Title -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">Laporan Kerja Harian</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Catat menit pemakaian HP untuk setiap sesi kerja hari ini</p>
        </div>
        <a href="/me" class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
            ← Kembali
        </a>
    </div>

    <!-- Form Input Laporan Kerja -->
    @include('work-reports.form')

</div>
@endsection
