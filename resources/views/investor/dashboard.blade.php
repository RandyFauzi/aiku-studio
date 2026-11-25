<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Investor — AIKu Studio</title>
    <meta name="theme-color" content="#F4F6F8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body class="bg-[#F4F6F8] text-slate-800 min-h-screen font-sans antialiased" x-data="investorPortalPage()" x-cloak>

    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white font-black text-sm flex items-center justify-center shrink-0">AI</div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-sm sm:text-base text-slate-900 truncate">AIKu Studio</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">Portal Investor</span>
                    </div>
                    <p class="text-[11px] text-slate-500 truncate" x-text="investor ? (investor.name + (investor.code ? ' · ' + investor.code : '')) : 'Memuat profil...'"></p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="passwordModal = true" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition cursor-pointer">
                    Ganti Sandi
                </button>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
        <!-- Title & Period Picker -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Laporan Aset & Bagi Hasil</p>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Ringkasan Profit Unit HP</h1>
                <p class="text-xs text-slate-500 mt-1">Data jam billable (Approved) dan estimasi keuntungan dihitung otomatis dari rekaman studio.</p>
            </div>

            <div class="flex items-center gap-2">
                <select x-model="selectedPeriodId" @change="loadDashboard()" class="h-10 px-3.5 rounded-xl border border-slate-200 bg-white text-xs sm:text-sm font-bold text-slate-800 shadow-2xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <template x-for="p in periods" :key="p.id">
                        <option :value="p.id" x-text="p.name + (p.isCurrent ? ' (Berjalan)' : '')"></option>
                    </template>
                </select>
                <button type="button" @click="loadDashboard()" :disabled="loading" class="h-10 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 shadow-2xs transition cursor-pointer">
                    <span x-text="loading ? 'Memuat...' : 'Segarkan'"></span>
                </button>
            </div>
        </div>

        <template x-if="error">
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold" x-text="error"></div>
        </template>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Keuntungan Siklus Ini</p>
                <p class="text-2xl font-black text-amber-700 mt-1" x-text="formatRupiah(summary?.profit_idr || 0)"></p>
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                          :class="summary?.payout_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                          x-text="summary?.payout_status === 'paid' ? '✓ Sudah Dibayar' : '⏳ Belum Dibayar'"></span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Jam Billable</p>
                <p class="text-2xl font-black text-emerald-700 mt-1" x-text="(summary?.billable_hours ?? 0) + ' jam'"></p>
                <p class="text-xs text-slate-500 mt-2" x-text="(summary?.tasks ?? 0) + ' task · Approval ' + (summary?.approval_rate ?? 0) + '%'"></p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Unit HP Terdaftar</p>
                <p class="text-2xl font-black text-slate-900 mt-1" x-text="(summary?.device_count ?? 0) + ' unit'"></p>
                <p class="text-xs text-slate-500 mt-2" x-text="(summary?.logged_hours ?? 0) + ' jam total terekam'"></p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rekening Pencairan</p>
                <p class="text-sm font-extrabold text-slate-900 mt-1.5" x-text="(investor?.bank_name || '-') + ' ' + (investor?.no_rek || '')"></p>
                <p class="text-xs text-slate-500 mt-1" x-text="investor?.account_holder ? 'a.n. ' + investor.account_holder : 'Belum dilengkapi admin'"></p>
            </div>
        </div>

        <!-- Contracts / Devices Table -->
        <section class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900">Performa Per Unit HP</h2>
                    <p class="text-xs text-slate-500">Rincian jam billable dan bagi hasil per kode unit HP milik Anda.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-5">Kode HP</th>
                            <th class="py-3 px-4">Perangkat</th>
                            <th class="py-3 px-4 text-right">Tarif / Jam</th>
                            <th class="py-3 px-4 text-right">Jam Billable</th>
                            <th class="py-3 px-4 text-right">Utilisasi</th>
                            <th class="py-3 px-5 text-right">Keuntungan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-if="!summary?.contracts?.length">
                            <tr>
                                <td colspan="6" class="py-8 px-5 text-center text-slate-400">Belum ada unit HP yang terhubung ke akun Anda.</td>
                            </tr>
                        </template>
                        <template x-for="c in (summary?.contracts || [])" :key="c.id">
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-900 text-white font-mono font-bold text-[11px]" x-text="c.device_code"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800" x-text="'HP ' + c.device_label"></div>
                                    <div class="text-[11px] text-slate-400" x-text="c.is_active ? 'Kontrak Aktif' : 'Kontrak Nonaktif'"></div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-700" x-text="formatRupiah(c.rate_per_hour_idr)"></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="font-extrabold text-emerald-700" x-text="c.billable_hours + ' jam'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="c.tasks + ' task · ' + c.approval_rate + '%'"></div>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-600">
                                    <div x-text="c.active_days + ' hari'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'~' + c.avg_daily_hours + 'j/hari'"></div>
                                </td>
                                <td class="py-3.5 px-5 text-right font-black text-amber-700 text-sm" x-text="formatRupiah(c.profit_idr)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Daily Trend & Payout History -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900">Rincian Harian (WITA)</h2>
                    <p class="text-xs text-slate-500">Aktivitas harian unit HP Anda pada siklus terpilih.</p>
                </div>
                <div class="p-5 max-h-80 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 uppercase text-[11px]">
                                <th class="py-2 pr-3">Tanggal</th>
                                <th class="py-2 pr-3 text-right">Task</th>
                                <th class="py-2 pr-3 text-right">Billable</th>
                                <th class="py-2 text-right">Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="!summary?.daily_series?.length">
                                <tr><td colspan="4" class="py-8 text-center text-slate-400">Belum ada rekaman billable pada siklus ini.</td></tr>
                            </template>
                            <template x-for="d in (summary?.daily_series || [])" :key="d.date">
                                <tr>
                                    <td class="py-2.5 pr-3 font-mono font-semibold text-slate-800" x-text="d.date"></td>
                                    <td class="py-2.5 pr-3 text-right text-slate-600" x-text="d.tasks"></td>
                                    <td class="py-2.5 pr-3 text-right font-bold text-emerald-700" x-text="d.billable_hours + ' jam'"></td>
                                    <td class="py-2.5 text-right font-extrabold text-amber-700" x-text="formatRupiah(d.profit_idr)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900">Riwayat Pencairan Bagi Hasil</h2>
                    <p class="text-xs text-slate-500">Catatan status pembayaran dari admin studio per siklus.</p>
                </div>
                <div class="p-5 max-h-80 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 uppercase text-[11px]">
                                <th class="py-2 pr-3">Periode</th>
                                <th class="py-2 pr-3 text-right">Billable</th>
                                <th class="py-2 pr-3 text-right">Nominal</th>
                                <th class="py-2 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="!payoutHistory.length">
                                <tr><td colspan="4" class="py-8 text-center text-slate-400">Belum ada riwayat pencairan tercatat.</td></tr>
                            </template>
                            <template x-for="p in payoutHistory" :key="p.id">
                                <tr>
                                    <td class="py-2.5 pr-3 font-semibold text-slate-800" x-text="p.period_name"></td>
                                    <td class="py-2.5 pr-3 text-right text-slate-600" x-text="p.total_billable_hours + ' jam'"></td>
                                    <td class="py-2.5 pr-3 text-right font-bold text-slate-900" x-text="formatRupiah(p.total_amount_idr)"></td>
                                    <td class="py-2.5 text-right">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              :class="p.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                              x-text="p.status === 'paid' ? 'Dibayar' : 'Pending'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal Ganti Password -->
    <div x-show="passwordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
        <div @click.outside="passwordModal = false" class="bg-white rounded-2xl border border-slate-200 w-full max-w-md shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-extrabold text-slate-900">Ganti Kata Sandi</h3>
                <button type="button" @click="passwordModal = false" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <form @submit.prevent="changePassword()" class="p-6 space-y-4 text-xs">
                <template x-if="passwordMsg">
                    <div class="p-3 rounded-xl border text-xs font-semibold"
                         :class="passwordOk ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'"
                         x-text="passwordMsg"></div>
                </template>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi Saat Ini *</label>
                    <input type="password" x-model="pwForm.current_password" required class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi Baru (min. 6 karakter) *</label>
                    <input type="password" x-model="pwForm.new_password" required minlength="6" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi Baru *</label>
                    <input type="password" x-model="pwForm.new_password_confirmation" required minlength="6" class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="passwordModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold cursor-pointer">Batal</button>
                    <button type="submit" :disabled="savingPw" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold disabled:opacity-50 cursor-pointer" x-text="savingPw ? 'Menyimpan...' : 'Simpan Sandi'"></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function investorPortalPage() {
            return {
                loading: true,
                error: '',
                investor: null,
                periods: [],
                selectedPeriodId: '',
                summary: null,
                payoutHistory: [],
                passwordModal: false,
                savingPw: false,
                passwordMsg: '',
                passwordOk: false,
                pwForm: { current_password: '', new_password: '', new_password_confirmation: '' },

                async init() {
                    await this.loadDashboard();
                },

                formatRupiah(val) {
                    const num = Number(val || 0);
                    return 'Rp ' + num.toLocaleString('id-ID');
                },

                async loadDashboard() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const qs = this.selectedPeriodId ? `?periodId=${encodeURIComponent(this.selectedPeriodId)}` : '';
                        const res = await fetch('/api/investor/dashboard' + qs, { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Gagal memuat dashboard investor.');
                        this.investor = data.investor;
                        this.periods = data.periods || [];
                        this.selectedPeriodId = data.selected_period?.id || this.selectedPeriodId;
                        this.summary = data.summary || null;
                        this.payoutHistory = data.payout_history || [];
                    } catch (e) {
                        this.error = e.message || 'Terjadi kesalahan memuat data.';
                    } finally {
                        this.loading = false;
                    }
                },

                async changePassword() {
                    this.savingPw = true;
                    this.passwordMsg = '';
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch('/api/investor/password', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify(this.pwForm),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            const firstErr = data.errors ? Object.values(data.errors).flat()[0] : null;
                            throw new Error(firstErr || data.message || 'Gagal mengganti kata sandi.');
                        }
                        this.passwordOk = true;
                        this.passwordMsg = data.message || 'Kata sandi berhasil diperbarui.';
                        this.pwForm = { current_password: '', new_password: '', new_password_confirmation: '' };
                    } catch (e) {
                        this.passwordOk = false;
                        this.passwordMsg = e.message;
                    } finally {
                        this.savingPw = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
