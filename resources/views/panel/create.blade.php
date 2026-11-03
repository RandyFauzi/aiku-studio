<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Buat Slip Gaji</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
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
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="createSlipData()" x-init="init()" x-cloak>

    <!-- LEFT SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200/80 flex flex-col shrink-0 min-h-screen transition-all duration-300 z-30"
           :class="sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-0 md:w-0 overflow-hidden hidden'">
        
        <!-- Brand Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-100">
            <div class="flex items-center gap-3 cursor-pointer group">
                <div class="relative flex items-center justify-center w-9 h-9">
                    <div class="w-7 h-7 rounded-full border-[3px] border-[#00B090] absolute -left-0.5"></div>
                    <div class="w-7 h-7 rounded-full border-[3px] border-emerald-400 absolute -right-0.5 mix-blend-multiply opacity-80"></div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-extrabold tracking-tight text-slate-900">AIKu</span>
                    <span class="text-xs font-bold text-[#00B090] bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-100">Studio</span>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Sidebar Navigation Menu -->
        <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                        <div class="space-y-1.5">
                <div class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Menu Utama</div>
                <a href="index.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Dashboard</span>
                </a>
                <a href="recordings.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Task List (Rekaman)</span>
                </a>
                <a href="payslips.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Riwayat Slip Gaji</span>
                </a>
                <a href="shifts.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Pengaturan Shift</span>
                </a>
                <a href="create.html" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition text-[#009B7E] bg-[#EDFAF8] shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Buat Slip Gaji</span>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-[#00B090]"></span>
                </a>
                <a href="contributors.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Teams & Kontributor</span>
                </a>
                <a href="profit.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    <span>Reporting & Profit</span>
                </a>
            </div>
        </div>

        <div class="px-4 pb-3">@include('attendance.nav')</div>
        <!-- Sidebar Footer Admin -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3 truncate">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-sm">
                    AK
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-800 truncate">AIKu Studio Admin</div>
                    <div class="text-[11px] text-slate-400 truncate">admin@aikustudio.site</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN APP CONTENT AREA -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-3">
                <a href="create.html" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Slip Gaji</span>
                </a>

                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                        AM
                    </div>
                    <div class="hidden lg:block text-left">
                        <div class="text-xs font-bold text-slate-800 leading-tight">Abdul Mughni</div>
                        <div class="text-[11px] text-slate-400 leading-tight">AIKu Studio Admin</div>
                    </div>
                </div>
            </div>
        </header>
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- Breadcrumb & Header -->
            <div class="mb-8 border-b border-gray-100 pb-6 flex justify-between items-end">
                <div>
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2" x-text="slipId ? 'Overview / Payslips / Edit' : 'Overview / Payslips / Create'"></p>
                    <h2 class="text-3xl font-extrabold text-gray-900" x-text="slipId ? 'Edit Payslip' : 'Generate New Payslip'"></h2>
                </div>
                <div class="text-right flex items-end gap-6">
                    <template x-if="slipId">
                        <button type="button" @click="exportPdfFromEdit()" class="px-4 py-2 border-2 border-gray-800 rounded-lg text-gray-800 font-bold text-sm inline-flex items-center gap-2 hover:bg-gray-100 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export PDF
                        </button>
                    </template>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-1">Live Estimated THP</p>
                        <h3 class="text-2xl font-bold text-brand" x-text="formatRupiah(calculateTHP())"></h3>
                    </div>
                </div>
            </div>

            <!-- Form Split Layout -->
            <form @submit.prevent="submitForm" class="flex flex-col lg:flex-row gap-12">
                
                <!-- Left Column: General Settings -->
                <div class="w-full lg:w-1/3 space-y-6">
                    <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Payout Details</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Contributor Name</label>
                                <select x-model="formData.employee_name" @change="onWorkerChange()" required class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all appearance-none">
                                    <option value="" disabled selected>-- Select Contributor --</option>
                                    <template x-for="c in contributors" :key="c.id">
                                        <option :value="c.nama" x-text="c.nama"></option>
                                    </template>
                                    <option x-show="formData.employee_name && !contributors.some(c => c.nama === formData.employee_name)" 
                                            :value="formData.employee_name" 
                                            x-text="formData.employee_name"></option>
                                </select>
                            </div>
                            
                            <div x-data="{ 
                                open: false,
                                customStart: '',
                                customEnd: '',
                                isTraining: false,
                                applyCustom() {
                                    if(this.customStart && this.customEnd) {
                                        const start = new Date(this.customStart).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                                        const end = new Date(this.customEnd).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                                        const label = this.isTraining ? ('Training (' + start + ' - ' + end + ')') : (start + ' - ' + end);
                                        onPeriodSelect(label);
                                        this.open = false;
                                    }
                                }
                            }" class="relative">
                                <label class="block text-xs font-semibold text-gray-500 mb-1 flex justify-between items-center">
                                    <span>Period</span>
                                    <span class="text-[10px] text-brand font-medium">Bisa pilih dropdown atau edit langsung</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        x-model="formData.period" 
                                        @focus="open = true"
                                        placeholder="Pilih atau sesuaikan periode..."
                                        class="w-full bg-white border border-gray-200 rounded-lg pl-4 pr-10 py-2.5 text-sm font-medium focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all cursor-text"
                                    >
                                    <button type="button" @click="open = !open" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-700 transition-colors">
                                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                </div>

                                <!-- Dropdown menu -->
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden max-h-[380px] overflow-y-auto"
                                     style="display: none;">
                                    
                                    <!-- SECTION 1: PERIODE REGULER (CUT-OFF 22 - 21) -->
                                    <div class="py-1">
                                        <div class="px-4 py-2 text-[10px] font-extrabold text-[#009B7E] uppercase tracking-wider bg-emerald-50/70 border-b border-emerald-100 flex items-center justify-between">
                                            <span>📅 Periode Reguler (Cut-Off 22 - 21)</span>
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-black">Standar Baru</span>
                                        </div>
                                        <ul class="text-sm text-gray-700 divide-y divide-gray-50">
                                            <template x-for="p in periods" :key="p">
                                                <li>
                                                    <button type="button" 
                                                            @click="onPeriodSelect(p); open = false" 
                                                            class="w-full text-left px-4 py-2.5 text-xs hover:bg-[#00B090] hover:text-white transition-colors flex items-center justify-between group"
                                                            :class="formData.period === p ? 'bg-emerald-50/80 font-bold text-[#007F67]' : ''">
                                                        <span x-text="p"></span>
                                                        <span x-show="formData.period === p" class="text-[10px] bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-extrabold group-hover:bg-white group-hover:text-emerald-900">Aktif</span>
                                                    </button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>

                                    <!-- SECTION 2: PILIHAN TRAINING (BATCH KHUSUS) -->
                                    <div class="p-2.5 bg-amber-50/70 border-t border-b border-amber-200/60">
                                        <div class="flex items-center justify-between px-2 mb-1.5 text-[10px] font-extrabold text-amber-900 uppercase tracking-wider">
                                            <span>🎓 Periode Training (Batch Khusus)</span>
                                            <span class="bg-amber-200 text-amber-900 text-[9px] px-1.5 py-0.5 rounded font-black tracking-normal">Training Batch</span>
                                        </div>
                                        <div class="space-y-1">
                                            <button type="button" 
                                                    @click="onPeriodSelect('Training (18 Sep 2026 - 20 Sep 2026)'); open = false" 
                                                    class="w-full text-left px-3 py-1.5 text-xs font-semibold text-amber-950 rounded-lg hover:bg-amber-200/70 transition-colors flex items-center justify-between"
                                                    :class="formData.period === 'Training (18 Sep 2026 - 20 Sep 2026)' ? 'bg-amber-200/90 font-bold' : ''">
                                                <span>Training (18 Sep 2026 - 20 Sep 2026)</span>
                                                <span class="text-[10px] text-amber-800 bg-white/90 px-1.5 py-0.5 rounded border border-amber-300">18 - 20 Sep</span>
                                            </button>
                                            <button type="button" 
                                                    @click="onPeriodSelect('Training (19 Sep 2026 - 20 Sep 2026)'); open = false" 
                                                    class="w-full text-left px-3 py-1.5 text-xs font-semibold text-amber-950 rounded-lg hover:bg-amber-200/70 transition-colors flex items-center justify-between"
                                                    :class="formData.period === 'Training (19 Sep 2026 - 20 Sep 2026)' ? 'bg-amber-200/90 font-bold' : ''">
                                                <span>Training (19 Sep 2026 - 20 Sep 2026)</span>
                                                <span class="text-[10px] text-amber-800 bg-white/90 px-1.5 py-0.5 rounded border border-amber-300">19 - 20 Sep</span>
                                            </button>
                                            <button type="button" 
                                                    @click="onPeriodSelect('Training'); open = false" 
                                                    class="w-full text-left px-3 py-1.5 text-xs font-semibold text-amber-950 rounded-lg hover:bg-amber-200/70 transition-colors flex items-center justify-between"
                                                    :class="formData.period === 'Training' ? 'bg-amber-200/90 font-bold' : ''">
                                                <span>Training</span>
                                                <span class="text-[10px] text-amber-800 bg-white/90 px-1.5 py-0.5 rounded border border-amber-300">Hanya Label</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- SECTION 3: RENTANG TANGGAL KUSTOM -->
                                    <div class="p-3 bg-gray-50/90">
                                        <label class="block text-xs font-bold text-gray-600 mb-2">Atau sesuaikan rentang tanggal:</label>
                                        <div class="flex items-center gap-2 mb-2">
                                            <input type="date" x-model="customStart" class="w-full text-xs p-2 border border-gray-200 rounded-lg outline-none focus:border-brand text-gray-700 bg-white shadow-xs">
                                            <span class="text-gray-400 text-xs font-bold">s/d</span>
                                            <input type="date" x-model="customEnd" class="w-full text-xs p-2 border border-gray-200 rounded-lg outline-none focus:border-brand text-gray-700 bg-white shadow-xs">
                                        </div>
                                        <div class="flex items-center gap-2 mb-3">
                                            <input type="checkbox" id="isTrainingCheck" x-model="isTraining" class="rounded border-gray-300 text-brand focus:ring-brand w-3.5 h-3.5 cursor-pointer">
                                            <label for="isTrainingCheck" class="text-xs text-gray-700 font-medium cursor-pointer select-none">
                                                Format sebagai periode <strong class="text-amber-800">Training</strong>
                                            </label>
                                        </div>
                                        <button type="button" 
                                                @click="applyCustom()" 
                                                class="w-full text-center px-4 py-2 bg-gray-900 text-white rounded-lg text-xs font-bold hover:bg-[#00B090] transition-colors shadow-sm">
                                            Terapkan Tanggal
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 mt-4 border-t border-gray-200">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-semibold text-gray-500">Base Rate</span>
                                    <span class="text-sm font-bold text-gray-900">Rp 50,000 / Hr</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-semibold text-gray-500">Approved Hours</span>
                                    <span class="text-sm font-bold text-green-600" x-text="calculateApprovedHours() + ' Hrs'"></span>
                                </div>
                            </div>

                            <div class="pt-4 mt-4 border-t border-gray-200">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5 flex items-center justify-between">
                                    <span>Bonus / Insentif Tambahan (Rp)</span>
                                    <span class="text-[11px] text-emerald-600 font-bold" x-show="formData.bonus > 0" x-text="'+ ' + formatRupiah(formData.bonus || 0)"></span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-gray-400">Rp</span>
                                    <input 
                                        type="number" 
                                        x-model.number="formData.bonus" 
                                        min="0"
                                        step="5000"
                                        placeholder="0"
                                        class="w-full bg-white border border-gray-200 rounded-lg pl-9 pr-3 py-2 text-sm font-bold text-emerald-700 focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all"
                                    >
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1.5 leading-relaxed">Isi nominal bonus manual (hanya akan muncul di slip PDF jika diisi > 0).</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex items-center gap-3 pt-4">
                        <button type="submit" :disabled="isSubmitting" class="flex-1 bg-gray-900 hover:bg-black text-white py-3 rounded-full text-sm font-bold shadow-md transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting">Save & Generate PDF</span>
                            <span x-show="isSubmitting" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                    
                    <div x-show="message" x-transition class="p-4 rounded-xl text-sm font-medium border" :class="isSuccess ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'" x-text="message"></div>
                </div>

                <!-- Right Column: Evidence Days & Tasks Table -->
                <div class="w-full lg:w-2/3 space-y-4">
                    <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                                <span>Log Harian & Rincian Task</span>
                                <span class="bg-indigo-100 text-brand text-xs font-extrabold px-2.5 py-0.5 rounded-full" x-text="formData.evidence.length + ' Hari'"></span>
                                <span x-show="isLoadingEvidence" class="inline-flex items-center gap-1.5 text-xs text-[#009B7E] font-semibold animate-pulse">
                                    <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Memuat data cut-off...</span>
                                </span>
                            </h3>
                            <p class="text-xs text-gray-400 font-medium">Log tugas terisi otomatis berdasarkan cut-off 22-21 yang dipilih atau input manual.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    @click="loadWorkerEvidence()" 
                                    :disabled="isLoadingEvidence || !formData.employee_name" 
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all shadow-2xs flex items-center gap-1.5 disabled:opacity-40" 
                                    title="Tarik data rekaman terbaru dari database sesuai cut-off terpilih">
                                <svg class="w-3.5 h-3.5" :class="isLoadingEvidence ? 'animate-spin text-[#00B090]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Sinkronkan Database</span>
                            </button>
                            <button type="button" @click="addEvidence" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-brand font-bold text-xs rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>+ Tambah Hari Baru</span>
                            </button>
                        </div>
                    </div>

                    <!-- Days Accordion List -->
                    <div class="space-y-4">
                        <template x-for="(day, dayIndex) in formData.evidence" :key="dayIndex">
                            <div class="bg-white border border-gray-200/90 rounded-2xl shadow-sm overflow-hidden transition-all hover:border-gray-300">
                                
                                <!-- DAY ACCORDION HEADER (Clickable) -->
                                <div class="p-4 bg-gray-50/70 border-b border-gray-100 cursor-pointer hover:bg-gray-100/60 transition-colors select-none"
                                     @click="day.is_open = !day.is_open">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-xl bg-indigo-100 text-brand font-extrabold text-xs flex items-center justify-center shrink-0">
                                                <span x-text="'H' + (dayIndex + 1)"></span>
                                            </div>
                                            
                                            <!-- Date Picker (click.stop so it doesn't toggle accordion when picking date) -->
                                            <div class="flex items-center gap-1.5 bg-white border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-700 shadow-sm"
                                                 @click.stop>
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <input type="date" x-model="day.date" required class="bg-transparent border-none p-0 text-xs font-semibold text-gray-700 focus:ring-0 outline-none">
                                            </div>

                                            <span class="bg-gray-200/70 text-gray-600 text-[11px] font-extrabold px-2 py-0.5 rounded-full" 
                                                  x-text="(day.tasks ? day.tasks.length : 1) + ' Task'"></span>
                                        </div>

                                        <div class="flex items-center gap-3 self-end sm:self-center">
                                            <!-- Status Badge -->
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                  :class="getTaskStatus(day).bg + ' ' + getTaskStatus(day).color + ' ' + getTaskStatus(day).border"
                                                  x-text="getTaskStatus(day).label"></span>

                                            <!-- Day Total Duration -->
                                            <div class="text-right">
                                                <span class="text-xs font-black text-gray-900" x-text="parseFloat(day.total_video_mins || 0).toFixed(1) + ' mnt'"></span>
                                                <span class="text-[11px] text-gray-400 font-medium" x-text="'(' + minsToHours(day.total_video_mins) + ')'"></span>
                                            </div>

                                            <!-- Remove Day Button -->
                                            <button type="button" @click.stop="removeEvidence(dayIndex)" class="text-gray-400 hover:text-red-500 transition-colors p-1.5 rounded-lg hover:bg-red-50" title="Hapus seluruh hari ini">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>

                                            <!-- Chevron Icon -->
                                            <div class="w-6 h-6 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-400 transition-transform duration-200"
                                                 :class="day.is_open ? 'rotate-180' : ''">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- DAY COLLAPSIBLE CONTENT (Task List per Day) -->
                                <div x-show="day.is_open" x-collapse class="p-4 sm:p-5 bg-white space-y-4">
                                    <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-wider pb-2 border-b border-gray-100">
                                        <span>Daftar Tugas / Task (<span x-text="day.tasks ? day.tasks.length : 0"></span>)</span>
                                        <button type="button" @click="addTaskToDay(dayIndex)" class="text-brand hover:text-indigo-800 font-extrabold text-xs flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            <span>+ Tambah Task di Tanggal Ini</span>
                                        </button>
                                    </div>

                                    <!-- Tasks within this day -->
                                    <div class="space-y-3">
                                        <template x-for="(task, taskIndex) in day.tasks" :key="taskIndex">
                                            <div class="bg-gray-50/80 border border-gray-200/80 rounded-xl p-3.5 space-y-3 hover:bg-gray-50 transition-colors">
                                                <!-- Task Title & Remove -->
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="flex-1 flex items-center gap-2">
                                                        <span class="w-5 h-5 rounded-full bg-white text-gray-600 font-extrabold text-[10px] flex items-center justify-center border border-gray-200 shrink-0" 
                                                              x-text="taskIndex + 1"></span>
                                                        <input type="text" 
                                                               x-model="task.task_name" 
                                                               placeholder="Nama Task (contoh: Clean a Closet Shelf / Hang Clothes)" 
                                                               class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-900 placeholder-gray-400 focus:border-brand focus:ring-1 focus:ring-brand outline-none shadow-sm">
                                                    </div>

                                                    <button type="button" @click="removeTaskFromDay(dayIndex, taskIndex)" class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-md" title="Hapus task">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>

                                                <!-- Inputs: Approved, Rejected, Pending -->
                                                <div class="grid grid-cols-3 gap-2.5">
                                                    <!-- Approved -->
                                                    <div class="bg-white border border-emerald-200 rounded-lg p-2 shadow-xs">
                                                        <div class="flex justify-between items-center mb-0.5">
                                                            <span class="text-[10px] font-bold text-emerald-800 uppercase">Approved</span>
                                                            <span class="text-[9px] font-bold text-emerald-600" x-text="minsToHours(task.approved_mins)"></span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="any" x-model="task.approved_mins" @input="syncDayTotals(day)" placeholder="0"
                                                                   class="w-full text-xs font-bold text-emerald-700 bg-emerald-50/40 border border-emerald-200 rounded px-2 py-0.5 outline-none focus:ring-1 focus:ring-emerald-400">
                                                            <span class="text-[10px] text-emerald-600 font-medium">mnt</span>
                                                        </div>
                                                    </div>

                                                    <!-- Rejected -->
                                                    <div class="bg-white border border-rose-200 rounded-lg p-2 shadow-xs">
                                                        <div class="flex justify-between items-center mb-0.5">
                                                            <span class="text-[10px] font-bold text-rose-800 uppercase">Rejected</span>
                                                            <span class="text-[9px] font-bold text-rose-600" x-text="minsToHours(task.rejected_mins)"></span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="any" x-model="task.rejected_mins" @input="syncDayTotals(day)" placeholder="0"
                                                                   class="w-full text-xs font-bold text-rose-700 bg-rose-50/40 border border-rose-200 rounded px-2 py-0.5 outline-none focus:ring-1 focus:ring-rose-400">
                                                            <span class="text-[10px] text-rose-600 font-medium">mnt</span>
                                                        </div>
                                                    </div>

                                                    <!-- Pending -->
                                                    <div class="bg-white border border-amber-200 rounded-lg p-2 shadow-xs">
                                                        <div class="flex justify-between items-center mb-0.5">
                                                            <span class="text-[10px] font-bold text-amber-800 uppercase">Pending</span>
                                                            <span class="text-[9px] font-bold text-amber-600" x-text="minsToHours(task.pending_mins)"></span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="any" x-model="task.pending_mins" @input="syncDayTotals(day)" placeholder="0"
                                                                   class="w-full text-xs font-bold text-amber-700 bg-amber-50/40 border border-amber-200 rounded px-2 py-0.5 outline-none focus:ring-1 focus:ring-amber-400">
                                                            <span class="text-[10px] text-amber-600 font-medium">mnt</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Notes QA -->
                                                <div>
                                                    <input type="text" x-model="task.notes" placeholder="Catatan QA untuk task ini (opsional)"
                                                           class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1 text-xs text-gray-700 placeholder-gray-400 outline-none focus:border-brand">
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Quick button to add task in this day -->
                                    <button type="button" @click="addTaskToDay(dayIndex)" class="w-full py-2 border border-dashed border-indigo-200 hover:border-brand hover:bg-indigo-50/50 rounded-xl text-xs font-bold text-brand transition-all flex items-center justify-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span>+ Tambah Task Lain pada Tanggal Ini</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Add Day Button -->
                    <div class="pt-2">
                        <button type="button" @click="addEvidence" class="w-full py-3.5 border-2 border-dashed border-gray-300 hover:border-brand hover:bg-indigo-50/30 rounded-2xl text-xs font-extrabold text-gray-600 hover:text-brand transition-all flex items-center justify-center gap-2 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>+ Tambah Hari Baru</span>
                        </button>
                    </div>
                </div>
            </form>
        
        </div>
    </main>

    <script src="/assets/app.js"></script>
<form method="post" action="/logout" class="fixed bottom-3 left-3 z-50">@csrf<button class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 shadow" title="Keluar dari akun panel">Keluar akun</button></form>
</body>
</html>