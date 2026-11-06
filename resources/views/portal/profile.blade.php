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
        <a href="/me/rekaman" class="rounded-[28px] bg-white p-5 border border-slate-100 shadow-2xs hover:border-emerald-300 transition block">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Riwayat Rekaman</span>
            <span class="text-lg font-semibold text-emerald-700 block mt-1">Rekaman milikmu</span>
            <p class="text-[11px] text-emerald-600 font-semibold mt-1">Lihat task dan durasi yang sudah ditandai ↗</p>
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
            <a href="/me/rekaman" class="p-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="text-xs font-bold text-slate-800">Riwayat Rekaman</span>
                </div>
                <span class="text-xs text-slate-400">→</span>
            </a>
        </div>
    </section>

    <!-- Security / Change Password Section -->
    <section class="rounded-[28px] bg-white p-5 sm:p-6 border border-slate-100 shadow-2xs space-y-4"
             x-data="{
                 open: false,
                 isSubmitting: false,
                 currentPassword: '',
                 newPassword: '',
                 newPasswordConfirmation: '',
                 showCurrent: false,
                 showNew: false,
                 showConfirm: false,
                 errorMessage: '',
                 successMessage: '',
                 async submitPassword() {
                     this.errorMessage = '';
                     this.successMessage = '';
                     
                     if (!this.currentPassword) {
                         this.errorMessage = 'Kata sandi saat ini wajib diisi.';
                         return;
                     }
                     if (!this.newPassword || this.newPassword.length < 6) {
                         this.errorMessage = 'Kata sandi baru minimal 6 karakter.';
                         return;
                     }
                     if (this.newPassword !== this.newPasswordConfirmation) {
                         this.errorMessage = 'Konfirmasi kata sandi baru tidak cocok.';
                         return;
                     }

                     this.isSubmitting = true;
                     try {
                         const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.content;
                         const res = await fetch('/api/me/password', {
                             method: 'PUT',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'Accept': 'application/json',
                                 'X-CSRF-TOKEN': csrfToken
                             },
                             body: JSON.stringify({
                                 current_password: this.currentPassword,
                                 new_password: this.newPassword,
                                 new_password_confirmation: this.newPasswordConfirmation
                             })
                         });
                         
                         const data = await res.json();
                         if (res.ok && data.success) {
                             this.currentPassword = '';
                             this.newPassword = '';
                             this.newPasswordConfirmation = '';
                             this.open = false;
                             if (window.Swal) {
                                 window.Swal.fire({
                                     icon: 'success',
                                     title: 'Berhasil!',
                                     text: 'Kata sandi akun Anda berhasil diperbarui.',
                                     confirmButtonColor: '#009B7E'
                                 });
                             }
                         } else {
                             const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal memperbarui kata sandi.');
                             this.errorMessage = msg;
                         }
                     } catch (err) {
                         this.errorMessage = 'Terjadi kesalahan jaringan. Coba lagi.';
                     } finally {
                         this.isSubmitting = false;
                     }
                 }
             }">

        <!-- Header Row -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Keamanan & Kata Sandi</h4>
                    <p class="text-[11px] text-slate-400 font-medium">Ubah kata sandi untuk menjaga keamanan akun Anda</p>
                </div>
            </div>

            <button type="button" 
                    @click="open = !open" 
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                    :class="open ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/80'">
                <span x-text="open ? 'Tutup' : 'Ganti Password'"></span>
                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>

        <!-- Collapsible Form -->
        <div x-show="open" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="pt-3 border-t border-slate-100 space-y-3.5">

            <!-- Error Notification -->
            <div x-show="errorMessage" x-cloak class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-start gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span x-text="errorMessage"></span>
            </div>

            <!-- Field 1: Kata Sandi Saat Ini -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kata Sandi Saat Ini</label>
                <div class="relative flex items-center w-full rounded-2xl border border-slate-200 bg-slate-50/50 transition focus-within:border-emerald-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/10">
                    <input :type="showCurrent ? 'text' : 'password'" 
                           x-model="currentPassword" 
                           placeholder="Masukkan kata sandi lama" 
                           class="w-full h-11 px-4 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none">
                    <button type="button" @click="showCurrent = !showCurrent" class="px-3.5 text-slate-400 hover:text-slate-600 transition">
                        <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="showCurrent" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
            </div>

            <!-- Field 2: Kata Sandi Baru -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kata Sandi Baru (Min. 6 Karakter)</label>
                <div class="relative flex items-center w-full rounded-2xl border border-slate-200 bg-slate-50/50 transition focus-within:border-emerald-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/10">
                    <input :type="showNew ? 'text' : 'password'" 
                           x-model="newPassword" 
                           placeholder="Masukkan kata sandi baru" 
                           class="w-full h-11 px-4 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none">
                    <button type="button" @click="showNew = !showNew" class="px-3.5 text-slate-400 hover:text-slate-600 transition">
                        <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
            </div>

            <!-- Field 3: Konfirmasi Kata Sandi Baru -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Konfirmasi Kata Sandi Baru</label>
                <div class="relative flex items-center w-full rounded-2xl border border-slate-200 bg-slate-50/50 transition focus-within:border-emerald-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/10">
                    <input :type="showConfirm ? 'text' : 'password'" 
                           x-model="newPasswordConfirmation" 
                           placeholder="Ulangi kata sandi baru" 
                           class="w-full h-11 px-4 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none">
                    <button type="button" @click="showConfirm = !showConfirm" class="px-3.5 text-slate-400 hover:text-slate-600 transition">
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center gap-2 pt-2">
                <button type="button" 
                        @click="open = false; errorMessage = '';" 
                        class="px-4 h-11 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="button" 
                        @click="submitPassword()" 
                        :disabled="isSubmitting"
                        class="flex-1 h-11 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-bold text-xs transition shadow-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span x-show="!isSubmitting">Simpan Kata Sandi Baru</span>
                    <span x-show="isSubmitting" class="flex items-center gap-1.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan...</span>
                    </span>
                </button>
            </div>

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
