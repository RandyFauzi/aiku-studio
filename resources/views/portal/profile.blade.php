@extends('portal.layout', ['currentPage' => 'profil', 'pageTitle' => 'Profil Kontributor'])

@section('content')
<div class="space-y-6">

    <!-- Subheader -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Profil Kontributor</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Informasi akun dan ringkasan aktivitas kerja</p>
        </div>
    </div>

    <!-- Profile Info Card -->
    <section class="rounded-[32px] bg-white p-6 border border-slate-100 shadow-2xs flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-semibold text-2xl flex items-center justify-center shadow-xs shrink-0 ring-4 ring-emerald-50">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</h3>
            <p class="text-xs text-slate-400 font-medium">{{ auth()->user()->email }}</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 mt-2 border border-emerald-200/80">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Kontributor Aktif</span>
            </span>
        </div>
    </section>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Kehadiran Bulan Ini</span>
            <span class="text-2xl font-semibold text-slate-900 font-mono block mt-1" x-text="totalMonthAttendances + ' Hari'"></span>
            <p class="text-[11px] text-slate-400 mt-1">Presensi terverifikasi melalui WiFi Studio atau GPS.</p>
        </div>
        <a href="/me/slip" class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs hover:border-emerald-300 transition block">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Arsip Dokumen Slip Gaji</span>
            <span class="text-2xl font-semibold text-emerald-700 font-mono block mt-1" x-text="slips.length + ' Dokumen'"></span>
            <p class="text-[11px] text-emerald-600 font-semibold mt-1">Lihat riwayat dan unduh PDF ↗</p>
        </a>
    </div>

    <!-- Quick Links Card -->
    <section class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs space-y-3">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Navigasi Halaman Anda</h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <a href="/me" class="p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-xs font-bold text-slate-800">Utama & Presensi</span>
                </div>
                <span class="text-xs text-slate-400">→</span>
            </a>
            <a href="/me/laporan" class="p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs font-bold text-slate-800">Laporan Kerja Harian</span>
                </div>
                <span class="text-xs text-slate-400">→</span>
            </a>
            <a href="/me/jadwal" class="p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-bold text-slate-800">Jadwal Shift Bulanan</span>
                </div>
                <span class="text-xs text-slate-400">→</span>
            </a>
            <a href="/me/slip" class="p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="text-xs font-bold text-slate-800">Slip Gaji & PDF</span>
                </div>
                <span class="text-xs text-slate-400">→</span>
            </a>
        </div>
    </section>

    <!-- Logout Button -->
    <form method="post" action="/logout">
        @csrf
        <button type="submit" class="w-full py-4 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition border border-rose-200/60 shadow-2xs flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Keluar dari Akun</span>
        </button>
    </form>

</div>
@endsection
