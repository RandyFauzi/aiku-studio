<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Pengaturan Shift & Simulasi Produksi</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; background: white !important; }
            .print-only { display: block !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="shiftsData()" x-init="initData()" x-cloak>

    <!-- LEFT SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200/80 flex flex-col shrink-0 min-h-screen transition-all duration-300 z-30"
           :class="sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-0 md:w-0 overflow-hidden hidden'">
        
        <!-- Brand Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-100">
            <a href="index.html" class="flex items-center gap-3 cursor-pointer group">
                <div class="relative flex items-center justify-center w-9 h-9">
                    <div class="w-7 h-7 rounded-full border-[3px] border-[#00B090] absolute -left-0.5"></div>
                    <div class="w-7 h-7 rounded-full border-[3px] border-emerald-400 absolute -right-0.5 mix-blend-multiply opacity-80"></div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-extrabold tracking-tight text-slate-900">AIKu</span>
                    <span class="text-xs font-bold text-[#00B090] bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-100">Studio</span>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </a>
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

                <a href="shifts.html" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition text-[#009B7E] bg-[#EDFAF8] shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Pengaturan Shift</span>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-[#00B090]"></span>
                </a>

                <a href="create.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Buat Slip Gaji</span>
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
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="hidden sm:block">
                    <div class="text-sm font-extrabold text-slate-900">Manajemen Shift & Produksi</div>
                    <div class="text-xs text-slate-400">Rancangan Tim 12 Karyawan • 3 Alat • 6 HP</div>
                </div>
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button @click="resetToDefault()" 
                        class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold transition flex items-center gap-1.5"
                        title="Kembalikan ke Jadwal Standar">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span class="hidden md:inline">Reset Default</span>
                </button>

                <button @click="printSchedule()" 
                        class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Jadwal</span>
                </button>

                <button @click="saveConfig()" 
                        class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    <span>Simpan Pengaturan</span>
                </button>

                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                        AM
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT BODY -->
        <div class="w-full max-w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- PAGE TITLE & TAB SWITCHER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ⚡ AIKu Egocentric Video Shift Engine
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            🏖️ Libur Setiap Hari Senin
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Pengaturan Shift & Simulasi Cerdas</h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">
                        Rancangan jadwal shift 12 karyawan, visual timeline charging alat, dan proyeksi produksi 1 bulan penuh.
                    </p>
                </div>

                <!-- TAB SWITCHER PILLS -->
                <div class="inline-flex p-1.5 bg-slate-200/80 rounded-2xl text-xs font-bold gap-1 self-start md:self-auto shadow-inner">
                    <button @click="activeTab = 'jadwal'"
                            type="button"
                            class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2"
                            :class="activeTab === 'jadwal' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                        <span>📋</span>
                        <span>Jadwal & Timeline</span>
                    </button>
                    <button @click="activeTab = 'pengaturan'"
                            type="button"
                            class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2"
                            :class="activeTab === 'pengaturan' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                        <span>🎛️</span>
                        <span>Setting & Harian</span>
                    </button>
                    <button @click="activeTab = 'bulanan'"
                            type="button"
                            class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2"
                            :class="activeTab === 'bulanan' ? 'bg-[#00B090] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                        <span>🧠</span>
                        <span>Simulasi 1 Bulan (Libur Senin)</span>
                    </button>
                </div>
            </div>

            <!-- TOP KPI HIGHLIGHT CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Total Alat & HP -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-teal-50 h-16 w-16 rounded-full opacity-60"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kapasitas Alat</h4>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">3 Alat <span class="text-xs font-semibold text-emerald-600 ml-1">• 6 HP</span></div>
                    <div class="text-[10px] text-slate-400 mt-1">1 Alat = 2 HP Stereo (2x Output)</div>
                </div>

                <!-- Potensi Produksi Harian -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-emerald-50 h-16 w-16 rounded-full opacity-60"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Target Harian</h4>
                    <div class="text-xl sm:text-2xl font-black text-[#009B7E] tracking-tight">60 Jam <span class="text-xs font-semibold text-emerald-500">/ hari</span></div>
                    <div class="text-[10px] text-emerald-600 font-semibold mt-1">30 jam kerja tim × 2 HP</div>
                </div>

                <!-- Jam Operasional -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-blue-50 h-16 w-16 rounded-full opacity-60"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jam Operasional</h4>
                    <div class="text-xl sm:text-2xl font-black text-blue-600 tracking-tight font-mono" x-text="calculatedTimes[0].start + '–' + calculatedTimes[4].end"></div>
                    <div class="text-[10px] text-slate-400 mt-1">5 Sesi Rekaman + 4 Jeda Cas</div>
                </div>

                <!-- Hari Kerja Aktif (Bulan) -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs relative overflow-hidden group">
                    <div class="absolute -right-3 -bottom-3 bg-amber-50 h-16 w-16 rounded-full opacity-60"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Hari Kerja Bulanan</h4>
                    <div class="text-xl sm:text-2xl font-black text-amber-600 tracking-tight" x-text="monthSimulation.activeDays + ' Hari'"></div>
                    <div class="text-[10px] text-amber-600 font-medium mt-1" x-text="monthSimulation.offMondaysCount + ' Senin Libur Rutin'"></div>
                </div>

                <!-- Potensi Produksi Bulanan -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs relative overflow-hidden group col-span-2 lg:col-span-1">
                    <div class="absolute -right-3 -bottom-3 bg-purple-50 h-16 w-16 rounded-full opacity-60"></div>
                    <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Target Produksi 1 Bulan</h4>
                    <div class="text-xl sm:text-2xl font-black text-purple-700 tracking-tight" x-text="formatNumber(monthSimulation.totalMonthlyProductionHours) + ' Jam'"></div>
                    <div class="text-[10px] text-purple-600 font-medium mt-1" x-text="'Est. Gaji ' + formatRupiah(monthSimulation.totalMonthlyPayrollCost)"></div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: RANCANGAN SHIFT & TIMELINE ALAT (GAMBAR 1 & 2)       -->
            <!-- ========================================================= -->
            <div x-show="activeTab === 'jadwal'" class="space-y-6">

                <!-- KETENTUAN DASAR SIMULASI (CARD PERSIS GAMBAR 1) -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-xs">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Ketentuan Operasional & Aturan Shift</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-xs sm:text-sm">
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 font-black flex items-center justify-center shrink-0 text-xs">
                                4H
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">Rio, Aufa, Zikri (3 Perekam Utama)</div>
                                <div class="text-slate-500 text-xs mt-0.5">4 jam kerja = <strong>8 jam produksi</strong> per orang (2 sesi pagi berturut-turut).</div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 font-black flex items-center justify-center shrink-0 text-xs">
                                2H
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">9 Karyawan Lainnya (Reguler)</div>
                                <div class="text-slate-500 text-xs mt-0.5">2 jam kerja = <strong>4 jam produksi</strong> per orang (1 sesi kerja per hari).</div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 font-black flex items-center justify-center shrink-0 text-xs">
                                2×
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">1 Alat = 2 HP (Stereo Dual Camera)</div>
                                <div class="text-slate-500 text-xs mt-0.5">Setiap 1 jam kerja menghasilkan <strong>2 jam rekaman video</strong> valid.</div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-pink-100 text-pink-700 font-black flex items-center justify-center shrink-0 text-xs">
                                🤝
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">Sasa & Nuga Wajib Bersama</div>
                                <div class="text-slate-500 text-xs mt-0.5">Wajib ditempatkan pada jam yang sama (Sesi 4: 17.00–19.00).</div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 font-black flex items-center justify-center shrink-0 text-xs">
                                ⚡
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">Jeda Charging 1 Jam</div>
                                <div class="text-slate-500 text-xs mt-0.5">Di antara setiap sesi rekaman untuk menjaga baterai 6 HP tetap prima.</div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/60 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 font-black flex items-center justify-center shrink-0 text-xs">
                                🏖️
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">Hari Senin Libur Studio</div>
                                <div class="text-slate-500 text-xs mt-0.5">Operasional berlangsung 6 hari (Selasa–Minggu). Senin libur pemeliharaan.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 1: RANCANGAN SHIFT YANG DIREKOMENDASIKAN (HIGH CONTRAST CARD AS IN IMAGE 1) -->
                <div class="bg-[#121417] text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-800">
                        <div>
                            <div class="text-xs font-bold text-emerald-400 tracking-wider uppercase mb-1">1. Rancangan Shift yang Direkomendasikan</div>
                            <h3 class="text-xl sm:text-2xl font-black text-white">Jadwal 12 Karyawan</h3>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-950/80 text-emerald-400 border border-emerald-800/80">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span>3 alat • 6 HP</span>
                        </div>
                    </div>

                    <!-- Shift Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                                    <th class="py-3 px-4 w-1/4">Waktu</th>
                                    <th class="py-3 px-4 w-1/4">Alat 1</th>
                                    <th class="py-3 px-4 w-1/4">Alat 2</th>
                                    <th class="py-3 px-4 w-1/4">Alat 3</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-sm">
                                <!-- Sesi 1 -->
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-4 px-4 font-mono font-bold text-white" x-text="calculatedTimes[0].start + '–' + calculatedTimes[0].end">08–10</td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-emerald-300">
                                            <span class="w-6 h-6 rounded-lg bg-emerald-900/60 text-emerald-300 flex items-center justify-center text-[11px]">R</span>
                                            <span>Rio</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-emerald-300">
                                            <span class="w-6 h-6 rounded-lg bg-emerald-900/60 text-emerald-300 flex items-center justify-center text-[11px]">A</span>
                                            <span>Aufa</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-emerald-300">
                                            <span class="w-6 h-6 rounded-lg bg-emerald-900/60 text-emerald-300 flex items-center justify-center text-[11px]">Z</span>
                                            <span>Zikri</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Cas 1 -->
                                <tr class="bg-amber-950/20 text-amber-500/90 text-xs font-semibold">
                                    <td class="py-3 px-4 font-mono font-bold text-amber-400" x-text="calculatedTimes[0].end + '–' + calculatedTimes[1].start">10–11</td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                </tr>

                                <!-- Sesi 2 -->
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-4 px-4 font-mono font-bold text-white" x-text="calculatedTimes[1].start + '–' + calculatedTimes[1].end">11–13</td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-emerald-300">
                                            <span class="w-6 h-6 rounded-lg bg-emerald-900/60 text-emerald-300 flex items-center justify-center text-[11px]">R</span>
                                            <span>Rio</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-emerald-300">
                                            <span class="w-6 h-6 rounded-lg bg-emerald-900/60 text-emerald-300 flex items-center justify-center text-[11px]">A</span>
                                            <span>Aufa</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-emerald-300">
                                            <span class="w-6 h-6 rounded-lg bg-emerald-900/60 text-emerald-300 flex items-center justify-center text-[11px]">Z</span>
                                            <span>Zikri</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Cas 2 -->
                                <tr class="bg-amber-950/20 text-amber-500/90 text-xs font-semibold">
                                    <td class="py-3 px-4 font-mono font-bold text-amber-400" x-text="calculatedTimes[1].end + '–' + calculatedTimes[2].start">13–14</td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                </tr>

                                <!-- Sesi 3 -->
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-4 px-4 font-mono font-bold text-white" x-text="calculatedTimes[2].start + '–' + calculatedTimes[2].end">14–16</td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">R</span>
                                            <span>Rahmat</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">Y</span>
                                            <span>Yoga</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">G</span>
                                            <span>Ghina</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Cas 3 -->
                                <tr class="bg-amber-950/20 text-amber-500/90 text-xs font-semibold">
                                    <td class="py-3 px-4 font-mono font-bold text-amber-400" x-text="calculatedTimes[2].end + '–' + calculatedTimes[3].start">16–17</td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                </tr>

                                <!-- Sesi 4 (Sasa & Nuga Special Pairing) -->
                                <tr class="bg-indigo-950/30 border-l-4 border-indigo-500 hover:bg-indigo-950/50 transition">
                                    <td class="py-4 px-4 font-mono font-bold text-white flex flex-col">
                                        <span x-text="calculatedTimes[3].start + '–' + calculatedTimes[3].end">17–19</span>
                                        <span class="text-[10px] text-indigo-400 font-sans font-bold">Wajib Bersama</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-indigo-200">
                                            <span class="w-6 h-6 rounded-lg bg-indigo-900/70 text-indigo-300 flex items-center justify-center text-[11px]">N</span>
                                            <span>Nuga</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-pink-300">
                                            <span class="w-6 h-6 rounded-lg bg-pink-900/70 text-pink-300 flex items-center justify-center text-[11px]">S</span>
                                            <span>Sasa</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">F</span>
                                            <span>Fai</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Cas 4 -->
                                <tr class="bg-amber-950/20 text-amber-500/90 text-xs font-semibold">
                                    <td class="py-3 px-4 font-mono font-bold text-amber-400" x-text="calculatedTimes[3].end + '–' + calculatedTimes[4].start">19–20</td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center gap-1.5"><span class="text-amber-400">⚡</span> <span>Cas</span></span></td>
                                </tr>

                                <!-- Sesi 5 -->
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-4 px-4 font-mono font-bold text-white" x-text="calculatedTimes[4].start + '–' + calculatedTimes[4].end">20–22</td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">M</span>
                                            <span>Maryam</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">A</span>
                                            <span>Afif</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-2 font-bold text-slate-200">
                                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center text-[11px]">F</span>
                                            <span>Fahri</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Note Footer -->
                    <div class="mt-6 pt-4 border-t border-slate-800 text-xs text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Rio, Aufa, dan Zikri mengisi dua sesi pagi. Sasa dan Nuga bersama pada pukul 17.00–19.00.</span>
                    </div>
                </div>

                <!-- BAGIAN 2: TIMELINE PENGGUNAAN ALAT DAN CHARGING (CARD PERSIS GAMBAR 2) -->
                <div class="bg-[#121417] text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-800">
                        <div>
                            <div class="text-xs font-bold text-emerald-400 tracking-wider uppercase mb-1">2. Timeline Penggunaan Alat dan Charging</div>
                            <h3 class="text-xl sm:text-2xl font-black text-white">Siklus Harian Penggunaan Alat</h3>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-bold">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-sm bg-[#00B090]"></span>
                                <span>Rekaman</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-sm bg-[#F59E0B]"></span>
                                <span>Charging (Cas)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Gantt Visual -->
                    <div class="space-y-4 pt-2 overflow-x-auto">
                        <div class="min-w-[700px] space-y-4">
                            <!-- Hour Headers -->
                            <div class="text-center text-xs font-bold text-slate-400 pl-16 pr-2" style="display: grid; grid-template-columns: repeat(14, minmax(0, 1fr));">
                                <span>08</span><span>09</span><span>10</span><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span><span>18</span><span>19</span><span>20</span><span>21</span>
                            </div>

                            <!-- Alat 1 -->
                            <div class="flex items-center gap-4">
                                <span class="w-12 text-sm font-bold text-slate-300 shrink-0">Alat 1</span>
                                <div class="flex-1 gap-1 h-12 bg-slate-900/60 p-1 rounded-xl border border-slate-800" style="display: grid; grid-template-columns: repeat(14, minmax(0, 1fr));">
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="08-10: Rio (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="10-11: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="11-13: Rio (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="13-14: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="14-16: Rahmat (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="16-17: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="17-19: Nuga (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="19-20: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="20-22: Maryam (Rekaman)">Rekaman</div>
                                </div>
                            </div>

                            <!-- Alat 2 -->
                            <div class="flex items-center gap-4">
                                <span class="w-12 text-sm font-bold text-slate-300 shrink-0">Alat 2</span>
                                <div class="flex-1 gap-1 h-12 bg-slate-900/60 p-1 rounded-xl border border-slate-800" style="display: grid; grid-template-columns: repeat(14, minmax(0, 1fr));">
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="08-10: Aufa (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="10-11: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="11-13: Aufa (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="13-14: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="14-16: Yoga (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="16-17: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="17-19: Sasa (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="19-20: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="20-22: Afif (Rekaman)">Rekaman</div>
                                </div>
                            </div>

                            <!-- Alat 3 -->
                            <div class="flex items-center gap-4">
                                <span class="w-12 text-sm font-bold text-slate-300 shrink-0">Alat 3</span>
                                <div class="flex-1 gap-1 h-12 bg-slate-900/60 p-1 rounded-xl border border-slate-800" style="display: grid; grid-template-columns: repeat(14, minmax(0, 1fr));">
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="08-10: Zikri (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="10-11: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="11-13: Zikri (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="13-14: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="14-16: Ghina (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="16-17: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="17-19: Fai (Rekaman)">Rekaman</div>
                                    <div class="col-span-1 bg-[#F59E0B] rounded-lg flex items-center justify-center font-bold text-[10px] text-slate-950" title="19-20: Cas">⚡</div>
                                    <div class="col-span-2 bg-[#00B090] rounded-lg flex items-center justify-center font-bold text-[11px] shadow-xs" title="20-22: Fahri (Rekaman)">Rekaman</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Text -->
                    <div class="mt-8 pt-6 border-t border-slate-800/80 text-center space-y-1">
                        <div class="text-lg sm:text-xl font-black text-white">Total 10 jam rekaman aktif per alat</div>
                        <p class="text-xs text-slate-400">
                            Setiap alat menjalani 5 sesi rekaman dengan 4 jeda charging. Operasional berlangsung 08.00–22.00.
                        </p>
                    </div>
                </div>

            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: PENGATURAN & SIMULASI HARIAN (GAMBAR 3 & 4)         -->
            <!-- ========================================================= -->
            <div x-show="activeTab === 'pengaturan'" class="space-y-6">

                <!-- BAGIAN 4: CONTROLLER PENGATURAN INTERAKTIF (GAMBAR 4) -->
                <div class="bg-[#121417] text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800 space-y-8">
                    <div>
                        <div class="text-xs font-bold text-emerald-400 tracking-wider uppercase mb-1">Pengaturan Shift Interaktif</div>
                        <h3 class="text-xl sm:text-2xl font-black text-white">Sesuaikan Parameter Shift</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Mulai Produksi Radio -->
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-slate-300 block">Mulai produksi</label>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="07:00" x-model="config.start_time" class="w-4 h-4 text-emerald-500 bg-slate-900 border-slate-700 focus:ring-emerald-500">
                                    <span class="text-sm font-semibold" :class="config.start_time === '07:00' ? 'text-emerald-400 font-bold' : 'text-slate-400'">07.00</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="08:00" x-model="config.start_time" class="w-4 h-4 text-emerald-500 bg-slate-900 border-slate-700 focus:ring-emerald-500">
                                    <span class="text-sm font-semibold" :class="config.start_time === '08:00' ? 'text-emerald-400 font-bold' : 'text-slate-400'">08.00</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="09:00" x-model="config.start_time" class="w-4 h-4 text-emerald-500 bg-slate-900 border-slate-700 focus:ring-emerald-500">
                                    <span class="text-sm font-semibold" :class="config.start_time === '09:00' ? 'text-emerald-400 font-bold' : 'text-slate-400'">09.00</span>
                                </label>
                            </div>
                        </div>

                        <!-- Jeda Charging Slider -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm font-bold">
                                <span class="text-slate-300">Jeda charging</span>
                                <span class="text-emerald-400 font-mono" x-text="config.charging_duration_min + ' menit'">60 menit</span>
                            </div>
                            <input type="range" min="30" max="90" step="15" x-model.number="config.charging_duration_min"
                                   class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-[#00B090]">
                            <div class="flex justify-between text-xs text-slate-500 font-medium">
                                <span>30 mnt</span>
                                <span>60 mnt</span>
                                <span>90 mnt</span>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Live Sesi Table -->
                    <div class="space-y-4 pt-4 border-t border-slate-800">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <span class="w-2/5">Sesi</span>
                            <span class="w-1/3 text-center">Jam</span>
                            <span class="w-1/4 text-right">Produksi</span>
                        </div>

                        <div class="divide-y divide-slate-800/80 text-sm">
                            <!-- Sesi 1 -->
                            <div class="py-3 flex items-center justify-between">
                                <div class="w-2/5 font-bold text-white">Rio / Aufa / Zikri</div>
                                <div class="w-1/3 text-center font-mono text-slate-300" x-text="calculatedTimes[0].start + '–' + calculatedTimes[0].end">08:00–10:00</div>
                                <div class="w-1/4 text-right font-bold text-emerald-400">12 jam</div>
                            </div>

                            <!-- Sesi 2 -->
                            <div class="py-3 flex items-center justify-between">
                                <div class="w-2/5 font-bold text-white">Rio / Aufa / Zikri</div>
                                <div class="w-1/3 text-center font-mono text-slate-300" x-text="calculatedTimes[1].start + '–' + calculatedTimes[1].end">11:00–13:00</div>
                                <div class="w-1/4 text-right font-bold text-emerald-400">12 jam</div>
                            </div>

                            <!-- Sesi 3 -->
                            <div class="py-3 flex items-center justify-between">
                                <div class="w-2/5 font-bold text-white">Rahmat / Yoga / Ghina</div>
                                <div class="w-1/3 text-center font-mono text-slate-300" x-text="calculatedTimes[2].start + '–' + calculatedTimes[2].end">14:00–16:00</div>
                                <div class="w-1/4 text-right font-bold text-emerald-400">12 jam</div>
                            </div>

                            <!-- Sesi 4 -->
                            <div class="py-3 flex items-center justify-between">
                                <div class="w-2/5 font-bold text-white">Nuga / Sasa / Fai</div>
                                <div class="w-1/3 text-center font-mono text-slate-300" x-text="calculatedTimes[3].start + '–' + calculatedTimes[3].end">17:00–19:00</div>
                                <div class="w-1/4 text-right font-bold text-emerald-400">12 jam</div>
                            </div>

                            <!-- Sesi 5 -->
                            <div class="py-3 flex items-center justify-between">
                                <div class="w-2/5 font-bold text-white">Maryam / Afif / Fahri</div>
                                <div class="w-1/3 text-center font-mono text-slate-300" x-text="calculatedTimes[4].start + '–' + calculatedTimes[4].end">20:00–22:00</div>
                                <div class="w-1/4 text-right font-bold text-emerald-400">12 jam</div>
                            </div>
                        </div>

                        <!-- Selesai Operasional Big Banner -->
                        <div class="pt-6 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-base sm:text-lg font-bold text-slate-400">Selesai operasional</span>
                            <span class="text-3xl sm:text-5xl font-black text-white font-mono" x-text="calculatedTimes[4].end">22:00</span>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: RINGKASAN TOTAL PRODUKSI HARIAN (CARD PERSIS GAMBAR 3) -->
                <div class="bg-[#121417] text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800 space-y-6">
                    <div class="border-b border-slate-800 pb-4">
                        <div class="text-xs font-bold text-emerald-400 tracking-wider uppercase mb-1">3. Ringkasan Total Produksi Harian</div>
                        <h3 class="text-xl sm:text-2xl font-black text-white">Kalkulasi Output Produksi Per Hari</h3>
                    </div>

                    <!-- 2 Cards Output Per Orang -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-900/80 p-5 rounded-2xl border border-slate-800">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rio, Aufa, Zikri</div>
                            <div class="text-4xl sm:text-5xl font-black text-white font-mono">8 jam</div>
                            <div class="text-xs text-slate-400 font-medium mt-1">Produksi per orang (4 jam kerja × 2 HP)</div>
                        </div>

                        <div class="bg-slate-900/80 p-5 rounded-2xl border border-slate-800">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">9 karyawan lainnya</div>
                            <div class="text-4xl sm:text-5xl font-black text-white font-mono">4 jam</div>
                            <div class="text-xs text-slate-400 font-medium mt-1">Produksi per orang (2 jam kerja × 2 HP)</div>
                        </div>
                    </div>

                    <!-- Komposisi Produksi Harian Bar -->
                    <div class="bg-slate-900/80 p-5 rounded-2xl border border-slate-800 space-y-3">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Komposisi produksi harian</div>
                        
                        <div class="h-10 w-full bg-slate-950 rounded-xl overflow-hidden flex shadow-inner text-xs font-black">
                            <div style="width: 40%" class="bg-[#00B090] text-slate-950 flex items-center justify-center gap-1">
                                <span>24 jam</span>
                            </div>
                            <div style="width: 60%" class="bg-slate-600 text-white flex items-center justify-center gap-1">
                                <span>36 jam</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-xs font-medium text-slate-400 pt-1">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-sm bg-[#00B090]"></span>
                                <span>3 perekam utama (3 × 8h = 24h)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-sm bg-slate-600"></span>
                                <span>9 perekam lainnya (9 × 4h = 36h)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Potensi Produksi Banner -->
                    <div class="bg-gradient-to-r from-slate-900 to-slate-950 p-6 rounded-2xl border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-bold text-slate-300">Total potensi produksi harian</div>
                            <div class="text-xs text-slate-400 mt-0.5">30 jam kerja tim × 2 HP</div>
                            <div class="text-[11px] text-slate-500 mt-2">Jam rekaman gabungan dari seluruh HP, sebelum memperhitungkan video gagal atau tidak valid.</div>
                        </div>
                        <div class="text-4xl sm:text-6xl font-black text-[#00B090] font-mono shrink-0">
                            60 jam
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: SIMULASI CERDAS 1 BULAN (LIBUR HARI SENIN)         -->
            <!-- ========================================================= -->
            <div x-show="activeTab === 'bulanan'" class="space-y-6">

                <!-- SIMULATOR CONTROL PANEL -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-100 text-purple-800 mb-1">
                                🧠 Smart Production & Payroll Predictor
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900">Simulasi Cerdas Produksi 1 Bulan</h2>
                            <p class="text-xs text-slate-500">Kalkulasi presisi output rekaman egocentric 6 HP dengan sistem studio libur setiap hari Senin.</p>
                        </div>

                        <!-- Pilih Siklus / Bulan -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-500">Pilih Siklus:</label>
                            <select x-model="selectedMonthKey" @change="recalculateMonth()"
                                    class="bg-slate-50 border border-slate-200 text-slate-800 text-xs sm:text-sm font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-[#00B090] focus:outline-none">
                                <option value="2026-11">Bulan November 2026 (30 Hari)</option>
                                <option value="2026-12">Bulan Desember 2026 (31 Hari)</option>
                                <option value="2026-10">Bulan Oktober 2026 (31 Hari)</option>
                                <option value="cut-off-22">Siklus Cut-Off 22 - 21 (30 Hari)</option>
                                <option value="custom">Kustomisasi Jumlah Hari</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom days input if custom selected -->
                    <div x-show="selectedMonthKey === 'custom'" class="p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-center gap-4 text-xs font-medium text-amber-900">
                        <span>Tentukan Total Hari dalam Periode:</span>
                        <input type="number" min="20" max="35" x-model.number="customTotalDays" @input="recalculateMonth()"
                               class="w-20 px-3 py-1.5 bg-white border border-amber-300 rounded-lg font-bold text-center">
                        <span class="text-slate-500">Sistem otomatis menghitung hari Senin yang jatuh pada rentang tersebut.</span>
                    </div>

                    <!-- Additional Sliders: QA Yield Rate & Hourly Rate -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- QA Yield Rate Slider -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 space-y-2">
                            <div class="flex justify-between items-center text-xs font-bold">
                                <span class="text-slate-700">Toleransi QA / Yield Pass Rate:</span>
                                <span class="text-emerald-700 font-mono text-sm font-extrabold" x-text="monthSimulation.qaYieldRate + '% (Approved)'">90%</span>
                            </div>
                            <input type="range" min="70" max="100" step="5" x-model.number="monthSimulation.qaYieldRate"
                                   class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#00B090]">
                            <div class="flex justify-between text-[11px] text-slate-400">
                                <span>70% (Konservatif)</span>
                                <span>90% (Standar Studio)</span>
                                <span>100% (Maksimal Teoretis)</span>
                            </div>
                        </div>

                        <!-- Hourly Rate per Worker -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 space-y-2">
                            <div class="flex justify-between items-center text-xs font-bold">
                                <span class="text-slate-700">Tarif Honor Karyawan:</span>
                                <span class="text-slate-900 font-mono text-sm font-extrabold" x-text="formatRupiah(monthSimulation.hourlyRate) + ' / Jam Kerja'">Rp 50.000 / Jam</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="number" step="5000" min="20000" max="100000" x-model.number="monthSimulation.hourlyRate"
                                       class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl font-bold text-sm text-slate-800">
                                <button @click="monthSimulation.hourlyRate = 50000" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-200 hover:bg-slate-300 text-slate-700 shrink-0">Reset 50k</button>
                            </div>
                            <div class="text-[11px] text-slate-400">Dasar perhitungan honor resmi AIKu Studio per jam kerja approved.</div>
                        </div>
                    </div>
                </div>

                <!-- 1 MONTH INTERACTIVE CALENDAR GRID WITH MONDAY OFF SYSTEM -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-slate-900">Kalender Operasional Bulanan</h3>
                            <p class="text-xs text-slate-500">Hari Senin otomatis libur studio (0 jam). Klik tanggal untuk toggle libur khusus / lembur jika diperlukan.</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-bold">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-700">Aktif (60 Jam)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                                <span class="text-slate-700">Libur Senin (0 Jam)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                                <span class="text-slate-700">Libur Khusus</span>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-2 pt-2 text-center text-xs">
                        <!-- Day Headers -->
                        <div class="font-extrabold text-rose-600 bg-rose-50 py-2 rounded-xl">SENIN (LIBUR)</div>
                        <div class="font-bold text-slate-700 bg-slate-100 py-2 rounded-xl">SELASA</div>
                        <div class="font-bold text-slate-700 bg-slate-100 py-2 rounded-xl">RABU</div>
                        <div class="font-bold text-slate-700 bg-slate-100 py-2 rounded-xl">KAMIS</div>
                        <div class="font-bold text-slate-700 bg-slate-100 py-2 rounded-xl">JUMAT</div>
                        <div class="font-bold text-slate-700 bg-slate-100 py-2 rounded-xl">SABTU</div>
                        <div class="font-bold text-slate-700 bg-slate-100 py-2 rounded-xl">MINGGU</div>

                        <!-- Date Cells -->
                        <template x-for="day in monthSimulation.calendarDays" :key="day.dateStr">
                            <div @click="toggleDayStatus(day)" 
                                 class="p-2 sm:p-3 rounded-2xl border transition-all cursor-pointer relative group flex flex-col justify-between min-h-[72px]"
                                 :class="{
                                     'bg-rose-50/70 border-rose-200 text-rose-900 hover:bg-rose-100': day.isMonday && day.isOff,
                                     'bg-amber-50 border-amber-200 text-amber-900 hover:bg-amber-100': !day.isMonday && day.isOff,
                                     'bg-emerald-50/80 border-emerald-200 text-emerald-950 hover:bg-emerald-100 shadow-2xs': !day.isOff
                                 }">
                                
                                <div class="flex items-center justify-between text-xs font-extrabold">
                                    <span x-text="day.dayNumber"></span>
                                    <span class="text-[10px]" x-text="day.isOff ? '🏖️' : '⚡'"></span>
                                </div>

                                <div class="mt-1">
                                    <template x-if="day.isOff">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-200/80 text-rose-800">
                                            Libur
                                        </span>
                                    </template>
                                    <template x-if="!day.isOff">
                                        <div class="flex flex-col">
                                            <span class="text-[11px] font-black text-emerald-700 font-mono">60h Video</span>
                                            <span class="text-[9px] text-emerald-600/80">30h Kerja</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- DETAILED MONTHLY METRIC HIGHLIGHTS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Hari & Jam Kerja Card -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Durasi & Kehadiran</span>
                            <span class="text-lg">📅</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600">Total Hari dalam Bulan:</span>
                                <span class="font-extrabold text-slate-900" x-text="monthSimulation.totalDaysInMonth + ' Hari'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-rose-600 font-bold">
                                <span>Hari Libur (Senin):</span>
                                <span x-text="monthSimulation.offMondaysCount + ' Hari'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-emerald-700 font-extrabold pt-2 border-t border-slate-100">
                                <span>Hari Kerja Aktif:</span>
                                <span class="text-lg" x-text="monthSimulation.activeDays + ' Hari'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-blue-700 font-extrabold">
                                <span>Total Jam Kerja Manusia:</span>
                                <span x-text="(monthSimulation.activeDays * 30) + ' Jam'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Output Video (Kamera 6 HP) -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Output Rekaman 6 HP</span>
                            <span class="text-lg">📹</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600">Total Potensi Bruto:</span>
                                <span class="font-extrabold text-slate-900 font-mono" x-text="formatNumber(monthSimulation.totalMonthlyProductionHours) + ' Jam'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-emerald-700 font-bold">
                                <span>Estimasi Approved (<span x-text="monthSimulation.qaYieldRate + '%'"></span>):</span>
                                <span class="font-mono font-extrabold" x-text="formatNumber(monthSimulation.estimatedApprovedHours) + ' Jam'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-rose-500 font-medium">
                                <span>Estimasi Rejected / Gagal:</span>
                                <span class="font-mono" x-text="formatNumber(monthSimulation.estimatedRejectedHours) + ' Jam'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-purple-700 font-extrabold pt-2 border-t border-slate-100">
                                <span>Output Per Hari Aktif:</span>
                                <span class="text-lg font-mono">60.00 Jam</span>
                            </div>
                        </div>
                    </div>

                    <!-- Anggaran Biaya Payroll Bulanan -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Proyeksi Finansial</span>
                            <span class="text-lg">💰</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600">3 Perekam Utama:</span>
                                <span class="font-extrabold text-slate-900" x-text="formatRupiah(monthSimulation.payrollUtamaTotal)"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600">9 Perekam Reguler:</span>
                                <span class="font-extrabold text-slate-900" x-text="formatRupiah(monthSimulation.payrollRegulerTotal)"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-[#009B7E] font-black pt-2 border-t border-slate-100">
                                <span>Total Anggaran Payroll:</span>
                                <span class="text-lg" x-text="formatRupiah(monthSimulation.totalMonthlyPayrollCost)"></span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-slate-400">
                                <span>Biaya per Jam Video:</span>
                                <span class="font-bold text-slate-700" x-text="formatRupiah(monthSimulation.costPerVideoHour) + ' / jam'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MONTHLY ROSTER TABLE (12 KARYAWAN DETAIL) -->
                <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-xs">
                    <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900">Roster & Estimasi Gaji 12 Karyawan (1 Bulan Penuh)</h3>
                            <p class="text-xs text-slate-400">Rincian jam kerja, jam produksi, dan estimasi take home pay per karyawan untuk <span class="font-bold text-slate-700" x-text="monthSimulation.activeDays + ' hari kerja'"></span>.</p>
                        </div>
                        <button @click="copyWhatsAppRosterSummary()" 
                                class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#009B7E] border border-emerald-200 text-xs font-bold transition flex items-center gap-1.5 self-start sm:self-auto shadow-2xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                            <span>Salin Ringkasan WhatsApp</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-3.5 pl-6 pr-3">Karyawan / Perekam</th>
                                    <th class="px-3 py-3.5">Peran / Kategori</th>
                                    <th class="px-3 py-3.5 text-center">Hari Kerja</th>
                                    <th class="px-3 py-3.5 text-center">Jam Kerja / Hari</th>
                                    <th class="px-3 py-3.5 text-center">Jam Kerja Bulanan</th>
                                    <th class="px-3 py-3.5 text-center">Target Video (2 HP)</th>
                                    <th class="py-3.5 pl-3 pr-6 text-right">Estimasi Gaji Bulanan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <template x-for="k in monthSimulation.roster" :key="k.name">
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <!-- Nama -->
                                        <td class="py-3.5 pl-6 pr-3 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs uppercase shadow-2xs text-white"
                                                     :class="k.role === 'Utama' ? 'bg-gradient-to-tr from-purple-600 to-indigo-500' : 'bg-gradient-to-tr from-emerald-600 to-teal-400'"
                                                     x-text="k.short.substring(0,2)">
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900" x-text="k.name"></div>
                                                    <div class="text-[11px] text-slate-400" x-text="k.alat + ' • Sesi ' + k.sessions.join(' & ')"></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kategori -->
                                        <td class="px-3 py-3.5 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                                  :class="k.role === 'Utama' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                                                  x-text="k.role === 'Utama' ? '⭐ Perekam Utama' : 'Perekam Reguler'">
                                            </span>
                                        </td>

                                        <!-- Hari Kerja -->
                                        <td class="px-3 py-3.5 whitespace-nowrap text-center font-bold text-slate-700" x-text="k.monthlyActiveDays + ' Hari'"></td>

                                        <!-- Jam Kerja / Hari -->
                                        <td class="px-3 py-3.5 whitespace-nowrap text-center font-mono font-bold text-slate-800" x-text="k.daily_work_hrs + ' Jam'"></td>

                                        <!-- Total Jam Kerja Bulanan -->
                                        <td class="px-3 py-3.5 whitespace-nowrap text-center font-mono font-bold text-blue-600" x-text="k.monthlyWorkHours + ' Jam'"></td>

                                        <!-- Target Video Bulanan -->
                                        <td class="px-3 py-3.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center gap-1 font-mono font-extrabold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200/80" x-text="k.monthlyProdHours + ' Jam Video'">
                                            </span>
                                        </td>

                                        <!-- Estimasi Gaji Bulanan -->
                                        <td class="py-3.5 pl-3 pr-6 whitespace-nowrap text-right font-extrabold text-slate-900 font-mono text-base" x-text="formatRupiah(k.monthlySalary)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-slate-50 font-black text-slate-900 text-sm border-t-2 border-slate-200">
                                <tr>
                                    <td colspan="4" class="py-4 pl-6 pr-3">TOTAL 12 KARYAWAN (1 BULAN PENUH)</td>
                                    <td class="px-3 py-4 text-center font-mono text-blue-700" x-text="(monthSimulation.activeDays * 30) + ' Jam Kerja'"></td>
                                    <td class="px-3 py-4 text-center font-mono text-emerald-700" x-text="formatNumber(monthSimulation.totalMonthlyProductionHours) + ' Jam Video'"></td>
                                    <td class="py-4 pl-3 pr-6 text-right font-mono text-lg text-[#009B7E]" x-text="formatRupiah(monthSimulation.totalMonthlyPayrollCost)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- ALPINE JS LOGIC SCRIPT -->
    <script>
        function shiftsData() {
            return {
                sidebarOpen: localStorage.getItem('aiku_sidebar_open') !== 'false',
                activeTab: 'jadwal', // 'jadwal', 'pengaturan', 'bulanan'

                // Master Configuration
                config: {
                    start_time: '08:00',
                    charging_duration_min: 60,
                    session_duration_min: 120,
                    sessions_count: 5,
                    tools_count: 3,
                    hp_per_tool: 2,
                    hourly_rate: 50000,
                    day_off: 'monday',
                    qa_yield_rate: 90,
                    karyawan: []
                },

                // Simulation state
                selectedMonthKey: '2026-11',
                customTotalDays: 30,

                monthSimulation: {
                    totalDaysInMonth: 30,
                    activeDays: 26,
                    offMondaysCount: 4,
                    totalMonthlyProductionHours: 1560,
                    totalMonthlyPayrollCost: 39000000,
                    payrollUtamaTotal: 15600000,
                    payrollRegulerTotal: 23400000,
                    costPerVideoHour: 25000,
                    qaYieldRate: 90,
                    hourlyRate: 50000,
                    estimatedApprovedHours: 1404,
                    estimatedRejectedHours: 156,
                    calendarDays: [],
                    roster: []
                },

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num || 0);
                },

                async initData() {
                    try {
                        const res = await fetch('/api/shifts/config');
                        const data = await res.json();
                        if (data.success && data.config) {
                            this.config = Object.assign(this.config, data.config);
                        }
                    } catch (e) {
                        console.error('Failed to load shift config:', e);
                    }
                    this.recalculateMonth();
                },

                // Realtime dynamic calculated start and end times for all 5 sessions
                get calculatedTimes() {
                    const startStr = this.config.start_time || '08:00';
                    const [hStr, mStr] = startStr.split(':');
                    let currentMinutes = parseInt(hStr, 10) * 60 + parseInt(mStr, 10);

                    const sessionDur = this.config.session_duration_min || 120;
                    const chargeDur = this.config.charging_duration_min || 60;

                    const list = [];
                    for (let i = 0; i < 5; i++) {
                        const sStartMins = currentMinutes;
                        const sEndMins = sStartMins + sessionDur;

                        const formatTime = (mins) => {
                            const hh = Math.floor(mins / 60) % 24;
                            const mm = mins % 60;
                            return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
                        };

                        list.push({
                            session: i + 1,
                            start: formatTime(sStartMins),
                            end: formatTime(sEndMins),
                            rawStart: sStartMins,
                            rawEnd: sEndMins
                        });

                        // Advance by session + charging for next session
                        currentMinutes = sEndMins + chargeDur;
                    }
                    return list;
                },

                recalculateMonth() {
                    let totalDays = 30;
                    let year = 2026;
                    let monthIdx = 10; // November (0-based)

                    if (this.selectedMonthKey === '2026-11') {
                        totalDays = 30;
                        year = 2026;
                        monthIdx = 10;
                    } else if (this.selectedMonthKey === '2026-12') {
                        totalDays = 31;
                        year = 2026;
                        monthIdx = 11;
                    } else if (this.selectedMonthKey === '2026-10') {
                        totalDays = 31;
                        year = 2026;
                        monthIdx = 9;
                    } else if (this.selectedMonthKey === 'cut-off-22') {
                        totalDays = 30;
                        year = 2026;
                        monthIdx = 9;
                    } else if (this.selectedMonthKey === 'custom') {
                        totalDays = Math.max(20, Math.min(35, this.customTotalDays || 30));
                    }

                    const daysArr = [];
                    let mondaysCount = 0;
                    let activeCount = 0;

                    for (let d = 1; d <= totalDays; d++) {
                        const dt = new Date(year, monthIdx, d);
                        const dayOfWeek = dt.getDay(); // 0 is Sunday, 1 is Monday
                        const isMonday = (dayOfWeek === 1);
                        const isOff = isMonday;

                        if (isMonday) mondaysCount++;
                        if (!isOff) activeCount++;

                        daysArr.push({
                            dayNumber: d,
                            dateStr: `${year}-${monthIdx + 1}-${d}`,
                            dayOfWeek,
                            isMonday,
                            isOff
                        });
                    }

                    this.monthSimulation.totalDaysInMonth = totalDays;
                    this.monthSimulation.activeDays = activeCount;
                    this.monthSimulation.offMondaysCount = mondaysCount;
                    this.monthSimulation.calendarDays = daysArr;

                    this.updateRosterAndTotals();
                },

                toggleDayStatus(day) {
                    day.isOff = !day.isOff;
                    let active = 0;
                    let offMondays = 0;
                    this.monthSimulation.calendarDays.forEach(d => {
                        if (!d.isOff) active++;
                        if (d.isMonday && d.isOff) offMondays++;
                    });
                    this.monthSimulation.activeDays = active;
                    this.monthSimulation.offMondaysCount = offMondays;
                    this.updateRosterAndTotals();
                },

                updateRosterAndTotals() {
                    const activeDays = this.monthSimulation.activeDays;
                    const dailyProduction = 60; // 30 man-hours * 2 HP
                    const totalMonthlyProd = activeDays * dailyProduction;
                    const hourlyRate = this.monthSimulation.hourlyRate || 50000;
                    const qaRate = (this.monthSimulation.qaYieldRate || 90) / 100;

                    this.monthSimulation.totalMonthlyProductionHours = totalMonthlyProd;
                    this.monthSimulation.estimatedApprovedHours = +(totalMonthlyProd * qaRate).toFixed(2);
                    this.monthSimulation.estimatedRejectedHours = +(totalMonthlyProd * (1 - qaRate)).toFixed(2);

                    const defaultKaryawan = [
                        { name: 'Muhammad Rio Hadi', short: 'Rio', role: 'Utama', daily_work_hrs: 4, daily_prod_hrs: 8, sessions: [1, 2], alat: 'Alat 1' },
                        { name: 'Aufa Anugraha', short: 'Aufa', role: 'Utama', daily_work_hrs: 4, daily_prod_hrs: 8, sessions: [1, 2], alat: 'Alat 2' },
                        { name: 'Muhamad Zikri Fadhilah', short: 'Zikri', role: 'Utama', daily_work_hrs: 4, daily_prod_hrs: 8, sessions: [1, 2], alat: 'Alat 3' },
                        { name: 'Rahmatullah', short: 'Rahmat', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [3], alat: 'Alat 1' },
                        { name: 'Muhammad Diyoga', short: 'Yoga', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [3], alat: 'Alat 2' },
                        { name: 'Ghina Mawaddah', short: 'Ghina', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [3], alat: 'Alat 3' },
                        { name: 'Muhammad Nugraha Dwiputra', short: 'Nuga', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [4], alat: 'Alat 1' },
                        { name: 'Sasa', short: 'Sasa', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [4], alat: 'Alat 2' },
                        { name: 'Fai', short: 'Fai', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [4], alat: 'Alat 3' },
                        { name: 'Siti Mariyam', short: 'Maryam', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [5], alat: 'Alat 1' },
                        { name: 'Afif Anshari', short: 'Afif', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [5], alat: 'Alat 2' },
                        { name: 'Fahri', short: 'Fahri', role: 'Reguler', daily_work_hrs: 2, daily_prod_hrs: 4, sessions: [5], alat: 'Alat 3' }
                    ];

                    let totalPayroll = 0;
                    let utamaTotal = 0;
                    let regulerTotal = 0;

                    const roster = defaultKaryawan.map(k => {
                        const monthlyWorkHours = activeDays * k.daily_work_hrs;
                        const monthlyProdHours = activeDays * k.daily_prod_hrs;
                        const monthlySalary = monthlyWorkHours * hourlyRate;

                        totalPayroll += monthlySalary;
                        if (k.role === 'Utama') utamaTotal += monthlySalary;
                        else regulerTotal += monthlySalary;

                        return {
                            ...k,
                            monthlyActiveDays: activeDays,
                            monthlyWorkHours,
                            monthlyProdHours,
                            monthlySalary
                        };
                    });

                    this.monthSimulation.roster = roster;
                    this.monthSimulation.totalMonthlyPayrollCost = totalPayroll;
                    this.monthSimulation.payrollUtamaTotal = utamaTotal;
                    this.monthSimulation.payrollRegulerTotal = regulerTotal;
                    this.monthSimulation.costPerVideoHour = Math.round(totalPayroll / (totalMonthlyProd || 1));
                },

                async saveConfig() {
                    try {
                        const res = await fetch('/api/shifts/config', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.config)
                        });
                        const data = await res.json();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Disimpan',
                                text: 'Konfigurasi shift studio berhasil diperbarui!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert('Gagal menyimpan: ' + data.error);
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Gagal terhubung ke server');
                    }
                },

                resetToDefault() {
                    this.config.start_time = '08:00';
                    this.config.charging_duration_min = 60;
                    this.config.session_duration_min = 120;
                    this.monthSimulation.qaYieldRate = 90;
                    this.monthSimulation.hourlyRate = 50000;
                    this.recalculateMonth();
                    Swal.fire({
                        icon: 'info',
                        title: 'Reset Selesai',
                        text: 'Jadwal dan parameter shift telah dikembalikan ke standar awal.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },

                printSchedule() {
                    window.print();
                },

                copyWhatsAppRosterSummary() {
                    const text = `*JADWAL SHIFT & TARGET BULANAN AIKU STUDIO*\n` +
                        `• Hari Kerja: ${this.monthSimulation.activeDays} Hari (Libur Hari Senin)\n` +
                        `• Total Target Produksi 6 HP: ${this.monthSimulation.totalMonthlyProductionHours} Jam Video\n` +
                        `• Jam Operasional Harian: ${this.calculatedTimes[0].start} - ${this.calculatedTimes[4].end}\n\n` +
                        `*PEMBAGIAN SESI:*\n` +
                        `1. ${this.calculatedTimes[0].start}-${this.calculatedTimes[0].end}: Rio (Alat 1), Aufa (Alat 2), Zikri (Alat 3)\n` +
                        `2. ${this.calculatedTimes[1].start}-${this.calculatedTimes[1].end}: Rio (Alat 1), Aufa (Alat 2), Zikri (Alat 3)\n` +
                        `3. ${this.calculatedTimes[2].start}-${this.calculatedTimes[2].end}: Rahmat (Alat 1), Yoga (Alat 2), Ghina (Alat 3)\n` +
                        `4. ${this.calculatedTimes[3].start}-${this.calculatedTimes[3].end}: Nuga (Alat 1), Sasa (Alat 2), Fai (Alat 3)\n` +
                        `5. ${this.calculatedTimes[4].start}-${this.calculatedTimes[4].end}: Maryam (Alat 1), Afif (Alat 2), Fahri (Alat 3)\n\n` +
                        `_AIKu Studio Egocentric Video Production_`;

                    navigator.clipboard.writeText(text).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersalin!',
                            text: 'Ringkasan jadwal berhasil disalin ke clipboard untuk WhatsApp Tim.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    });
                }
            };
        }
    </script>
<form method="post" action="/logout" class="fixed bottom-3 left-3 z-50">@csrf<button class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 shadow" title="Keluar dari akun panel">Keluar akun</button></form>
</body>
</html>
