<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Pengaturan HP & Akun</title>
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
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="devicesManagerData()" x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN CONTENT -->
    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
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
                    <p class="text-emerald-100/90 text-sm leading-relaxed max-w-2xl">
                        Pilih HP di bawah untuk melihat atau mengganti akun email aktifnya. Pekerja cukup lapor menggunakan nama HP tanpa perlu tahu email akun.
                    </p>
                </div>

                <!-- Kode Vendor Atlas Card -->
                <div x-data="{ copied: false }" class="shrink-0">
                    <div class="bg-white/10 hover:bg-white/15 border border-white/20 backdrop-blur-xs rounded-2xl p-3.5 sm:px-4 sm:py-3 flex items-center gap-3.5 transition group shadow-xs">
                        <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center text-emerald-200 shrink-0">
                            <svg class="w-5 h-5 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold tracking-wider uppercase text-emerald-200/90">
                                Kode Vendor Atlas
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="font-mono text-base sm:text-lg font-black tracking-widest text-white select-all">QUAXTJ</span>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText('QUAXTJ'); copied = true; setTimeout(() => copied = false, 2000)" 
                                        class="p-1.5 rounded-lg bg-white/15 hover:bg-white/25 active:scale-95 text-emerald-100 hover:text-white transition cursor-pointer"
                                        :title="copied ? 'Tersalin!' : 'Salin Kode Vendor'">
                                    <template x-if="!copied">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </template>
                                    <template x-if="copied">
                                        <svg class="w-4 h-4 text-emerald-300 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                </button>
                                <span x-show="copied" x-transition.opacity class="text-[10px] font-bold text-emerald-200 bg-emerald-900/70 px-2 py-0.5 rounded-md border border-emerald-400/30">
                                    Tersalin!
                                </span>
                            </div>
                        </div>
                    </div>
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

                    <button x-show="!showAddPhone" x-ref="addPhoneButton" type="button" @click="showAddPhone = true; phoneError = ''; $nextTick(() => $refs.phoneName.focus())" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-50 transition">
                        <span aria-hidden="true">+</span> Tambah HP
                    </button>
                    <form x-show="showAddPhone" @submit.prevent="addPhone()" class="rounded-2xl border border-emerald-200 bg-white p-4 space-y-3">
                        <label for="new-phone-name" class="block text-sm font-bold text-slate-800">Nama HP</label>
                        <input id="new-phone-name" x-ref="phoneName" x-model="phoneName" :disabled="isAddingPhone" type="text" required maxlength="60" placeholder="Contoh: Merah atau Samsung A15" aria-describedby="phone-name-help phone-name-error" :aria-invalid="!!phoneError" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 outline-none">
                        <p id="phone-name-help" class="text-xs text-slate-500">Setelah disimpan, tambahkan akun email untuk HP ini.</p>
                        <p id="phone-name-error" x-show="phoneError" x-text="phoneError" role="alert" class="text-sm text-rose-700"></p>
                        <div class="flex gap-2">
                            <button type="submit" :disabled="isAddingPhone || !phoneName.trim()" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white disabled:opacity-50" x-text="isAddingPhone ? 'Menyimpan...' : 'Simpan HP'"></button>
                            <button type="button" :disabled="isAddingPhone" @click="showAddPhone = false; phoneName = ''; phoneError = ''; $nextTick(() => $refs.addPhoneButton.focus())" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600">Batal</button>
                        </div>
                    </form>
                    <p x-show="phoneSuccess" x-text="phoneSuccess" role="status" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800"></p>

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

                            <div x-show="kamerakita.owner_access" x-cloak class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="createKamerakitaPartner" @change="onToggleCreatePartner()" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>
                                        <span class="block text-sm font-bold text-slate-800">Buat akun Worker KameraKita juga</span>
                                        <span class="block mt-0.5 text-xs text-slate-600">Privat untuk Studio ini. Email yang sama didaftarkan sebagai worker di bawah vendor yang dipilih.</span>
                                    </span>
                                </label>
                                <div x-show="createKamerakitaPartner" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Vendor Pengampu *</label>
                                        <select x-model="partnerParentId" @change="onParentChange()" :required="createKamerakitaPartner" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none">
                                            <template x-for="parent in kamerakita.parents || []" :key="parent.id">
                                                <option :value="parent.id" x-text="parent.full_name + ' (' + parent.mitra_id + ')' "></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp worker *</label>
                                        <input type="tel" x-model="partnerWhatsapp" maxlength="20" :required="createKamerakitaPartner" placeholder="08xxxxxxxxxx" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none">
                                    </div>
                                    <p class="sm:col-span-2 text-[11px] text-slate-500 italic">Nama worker KameraKita akan otomatis mengikuti isi Catatan / Label di atas.</p>
                                    <p x-show="!kamerakita.configured" class="sm:col-span-2 text-xs font-semibold text-amber-800">Koneksi server belum disiapkan. Minta admin memasang URL dan token sesuai panduan integrasi.</p>
                                    <p x-show="kamerakita.configured && !(kamerakita.parents || []).length" class="sm:col-span-2 text-xs font-semibold text-amber-800">Daftar vendor pengampu belum dapat dimuat. Periksa token dan koneksi Kamerakita.</p>
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
                                                <div x-show="acc.kamerakita_partner_id && kamerakita.owner_access" class="mt-1">
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold" :class="acc.kamerakita_status === 'active' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700'" x-text="'KameraKita: ' + (acc.kamerakita_status || 'terhubung')"></span>
                                                </div>
                                                <div x-show="!acc.kamerakita_partner_id && kamerakita.owner_access" class="mt-1">
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-500">Belum terhubung ke KK</span>
                                                </div>
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
                                                    <button x-show="acc.kamerakita_partner_id && kamerakita.owner_access" type="button" @click="toggleKamerakitaStatus(acc)" class="px-2.5 py-1 rounded-lg border border-indigo-200 bg-white hover:bg-indigo-50 text-indigo-700 font-bold text-xs transition" x-text="acc.kamerakita_status === 'active' ? 'Nonaktifkan Worker' : 'Aktifkan Worker'"></button>
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

                                                    <button type="button" 
                                                            @click="deleteAccount(acc)"
                                                            class="p-1 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                                                            :title="acc.kamerakita_partner_id ? 'Hapus dari daftar HP dan hapus worker KameraKita' : 'Hapus dari daftar HP ini'">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
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
                                       :disabled="!!editForm.kamerakita_partner_id"
                                       placeholder="contoh: akun.streamer1@gmail.com"
                                       class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-sm font-mono font-medium focus:outline-none focus:border-[#00B090] focus:ring-2 focus:ring-[#00B090]/20 bg-slate-50/50 focus:bg-white transition">
                                <p x-show="editForm.kamerakita_partner_id" class="mt-1 text-[11px] text-slate-500">Email dikunci agar identitas akun worker tetap sama di kedua sistem.</p>
                            </div>

                            <!-- Catatan / Label -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Label (Opsional)</label>
                                <input type="text" 
                                       x-model="editForm.account_name" 
                                       placeholder="misal: 3 Nov, Akun Batch 3"
                                       class="w-full h-11 px-3.5 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:border-[#00B090] focus:ring-2 focus:ring-[#00B090]/20 bg-slate-50/50 focus:bg-white transition">
                            </div>
                            <div x-show="editForm.kamerakita_partner_id && kamerakita.owner_access" x-cloak class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 space-y-3">
                                <div class="text-xs font-bold text-indigo-800">Profil Worker KameraKita</div>
                                <p x-show="editForm.partner_loading" class="text-xs text-slate-500">Memuat profil worker…</p>
                                <div x-show="!editForm.partner_loading" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama lengkap *</label>
                                        <input type="text" x-model="editForm.partner_full_name" maxlength="255" :required="!!editForm.kamerakita_partner_id && kamerakita.owner_access" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp *</label>
                                        <input type="tel" x-model="editForm.partner_whatsapp" maxlength="20" :required="!!editForm.kamerakita_partner_id && kamerakita.owner_access" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Vendor Pengampu *</label>
                                        <select x-model="editForm.partner_parent_id" @change="onEditParentChange()" :required="!!editForm.kamerakita_partner_id && kamerakita.owner_access" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                                            <template x-for="parent in kamerakita.parents || []" :key="parent.id">
                                                <option :value="parent.id" x-text="parent.full_name + ' (' + parent.mitra_id + ')' "></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div x-show="!editForm.kamerakita_partner_id && kamerakita.owner_access" x-cloak class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" x-model="editForm.link_kamerakita" @change="onToggleEditLinkKamerakita()" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>
                                        <span class="block text-xs font-bold text-indigo-900">Tautkan ke Akun Worker KameraKita</span>
                                        <span class="block mt-0.5 text-[11px] text-slate-500">Buat atau hubungkan akun worker KameraKita untuk email ini.</span>
                                    </span>
                                </label>
                                <div x-show="editForm.link_kamerakita" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama lengkap worker *</label>
                                        <input type="text" x-model="editForm.partner_full_name" maxlength="255" :required="editForm.link_kamerakita" placeholder="Nama worker" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Vendor Pengampu *</label>
                                        <select x-model="editForm.partner_parent_id" @change="onEditParentChange()" :required="editForm.link_kamerakita" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm">
                                            <option value="">Pilih vendor pengampu</option>
                                            <template x-for="parent in kamerakita.parents || []" :key="parent.id">
                                                <option :value="parent.id" x-text="parent.full_name + ' (' + parent.mitra_id + ')' "></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp worker *</label>
                                        <input type="tel" x-model="editForm.partner_whatsapp" maxlength="20" :required="editForm.link_kamerakita" placeholder="08xxxxxxxxxx" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                                    </div>
                                </div>
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
                                    :disabled="isSavingEdit || !editForm.email || editForm.partner_loading"
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
                showAddPhone: false,
                phoneName: '',
                phoneError: '',
                phoneSuccess: '',
                isAddingPhone: false,
                accounts: [],
                selectedPhoneKey: 'putih',
                formEmail: '',
                formName: '',
                createKamerakitaPartner: false,
                partnerFullName: '',
                partnerWhatsapp: '',
                partnerParentId: '',
                kamerakita: { configured: false, owner_access: false, parents: [] },
                isLoading: false,
                isSubmitting: false,
                showEditModal: false,
                isSavingEdit: false,
                editForm: {
                    id: null,
                    phone_key: '',
                    email: '',
                    account_name: '',
                    kamerakita_partner_id: null,
                    partner_loading: false,
                    partner_full_name: '',
                    partner_whatsapp: '',
                    partner_parent_id: '',
                    link_kamerakita: false,
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
                    this.createKamerakitaPartner = false;
                    this.partnerFullName = '';
                    this.partnerWhatsapp = '';
                    this.partnerParentId = '';
                },

                async addPhone() {
                    if (this.isAddingPhone || !this.phoneName.trim()) return;
                    this.phoneError = '';
                    this.phoneSuccess = '';
                    const label = this.phoneName.trim().replace(/\s+/g, ' ').replace(/^HP\s+/i, '');
                    if (this.phones.some(p => p.label.toLocaleLowerCase() === label.toLocaleLowerCase())) {
                        this.phoneError = 'Nama HP sudah terdaftar. Pilih perangkat tersebut dari daftar.';
                        this.$refs.phoneName.focus();
                        return;
                    }
                    this.isAddingPhone = true;
                    try {
                        const res = await fetch('/api/devices/phones', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ label }) });
                        const data = await res.json();
                        if (!res.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'HP belum tersimpan.');
                        this.phones.push({ ...data.phone, active_email: null, total_accounts: 0 });
                        this.selectPhone(data.phone.key);
                        this.showAddPhone = false;
                        this.phoneName = '';
                        this.phoneSuccess = 'HP ' + data.phone.label + ' ditambahkan. Silakan daftarkan akun emailnya.';
                    } catch (error) {
                        this.phoneError = error.message || 'Gagal terhubung. Coba kembali.';
                        this.$nextTick(() => this.$refs.phoneName.focus());
                    } finally {
                        this.isAddingPhone = false;
                    }
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
                        this.kamerakita = data.kamerakita || { configured: false, parents: [] };

                        if (this.phones.length && !this.phones.some(p => p.key === this.selectedPhoneKey)) {
                            this.selectedPhoneKey = this.phones[0].key;
                        }

                        if (this.createKamerakitaPartner) {
                            this.autoSelectParent();
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

                autoSelectParent() {
                    const parents = this.kamerakita?.parents || [];
                    if (!parents.length) return;

                    // Prioritaskan akun KMK-847 (yang aktif terpakai saat ini)
                    let target = parents.find(p => 
                        (p.mitra_id && p.mitra_id.toUpperCase().includes('847'))
                    ) || parents.find(p => 
                        (p.full_name && p.full_name.toUpperCase().includes('AI KU STUDIO 2'))
                    ) || parents[0];

                    if (target) {
                        if (!this.partnerParentId) {
                            this.partnerParentId = target.id;
                        }
                        if (!this.partnerWhatsapp && target.whatsapp_number) {
                            this.partnerWhatsapp = target.whatsapp_number;
                        }
                    }
                },

                onParentChange() {
                    const parent = (this.kamerakita?.parents || []).find(p => p.id === this.partnerParentId);
                    if (parent && parent.whatsapp_number) {
                        this.partnerWhatsapp = parent.whatsapp_number;
                    }
                },

                onToggleCreatePartner() {
                    if (this.createKamerakitaPartner) {
                        this.autoSelectParent();
                    }
                },

                onEditParentChange() {
                    const parent = (this.kamerakita?.parents || []).find(p => p.id === this.editForm.partner_parent_id);
                    if (parent && parent.whatsapp_number) {
                        this.editForm.partner_whatsapp = parent.whatsapp_number;
                    }
                },

                onToggleEditLinkKamerakita() {
                    if (this.editForm.link_kamerakita) {
                        const parents = this.kamerakita?.parents || [];
                        if (parents.length && !this.editForm.partner_parent_id) {
                            let target = parents.find(p => 
                                (p.mitra_id && p.mitra_id.toUpperCase().includes('847'))
                            ) || parents.find(p => 
                                (p.full_name && p.full_name.toUpperCase().includes('AI KU STUDIO 2'))
                            ) || parents[0];

                            if (target) {
                                this.editForm.partner_parent_id = target.id;
                                if (!this.editForm.partner_whatsapp && target.whatsapp_number) {
                                    this.editForm.partner_whatsapp = target.whatsapp_number;
                                }
                            }
                        }
                        if (!this.editForm.partner_full_name && this.editForm.account_name) {
                            this.editForm.partner_full_name = this.editForm.account_name;
                        }
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
                                account_name: this.formName,
                                create_kamerakita_partner: this.createKamerakitaPartner,
                                full_name: this.formName || ('Akun ' + (this.selectedPhone?.label || 'Studio')),
                                whatsapp_number: this.partnerWhatsapp,
                                mitra_parent_id: this.partnerParentId
                            })
                        });

                        const data = await res.json();
                        if (!res.ok) {
                            if (res.status === 502 && data.account) {
                                this.formEmail = '';
                                this.formName = '';
                                this.createKamerakitaPartner = false;
                                this.partnerFullName = '';
                                this.partnerWhatsapp = '';
                                this.partnerParentId = '';
                                await this.fetchDevices();
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Tersimpan Sebagian',
                                    text: data.message
                                });
                                return;
                            }
                            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Gagal mendaftarkan akun.');
                        }

                        this.formEmail = '';
                        this.formName = '';
                        this.createKamerakitaPartner = false;
                        this.partnerFullName = '';
                        this.partnerWhatsapp = '';
                        this.partnerParentId = '';
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

                async toggleKamerakitaStatus(acc) {
                    const nextStatus = acc.kamerakita_status === 'active' ? 'suspended' : 'active';
                    const result = await Swal.fire({
                        title: nextStatus === 'active' ? 'Aktifkan Worker?' : 'Nonaktifkan Worker?',
                        text: `${acc.email} akan ${nextStatus === 'active' ? 'dapat mengakses' : 'kehilangan akses ke'} akun KameraKita.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Lanjutkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: nextStatus === 'active' ? '#4f46e5' : '#d97706'
                    });
                    if (!result.isConfirmed) return;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(`/api/devices/accounts/${acc.id}/kamerakita-status`, {
                            method: 'PATCH',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify({ status: nextStatus })
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Status worker gagal diperbarui.');
                        await this.fetchDevices();
                        Swal.fire({ icon: 'success', title: 'Status diperbarui', text: data.message, timer: 1700, showConfirmButton: false });
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Gagal memperbarui', text: error.message });
                    }
                },

                async deleteAccount(acc) {
                    let title = 'Hapus dari Daftar HP?';
                    let text = `Hapus akun ${acc.email} dari daftar HP ${this.selectedPhone?.label}?`;
                    if (acc.kamerakita_partner_id) {
                        title = 'Hapus Akun & Worker KameraKita?';
                        text = `Hapus akun ${acc.email} dari daftar HP ${this.selectedPhone?.label} dan OTOMATIS HAPUS akun worker di KameraKita?`;
                    } else if (acc.is_active) {
                        text = `Akun ${acc.email} sedang aktif di HP ${this.selectedPhone?.label}. Yakin ingin menghapusnya?`;
                    }

                    const confirm = await Swal.fire({
                        title: title,
                        text: text,
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
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: error.message
                        });
                    }
                },

                getPhoneLabel(key) {
                    const p = this.phones.find(x => x.key === key);
                    return p ? p.label : (key ? key.charAt(0).toUpperCase() + key.slice(1) : '');
                },

                async openEditModal(acc) {
                    if (!acc) return;
                    this.editForm = {
                        id: acc.id,
                        phone_key: acc.phone_key || this.selectedPhoneKey,
                        email: acc.email,
                        account_name: acc.account_name || '',
                        is_active: !!acc.is_active,
                        kamerakita_partner_id: acc.kamerakita_partner_id || null,
                        partner_loading: !!acc.kamerakita_partner_id,
                        partner_full_name: '',
                        partner_whatsapp: '',
                        partner_parent_id: '',
                        link_kamerakita: false
                    };
                    this.showEditModal = true;
                    if (acc.kamerakita_partner_id && this.kamerakita.owner_access) {
                        try {
                            const response = await fetch(`/api/devices/accounts/${acc.id}/kamerakita`, { headers: { 'Accept': 'application/json' } });
                            const payload = await response.json();
                            if (!response.ok) throw new Error(payload.message || 'Profil worker tidak dapat dimuat.');
                            Object.assign(this.editForm, {
                                partner_full_name: payload.data.full_name || '',
                                partner_whatsapp: payload.data.whatsapp_number || '',
                                partner_parent_id: payload.data.mitra_parent_id || '',
                                partner_loading: false
                            });
                        } catch (error) {
                            this.editForm.partner_loading = false;
                            Swal.fire({ icon: 'error', title: 'Gagal memuat profil worker', text: error.message });
                        }
                    }
                },

                closeEditModal() {
                    this.showEditModal = false;
                    this.editForm = {
                        id: null,
                        phone_key: '',
                        email: '',
                        account_name: '',
                        is_active: false,
                        kamerakita_partner_id: null,
                        partner_loading: false,
                        partner_full_name: '',
                        partner_whatsapp: '',
                        partner_parent_id: '',
                        link_kamerakita: false
                    };
                },

                async saveEditAccount() {
                    if (!this.editForm.id || !this.editForm.email) return;
                    this.isSavingEdit = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (this.editForm.kamerakita_partner_id && this.kamerakita.owner_access) {
                            const partnerResponse = await fetch(`/api/devices/accounts/${this.editForm.id}/kamerakita`, {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                                body: JSON.stringify({ full_name: this.editForm.partner_full_name, whatsapp_number: this.editForm.partner_whatsapp, mitra_parent_id: this.editForm.partner_parent_id })
                            });
                            const partnerPayload = await partnerResponse.json();
                            if (!partnerResponse.ok) throw new Error(partnerPayload.message || Object.values(partnerPayload.errors || {}).flat().join(' ') || 'Profil worker gagal diperbarui.');
                        } else if (!this.editForm.kamerakita_partner_id && this.editForm.link_kamerakita && this.kamerakita.owner_access) {
                            const linkResponse = await fetch(`/api/devices/accounts/${this.editForm.id}/kamerakita`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                                body: JSON.stringify({
                                    full_name: this.editForm.partner_full_name || this.editForm.account_name,
                                    whatsapp_number: this.editForm.partner_whatsapp,
                                    mitra_parent_id: this.editForm.partner_parent_id
                                })
                            });
                            const linkPayload = await linkResponse.json();
                            if (!linkResponse.ok) throw new Error(linkPayload.message || Object.values(linkPayload.errors || {}).flat().join(' ') || 'Gagal menautkan ke worker KameraKita.');
                        }
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
