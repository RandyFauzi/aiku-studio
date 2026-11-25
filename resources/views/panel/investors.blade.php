<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Investor & Aset HP</title>
    <meta name="theme-color" content="#edf6d9">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: { DEFAULT: '#00B090', 50: '#EDFAF8', 100: '#D5F4EF', 200: '#ADE8DF', 500: '#00B090', 600: '#009B7E', 700: '#007F67' }
                    }
                }
            }
        };
    </script>
    <style>[x-cloak] { display: none !important; }</style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="investorAnalyticsData()" x-cloak>

    @include('layouts.sidebar')

    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="text-sm font-semibold text-slate-700 hidden sm:block">
                    Payroll & Keuangan <span class="text-slate-300 mx-1">/</span> Investor & Aset HP
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <button type="button" @click="loadAnalytics()" :disabled="isLoading" class="border border-slate-200 hover:bg-slate-50 text-slate-700 px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-2xs flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span class="hidden sm:inline">Segarkan</span>
                </button>
                <button type="button" @click="openInvestorModal()" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-4 py-2 rounded-xl text-xs md:text-sm font-bold transition shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Investor</span>
                </button>
            </div>
        </header>

        <div class="w-full max-w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <!-- Header & Period Filter -->
            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Device Investment & Revenue Share</p>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Analitik & Profit Investor HP</h1>
                    <p class="text-xs text-slate-500 mt-1">Pemantauan jam billable (Approved), utilisasi unit HP berkode khusus, dan kalkulasi bagi hasil Rp 10.000/jam yang terintegrasi ke Estimasi Profit.</p>
                </div>

                <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 shrink-0">
                    <!-- Search -->
                    <div class="relative w-full sm:w-64">
                        <input type="search" x-model="searchQuery" placeholder="Cari investor, kode HP, email..."
                               class="w-full bg-white border border-slate-200/90 rounded-xl py-2 pl-9 pr-3 text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-[#00B090] focus:outline-none shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <!-- Period Dropdown -->
                    <div class="relative shrink-0" x-data="{ openPeriodDropdown: false }">
                        <button type="button"
                                @click="openPeriodDropdown = !openPeriodDropdown"
                                class="bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-800 py-2 px-3.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2.5 transition cursor-pointer shadow-2xs">
                            <svg class="w-4 h-4 text-[#00B090] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-xs text-slate-400 font-medium">Siklus:</span>
                            <span class="font-bold text-slate-900" x-text="selectedPeriodObj ? selectedPeriodObj.name : 'Pilih Siklus'"></span>
                            <svg class="w-3.5 h-3.5 text-slate-400" :class="openPeriodDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div x-show="openPeriodDropdown" @click.outside="openPeriodDropdown = false" x-cloak
                             class="absolute right-0 mt-1.5 w-80 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-2 text-xs text-slate-700">
                            <div class="px-2.5 py-1.5 mb-1 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Siklus Cut-Off</div>
                            <div class="max-h-72 overflow-y-auto space-y-1">
                                <template x-for="p in periods" :key="p.id">
                                    <button type="button" @click="selectPeriod(p.id); openPeriodDropdown = false"
                                            class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition cursor-pointer"
                                            :class="selectedPeriodId === p.id ? 'bg-[#EDFAF8] text-emerald-950 font-bold border border-emerald-200' : 'hover:bg-slate-50 text-slate-700'">
                                        <span x-text="p.name"></span>
                                        <span x-show="p.isCurrent" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Berjalan</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Investor & Unit HP</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900" x-text="totals.active_investor_count"></span>
                        <span class="text-xs font-semibold text-slate-500">Investor</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-lg font-extrabold text-brand-700" x-text="totals.device_count"></span>
                        <span class="text-xs font-semibold text-slate-500">Unit HP</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Skema aktif: Billable Approved Hours</p>
                </div>

                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Jam Billable HP</p>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-black text-slate-900" x-text="(totals.total_billable_hours || 0).toFixed(2)"></span>
                        <span class="text-sm font-bold text-slate-400">Jam</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        <span x-text="totals.total_tasks + ' task'"></span> • Logged: <span class="font-semibold" x-text="(totals.total_logged_hours || 0).toFixed(2) + 'j'"></span>
                    </p>
                </div>

                <div class="bg-gradient-to-br from-amber-50/80 to-orange-50/40 border border-amber-200/80 rounded-2xl p-5 shadow-2xs">
                    <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Total Bagi Hasil Investor</p>
                    <div class="mt-2 text-2xl font-black text-amber-900 tracking-tight" x-text="formatRupiah(totals.total_investor_profit_idr)"></div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1">Otomatis memotong Estimasi Profit Studio</p>
                </div>

                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status Pencairan Siklus</p>
                    <div class="mt-2 text-lg font-black text-emerald-700" x-text="formatRupiah(totals.total_paid_idr)"></div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Belum dibayar: <strong class="text-rose-600" x-text="formatRupiah(totals.total_unpaid_idr)"></strong>
                    </p>
                </div>
            </div>

            <!-- DAILY UTILIZATION CHART -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-2xs" x-show="dailyUtilization.length > 0">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Tren Pemakaian Harian HP Investor</h3>
                        <p class="text-xs text-slate-500">Akumulasi jam billable & profit harian seluruh unit HP investor pada siklus terpilih.</p>
                    </div>
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full" x-text="dailyUtilization.length + ' hari aktif'"></span>
                </div>
                <div class="flex items-end gap-2 h-36 overflow-x-auto pb-2 pt-4">
                    <template x-for="d in dailyUtilization" :key="d.date">
                        <div class="flex flex-col items-center min-w-[44px] flex-1 group">
                            <div class="text-[10px] font-bold text-slate-600 opacity-0 group-hover:opacity-100 transition mb-1 whitespace-nowrap" x-text="d.billable_hours + 'j'"></div>
                            <div class="w-full max-w-[32px] bg-amber-400/90 hover:bg-amber-500 rounded-t-lg transition-all cursor-pointer relative"
                                 :style="`height: ${Math.max(8, Math.round((d.profit_idr / maxDailyProfit) * 90))}px`"
                                 :title="d.date + ': ' + d.billable_hours + ' jam billable (' + formatRupiah(d.profit_idr) + ')'"></div>
                            <div class="text-[10px] text-slate-400 font-mono mt-1.5" x-text="d.date.slice(5)"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- INVESTORS LIST -->
            <div class="space-y-4">
                <template x-if="isLoading">
                    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-sm text-slate-500">
                        Memuat analitik investor & data HP...
                    </div>
                </template>

                <template x-if="!isLoading && filteredInvestors.length === 0">
                    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center space-y-3">
                        <p class="text-sm font-bold text-slate-700">Belum ada data investor HP</p>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">Tambahkan investor pertama Anda, lalu daftarkan unit HP dengan kode khusus (mis. <code class="bg-slate-100 px-1.5 py-0.5 rounded">INV-01-HP1</code>) dan tarif Rp 10.000/jam.</p>
                        <button type="button" @click="openInvestorModal()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white text-xs font-bold transition">
                            + Tambah Investor Sekarang
                        </button>
                    </div>
                </template>

                <template x-for="inv in filteredInvestors" :key="inv.id">
                    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
                        <!-- Investor Card Header -->
                        <div class="p-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-50/50">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-800 border border-amber-200 flex items-center justify-center font-black text-sm shrink-0" x-text="inv.code"></div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-base font-extrabold text-slate-900" x-text="inv.name"></h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              :class="inv.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                              x-text="inv.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                        <button type="button" @click="togglePayout(inv)"
                                                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border transition cursor-pointer"
                                                :class="inv.payout_status === 'paid' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-200'"
                                                :title="'Klik untuk ubah status bayar periode ini'">
                                            <span x-text="inv.payout_status === 'paid' ? '✓ Sudah Dibayar' : '⏳ Belum Dibayar'"></span>
                                        </button>
                                        <span x-show="inv.login_account"
                                              class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              :class="inv.login_account?.is_active ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                              x-text="inv.login_account?.is_active ? ('Login Aktif · ' + inv.login_account?.email) : 'Login Nonaktif'"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        <span x-text="inv.no_wa || 'Tanpa WA'"></span>
                                        <span class="mx-1.5 text-slate-300">•</span>
                                        <span x-text="(inv.bank_name || inv.no_rek) ? ((inv.bank_name || '') + ' ' + (inv.no_rek || '')) : 'Rekening belum diisi'"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 justify-between lg:justify-end">
                                <div class="text-left lg:text-right mr-2">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase">Profit Siklus Ini</div>
                                    <div class="text-lg font-black text-amber-700" x-text="formatRupiah(inv.profit_idr)"></div>
                                    <div class="text-[11px] text-slate-500" x-text="inv.billable_hours + ' jam billable (' + inv.approval_rate + '% appr)'"></div>
                                </div>

                                <button type="button" @click="openContractModal(inv)" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                                    + Unit HP
                                </button>
                                <button type="button" @click="openDetail(inv)" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                                    Analitik Harian
                                </button>
                                <button type="button" @click="copyInvestorReport(inv)" class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition cursor-pointer">
                                    Salin WA
                                </button>
                                <button type="button" @click="openAccountModal(inv)"
                                        class="px-3 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer"
                                        :class="inv.login_account ? 'bg-sky-50 border-sky-200 hover:bg-sky-100 text-sky-800' : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700'"
                                        x-text="inv.login_account ? 'Akun Login' : '+ Buat Login'"></button>
                                <button type="button" @click="openInvestorModal(inv)" class="px-2.5 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition cursor-pointer">
                                    Edit
                                </button>
                                <button type="button" @click="deleteInvestor(inv)" class="px-2.5 py-1.5 rounded-xl bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-semibold transition cursor-pointer">
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Contracts / Devices Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-white border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="py-3 px-5">Kode Khusus HP</th>
                                        <th class="py-3 px-4">Perangkat & Email Aktif</th>
                                        <th class="py-3 px-4 text-right">Tarif / Jam</th>
                                        <th class="py-3 px-4 text-right">Jam Billable</th>
                                        <th class="py-3 px-4 text-right">Logged / Pending</th>
                                        <th class="py-3 px-4 text-right">Utilisasi</th>
                                        <th class="py-3 px-4 text-right">Keuntungan Investor</th>
                                        <th class="py-3 px-5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-if="!inv.contracts || inv.contracts.length === 0">
                                        <tr>
                                            <td colspan="8" class="py-6 px-5 text-center text-slate-400">
                                                Investor ini belum memiliki unit HP tertaut. Klik <strong class="text-slate-700">+ Unit HP</strong> untuk menambahkan HP.
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-for="c in inv.contracts" :key="c.id">
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-5">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-900 text-white font-mono font-bold text-[11px]" x-text="c.device_code"></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-800" x-text="'HP ' + c.device_label"></div>
                                                <div class="text-[11px] text-slate-500 font-mono" x-text="c.active_email || 'Belum ada email aktif di HP & Akun'"></div>
                                            </td>
                                            <td class="py-3 px-4 text-right font-semibold text-slate-700">
                                                <div x-text="formatRupiah(c.rate_per_hour_idr)"></div>
                                                <div class="text-[10px] text-slate-400" x-text="c.rate_basis === 'duration' ? 'Total Rekam' : 'Billable Approved'"></div>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="font-extrabold text-emerald-700" x-text="c.billable_hours + ' jam'"></div>
                                                <div class="text-[10px] text-slate-400" x-text="c.tasks + ' task · ' + c.approval_rate + '%'"></div>
                                            </td>
                                            <td class="py-3 px-4 text-right text-slate-600">
                                                <div x-text="c.logged_hours + 'j logged'"></div>
                                                <div class="text-[10px] text-amber-600" x-show="c.pending_hours > 0" x-text="c.pending_hours + 'j pending'"></div>
                                            </td>
                                            <td class="py-3 px-4 text-right text-slate-600">
                                                <div x-text="c.active_days + ' hari'"></div>
                                                <div class="text-[10px] text-slate-400" x-text="'~' + c.avg_daily_hours + 'j/hari'"></div>
                                            </td>
                                            <td class="py-3 px-4 text-right font-black text-amber-700 text-sm" x-text="formatRupiah(c.profit_idr)"></td>
                                            <td class="py-3 px-5 text-right whitespace-nowrap space-x-2">
                                                <button type="button" @click="openContractModal(inv, c)" class="text-slate-600 hover:text-slate-900 font-semibold underline cursor-pointer">Edit</button>
                                                <button type="button" @click="deleteContract(c)" class="text-rose-600 hover:text-rose-800 font-semibold underline cursor-pointer">Lepas</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    <!-- MODAL TAMBAH / EDIT INVESTOR -->
    <div x-show="showInvestorModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
        <div @click.outside="showInvestorModal = false" class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-extrabold text-slate-900" x-text="isEditingInvestor ? 'Edit Investor' : 'Tambah Investor Baru'"></h3>
                <button type="button" @click="showInvestorModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
            </div>
            <form @submit.prevent="saveInvestor()" class="p-6 space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Investor</label>
                        <input type="text" x-model="investorForm.code" placeholder="Otomatis (INV-01)" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 uppercase font-mono">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Investor *</label>
                        <input type="text" x-model="investorForm.name" required placeholder="Nama investor" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-semibold">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">No. WhatsApp</label>
                        <input type="text" x-model="investorForm.no_wa" placeholder="08..." class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email</label>
                        <input type="email" x-model="investorForm.email" placeholder="opsional@email.com" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Bank</label>
                        <input type="text" x-model="investorForm.bank_name" placeholder="BCA / Mandiri / BRI" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">No. Rekening</label>
                        <input type="text" x-model="investorForm.no_rek" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Atas Nama</label>
                        <input type="text" x-model="investorForm.account_holder" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan</label>
                    <textarea x-model="investorForm.notes" rows="2" class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showInvestorModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" :disabled="saving" class="px-5 py-2 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white font-bold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : 'Simpan Investor'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT UNIT HP INVESTOR -->
    <div x-show="showContractModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
        <div @click.outside="showContractModal = false" class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEditingContract ? 'Edit Kontrak Unit HP' : 'Tambah Unit HP Investor'"></h3>
                    <p class="text-[11px] text-slate-500" x-text="'Investor: ' + (contractTargetInvestor?.name || '')"></p>
                </div>
                <button type="button" @click="showContractModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
            </div>
            <form @submit.prevent="saveContract()" class="p-6 space-y-4 text-xs">
                <template x-if="!isEditingContract">
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl">
                            <button type="button" @click="contractForm.device_mode = 'new'"
                                    :class="contractForm.device_mode === 'new' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-500'"
                                    class="py-1.5 rounded-lg transition">Daftarkan HP Baru</button>
                            <button type="button" @click="contractForm.device_mode = 'existing'"
                                    :class="contractForm.device_mode === 'existing' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-500'"
                                    class="py-1.5 rounded-lg transition">Pilih HP yang Ada</button>
                        </div>

                        <div x-show="contractForm.device_mode === 'new'">
                            <label class="block font-bold text-slate-700 mb-1">Nama HP di Sistem (Otomatis masuk ke HP & Akun) *</label>
                            <input type="text" x-model="contractForm.new_device_label" :required="contractForm.device_mode === 'new'" placeholder="Contoh: INV-01 Samsung A15" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-semibold">
                        </div>

                        <div x-show="contractForm.device_mode === 'existing'">
                            <label class="block font-bold text-slate-700 mb-1">Pilih Perangkat HP Terdaftar *</label>
                            <select x-model="contractForm.device_key" :required="contractForm.device_mode === 'existing'" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-semibold">
                                <option value="">-- Pilih HP Studio --</option>
                                <template x-for="d in availableDevices" :key="d.key">
                                    <option :value="d.key" x-text="'HP ' + d.label + ' (' + d.key + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Khusus HP Investor *</label>
                        <input type="text" x-model="contractForm.device_code" required placeholder="Contoh: INV-01-HP1" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-mono uppercase font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tarif Keuntungan (Rp / Jam) *</label>
                        <input type="number" x-model.number="contractForm.rate_per_hour_idr" required min="0" step="500" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Dasar Hitungan</label>
                        <select x-model="contractForm.rate_basis" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-semibold">
                            <option value="billable">Jam Billable (Approved)</option>
                            <option value="duration">Total Jam Rekam</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Efektif Mulai (Opsional)</label>
                        <input type="date" x-model="contractForm.effective_from" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Berakhir (Opsional)</label>
                        <input type="date" x-model="contractForm.effective_to" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showContractModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" :disabled="saving" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : 'Simpan Unit HP'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ANALITIK HARIAN INVESTOR -->
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
        <div @click.outside="showDetailModal = false" class="bg-white rounded-2xl border border-slate-200 w-full max-w-2xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="'Rincian Harian — ' + (selectedInvestorDetail?.name || '')"></h3>
                    <p class="text-[11px] text-slate-500" x-text="selectedPeriodObj?.name || ''"></p>
                </div>
                <button type="button" @click="showDetailModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 uppercase text-[11px]">
                            <th class="py-2 pr-4">Tanggal (WITA)</th>
                            <th class="py-2 pr-4 text-right">Jumlah Task</th>
                            <th class="py-2 pr-4 text-right">Jam Billable</th>
                            <th class="py-2 text-right">Keuntungan Investor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-if="!selectedInvestorDetail?.daily_series?.length">
                            <tr><td colspan="4" class="py-8 text-center text-slate-400">Belum ada rekaman billable pada siklus ini.</td></tr>
                        </template>
                        <template x-for="d in (selectedInvestorDetail?.daily_series || [])" :key="d.date">
                            <tr>
                                <td class="py-2.5 pr-4 font-mono font-semibold text-slate-800" x-text="d.date"></td>
                                <td class="py-2.5 pr-4 text-right text-slate-600" x-text="d.tasks + ' task'"></td>
                                <td class="py-2.5 pr-4 text-right font-bold text-emerald-700" x-text="d.billable_hours + ' jam'"></td>
                                <td class="py-2.5 text-right font-extrabold text-amber-700" x-text="formatRupiah(d.profit_idr)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL KELOLA AKUN LOGIN INVESTOR -->
    <div x-show="showAccountModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
        <div @click.outside="showAccountModal = false" class="bg-white rounded-2xl border border-slate-200 w-full max-w-md shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="accountTargetInvestor?.login_account ? 'Kelola Akun Login Investor' : 'Buat Akun Login Investor'"></h3>
                    <p class="text-[11px] text-slate-500" x-text="'Investor: ' + (accountTargetInvestor?.name || '') + ' (' + (accountTargetInvestor?.code || '') + ')'"></p>
                </div>
                <button type="button" @click="showAccountModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
            </div>
            <form @submit.prevent="saveAccount()" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Login *</label>
                    <input type="email" x-model="accountForm.email" required placeholder="investor@email.com" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 font-semibold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username / Nama Tampil</label>
                    <input type="text" x-model="accountForm.username" placeholder="Nama tampilan saat login" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1" x-text="accountTargetInvestor?.login_account ? 'Reset Kata Sandi (Opsional, min. 6 karakter)' : 'Kata Sandi Awal (min. 6 karakter) *'"></label>
                    <input type="password" x-model="accountForm.password" :required="!accountTargetInvestor?.login_account" minlength="6" placeholder="Minimal 6 karakter" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                </div>
                <label class="flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" x-model="accountForm.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Izinkan investor ini login ke Portal Investor (/investor)</span>
                </label>
                <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100">
                    <template x-if="accountTargetInvestor?.login_account">
                        <button type="button" @click="toggleAccountStatus(accountTargetInvestor)"
                                class="px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer"
                                :class="accountTargetInvestor.login_account.is_active ? 'border-rose-200 text-rose-600 hover:bg-rose-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'"
                                x-text="accountTargetInvestor.login_account.is_active ? 'Nonaktifkan Cepat' : 'Aktifkan Cepat'"></button>
                    </template>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" @click="showAccountModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold">Batal</button>
                        <button type="submit" :disabled="saving" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : 'Simpan Akun'"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/app.js?v={{ filemtime(public_path('assets/app.js')) }}"></script>
</body>
</html>
