@extends('portal.layout', ['currentPage' => 'slip', 'pageTitle' => 'Slip Gaji'])

@section('content')
<div class="space-y-6">

    <!-- Subheader -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Slip Gaji</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Estimasi berjalan & arsip dokumen resmi</p>
        </div>
        <button type="button" @click="loadSlips()" :disabled="loadingSlips" class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
            Segarkan
        </button>
    </div>

    <!-- LIVE CYCLE ESTIMATION CARD (Dark Hero Card) -->
    <section class="rounded-[32px] portal-dark-card text-white p-5 sm:p-7 shadow-xl space-y-4">
        <div class="flex items-center justify-between gap-2 border-b border-white/10 pb-3">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400">Periode Berjalan (Cut-Off 22 - 21)</span>
            <span class="text-xs font-bold text-slate-300" x-text="currentPeriod?.name || 'Siklus Ini'"></span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Estimasi Take Home Pay</span>
                <span class="text-2xl sm:text-3xl font-semibold text-emerald-400 font-mono tracking-tight" x-text="formatRupiah(liveSlip?.total_thp || 0)"></span>
                <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-300">
                    <span>Approved: <strong class="text-white" x-text="(liveSlip?.total_approved_hours || 0) + ' Jam'"></strong></span>
                    <span>&bull;</span>
                    <span>Logged: <strong class="text-white" x-text="(liveSlip?.total_logged_hours || 0) + ' Jam'"></strong></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="openLiveSlip()" :disabled="!liveSlip" class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold text-xs shadow-sm transition">
                    Buka Slip Live ↗
                </button>
                <button type="button" @click="downloadLiveSlipPdf()" :disabled="!liveSlip || downloadingSlipId === 'live'" class="px-3.5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition border border-white/10">
                    <span x-text="downloadingSlipId === 'live' ? 'PDF…' : 'PDF'"></span>
                </button>
            </div>
        </div>
    </section>

    <!-- OFFICIAL SLIPS ARCHIVE -->
    <section class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-900">Arsip Slip Resmi</h3>

        <div class="space-y-2.5">
            <template x-for="slip in slips" :key="slip.id">
                <div class="rounded-[24px] bg-white p-4 sm:p-5 border border-slate-100 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-emerald-200 transition">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-semibold text-slate-900" x-text="slip.period"></h4>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold"
                                  :class="slip.status === 'sudah' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                  x-text="slip.status === 'sudah' ? '✓ Sudah Ditransfer' : 'Menunggu Cair'">
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                            <span>Approved: <strong class="text-slate-800" x-text="slip.total_approved_hours + 'h'"></strong></span>
                            <span class="text-emerald-700 font-bold" x-text="formatRupiah(slip.total_thp)"></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="openSlip(slip.id)" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition">
                            Buka Slip ↗
                        </button>
                        <button type="button" @click="downloadSlipPdf(slip)" :disabled="downloadingSlipId === slip.id" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition">
                            <span x-text="downloadingSlipId === slip.id ? '…' : 'PDF'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="!loadingSlips && slips.length === 0">
                <div class="p-8 text-center rounded-[28px] bg-white border border-slate-100 shadow-2xs">
                    <p class="text-xs font-semibold text-slate-700">Belum ada slip resmi yang diterbitkan.</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Slip resmi diterbitkan setiap tanggal 22 setelah cut-off.</p>
                </div>
            </template>
        </div>
    </section>

</div>
@endsection
