@extends('portal.layout', ['currentPage' => 'rekaman', 'pageTitle' => 'Riwayat Rekaman'])

@section('content')
<div x-data="myRecordings" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h1 class="text-xl font-bold text-slate-900">Riwayat rekaman</h1><p class="mt-1 text-sm text-slate-500">Rekaman yang sudah ditandai admin sebagai milikmu.</p></div>
        <a href="/me/slip" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-600">Arsip slip lama ↗</a>
    </div>
    <section class="rounded-3xl border border-slate-100 bg-white p-5 space-y-4">
        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-3 sm:grid-cols-[180px_minmax(0,1fr)_auto]">
            <label class="block min-w-0 text-xs font-semibold text-slate-600">Bulan rekaman<input type="month" x-model="month" @change="load()" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm"></label>
            <label class="col-span-2 row-start-2 block min-w-0 text-xs font-semibold text-slate-600 sm:col-span-1 sm:row-start-auto">Cari task atau ID<input type="search" x-model="search" @input.debounce.300ms="load()" maxlength="100" placeholder="Contoh: bungkus kado" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-200 px-3 text-base"></label>
            <button type="button" @click="load(false)" :disabled="loading" class="col-start-2 row-start-1 min-h-11 rounded-xl border border-slate-200 px-3 text-xs font-semibold disabled:opacity-40 sm:col-start-auto sm:row-start-auto">Muat ulang</button>
        </div>
        <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><div><p class="text-xs text-slate-500">Rekaman ditemukan</p><p class="mt-1 text-xl font-bold text-slate-900" x-text="summary.total_recordings"></p></div><div><p class="text-xs text-slate-500">Total durasi</p><p class="mt-1 text-lg font-bold text-emerald-800" x-text="minutes(summary.total_minutes)"></p></div></div>
    </section>
    <p x-show="error" x-text="error" role="alert" class="rounded-2xl bg-red-50 p-4 text-sm text-red-800"></p>
    <p x-show="loading" role="status" class="py-5 text-center text-sm text-slate-500">Memuat riwayat rekaman…</p>
    <div x-show="!loading && !error && !rows.length" class="rounded-3xl border border-slate-100 bg-white p-8 text-center"><h2 class="font-semibold text-slate-800">Belum ada rekaman pada pilihan ini</h2><p class="mt-2 text-sm text-slate-500">Rekaman muncul setelah admin menandai pemiliknya. Coba pilih bulan lain atau ubah pencarian.</p></div>
    <div x-show="!loading && !error" class="space-y-5">
        <template x-for="group in groups" :key="group.date">
            <section class="overflow-hidden rounded-3xl border border-slate-100 bg-white">
                <h2 class="border-b border-slate-100 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-600" x-text="day(group.date)"></h2>
                <div class="divide-y divide-slate-100"><template x-for="row in group.rows" :key="row.record_id">
                    <article class="flex flex-wrap items-start justify-between gap-3 p-5">
                        <div class="min-w-0 flex-1"><h3 class="break-words text-sm font-bold text-slate-900" x-text="row.task_label || row.task_title || 'Rekaman'"></h3><p class="mt-1 text-xs text-slate-500" x-text="'Waktu Atlas · ' + at(row.recorded_at)"></p><p class="mt-2 break-all font-mono text-xs text-slate-400" x-text="'ID: ' + row.record_id"></p></div>
                        <div class="text-right"><p class="text-sm font-bold text-emerald-800" x-text="minutes(row.duration_minutes)"></p><span class="mt-2 inline-block rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-600" x-text="status(row.processing_status)"></span></div>
                    </article>
                </template></div>
            </section>
        </template>
        <div x-show="rows.length" class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500"><span x-text="'Halaman ' + page + ' / ' + lastPage"></span><div class="flex gap-2"><button type="button" @click="page--; load(false)" :disabled="page <= 1" class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 disabled:opacity-40">Sebelumnya</button><button type="button" @click="page++; load(false)" :disabled="page >= lastPage" class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 disabled:opacity-40">Berikutnya</button></div></div>
    </div>
</div>
@endsection
