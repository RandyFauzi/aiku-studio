@extends('portal.layout', ['currentPage' => 'rekaman', 'pageTitle' => 'Arsip Slip Lama'])

@section('content')
<div x-data="workerSlipArchive" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h1 class="text-xl font-bold text-slate-900">Arsip slip lama</h1><p class="mt-1 text-sm text-slate-500">Slip final milikmu dari periode gaji yang sudah lewat.</p></div><a href="/me/rekaman" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold">← Riwayat rekaman</a></div>
    <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"></p>
    <p x-show="loading" role="status" class="py-6 text-center text-sm text-slate-500">Memuat arsip…</p>
    <p x-show="!loading && !rows.length && !error" class="rounded-3xl border border-slate-100 bg-white p-8 text-center text-sm text-slate-500">Belum ada slip final dari periode sebelumnya.</p>
    <div x-show="!loading" class="space-y-3"><template x-for="slip in rows" :key="slip.id"><article class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-100 bg-white p-5"><div><h2 class="text-sm font-bold text-slate-900" x-text="slip.period"></h2><p class="mt-2 text-lg font-semibold text-emerald-800" x-text="amount(slip.total_thp)"></p><p class="mt-1 text-xs text-slate-500">Slip final · arsip periode sebelumnya</p></div><div class="flex gap-2"><a :href="'/slip/' + slip.id" class="min-h-11 rounded-xl border border-slate-200 px-4 py-3 text-xs font-semibold">Lihat slip</a><button type="button" @click="download(slip)" :disabled="downloading !== null" class="min-h-11 rounded-xl bg-emerald-800 px-4 text-xs font-bold text-white disabled:opacity-40" x-text="downloading === slip.id ? 'Mengunduh…' : 'Unduh PDF'"></button></div></article></template></div>
</div>
@endsection
