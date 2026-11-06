<template x-teleport="body">
    <div x-show="activePopover === rec.record_id && viewMode === '{{ $pickerView }}'" x-cloak x-data="recordingWorkerRecommendations(rec.record_id)"
        x-effect="if (activePopover === recordId && viewMode === '{{ $pickerView }}') load()" @click.outside="if (!saving && !document.querySelector('.swal2-container')) activePopover = null" @keydown.escape.window="if (!saving && !document.querySelector('.swal2-container')) activePopover = null"
        :style="workerPickerStyle" class="fixed z-[70] overflow-y-auto overscroll-contain rounded-2xl border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-2xl" role="dialog" :aria-label="'Pilih pekerja untuk recording ' + rec.record_id">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2">
            <div class="min-w-0"><h3 class="text-sm font-bold text-slate-900">Tandai pekerja</h3><p class="mt-1 break-words text-xs text-slate-500" x-text="rec.task_title || 'Tanpa judul task'"></p><p class="break-all text-xs text-slate-500" x-text="rec.participant_email"></p></div>
            <button type="button" @click="if (!saving) activePopover = null" :disabled="saving" class="min-h-11 px-3 text-xs text-slate-500">Tutup</button>
        </div>
        <div class="mt-3 flex gap-2 sm:hidden">
            <button type="button" @click="mobileTab = 'manual'" :aria-pressed="mobileTab === 'manual'" class="min-h-11 flex-1 rounded-lg border px-2 text-xs font-semibold" :class="mobileTab === 'manual' ? 'border-green-300 bg-green-50 text-green-900' : 'border-slate-200'">Semua pekerja</button>
            <button type="button" @click="mobileTab = 'recommendations'" :aria-pressed="mobileTab === 'recommendations'" class="min-h-11 flex-1 rounded-lg border px-2 text-xs font-semibold" :class="mobileTab === 'recommendations' ? 'border-green-300 bg-green-50 text-green-900' : 'border-slate-200'">Rekomendasi</button>
        </div>
        <div class="grid gap-3 pt-3 sm:grid-cols-[190px_minmax(0,1fr)]">
            <div class="min-w-0" :class="mobileTab === 'manual' ? '' : 'hidden sm:block'">
                <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Pilih pekerja</h4>
                <div class="max-h-60 overflow-y-auto space-y-1">
                    <button type="button" @click="assignWorker(rec.record_id, null); activePopover = null" :disabled="saving" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-2 text-left text-xs text-rose-700 hover:bg-rose-50"><span aria-hidden="true">×</span> Hapus Tanda (Kosongkan)</button>
                    <template x-for="c in contributors" :key="c.contributor_id || c.id">
                        <button type="button" @click="assignWorker(rec.record_id, (c.name || c.nama)); activePopover = null" :disabled="saving" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-2 text-left text-xs hover:bg-slate-100" :class="rec.assigned_worker_name === (c.name || c.nama) ? 'bg-green-50 font-bold text-green-900' : ''">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold" x-text="getWorkerInitials(c.name || c.nama)"></span><span class="break-words" x-text="c.name || c.nama"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="min-w-0 border-slate-100 sm:border-l sm:pl-3" :class="mobileTab === 'recommendations' ? '' : 'hidden sm:block'">
                <div class="flex items-center justify-between gap-2"><h4 class="text-xs font-bold uppercase tracking-wide text-green-900">Rekomendasi</h4><button type="button" @click="load(true)" :disabled="loading || saving" class="min-h-11 px-2 text-xs font-semibold text-slate-500 disabled:opacity-40">Segarkan</button></div>
                <p class="mb-3 text-xs leading-5 text-slate-500">Dari laporan, email/HP, task, dan jadwal. Termasuk laporan dua hari sebelumnya.</p>
                <p x-show="loading" role="status" class="py-3 text-xs text-slate-500">Memeriksa kandidat…</p>
                <p x-show="error" x-text="error" role="alert" class="mb-2 rounded-lg bg-red-50 p-2 text-xs text-red-700"></p>
                <div x-show="!loading" class="space-y-2">
                    <p x-text="message" class="text-xs leading-5" :class="ambiguous ? 'text-amber-800' : 'text-slate-500'"></p>
                    <template x-for="candidate in candidates" :key="candidate.contributor_id">
                        <article class="rounded-xl border border-slate-200 p-3 space-y-2">
                            <div class="flex items-start justify-between gap-2"><div class="min-w-0"><h5 class="break-words text-sm font-bold text-slate-900" x-text="candidate.name"></h5><p class="text-xs text-slate-500" x-text="candidate.phone_label + ' · ' + candidate.work_date"></p></div><span class="shrink-0 rounded-lg bg-green-50 px-2 py-1 text-xs text-green-900" x-text="candidate.signal_count + '/3 indikator'"></span></div>
                            <div class="flex flex-wrap gap-1"><template x-for="(signal, key) in candidate.signals" :key="key"><span class="rounded-md px-2 py-1 text-xs" :class="signal.matched ? 'bg-green-50 text-green-900' : 'bg-slate-100 text-slate-500'" x-text="signal.label"></span></template></div>
                            <p class="text-xs text-slate-500" x-text="candidate.reported_minutes == null ? 'Durasi HP belum dirinci' : candidate.reported_minutes + ' menit dilaporkan pada HP ini'"></p>
                            <ul class="space-y-1 text-xs leading-5 text-amber-800"><template x-for="warning in candidate.warnings" :key="warning"><li x-text="warning"></li></template></ul>
                            <button type="button" @click="confirmCandidate(candidate)" :disabled="saving || !candidate.can_confirm" class="min-h-11 w-full rounded-lg border border-green-200 bg-green-50 px-3 text-xs font-bold text-green-900 disabled:opacity-40" x-text="saving ? 'Menyimpan…' : (candidate.can_confirm ? 'Tandai ' + candidate.name : 'Perlu pemeriksaan manual')"></button>
                        </article>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
