@extends('portal.layout', ['currentPage' => 'rekaman', 'pageTitle' => 'Arsip Slip Lama'])

@section('content')
<div x-data="workerSlipArchive" class="space-y-6">
    <header>
        <a href="/me/rekaman" class="mb-3 inline-flex min-h-11 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 hover:text-emerald-800"><span class="rotate-180">@include('portal.icon', ['name' => 'arrow'])</span> Riwayat rekaman</a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Arsip slip lama</h1>
        <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-600">Slip final milikmu dari periode gaji yang sudah lewat.</p>
    </header>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-medium text-slate-600" x-text="loading ? 'Memuat dokumen…' : rows.length + ' dokumen tersimpan'"></p>
        <button type="button" @click="load()" :disabled="loading || downloading !== null" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">Muat ulang</button>
    </div>
    <div x-show="error" x-cloak class="rounded-2xl border border-red-100 bg-red-50 p-4"><p x-text="error" role="alert" class="text-sm text-red-800"></p><p class="mt-2 text-xs text-red-800">Silakan coba lagi. Jika masih terkendala, hubungi admin.</p></div>
    <p x-show="loading" role="status" class="rounded-3xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-600">Memuat arsip slip…</p>
    <div x-show="!loading && !rows.length && !error" x-cloak class="rounded-3xl border border-slate-200 bg-white px-5 py-10 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">@include('portal.icon', ['name' => 'report'])</div>
        <h2 class="font-semibold text-slate-900">Belum ada arsip slip</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Slip final dari periode sebelumnya akan tersimpan di sini.</p>
    </div>
    <div x-show="!loading" x-cloak class="space-y-4">
        <template x-for="slip in rows" :key="slip.id">
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">@include('portal.icon', ['name' => 'report'])</span>
                    <div class="min-w-0"><p class="mb-1 text-xs text-slate-500">Periode gaji</p><h2 class="break-words text-sm font-bold leading-relaxed text-slate-900" x-text="slip.period"></h2></div>
                </div>
                <div class="p-4 sm:flex sm:items-center sm:justify-between sm:gap-6 sm:p-5">
                    <div>
                        <div class="flex items-center justify-between gap-3 sm:justify-start"><p class="text-xs text-slate-600">Total pada slip</p><span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800">@include('portal.icon', ['name' => 'check']) Final</span></div>
                        <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums text-slate-900" x-text="amount(slip.total_thp)"></p>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:mt-0 sm:shrink-0">
                        <a :href="'/slip/' + slip.id" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-800 px-4 text-sm font-semibold text-white hover:bg-emerald-900">Lihat slip</a>
                        <button type="button" @click="download(slip)" :disabled="downloading !== null" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" x-text="downloading === slip.id ? 'Mengunduh…' : 'Unduh PDF'"></button>
                    </div>
                </div>
            </article>
        </template>
    </div>
</div>
@endsection
