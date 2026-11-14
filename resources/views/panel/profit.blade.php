<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Reporting & Profit</title>
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
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="profitData()" x-init="init()" x-cloak>

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
                <a href="/payslips" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="hidden sm:inline">Buka Riwayat Slip</span>
                </a>

                
            </div>
        </header>

        <div class="w-full max-w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Breadcrumb & Header Controls -->
            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Finance & Analytics / Studio Revenue & Margin</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Studio Profit & Margin</h2>
                    <p class="text-xs text-slate-500 mt-1">Kalkulasi keuntungan bersih studio berdasarkan jam rekaman disetujui (Approved), tarif klien, dan beban payroll kontributor.</p>
                </div>
                
                <!-- Period Selector & View Mode Controls (Sejajar Horizontal di Kanan) -->
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 shrink-0">
                    <!-- Custom Floating Dropdown Siklus Cut-Off -->
                    <div class="relative shrink-0" x-data="{ openPeriodDropdown: false }">
                        <button type="button" 
                                @click="openPeriodDropdown = !openPeriodDropdown"
                                class="bg-white hover:bg-slate-50/90 border border-slate-200/90 text-slate-800 py-2 px-3.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2.5 transition cursor-pointer shadow-2xs group">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#00B090] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-xs text-slate-400 font-medium">Siklus:</span>
                                <span class="font-bold text-slate-900" x-text="selectedPeriodObj ? selectedPeriodObj.name : 'Pilih Siklus'"></span>
                                <template x-if="selectedPeriodObj">
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold shrink-0"
                                          :class="selectedPeriodObj.isCurrent ? 'bg-emerald-100 text-emerald-800' : (selectedPeriodObj.isTraining ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-600')"
                                          x-text="selectedPeriodObj.isCurrent ? 'Berjalan' : (selectedPeriodObj.isTraining ? 'Arsip DB' : 'Selesai')">
                                    </span>
                                </template>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200 ml-1 group-hover:text-slate-600" 
                                 :class="openPeriodDropdown ? 'rotate-180' : ''" 
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Floating Dropdown Menu Siklus Cut-Off -->
                        <div x-show="openPeriodDropdown" 
                             @click.outside="openPeriodDropdown = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                             class="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-80 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2 text-xs text-slate-700"
                             x-cloak>
                            
                            <div class="flex items-center justify-between px-2.5 py-1.5 mb-1 border-b border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Siklus Cut-Off</span>
                                <span class="text-[10px] text-slate-500 font-semibold" x-text="periods.length + ' periode'"></span>
                            </div>

                            <div class="max-h-72 overflow-y-auto space-y-1 custom-scrollbar pr-0.5">
                                <template x-for="p in periods" :key="p.id">
                                    <button type="button"
                                            @click="selectPeriod(p.id); openPeriodDropdown = false"
                                            class="w-full text-left flex items-center justify-between px-3 py-2.5 rounded-xl transition cursor-pointer"
                                            :class="selectedPeriodId === p.id ? 'bg-[#EDFAF8] text-emerald-950 font-bold border border-emerald-200/80 shadow-2xs' : 'hover:bg-slate-50 text-slate-700 border border-transparent'">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" 
                                                  :class="p.isCurrent ? 'bg-emerald-500 ring-2 ring-emerald-200' : (p.isTraining ? 'bg-purple-500' : 'bg-slate-300')"></span>
                                            <div>
                                                <div class="text-xs" :class="selectedPeriodId === p.id ? 'font-extrabold text-slate-900' : 'font-semibold text-slate-700'" x-text="p.name"></div>
                                                <div class="text-[10px] mt-0.5 flex items-center gap-1.5">
                                                    <span class="px-1.5 py-0.2 rounded font-bold"
                                                          :class="p.isCurrent ? 'bg-emerald-100 text-emerald-800' : (p.isTraining ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-500')"
                                                          x-text="p.isCurrent ? 'Periode Berjalan' : (p.isTraining ? 'Arsip DB' : 'Siklus Selesai')"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <span x-show="selectedPeriodId === p.id" class="text-[#00B090] font-black text-sm shrink-0 ml-2">✓</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- View Mode Pill Tabs -->
                    <div class="inline-flex p-1 bg-slate-200/70 rounded-xl text-xs font-semibold shrink-0">
                        <button @click="if (isTrainingView) { const nonTraining = periods.find(p => !p.isTraining); if (nonTraining) selectPeriod(nonTraining.id); }"
                                class="px-3 py-1.5 rounded-lg transition-all cursor-pointer"
                                :class="!isTrainingView ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            ⚡ Rekap Siklus Live
                        </button>
                        <button @click="selectPeriod('training_2026')"
                                class="px-3 py-1.5 rounded-lg transition-all cursor-pointer"
                                :class="isTrainingView ? 'bg-white text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            🎓 Arsip Training (DB)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Top Highlight Metric Cards (Dashboard Style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Card 1: Gross Revenue -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/40 border border-indigo-100/80 p-5 rounded-2xl relative overflow-hidden shadow-2xs">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Pendapatan Client</p>
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 tracking-tight" x-text="formatRupiah(profitSummary.grossRevenue)"></div>
                    <p class="text-xs text-indigo-600 font-medium mt-1">
                        <span x-text="profitSummary.approvedHours + 'h'"></span> disetujui @ <span x-text="formatRupiah(effectiveClientRateIdr)"></span>/h
                    </p>
                </div>

                <!-- Card 2: Total Payroll Expense -->
                <div class="bg-gradient-to-br from-rose-50/70 to-pink-50/40 border border-rose-100/80 p-5 rounded-2xl relative overflow-hidden shadow-2xs">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-rose-700 uppercase tracking-wider">Beban Payroll</p>
                        <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600 tracking-tight" x-text="formatRupiah(profitSummary.totalPayrollCost)"></div>
                    <p class="text-xs text-rose-600 font-medium mt-1">
                        <span x-text="profitSummary.contributorsCount"></span> worker (<span x-text="profitSummary.payrollRatioPct + '%'"></span> dari omset)
                    </p>
                </div>

                <!-- Card 3: Gross Profit -->
                <div class="bg-gradient-to-br from-emerald-50/70 to-teal-50/40 border border-emerald-100/80 p-5 rounded-2xl relative overflow-hidden shadow-2xs">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Laba Kotor</p>
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 tracking-tight" x-text="formatRupiah(profitSummary.grossProfit)"></div>
                    <p class="text-xs text-emerald-600 font-medium mt-1">
                        Revenue - Total Beban Gaji
                    </p>
                </div>

                <!-- Card 4: Net Profit Studio -->
                <div class="bg-gradient-to-br from-violet-100/90 via-purple-50 to-indigo-50 border-2 border-brand/30 p-5 rounded-2xl relative overflow-hidden shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-black text-brand uppercase tracking-wider flex items-center gap-1">
                            <span>Laba Bersih</span>
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-brand text-white">Net</span>
                        </p>
                        <div class="w-7 h-7 rounded-lg bg-brand text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-brand tracking-tight" x-text="formatRupiah(profitSummary.netProfit)"></div>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800" x-text="'+' + profitSummary.profitMarginPct + '%'"></span>
                        <span class="text-[11px] text-gray-500 font-medium">Margin Bersih</span>
                    </div>
                </div>

                <!-- Card 5: Total Jam Logged & QC Approval -->
                <div class="bg-gradient-to-br from-teal-50/70 to-emerald-50/40 border border-teal-100/80 p-5 rounded-2xl relative overflow-hidden shadow-2xs">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-teal-800 uppercase tracking-wider">Total Jam Logged</p>
                        <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-800 tracking-tight">
                        <span x-text="profitSummary.loggedHours"></span><span class="text-base font-bold text-slate-400 ml-1">Jam</span>
                    </div>
                    <p class="text-xs text-teal-700 font-medium mt-1">
                        <span x-text="profitSummary.totalTasks + ' Tasks'"></span> (<span class="font-bold text-emerald-700" x-text="profitSummary.qcEfficiencyPct + '% Approved'"></span>)
                    </p>
                    <template x-if="parseFloat(profitSummary.pendingHours) > 0">
                        <div class="text-[10px] text-amber-600 font-bold mt-1">
                            Pending: <span x-text="profitSummary.pendingHours + 'h'"></span> (+<span x-text="formatRupiah(profitSummary.potentialPendingRevenue)"></span>)
                        </div>
                    </template>
                </div>
            </div>

            <!-- Interactive Settings & Financial Breakdown Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left: Interactive Rate & Parameter Calculator -->
                <div class="lg:col-span-2 bg-white border border-gray-200/80 rounded-2xl p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 gap-2 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Parameter Finansial & Kalkulator Rate</h3>
                                <p class="text-xs text-gray-400">Ubah angka di bawah ini, kalkulasi laba dan margin di seluruh halaman akan terhitung otomatis.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Sync
                            </span>
                            <button @click="resetSettings()" class="text-xs font-semibold text-gray-400 hover:text-gray-700 underline transition">Reset Default</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Mode Rate -->
                        <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-200/60">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Rate Client</label>
                            <div class="flex rounded-lg bg-gray-200/70 p-1 gap-1">
                                <button type="button" @click="rateMode = 'USD'; saveSettings()" 
                                        :class="rateMode === 'USD' ? 'bg-white text-gray-900 font-bold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                        class="flex-1 py-1.5 text-xs rounded-md transition text-center">
                                    USD ($/jam)
                                </button>
                                <button type="button" @click="rateMode = 'IDR'; saveSettings()" 
                                        :class="rateMode === 'IDR' ? 'bg-white text-gray-900 font-bold shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                        class="flex-1 py-1.5 text-xs rounded-md transition text-center">
                                    IDR (Rp/jam)
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2" x-text="rateMode === 'USD' ? 'Kombinasi tarif USD dengan Kurs Dollar' : 'Tarif tetap dalam mata uang Rupiah'"></p>
                        </div>

                        <!-- If USD Mode -->
                        <template x-if="rateMode === 'USD'">
                            <div class="contents">
                                <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-200/60">
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Tarif Client (USD/h)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">$</span>
                                        <input type="number" step="0.1" min="0" x-model.number="clientRateUsd" @input="saveSettings()"
                                               class="w-full pl-7 pr-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-brand focus:outline-none">
                                    </div>
                                    <p class="text-[11px] text-indigo-600 font-medium mt-1">Tarif proyek per jam billable</p>
                                </div>

                                <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-200/60">
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Kurs USD / IDR</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                                        <input type="number" step="100" min="0" x-model.number="kursUsd" @input="saveSettings()"
                                               class="w-full pl-9 pr-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-brand focus:outline-none">
                                    </div>
                                    <p class="text-[11px] text-emerald-600 font-semibold mt-1">
                                        = <span x-text="formatRupiah(effectiveClientRateIdr)"></span> / jam
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- If IDR Mode -->
                        <template x-if="rateMode === 'IDR'">
                            <div class="sm:col-span-2 bg-gray-50/70 p-3.5 rounded-xl border border-gray-200/60">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Tarif Client Flat (IDR/h)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                                    <input type="number" step="1000" min="0" x-model.number="clientRateIdr" @input="saveSettings()"
                                           class="w-full pl-9 pr-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-brand focus:outline-none">
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Tarif penerimaan langsung dari klien per jam</p>
                            </div>
                        </template>
                    </div>

                    <!-- Operational Expense Row -->
                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-700">Biaya Operasional Tambahan (OpEx):</span>
                            <span class="text-xs text-gray-400 font-normal">(Internet, listrik, sewa, bonus)</span>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">Rp</span>
                            <input type="number" step="10000" min="0" x-model.number="operationalExpense" @input="saveSettings()"
                                   placeholder="0"
                                   class="w-full pl-9 pr-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-brand focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Right: Distribution Progress & Summary -->
                <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Distribusi Pendapatan</h3>
                        <p class="text-xs text-gray-400 mb-4">Rasio pembagian dari 100% pendapatan klien.</p>

                        <!-- Multi-color Progress Bar -->
                        <div class="w-full h-4 bg-gray-100 rounded-full overflow-hidden flex shadow-inner mb-4">
                            <!-- Payroll Portion -->
                            <div class="bg-rose-500 h-full transition-all duration-500" 
                                 :style="'width: ' + profitSummary.payrollRatioPct + '%'" 
                                 :title="'Beban Payroll: ' + profitSummary.payrollRatioPct + '%'"></div>
                            <!-- OpEx Portion -->
                            <div class="bg-slate-400 h-full transition-all duration-500" 
                                 :style="'width: ' + profitSummary.opExRatioPct + '%'" 
                                 :title="'Biaya Operasional: ' + profitSummary.opExRatioPct + '%'"></div>
                            <!-- Net Profit Portion -->
                            <div class="bg-emerald-500 h-full transition-all duration-500" 
                                 :style="'width: ' + profitSummary.profitRatioPct + '%'" 
                                 :title="'Laba Bersih: ' + profitSummary.profitRatioPct + '%'"></div>
                        </div>

                        <!-- Legend Items -->
                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                    <span class="text-gray-600 font-medium">Beban Gaji Kontributor</span>
                                </div>
                                <span class="font-bold text-gray-900" x-text="profitSummary.payrollRatioPct + '%'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                                    <span class="text-gray-600 font-medium">Biaya Operasional (OpEx)</span>
                                </div>
                                <span class="font-bold text-gray-900" x-text="profitSummary.opExRatioPct + '%'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <span class="text-emerald-700 font-bold">Laba Bersih Studio</span>
                                </div>
                                <span class="font-extrabold text-emerald-700" x-text="profitSummary.profitRatioPct + '%'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Quick Stat -->
                    <div class="mt-5 pt-4 border-t border-gray-100 bg-slate-50/70 p-3 rounded-xl border border-gray-100 text-xs flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Rata-rata Profit / Jam Kerja:</span>
                        <span class="font-extrabold text-gray-900 text-sm" x-text="formatRupiah(profitSummary.avgProfitPerHour) + ' /h'"></span>
                    </div>
                </div>

            </div>

            <!-- Contributor Profitability Table -->
            <div class="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Rincian Keuntungan Per Kontributor</h3>
                        <p class="text-xs text-gray-400">Peringkat kontributor berdasarkan margin dan kontribusi laba bersih untuk studio.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative w-48 sm:w-64">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama worker..." class="w-full pl-9 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00B090]">
                        </div>
                        <div class="text-xs font-semibold text-gray-500 shrink-0">
                            Total <span class="text-brand font-bold" x-text="contributorProfitList.length"></span> kontributor
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/75 border-b border-gray-200/70 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th scope="col" class="py-3.5 pl-5 pr-3">Employee / Kontributor</th>
                                <th scope="col" class="px-3 py-3.5 text-center">Tasks & Hari</th>
                                <th scope="col" class="px-3 py-3.5">Jam (Disetujui / Log)</th>
                                <th scope="col" class="px-3 py-3.5">Ditolak QC</th>
                                <th scope="col" class="px-3 py-3.5">Pendapatan Client</th>
                                <th scope="col" class="px-3 py-3.5">Gaji Worker (THP)</th>
                                <th scope="col" class="px-3 py-3.5">Laba Studio</th>
                                <th scope="col" class="px-3 py-3.5 text-center">Margin</th>
                                <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="isLoading">
                                <tr>
                                    <td colspan="9" class="px-6 py-14 text-center text-sm text-gray-400 font-medium">
                                        <div class="flex justify-center items-center gap-2.5">
                                            <svg class="animate-spin h-5 w-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>Menghitung analisa profit & beban studio...</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!isLoading && contributorProfitList.length === 0">
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-400 font-medium">Tidak ada data untuk periode ini.</td>
                                </tr>
                            </template>

                            <template x-for="(c, idx) in contributorProfitList" :key="c.name + '_' + idx">
                                <tr class="hover:bg-slate-50/70 transition-colors group">
                                    <!-- Employee Info -->
                                    <td class="py-3.5 pl-5 pr-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#00B090] to-teal-400 text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs shrink-0">
                                                <span x-text="c.name ? c.name.substring(0,2) : 'XX'"></span>
                                            </div>
                                            <div>
                                                <div class="text-xs sm:text-sm font-bold text-gray-900 group-hover:text-brand transition-colors" x-text="c.name"></div>
                                                <div class="text-[10px] text-gray-400 font-mono" x-text="c.isLive ? 'Worker #' + c.id : '#SLIP-' + String(c.id).padStart(4, '0')"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tasks & Hari Kerja -->
                                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                        <div class="text-xs font-bold text-slate-800" x-text="c.totalTasks + (c.isLive ? ' Tasks' : '')"></div>
                                        <div class="text-[10px] text-slate-400" x-text="c.workDays + (c.isLive ? ' Hari' : '')"></div>
                                    </td>

                                    <!-- Hours -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60" title="Jam Disetujui (Billable)">
                                                <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                <span x-text="c.apprHours + 'h'"></span>
                                            </span>
                                            <span class="text-xs text-gray-400 font-medium">/ <span x-text="c.loggedHours + 'h'"></span></span>
                                        </div>
                                    </td>

                                    <!-- Rejected Duration -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <template x-if="parseFloat(c.rejectedHours) > 0">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                                <span x-text="c.rejectedHours + 'h'"></span>
                                            </span>
                                        </template>
                                        <template x-if="parseFloat(c.rejectedHours) === 0">
                                            <span class="text-xs text-slate-400 font-medium">0.00h</span>
                                        </template>
                                    </td>

                                    <!-- Gross Revenue -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm font-bold text-indigo-900" x-text="formatRupiah(c.grossRevenue)"></div>
                                    </td>

                                    <!-- Payroll Cost -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm font-semibold text-rose-600" x-text="formatRupiah(c.payrollCost)"></div>
                                    </td>

                                    <!-- Profit -->
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm font-black" :class="c.profit >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="formatRupiah(c.profit)"></div>
                                    </td>

                                    <!-- Margin Pct -->
                                    <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold"
                                              :class="c.marginPct >= 30 ? 'bg-emerald-100 text-emerald-800' : (c.marginPct > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                              x-text="(c.marginPct >= 0 ? '+' : '') + c.marginPct + '%'">
                                        </span>
                                    </td>

                                    <!-- Action -->
                                    <td class="py-3.5 pl-3 pr-5 whitespace-nowrap text-right">
                                        <a :href="c.actionUrl" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white font-semibold text-xs transition shadow-xs"
                                           title="Buka Dokumen Slip Gaji">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Lihat Slip</span>
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Performance Insights -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200/80 p-4.5 rounded-2xl shadow-2xs flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-brand flex items-center justify-center font-bold text-base shrink-0">
                        👑
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Top Profit Contributor</p>
                        <h4 class="text-sm font-extrabold text-gray-900 mt-0.5" x-text="topContributor ? topContributor.name : '-'"></h4>
                        <p class="text-xs text-brand font-semibold" x-text="topContributor ? 'Laba Studio: ' + formatRupiah(topContributor.profit) : ''"></p>
                    </div>
                </div>

                <div class="bg-white border border-gray-200/80 p-4.5 rounded-2xl shadow-2xs flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base shrink-0">
                        ⚡
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">QC Approval Rate</p>
                        <h4 class="text-sm font-extrabold text-emerald-700 mt-0.5" x-text="profitSummary.qcEfficiencyPct + '% Approved'"></h4>
                        <p class="text-xs text-gray-500 font-medium" x-text="profitSummary.approvedHours + 'h dari ' + profitSummary.loggedHours + 'h logged'"></p>
                    </div>
                </div>

                <div class="bg-white border border-gray-200/80 p-4.5 rounded-2xl shadow-2xs flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base shrink-0">
                        🎯
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Target Efisiensi Studio</p>
                        <h4 class="text-sm font-extrabold text-indigo-900 mt-0.5">Sehat & Menguntungkan</h4>
                        <p class="text-xs text-indigo-600 font-medium" x-text="'Net Margin: ' + profitSummary.profitMarginPct + '%'"></p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="/assets/app.js"></script>
</body>
</html>
