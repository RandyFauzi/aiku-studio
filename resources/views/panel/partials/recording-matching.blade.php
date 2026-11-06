<div x-data="recordingMatching" @open-recording-owner.window="openFor($event.detail)" x-show="open" x-cloak
    @keydown.escape.window="if(open) close()" @keydown="trap($event)" @click.self="close()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
    <section x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="owner-dialog-title" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl space-y-4">
        <div class="flex items-center justify-between gap-3"><h2 id="owner-dialog-title" class="text-lg font-bold">Konfirmasi pemilik</h2><button type="button" @click="close()" :disabled="saving" class="min-h-11 px-3 text-sm">Tutup</button></div>
        <p class="text-sm text-slate-600" x-text="selected.length + ' recording · ' + selectedMinutes + ' menit dipilih'"></p>
        <label class="block text-sm font-semibold">Tanggal laporan kerja
            <input x-ref="reportDate" type="date" x-model="reportDate" @change="load()" :disabled="saving" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2">
        </label>
        <p class="text-xs text-slate-500">Upload delay? Pilih tanggal laporan sebelumnya. Pembatas shift hanya penanda waktu Atlas.</p>
        <p x-show="loading" role="status" class="text-sm text-slate-500">Memuat laporan…</p>
        <p x-show="error" role="alert" x-text="error" class="text-sm text-red-700"></p>
        <p x-show="!loading && !error && !options.length" class="text-sm text-amber-800">Tidak ada laporan dengan email HP yang sesuai. Pilih tanggal lain atau lengkapi email pada laporan.</p>
        <label class="block text-sm font-semibold">Pekerja / HP sesuai laporan
            <select x-model="target" :disabled="loading || saving" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2">
                <option value="">Pilih pemilik</option>
                <template x-for="option in options" :key="option.key"><option :value="option.key" :disabled="!option.report.can_confirm" x-text="option.report.name + ' · ' + option.phone.label + ' · ' + option.report.shift_label"></option></template>
            </select>
        </label>
        <div x-show="owner" class="rounded-lg bg-slate-50 p-3 text-sm space-y-1">
            <p class="break-all" x-text="owner?.phone.emails.join(', ')"></p>
            <p x-text="owner ? (owner.phone.duration_minutes == null ? 'Durasi HP tidak dirinci' : owner.phone.duration_minutes + ' menit pada HP ini') + ' · total laporan ' + owner.report.total_minutes + ' menit' : ''"></p>
            <p x-text="'Dikirim: ' + time(owner?.report.submitted_at)"></p>
            <p x-show="owner?.report.revision > 1" x-text="'Diperbarui: ' + time(owner?.report.reported_at)"></p>
            <p x-show="owner?.phone.email_source !== 'report'" class="text-xs text-amber-800">Email kandidat dari master HP. Pastikan akun yang benar-benar dipakai.</p>
        </div>
        <p x-show="chosen.some(r => r.assigned_contributor_id || r.assigned_worker_name?.trim() || r.manual_payment_status === 'Paid')" class="text-sm text-amber-800">Pilihan berisi recording yang sudah dimiliki atau dibayar. Batalkan dan pilih recording yang belum ditandai.</p>
        <label class="block text-sm font-semibold">Dasar konfirmasi<textarea x-model="reason" :disabled="saving" maxlength="500" rows="2" placeholder="Sesuai laporan dan rincian recording; upload terlambat dari shift sebelumnya." class="mt-1 block w-full rounded-lg border border-slate-200 p-3 text-sm"></textarea></label>
        <button type="button" @click="confirmOwner()" :disabled="!canConfirm" class="min-h-11 rounded-lg bg-green-900 px-4 py-2 font-bold text-white disabled:opacity-40" x-text="saving ? 'Menyimpan…' : 'Konfirmasi pemilik'"></button>
    </section>
</div>
