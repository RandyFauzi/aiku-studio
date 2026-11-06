@extends('portal.layout', ['currentPage' => 'rekaman', 'pageTitle' => 'Riwayat Rekaman'])

@section('content')
<div x-data="myRecordings" class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Riwayat rekaman</h1>
            <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-600">Rekaman yang sudah ditandai admin sebagai milikmu.</p>
        </div>
        <a href="/me/slip" class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
            @include('portal.icon', ['name' => 'report']) Arsip slip lama @include('portal.icon', ['name' => 'arrow'])
        </a>
    </header>

    <section aria-label="Filter dan ringkasan rekaman" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-4 sm:p-5">
        <div class="grid grid-cols-1 items-end gap-3 min-[360px]:grid-cols-[minmax(0,1fr)_auto] sm:grid-cols-[180px_minmax(0,1fr)_auto]">
            <label class="block min-w-0 text-xs font-semibold text-slate-600">Bulan rekaman
                <input type="month" x-model="month" @change="load()" class="mt-2 block min-h-11 w-full min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3 text-base focus:border-emerald-700 focus:ring-emerald-700">
            </label>
            <label class="block min-w-0 min-[360px]:col-span-2 min-[360px]:row-start-2 text-xs font-semibold text-slate-600 sm:col-span-1 sm:row-start-auto">Cari task atau ID
                <input type="search" x-model="search" @input.debounce.300ms="load()" maxlength="100" placeholder="Contoh: bungkus kado" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-base focus:border-emerald-700 focus:ring-emerald-700">
            </label>
            <button type="button" @click="load(false)" :disabled="loading" class="min-h-11 min-[360px]:col-start-2 min-[360px]:row-start-1 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 sm:col-start-auto sm:row-start-auto" x-text="loading ? 'Memuat…' : 'Muat ulang'"></button>
        </div>
        <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-4" :aria-busy="loading">
            <div class="rounded-2xl bg-slate-50 p-3 sm:p-4"><p class="text-xs font-medium text-slate-600">Rekaman ditemukan</p><p class="mt-2 text-2xl font-bold tabular-nums text-slate-900" x-text="loading ? '—' : summary.total_recordings"></p></div>
            <div class="rounded-2xl bg-emerald-50 p-3 sm:p-4"><p class="text-xs font-medium text-emerald-800">Total durasi</p><p class="mt-2 text-lg font-bold tabular-nums text-emerald-900 sm:text-xl" x-text="loading ? '—' : minutes(summary.total_minutes)"></p></div>
        </div>
    </section>

    <div x-show="error" x-cloak class="rounded-2xl border border-red-100 bg-red-50 p-4">
        <p x-text="error" role="alert" class="text-sm text-red-800"></p>
        <button type="button" @click="load(false)" class="mt-2 min-h-11 rounded-xl border border-red-200 px-4 text-sm font-semibold text-red-800">Coba lagi</button>
    </div>
    <p x-show="loading" role="status" class="rounded-3xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-600">Memuat riwayat rekaman…</p>
    <div x-show="!loading && !error && !rows.length" x-cloak class="rounded-3xl border border-slate-200 bg-white px-5 py-10 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">@include('portal.icon', ['name' => 'report'])</div>
        <h2 class="font-semibold text-slate-900">Belum ada rekaman pada pilihan ini</h2>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">Rekaman muncul setelah admin menandai pemiliknya. Coba pilih bulan lain atau ubah pencarian.</p>
        <button type="button" x-show="search" @click="search = ''; load()" class="mt-4 min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700">Hapus pencarian</button>
    </div>
    <section x-show="!loading && !error && rows.length" x-cloak aria-label="Daftar rekaman" class="space-y-4">
        <div class="flex items-center justify-between gap-3"><h2 class="text-base font-bold text-slate-900">Daftar rekaman</h2><span class="text-xs text-slate-500">Waktu Atlas · WITA</span></div>
        <template x-for="group in groups" :key="group.date">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
                    <h3 class="text-sm font-semibold text-slate-700" x-text="day(group.date)"></h3>
                </div>
                <div class="divide-y divide-slate-100"><template x-for="row in group.rows" :key="row.record_id">
                    <article class="p-4 sm:px-5">
                        <div class="flex items-start justify-between gap-4">
                            <h4 class="min-w-0 break-words text-sm font-bold leading-relaxed text-slate-900" x-text="row.task_label || row.task_title || 'Rekaman'"></h4>
                            <p class="shrink-0 text-sm font-bold tabular-nums text-emerald-800" x-text="minutes(row.duration_minutes)"></p>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2">
                            <p class="inline-flex items-center gap-1.5 text-xs text-slate-600">@include('portal.icon', ['name' => 'clock']) <span x-text="at(row.recorded_at)"></span></p>
                            <span class="inline-block rounded-lg px-2 py-1 text-xs font-medium" :class="statusClass(row.processing_status)" x-text="status(row.processing_status)"></span>
                        </div>
                        <details class="mt-1">
                            <summary class="min-h-11 w-fit cursor-pointer content-center rounded-lg text-xs font-semibold text-slate-600 hover:text-emerald-800">Detail rekaman</summary>
                            <dl class="space-y-3 rounded-xl bg-slate-50 p-3 text-xs leading-relaxed">
                                <div><dt class="text-slate-500">Judul asli Atlas</dt><dd class="mt-1 break-words text-slate-800" x-text="row.task_title || '—'"></dd></div>
                                <div><dt class="text-slate-500">ID rekaman</dt><dd class="mt-1 break-all font-mono text-slate-800" x-text="row.record_id"></dd></div>
                            </dl>
                        </details>
                    </article>
                </template></div>
            </section>
        </template>
        <nav aria-label="Halaman riwayat rekaman" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <span class="text-xs text-slate-600" x-text="'Halaman ' + page + ' dari ' + lastPage"></span>
            <div class="grid w-full grid-cols-2 gap-3 sm:flex sm:w-auto">
                <button type="button" @click="page--; load(false)" :disabled="page <= 1 || loading" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40">Sebelumnya</button>
                <button type="button" @click="page++; load(false)" :disabled="page >= lastPage || loading" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40">Berikutnya</button>
            </div>
        </nav>
    </section>
</div>
@endsection
