<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Teams & Kontributor</title>
    <meta name="theme-color" content="#edf6d9">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AIKu Studio">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="contributorsListData()" x-init="fetchContributors()" x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN APP CONTENT AREA -->
    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-3">
                <a href="/create" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Slip Gaji</span>
                </a>
            </div>
        </header>
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- Breadcrumb & Header -->
            <div class="mb-6">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">Overview / Contributors / List</p>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-3">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Contributors List</h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola data profil kontributor, statistik approval rate, frekuensi izin, dan evaluasi kinerja tim.</p>
                    </div>
                    <button @click="openModal()" class="self-start sm:self-auto bg-gray-900 hover:bg-black text-white px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                        + Add Contributor
                    </button>
                </div>
            </div>

            <!-- Studio Performance Overview Banner -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">Statistik Kinerja Studio</h3>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Seluruh Kontributor</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Ringkasan akumulasi seluruh rekaman, jam kerja terverifikasi, dan distribusi evaluasi tim studio.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="fetchStudioSummary()" class="text-xs text-slate-600 hover:text-slate-900 font-medium px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition flex items-center gap-1.5" title="Muat ulang statistik studio">
                            <svg class="w-3.5 h-3.5" :class="isLoadingSummary ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Refresh Data</span>
                        </button>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Card 1: Studio Approval Rate -->
                    <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Approval Rate Studio</p>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-black text-slate-900" x-text="(studioSummary ? studioSummary.studio_approval_rate : 0) + '%'">0%</span>
                            <span class="text-[11px] font-medium text-emerald-600">Standar QA</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1" x-text="'Dari ' + (studioSummary ? studioSummary.total_recordings : 0) + ' sesi rekaman'"></p>
                    </div>

                    <!-- Card 2: Durasi Kerja Tercatat -->
                    <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Durasi Kerja Terlaksana</p>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-black text-slate-900" x-text="(studioSummary ? studioSummary.total_hours : 0) + ' jam'">0 jam</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Disetujui: <strong class="text-emerald-700" x-text="(studioSummary ? studioSummary.billable_hours : 0) + 'j'">0j</strong> • Ditolak: <strong class="text-rose-600" x-text="(studioSummary ? studioSummary.rejected_hours : 0) + 'j'">0j</strong>
                        </p>
                    </div>

                    <!-- Card 3: Total Presensi Hadir -->
                    <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Presensi Hadir</p>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl sm:text-2xl font-black text-slate-900" x-text="(studioSummary ? studioSummary.total_attendances : 0) + ' sesi'">0 sesi</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1" x-text="(studioSummary ? studioSummary.total_contributors : 0) + ' kontributor'"></p>
                    </div>

                    <!-- Card 4: Distribusi Kinerja Tim -->
                    <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Distribusi Status Tim</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <button type="button" 
                                    @click="filterGrade = (filterGrade === 'Sangat Baik' ? 'all' : 'Sangat Baik')"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full transition cursor-pointer"
                                    :class="filterGrade === 'Sangat Baik' ? 'bg-emerald-600 text-white ring-2 ring-emerald-400 shadow-xs' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'" 
                                    title="Klik untuk filter kinerja Sangat Baik">
                                Sangat Baik: <span x-text="studioSummary?.grade_distribution?.['Sangat Baik'] ?? 0">0</span>
                            </button>
                            <button type="button" 
                                    @click="filterGrade = (filterGrade === 'Bagus' ? 'all' : 'Bagus')"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full transition cursor-pointer"
                                    :class="filterGrade === 'Bagus' ? 'bg-teal-600 text-white ring-2 ring-teal-400 shadow-xs' : 'bg-teal-100 text-teal-800 hover:bg-teal-200'" 
                                    title="Klik untuk filter kinerja Bagus">
                                Bagus: <span x-text="studioSummary?.grade_distribution?.['Bagus'] ?? 0">0</span>
                            </button>
                            <button type="button" 
                                    @click="filterGrade = (filterGrade === 'Cukup' ? 'all' : 'Cukup')"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full transition cursor-pointer"
                                    :class="filterGrade === 'Cukup' ? 'bg-amber-600 text-white ring-2 ring-amber-400 shadow-xs' : 'bg-amber-100 text-amber-800 hover:bg-amber-200'" 
                                    title="Klik untuk filter kinerja Cukup">
                                Cukup: <span x-text="studioSummary?.grade_distribution?.['Cukup'] ?? 0">0</span>
                            </button>
                            <button type="button" 
                                    @click="filterGrade = (filterGrade === 'Perlu Evaluasi' ? 'all' : 'Perlu Evaluasi')"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full transition cursor-pointer"
                                    :class="filterGrade === 'Perlu Evaluasi' ? 'bg-rose-600 text-white ring-2 ring-rose-400 shadow-xs' : 'bg-rose-100 text-rose-800 hover:bg-rose-200'" 
                                    title="Klik untuk filter kinerja Perlu Evaluasi">
                                Evaluasi: <span x-text="studioSummary?.grade_distribution?.['Perlu Evaluasi'] ?? 0">0</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Controls -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="search" 
                           x-model="search" 
                           placeholder="Cari nama, email, WhatsApp, shift/tim, domisili, atau rekening kontributor..." 
                           class="w-full pl-10 pr-9 py-2.5 bg-slate-50 hover:bg-slate-100/60 focus:bg-white border border-slate-200/90 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition shadow-2xs">
                    <button type="button" 
                            x-show="search" 
                            @click="search = ''" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1 cursor-pointer" 
                            title="Hapus pencarian">
                        ✕
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Role Filter -->
                    <select x-model="filterRole" class="bg-slate-50 hover:bg-slate-100/60 focus:bg-white border border-slate-200/90 text-slate-700 py-2.5 px-3 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition shadow-2xs cursor-pointer">
                        <option value="all">Semua Peran</option>
                        <option value="contributor">Pekerja</option>
                        <option value="admin">Admin</option>
                    </select>

                    <!-- Grade Filter -->
                    <select x-model="filterGrade" class="bg-slate-50 hover:bg-slate-100/60 focus:bg-white border border-slate-200/90 text-slate-700 py-2.5 px-3 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition shadow-2xs cursor-pointer">
                        <option value="all">Semua Status Kinerja</option>
                        <option value="Sangat Baik">Sangat Baik</option>
                        <option value="Bagus">Bagus</option>
                        <option value="Cukup">Cukup</option>
                        <option value="Perlu Evaluasi">Perlu Evaluasi</option>
                    </select>

                    <!-- Reset Filter Button -->
                    <button type="button" 
                            x-show="search || filterRole !== 'all' || filterGrade !== 'all'" 
                            @click="search = ''; filterRole = 'all'; filterGrade = 'all'" 
                            class="text-xs text-rose-600 hover:text-rose-800 font-bold px-3 py-2 rounded-xl hover:bg-rose-50 border border-rose-200 transition cursor-pointer">
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-sm"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Kontributor</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status & Kinerja</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Peran / Role</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontak & Akun</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Shift / Tim</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Domisili & Rekening</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400 font-medium">
                                    <div class="flex justify-center items-center gap-2">
                                        <svg class="animate-spin h-5 w-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Loading records...
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && filteredContributors.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 font-medium">
                                    <template x-if="contributors.length === 0">
                                        <span>Belum ada data kontributor di database.</span>
                                    </template>
                                    <template x-if="contributors.length > 0">
                                        <div class="space-y-2">
                                            <p class="text-slate-600 font-semibold">Tidak ada kontributor yang cocok dengan pencarian atau filter.</p>
                                            <button type="button" 
                                                    @click="search = ''; filterRole = 'all'; filterGrade = 'all'" 
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-brand/10 hover:bg-brand/20 text-brand text-xs font-bold transition cursor-pointer">
                                                Reset Pencarian & Filter
                                            </button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <template x-for="c in filteredContributors" :key="c.id">
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                            <span x-text="c.nama ? c.nama.substring(0,2) : '?'"></span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-bold text-gray-900" x-text="c.nama"></div>
                                            <div class="text-[11px] text-slate-400" x-text="'ID #' + c.id"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <template x-if="perfLookup[c.id]">
                                        <div class="space-y-1">
                                            <!-- Mode Admin: Dikecualikan dari skor %an produksi -->
                                            <template x-if="c.role === 'admin' || perfLookup[c.id]?.is_admin">
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200 shadow-2xs">
                                                            Manajemen
                                                        </span>
                                                        <span class="text-[11px] font-semibold text-slate-400">Non-Produksi</span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 flex items-center gap-1.5 flex-wrap mt-1">
                                                        <span class="font-medium text-slate-600">Akses Operasional</span>
                                                        <span>•</span>
                                                        <span x-text="perfLookup[c.id].attendances + ' hadir'"></span>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Mode Pekerja: Evaluasi normal target produksi -->
                                            <template x-if="c.role !== 'admin' && !perfLookup[c.id]?.is_admin">
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold"
                                                              :class="{
                                                                  'bg-emerald-100 text-emerald-800 border border-emerald-200': perfLookup[c.id].grade === 'Sangat Baik',
                                                                  'bg-teal-100 text-teal-800 border border-teal-200': perfLookup[c.id].grade === 'Bagus',
                                                                  'bg-amber-100 text-amber-800 border border-amber-200': perfLookup[c.id].grade === 'Cukup',
                                                                  'bg-rose-100 text-rose-800 border border-rose-200': perfLookup[c.id].grade === 'Perlu Evaluasi'
                                                              }"
                                                              x-text="perfLookup[c.id].grade">
                                                        </span>
                                                        <span class="text-[11px] font-semibold text-slate-600" x-text="'Skor ' + perfLookup[c.id].score + '/100'"></span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 flex items-center gap-1.5 flex-wrap mt-1">
                                                        <span>Approval: <strong class="text-slate-800 font-semibold" x-text="perfLookup[c.id].approval_rate + '%'"></strong></span>
                                                        <span>•</span>
                                                        <span x-text="perfLookup[c.id].total_hours + ' jam'"></span>
                                                        <span>•</span>
                                                        <span x-text="perfLookup[c.id].attendances + ' hadir'"></span>
                                                        <template x-if="perfLookup[c.id].leaves > 0">
                                                            <span class="text-amber-600 font-semibold" x-text="'(' + perfLookup[c.id].leaves + ' izin)'"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!perfLookup[c.id]">
                                        <span class="text-xs text-slate-400 italic">Memuat statistik...</span>
                                    </template>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
                                          :class="c.role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="c.role === 'admin' ? 'bg-purple-600' : 'bg-emerald-600'"></span>
                                        <span x-text="c.role === 'admin' ? 'Admin' : 'Pekerja'"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs font-semibold text-gray-900" x-text="c.no_wa || '-'"></div>
                                    <div class="text-[11px] text-gray-500" x-text="c.email || '-'"></div>
                                    <span class="mt-0.5 inline-block text-[10px] font-semibold" :class="c.login_enabled?'text-brand-700':'text-slate-400'" x-text="c.login_enabled?'Login aktif':'Login belum aktif'"></span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs font-semibold text-gray-900" x-text="c.hp || '-'"></div>
                                    <div class="text-[11px] text-gray-500">Shift: <span x-text="c.shift || '-'"></span></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-700" x-text="c.domisili || '-'"></div>
                                    <div class="text-[11px] text-gray-400 font-mono" x-text="c.no_rek || '-'"></div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openPerfModal(c)" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-semibold transition" title="Lihat Profil & Analisis Kinerja Pintar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                            <span>Analisis Kinerja</span>
                                        </button>
                                        <button @click="openModal(c)" class="px-2 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition font-semibold">
                                            Edit
                                        </button>
                                        <button @click="deleteContributor(c.id)" class="px-2 py-1.5 rounded-lg text-rose-600 hover:text-rose-900 hover:bg-rose-50 transition font-semibold">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination & Counter -->
            <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-2 text-xs sm:text-sm text-gray-500">
                <span x-text="'Menampilkan ' + filteredContributors.length + ' dari ' + contributors.length + ' kontributor'"></span>
                <span x-show="search || filterRole !== 'all' || filterGrade !== 'all'" class="text-xs text-brand font-semibold">
                    Filter aktif terpasang
                </span>
            </div>
        
        </div>
    </main>

    <!-- Profile & Smart Performance Evaluation Modal -->
    <div x-show="perfModalOpen" role="dialog" aria-modal="true" aria-labelledby="performance-title" @keydown.escape.window="if(perfModalOpen)closePerfModal()" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-3 sm:px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="closePerfModal()">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            </div>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-200/80">
                <!-- Modal Top Header -->
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white px-5 sm:px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <div>
                            <h3 id="performance-title" class="text-base font-bold">Profil & Analisis Kinerja Kontributor</h3>
                            <p class="text-xs text-slate-300">Evaluasi pintar kualitas rekaman, approval rate, dan kedisiplinan kerja.</p>
                        </div>
                    </div>
                    <button type="button" @click="closePerfModal()" class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-700/50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 sm:p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                    <!-- Loading Spinner -->
                    <template x-if="isLoadingPerf">
                        <div class="py-16 text-center space-y-3">
                            <div class="inline-flex items-center justify-center p-3 rounded-xl bg-brand-50 text-brand">
                                <svg class="animate-spin h-7 w-7 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-600">Menganalisis riwayat rekaman dan presensi...</p>
                        </div>
                    </template>

                    <!-- Content Loaded -->
                    <div x-show="perfError" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <p x-text="perfError"></p>
                        <button type="button" @click="openPerfModal(perfContributor)" :disabled="isLoadingPerf" class="mt-3 rounded-lg border border-red-200 bg-white px-4 py-2 font-semibold disabled:opacity-50">Coba lagi</button>
                    </div>
                    <template x-if="!isLoadingPerf && selectedPerf">
                        <div class="space-y-5">
                            <!-- 1. Profile Identity Header Card -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-xl bg-brand text-white flex items-center justify-center font-bold text-lg uppercase shadow-sm">
                                        <span x-text="selectedPerf.contributor.nama.substring(0,2)"></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-base font-bold text-slate-900" x-text="selectedPerf.contributor.nama"></h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                  :class="selectedPerf.contributor.role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
                                                  x-text="selectedPerf.contributor.role === 'admin' ? 'Admin' : 'Pekerja'">
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            <span x-text="selectedPerf.contributor.email || 'Tanpa email'"></span> • 
                                            <span x-text="selectedPerf.contributor.no_wa || 'Tanpa WA'"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-600 space-y-0.5 sm:text-right bg-white sm:bg-transparent p-2.5 sm:p-0 rounded-lg border sm:border-0 border-slate-200">
                                    <div>Shift: <strong class="text-slate-800" x-text="selectedPerf.contributor.shift || '-'"></strong></div>
                                    <div>HP / Tim: <strong class="text-slate-800" x-text="selectedPerf.contributor.hp || '-'"></strong></div>
                                    <div>Domisili: <span x-text="selectedPerf.contributor.domisili || '-'"></span></div>
                                </div>
                            </div>

                            <!-- 2. Smart Evaluation Status Banner -->
                            <div class="p-4 rounded-xl border transition-all"
                                 :class="{
                                     'bg-purple-50/80 border-purple-200 text-purple-950': selectedPerf.contributor.role === 'admin' || selectedPerf.evaluation.is_admin,
                                     'bg-emerald-50/70 border-emerald-200 text-emerald-950': (selectedPerf.contributor.role !== 'admin' && !selectedPerf.evaluation.is_admin) && selectedPerf.evaluation.grade === 'Sangat Baik',
                                     'bg-teal-50/70 border-teal-200 text-teal-950': (selectedPerf.contributor.role !== 'admin' && !selectedPerf.evaluation.is_admin) && selectedPerf.evaluation.grade === 'Bagus',
                                     'bg-amber-50/70 border-amber-200 text-amber-950': (selectedPerf.contributor.role !== 'admin' && !selectedPerf.evaluation.is_admin) && selectedPerf.evaluation.grade === 'Cukup',
                                     'bg-rose-50/70 border-rose-200 text-rose-950': (selectedPerf.contributor.role !== 'admin' && !selectedPerf.evaluation.is_admin) && selectedPerf.evaluation.grade === 'Perlu Evaluasi'
                                 }">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-black/10 pb-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                                                  :class="{
                                                      'bg-purple-200 text-purple-900 border border-purple-300': selectedPerf.contributor.role === 'admin' || selectedPerf.evaluation.is_admin,
                                                      'bg-emerald-200 text-emerald-900': selectedPerf.evaluation.grade === 'Sangat Baik',
                                                      'bg-teal-200 text-teal-900': selectedPerf.evaluation.grade === 'Bagus',
                                                      'bg-amber-200 text-amber-900': selectedPerf.evaluation.grade === 'Cukup',
                                                      'bg-rose-200 text-rose-900': selectedPerf.evaluation.grade === 'Perlu Evaluasi'
                                                  }"
                                                  x-text="'STATUS: ' + selectedPerf.evaluation.grade">
                                            </span>
                                            <span class="text-xs font-semibold text-slate-600">Sistem Pintar AIKu Studio</span>
                                        </div>
                                        <p class="text-xs font-medium mt-1 leading-relaxed text-slate-700" x-text="selectedPerf.evaluation.summary"></p>
                                    </div>
                                    <div class="sm:text-right shrink-0">
                                        <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider"
                                           x-text="(selectedPerf.contributor.role === 'admin' || selectedPerf.evaluation.is_admin) ? 'Kategori Penilaian' : 'Skor Kinerja'"></p>
                                        <p class="text-xl sm:text-2xl font-black"
                                           :class="{
                                               'text-purple-700': selectedPerf.contributor.role === 'admin' || selectedPerf.evaluation.is_admin,
                                               'text-emerald-700': selectedPerf.evaluation.grade === 'Sangat Baik',
                                               'text-teal-700': selectedPerf.evaluation.grade === 'Bagus',
                                               'text-amber-700': selectedPerf.evaluation.grade === 'Cukup',
                                               'text-rose-700': selectedPerf.evaluation.grade === 'Perlu Evaluasi'
                                           }"
                                           x-text="(selectedPerf.contributor.role === 'admin' || selectedPerf.evaluation.is_admin) ? 'Operasional' : (selectedPerf.evaluation.score + ' / 100')">
                                        </p>
                                    </div>
                                </div>

                                <!-- Reasoning List -->
                                <div class="mt-3 space-y-1.5">
                                    <p class="text-xs font-bold text-slate-800">Pertimbangan & Alasan Penilaian:</p>
                                    <ul class="text-xs text-slate-700 space-y-1 pl-4 list-disc">
                                        <template x-for="(reason, rIdx) in selectedPerf.evaluation.reasons" :key="rIdx">
                                            <li x-text="reason"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <!-- 3. Key Performance Metrics Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <!-- Mode Admin -->
                                <template x-if="selectedPerf.contributor.role === 'admin' || selectedPerf.evaluation.is_admin">
                                    <div class="contents">
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-purple-600 uppercase tracking-wider">Peran Akun</p>
                                            <p class="text-lg font-black text-slate-900 mt-0.5">Administrator</p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Akses Penuh Panel</div>
                                                <div class="text-slate-400 mt-0.5">Non-Pekerja Lapangan</div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Target Produksi</p>
                                            <p class="text-lg font-black text-emerald-700 mt-0.5">Bebas Target</p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Dikecualikan dari QA bot</div>
                                                <div class="text-slate-400 mt-0.5">Fokus Manajemen Studio</div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Presensi Masuk</p>
                                            <p class="text-lg font-black text-slate-900 mt-0.5" x-text="selectedPerf.attendance.verified_attendances + ' hari'"></p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Presensi operasional</div>
                                                <div class="text-slate-400 mt-0.5">Terverifikasi sistem</div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Laporan Kerja</p>
                                            <p class="text-lg font-black text-slate-900 mt-0.5" x-text="(selectedPerf.work_reports?.total_reports || 0) + ' sesi'"></p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Aktivitas operasional</div>
                                                <div class="text-slate-400 mt-0.5">Tercatat di sistem</div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Mode Pekerja Lapangan -->
                                <template x-if="selectedPerf.contributor.role !== 'admin' && !selectedPerf.evaluation.is_admin">
                                    <div class="contents">
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Approval Rate</p>
                                            <p class="text-xl font-black text-slate-900 mt-0.5" x-text="selectedPerf.recordings.approval_rate + '%'"></p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Billable: <strong class="text-emerald-700" x-text="selectedPerf.recordings.billable_hours + ' jam'"></strong></div>
                                                <div>Reject: <strong class="text-rose-600" x-text="selectedPerf.recordings.rejected_hours + ' jam'"></strong></div>
                                                <div class="text-slate-400 mt-0.5" x-text="selectedPerf.recordings.total_count + ' sesi rekaman'"></div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Total Durasi Rekaman</p>
                                            <p class="text-xl font-black text-slate-900 mt-0.5" x-text="selectedPerf.recordings.total_duration_hours + ' jam'"></p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Setara: <span x-text="selectedPerf.recordings.total_duration_min + ' menit'"></span></div>
                                                <div>Pending: <span x-text="selectedPerf.recordings.pending_hours + ' jam'"></span></div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Presensi Masuk</p>
                                            <p class="text-xl font-black text-slate-900 mt-0.5" x-text="selectedPerf.attendance.verified_attendances + ' hari'"></p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Hadir terverifikasi</div>
                                                <div class="text-slate-400" x-text="selectedPerf.attendance.scheduled_count + ' shift jadwal'"></div>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
                                            <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Frekuensi Izin / Sakit</p>
                                            <p class="text-xl font-black text-slate-900 mt-0.5" x-text="selectedPerf.attendance.total_leaves + ' hari'"></p>
                                            <div class="text-[10px] text-slate-500 mt-1 leading-tight">
                                                <div>Izin: <strong class="text-amber-700" x-text="selectedPerf.attendance.leave_count + 'x'"></strong></div>
                                                <div>Sakit: <strong class="text-blue-700" x-text="selectedPerf.attendance.sick_count + 'x'"></strong></div>
                                                <div>Libur: <span x-text="selectedPerf.attendance.off_count + ' hari'"></span></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- 4. Actionable Learning & Improvement Plan -->
                            <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-200 text-blue-950 space-y-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <h5 class="text-xs font-bold text-blue-950 uppercase tracking-wider">Langkah Pembelajaran & Evaluasi Kinerja</h5>
                                </div>
                                <p class="text-xs text-blue-800">
                                    Panduan tindakan bagi admin dan pekerja untuk meningkatkan kualitas hasil rekaman serta kepatuhan operasional:
                                </p>
                                <ul class="text-xs text-blue-900 space-y-1.5 pl-4 list-disc">
                                    <template x-for="(rec, recIdx) in selectedPerf.evaluation.recommendations" :key="recIdx">
                                        <li class="font-medium" x-text="rec"></li>
                                    </template>
                                </ul>
                            </div>

                            <!-- 5. Work Reports Summary -->
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                                <div>
                                    <span class="font-semibold text-slate-800">Riwayat Laporan Kerja Harian:</span>
                                    <span class="ml-1" x-text="selectedPerf.work_reports.total_reports + ' laporan diserahkan (' + selectedPerf.work_reports.total_reported_hours + ' jam)'"></span>
                                </div>
                                <a href="/work-reports" class="text-brand hover:underline font-semibold text-xs">Buka Laporan Harian &rarr;</a>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Bottom Actions -->
                <div class="bg-slate-50 px-5 sm:px-6 py-3.5 border-t border-slate-200 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2">
                    <button type="button" @click="closePerfModal()" class="w-full sm:w-auto bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 px-4 py-2 rounded-xl text-xs font-semibold transition">
                        Tutup
                    </button>
                    <button type="button"
                            @click="if(selectedPerf){const c = selectedPerf.contributor; closePerfModal(); openModal(c)}"
                            :disabled="isLoadingPerf || !selectedPerf"
                            class="w-full sm:w-auto bg-slate-900 hover:bg-black text-white px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Edit Data Kontributor</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-900 opacity-50 backdrop-blur-sm" @click="closeModal()"></div>
            </div>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form @submit.prevent="saveContributor">
                    <div class="bg-white px-6 pt-6 pb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6" x-text="isEditing ? 'Edit Contributor' : 'Add New Contributor'"></h3>
                        
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Lengkap</label>
                                <input type="text" x-model="formData.nama" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                            </div>

                            <!-- Role Selector -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5">Peran Akun (Role)</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                                           :class="formData.role === 'contributor' ? 'border-brand bg-brand-50/50 text-brand-900 ring-1 ring-brand' : 'border-gray-200 hover:bg-gray-50 text-gray-700'">
                                        <input type="radio" name="role" value="contributor" x-model="formData.role" class="mt-0.5 text-brand focus:ring-brand">
                                        <div>
                                            <p class="text-xs font-bold">Pekerja (Contributor)</p>
                                            <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Akses portal jadwal, absensi & laporan kerja (/me)</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                                           :class="formData.role === 'admin' ? 'border-purple-600 bg-purple-50/50 text-purple-900 ring-1 ring-purple-600' : 'border-gray-200 hover:bg-gray-50 text-gray-700'">
                                        <input type="radio" name="role" value="admin" x-model="formData.role" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                                        <div>
                                            <p class="text-xs font-bold">Admin (Administrator)</p>
                                            <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Akses penuh panel operasional & payroll (/)</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">No WhatsApp</label>
                                    <input type="text" x-model="formData.no_wa" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Email login</label>
                                    <input type="email" x-model="formData.email" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 -mt-2">Akun baru memakai password awal: <code class="font-bold text-slate-700">password</code>. Gunakan email unik untuk tiap orang.</p>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Domisili</label>
                                <input type="text" x-model="formData.domisili" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Shift</label>
                                    <input type="text" x-model="formData.shift" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tim / HP</label>
                                    <input type="text" x-model="formData.hp" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">No. Rekening</label>
                                <input type="text" x-model="formData.no_rek" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row-reverse gap-2 sm:gap-3">
                        <button type="submit" class="w-full sm:w-auto bg-brand hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">
                            Save Changes
                        </button>
                        <button type="button" @click="closeModal()" class="w-full sm:w-auto bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/app.js?v={{ filemtime(public_path('assets/app.js')) }}"></script>
</body>
</html>
