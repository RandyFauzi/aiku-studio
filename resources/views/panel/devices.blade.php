<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Pengaturan HP & Akun</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="devicesManagerData()" x-init="init()" x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="text-sm font-semibold text-slate-700 hidden sm:block">
                    Operasional Studio <span class="text-slate-300 mx-1">/</span> HP & Akun
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="fetchDevices()" :disabled="isLoading" class="border border-slate-200 hover:bg-slate-50 text-slate-700 px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-2xs flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" :class="isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span class="hidden sm:inline">Segarkan Data</span>
                </button>
            </div>
        </header>

        <!-- CONTAINER (FULL WIDTH TO MATCH APP DASHBOARD STANDARD) -->
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- Title & Info Banner -->
            <div style="background: linear-gradient(135deg, #065f46 0%, #0f766e 100%);" class="rounded-3xl p-6 sm:p-7 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-emerald-100 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>Manajemen Perangkat & Akun Login</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Pengaturan HP & Akun</h1>
                    <p class="text-emerald-100/90 text-sm leading-relaxed">
                        Pilih HP di bawah untuk melihat atau mengganti akun email aktifnya. Pekerja cukup lapor menggunakan nama HP tanpa perlu tahu email akun.
                    </p>
                </div>
            </div>

            <!-- MAIN SPLIT FLEX LAYOUT -->
            <div class="flex flex-col lg:flex-row gap-6 w-full items-start">

                <!-- LEFT COLUMN: PHONE LIST (FIXED WIDTH ON DESKTOP) -->
                <div class="w-full lg:w-80 lg:shrink-0 space-y-3">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Daftar HP Studio</h2>
                        <span class="text-xs font-semibold text-slate-500" x-text="phones.length + ' Perangkat'"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2.5">
                        <template x-for="p in phones" :key="p.key">
                            <button type="button" 
                                    @click="selectPhone(p.key)"
                                    class="w-full text-left p-3 sm:p-4 rounded-2xl border transition-all flex items-center justify-between gap-3 shadow-2xs group"
                                    :class="selectedPhoneKey === p.key ? 'bg-white border-[#00B090] ring-2 ring-[#00B090]/20 shadow-sm' : 'bg-white/80 hover:bg-white border-slate-200/90 hover:border-slate-300'">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition"
                                         :class="selectedPhoneKey === p.key ? 'bg-emerald-50 text-[#009B7E]' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200/70'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                            <span x-text="'HP ' + p.label"></span>
                                        </div>
                                        <div class="text-xs truncate mt-0.5" 
                                             :class="p.active_email ? 'text-emerald-700 font-semibold' : 'text-slate-400 font-normal'"
                                             x-text="p.active_email || 'Belum ada akun aktif'">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="shrink-0 flex items-center">
                                    <span x-show="selectedPhoneKey === p.key" class="w-2.5 h-2.5 rounded-full bg-[#00B090]"></span>
                                    <span x-show="selectedPhoneKey !== p.key" class="text-slate-300 group-hover:text-slate-400 text-lg">›</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- RIGHT COLUMN: SELECTED PHONE DETAILS & ACCOUNT MANAGER (EXPANDS TO FILL REMAINING SPACE) -->
                <div class="w-full flex-1 min-w-0 space-y-6">

                    <!-- HERO: ACTIVE ACCOUNT ON SELECTED PHONE -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 text-[#009B7E]">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </span>
                                    <h3 class="text-lg font-bold text-slate-900" x-text="'Akun Aktif — HP ' + (selectedPhone?.label || '')"></h3>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Akun yang saat ini dipakai untuk rekaman di perangkat ini.</p>
                            </div>

                            <template x-if="currentActiveAccount">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0 self-start sm:self-auto">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Aktif Digunakan</span>
                                </span>
                            </template>
                        </div>

                        <div class="pt-5">
                            <template x-if="currentActiveAccount">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-emerald-50/60 border border-emerald-100 rounded-xl p-4">
                                    <div class="space-y-1">
                                        <div class="text-base font-extrabold text-slate-900 font-mono" x-text="currentActiveAccount.email"></div>
                                        <div class="text-xs text-slate-500 flex items-center gap-3">
                                            <span x-show="currentActiveAccount.account_name" x-text="'Catatan: ' + currentActiveAccount.account_name"></span>
                                            <span x-text="'Diaktifkan: ' + formatDate(currentActiveAccount.activated_at)"></span>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            @click="openEditModal(currentActiveAccount)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 bg-white hover:bg-emerald-50 text-[#009B7E] font-bold text-xs transition shadow-2xs shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        <span>Edit Akun</span>
                                    </button>
                                </div>
                            </template>

                            <template x-if="!currentActiveAccount">
                                <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-4 text-center space-y-1">
                                    <p class="text-sm font-bold text-amber-900">Belum ada akun yang diaktifkan untuk HP ini</p>
                                    <p class="text-xs text-amber-700">Daftarkan email baru atau pilih dari daftar riwayat akun di bawah.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- FORM: REGISTER & ACTIVATE NEW ACCOUNT -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Ganti ke Akun / Email Baru</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Daftarkan email yang baru login di HP ini. Akun lama otomatis nonaktif dan akun baru langsung aktif.</p>
                        </div>

                        <form @submit.prevent="registerAccount()" class="space-y-3.5">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email Login HP *</label>
                                    <input type="email" 
                                           x-model="formEmail" 
                                           required 
                                           placeholder="contoh: akun.streamer1@gmail.com" 
                                           class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:border-[#00B090] focus:ring-2 focus:ring-[#00B090]/20 bg-slate-50/50 focus:bg-white transition">
                                </div>
                                <div class="w-full sm:w-72">
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Label (Opsional)</label>
                                    <input type="text" 
                                           x-model="formName" 
                                           placeholder="misal: Akun Batch 3" 
                                           class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:border-[#00B090] focus:ring-2 focus:ring-[#00B090]/20 bg-slate-50/50 focus:bg-white transition">
                                </div>
                            </div>

                            <div class="flex justify-end pt-1">
                                <button type="submit" 
                                        :disabled="isSubmitting || !formEmail"
                                        class="h-11 px-5 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white text-xs sm:text-sm font-bold transition shadow-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="isSubmitting ? 'Mendaftarkan...' : 'Daftarkan & Jadikan Aktif'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TABLE: PREVIOUSLY REGISTERED ACCOUNTS POOL -->
                    <div class="bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-xs">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900" x-text="'Daftar Akun ' + (selectedPhone?.label || '')"></h3>
                                <p class="text-xs text-slate-400 mt-0.5">Semua akun yang pernah terdaftar di HP ini. Klik "Aktifkan" untuk switch tanpa ketik ulang.</p>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600" x-text="currentPhoneAccounts.length + ' Akun'"></span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200/70 text-slate-400 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 pl-6 pr-3">Alamat Email</th>
                                        <th class="px-3 py-3">Catatan</th>
                                        <th class="px-3 py-3">Status</th>
                                        <th class="px-3 py-3">Terakhir Aktif</th>
                                        <th class="py-3 pl-3 pr-6 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    <template x-if="currentPhoneAccounts.length === 0">
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-slate-400">
                                                Belum ada akun yang terdaftar pada HP ini.
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="acc in currentPhoneAccounts" :key="acc.id">
                                        <tr class="hover:bg-slate-50/70 transition" :class="acc.is_active ? 'bg-emerald-50/30' : ''">
                                            <td class="py-3.5 pl-6 pr-3 font-bold font-mono text-slate-900" x-text="acc.email"></td>
                                            <td class="px-3 py-3.5 text-slate-500" x-text="acc.account_name || '-'"></td>
                                            <td class="px-3 py-3.5">
                                                <template x-if="acc.is_active">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                        <span>AKTIF</span>
                                                    </span>
                                                </template>
                                                <template x-if="!acc.is_active">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500">
                                                        Nonaktif
                                                    </span>
                                                </template>
                                            </td>
                                            <td class="px-3 py-3.5 text-slate-400 tabular-nums" x-text="formatDate(acc.activated_at)"></td>
                                            <td class="py-3.5 pl-3 pr-6 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <template x-if="!acc.is_active">
                                                        <button type="button" 
                                                                @click="activateAccount(acc)"
                                                                class="px-2.5 py-1 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-[#009B7E] font-bold text-xs transition shadow-2xs">
                                                            Jadikan Aktif
                                                        </button>
                                                    </template>
                                                    <template x-if="acc.is_active">
                                                        <span class="text-xs text-slate-400 font-semibold px-2 py-1">
                                                            Sedang Aktif
                                                        </span>
                                                    </template>

                                                    <button type="button" 
                                                            @click="openEditModal(acc)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition shadow-2xs hover:border-slate-300"
                                                            title="Edit detail email / catatan">
                                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                        <span>Edit</span>
                                                    </button>

                                                    <template x-if="!acc.is_active">
                                                        <button type="button" 
                                                                @click="deleteAccount(acc)"
                                                                class="p-1 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                                                                title="Hapus dari daftar HP ini">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- EDIT ACCOUNT POPUP MODAL -->
        <div x-show="showEditModal" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true">
            
            <!-- Backdrop -->
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeEditModal()"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

            <!-- Modal Box -->
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div x-show="showEditModal"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                     @click.stop
                     class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all border border-slate-100">
                    
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#009B7E] flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Edit Detail Akun HP</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Perbarui email atau catatan akun pada perangkat ini.</p>
                            </div>
                        </div>
                        <button type="button" @click="closeEditModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveEditAccount()">
                        <div class="px-6 py-5 space-y-4">
                            <!-- Device info -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Perangkat HP</label>
                                <div class="flex items-center gap-2 h-11 px-3.5 rounded-xl bg-slate-100/80 border border-slate-200 text-slate-800 text-sm font-semibold">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    <span x-text="'HP ' + getPhoneLabel(editForm.phone_key)"></span>
                                    <template x-if="editForm.is_active">
                                        <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Sedang Aktif
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email Login *</label>
                                <input type="email" 
                                       x-model="editForm.email" 
                                       required 
                                       placeholder="contoh: akun.streamer1@gmail.com"
                                       class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-sm font-mono font-medium focus:outline-none focus:border-[#00B090] focus:ring-2 focus:ring-[#00B090]/20 bg-slate-50/50 focus:bg-white transition">
                            </div>

                            <!-- Catatan / Label -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Label (Opsional)</label>
                                <input type="text" 
                                       x-model="editForm.account_name" 
                                       placeholder="misal: 3 Nov, Akun Batch 3"
                                       class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:border-[#00B090] focus:ring-2 focus:ring-[#00B090]/20 bg-slate-50/50 focus:bg-white transition">
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 rounded-b-2xl">
                            <button type="button" 
                                    @click="closeEditModal()" 
                                    class="h-10 px-4 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition">
                                Batal
                            </button>
                            <button type="submit" 
                                    :disabled="isSavingEdit || !editForm.email"
                                    class="h-10 px-5 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white text-xs font-bold transition shadow-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="isSavingEdit" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span x-text="isSavingEdit ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function devicesManagerData() {
            return {
                sidebarOpen: window.innerWidth >= 768 && (localStorage.getItem('aiku_sidebar_open') !== 'false'),
                phones: [],
                accounts: [],
                selectedPhoneKey: 'putih',
                formEmail: '',
                formName: '',
                isLoading: false,
                isSubmitting: false,
                showEditModal: false,
                isSavingEdit: false,
                editForm: {
                    id: null,
                    phone_key: '',
                    email: '',
                    account_name: '',
                    is_active: false
                },

                async init() {
                    await this.fetchDevices();
                },

                get selectedPhone() {
                    return this.phones.find(p => p.key === this.selectedPhoneKey) || null;
                },

                get currentPhoneAccounts() {
                    return this.accounts.filter(a => a.phone_key === this.selectedPhoneKey);
                },

                get currentActiveAccount() {
                    return this.currentPhoneAccounts.find(a => a.is_active) || null;
                },

                selectPhone(key) {
                    this.selectedPhoneKey = key;
                    this.formEmail = '';
                    this.formName = '';
                },

                async fetchDevices() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('/api/devices', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) throw new Error('Gagal memuat data perangkat.');
                        const data = await res.json();
                        this.phones = data.phones || [];
                        this.accounts = data.accounts || [];

                        if (this.phones.length && !this.phones.some(p => p.key === this.selectedPhoneKey)) {
                            this.selectedPhoneKey = this.phones[0].key;
                        }
                    } catch (error) {
                        console.error('Error fetching devices:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: error.message || 'Terjadi kesalahan saat memuat data perangkat.'
                        });
                    } finally {
                        this.isLoading = false;
                    }
                },

                async registerAccount() {
                    if (!this.formEmail) return;
                    this.isSubmitting = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch('/api/devices/accounts', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                phone_key: this.selectedPhoneKey,
                                email: this.formEmail,
                                account_name: this.formName
                            })
                        });

                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || 'Gagal mendaftarkan akun.');
                        }

                        this.formEmail = '';
                        this.formName = '';
                        await this.fetchDevices();

                        Swal.fire({
                            icon: 'success',
                            title: 'Akun Diaktifkan!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: error.message
                        });
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async activateAccount(acc) {
                    const confirm = await Swal.fire({
                        title: 'Jadikan Akun Aktif?',
                        text: `Aktifkan akun ${acc.email} untuk HP ${this.selectedPhone?.label}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Aktifkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#00B090'
                    });

                    if (!confirm.isConfirmed) return;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch(`/api/devices/accounts/${acc.id}/activate`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });

                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal mengaktifkan akun.');

                        await this.fetchDevices();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Diaktifkan',
                            text: data.message,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message
                        });
                    }
                },

                async deleteAccount(acc) {
                    const confirm = await Swal.fire({
                        title: 'Hapus dari Daftar HP?',
                        text: `Hapus akun ${acc.email} dari daftar HP ${this.selectedPhone?.label}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#e11d48'
                    });

                    if (!confirm.isConfirmed) return;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch(`/api/devices/accounts/${acc.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });

                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal menghapus akun.');

                        await this.fetchDevices();

                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message
                        });
                    }
                },

                getPhoneLabel(key) {
                    const p = this.phones.find(x => x.key === key);
                    return p ? p.label : (key ? key.charAt(0).toUpperCase() + key.slice(1) : '');
                },

                openEditModal(acc) {
                    if (!acc) return;
                    this.editForm = {
                        id: acc.id,
                        phone_key: acc.phone_key || this.selectedPhoneKey,
                        email: acc.email,
                        account_name: acc.account_name || '',
                        is_active: !!acc.is_active
                    };
                    this.showEditModal = true;
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editForm = {
                        id: null,
                        phone_key: '',
                        email: '',
                        account_name: '',
                        is_active: false
                    };
                },

                async saveEditAccount() {
                    if (!this.editForm.id || !this.editForm.email) return;
                    this.isSavingEdit = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch(`/api/devices/accounts/${this.editForm.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                email: this.editForm.email,
                                account_name: this.editForm.account_name
                            })
                        });

                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Gagal memperbarui detail akun.'));
                        }

                        this.closeEditModal();
                        await this.fetchDevices();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Diperbarui',
                            text: data.message || 'Perubahan akun berhasil disimpan.',
                            timer: 1800,
                            showConfirmButton: false
                        });
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: error.message
                        });
                    } finally {
                        this.isSavingEdit = false;
                    }
                },

                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    const d = new Date(dateStr);
                    if (isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            };
        }
    </script>
</body>
</html>
