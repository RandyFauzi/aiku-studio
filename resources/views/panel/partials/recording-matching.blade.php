<section x-data="recordingMatching" class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-xs" aria-labelledby="matching-title">
    <div class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div><h2 id="matching-title" class="font-extrabold text-slate-900">Cocokkan recording per shift</h2>
            <p class="mt-1 text-sm text-slate-600">Pilih laporan dan HP, centang recording, lalu konfirmasi pemiliknya.</p></div>
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="recording-matching-content" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold" x-text="open ? 'Tutup panel' : 'Buka panel'"></button>
    </div>
    <div id="recording-matching-content" x-show="open" class="border-t border-slate-100 p-4 sm:p-5 space-y-4">
        <div class="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Pembatas mengikuti waktu yang tercatat di Atlas dan setting shift. Delay bisa memindahkan recording ke kelompok berikutnya. Cocokkan bukti laporan sebelum konfirmasi.</div>
        <fieldset :disabled="saving" class="flex flex-wrap items-end gap-3">
            <label class="text-sm font-semibold">Tanggal kelompok recording<input type="date" x-model="recordingDate" @change="load()" class="block mt-1 min-h-11 rounded-xl border border-slate-200 px-3"></label>
            <label class="text-sm font-semibold">Tanggal laporan kerja<input type="date" x-model="reportDate" @change="target = ''; load()" class="block mt-1 min-h-11 rounded-xl border border-slate-200 px-3"></label>
            <label class="text-sm font-semibold">Filter email recording<input type="text" x-model="email" @keydown.enter.prevent="load()" placeholder="Semua email" class="block mt-1 min-h-11 rounded-xl border border-slate-200 px-3"></label>
            <button type="button" @click="message = ''; load()" :disabled="loading" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold disabled:opacity-50">Muat ulang</button>
        </fieldset>
        <p class="text-xs text-slate-500">Data delay? Biarkan tanggal recording tetap, lalu pilih tanggal laporan sebelumnya. Pilihan centang berlaku per halaman.</p>
        <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 p-3 text-sm text-red-800"></p>
        <p x-show="message" x-text="message" role="status" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800"></p>
        <p x-show="loading" role="status" class="text-sm text-slate-500">Memuat recording dan laporan…</p>
        <div class="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]" x-show="!loading && !error">
            <div class="space-y-3 min-w-0">
                <h3 class="font-bold text-sm">1. Pilih laporan &amp; HP</h3>
                <p x-show="!reports.length" class="text-sm text-slate-500">Belum ada laporan pada tanggal ini. Pilih tanggal kerja lain.</p>
                <div class="max-h-[620px] overflow-y-auto space-y-3 pr-1">
                    <template x-for="option in options" :key="option.key">
                        <button type="button" @click="chooseTarget(option.key)" :disabled="saving || !option.report.can_confirm || !option.phone.emails.length"
                            class="block w-full rounded-lg border p-3 text-left text-sm transition disabled:opacity-60 focus-visible:ring-2 focus-visible:ring-green-700"
                            :class="target === option.key ? 'border-green-700 bg-green-50' : 'border-slate-200 hover:bg-slate-50'" :aria-pressed="target === option.key">
                            <span class="block font-extrabold" x-text="option.report.name"></span>
                            <span class="block text-xs text-slate-500" x-text="option.report.shift_label + ' (jadwal) · ' + option.report.work_date"></span>
                            <span class="block mt-2 font-semibold" x-text="option.phone.label"></span>
                            <span class="block break-all" x-text="option.phone.emails.join(', ') || 'Email belum diisi pada laporan'"></span>
                            <span class="block mt-1 text-xs" :class="option.phone.email_source === 'report' ? 'text-green-800' : 'text-amber-800'" x-text="option.phone.email_source === 'report' ? 'Email tercatat pada laporan' : 'Kandidat akun master — pastikan email yang dipakai'"></span>
                            <span class="block mt-2" x-text="(option.phone.duration_minutes == null ? 'Durasi HP tidak dirinci' : option.phone.duration_minutes + ' menit pada HP ini') + ' · total laporan ' + option.report.total_minutes + ' menit'"></span>
                            <span class="block text-xs text-slate-500" x-text="'Laporan diperbarui ' + time(option.report.reported_at) + ' WITA'"></span>
                        </button>
                    </template>
                </div>
                <p class="text-xs text-slate-500">Jam pembaruan laporan bukan bukti jam selesai rekaman. Shift di atas adalah jadwal, bukan klaim otomatis.</p>
            </div>
            <div class="min-w-0 space-y-3">
                <h3 class="font-bold text-sm">2. Centang recording milik pekerja</h3>
                <p x-show="!owner" class="text-sm text-slate-500">Pilih laporan dan HP untuk mengaktifkan centang pada email yang sesuai.</p>
                <template x-for="group in groups" :key="group.id">
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="flex flex-wrap justify-between gap-1 bg-slate-100 px-3 py-2 text-sm"><strong x-text="group.label"></strong><span class="text-xs text-slate-600" x-text="group.time_label"></span></div>
                        <p x-show="!group.recordings.length" class="p-3 text-xs text-slate-500">Tidak ada recording di kelompok ini pada halaman ini.</p>
                        <div class="divide-y divide-slate-100">
                            <template x-for="record in group.recordings" :key="record.record_id">
                                <label class="flex items-start gap-3 p-3 text-sm" :class="selectable(record) ? 'cursor-pointer hover:bg-green-50' : 'bg-slate-50/50'">
                                    <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded accent-green-800" :checked="selected.includes(record.record_id)" :disabled="!selectable(record) || saving" @change="toggle(record)" :aria-label="'Pilih recording ' + record.record_id">
                                    <span class="min-w-0 flex-1"><span class="block font-semibold" x-text="record.task_title || 'Tanpa judul'"></span><span class="block break-all text-xs text-slate-500" x-text="record.record_id"></span><span class="block break-all mt-1" x-text="record.participant_email"></span><span class="block text-xs text-slate-500" x-text="time(record.recorded_at) + ' WITA · ' + record.duration_min + ' menit · diterima ' + record.billable_min + ' menit'"></span>
                                        <span class="block mt-1 text-xs font-semibold" x-text="record.assigned_worker_name ? 'Pemilik: ' + record.assigned_worker_name : (record.assigned_contributor_id ? 'Sudah memiliki pemilik' : (record.manual_payment_status === 'Paid' ? 'Sudah dibayar' : 'Belum dicocokkan'))"></span></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span x-text="total + ' recording · halaman ' + page + ' / ' + lastPage"></span>
                    <div class="flex gap-2"><button type="button" @click="page--; load(false)" :disabled="page <= 1 || saving" class="min-h-11 rounded-lg border px-3 disabled:opacity-40">Sebelumnya</button><button type="button" @click="page++; load(false)" :disabled="page >= lastPage || saving" class="min-h-11 rounded-lg border px-3 disabled:opacity-40">Berikutnya</button></div>
                </div>
            </div>
        </div>
        <fieldset :disabled="saving || loading" x-show="owner && !error" class="rounded-xl border border-green-200 bg-green-50 p-4 space-y-3">
            <p class="text-sm font-semibold" x-text="selected.length + ' recording · ' + selectedMinutes + ' menit dipilih → ' + (owner?.report.name || '')"></p>
            <label class="block text-sm font-semibold">Dasar konfirmasi <textarea x-model="reason" rows="2" maxlength="500" placeholder="Contoh: sesuai laporan HP Putih, 6 rekaman; upload terlambat masuk dari shift sebelumnya." class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-3 text-sm"></textarea></label>
            <p class="text-xs text-slate-600">Periksa jumlah, task, dan durasi. Durasi yang mirip belum membuktikan pemilik. Konfirmasi memengaruhi penghasilan berdasarkan menit diterima Atlas.</p>
            <button type="button" @click="confirmOwner()" :disabled="!canConfirm" class="min-h-11 rounded-xl bg-green-900 px-5 text-sm font-bold text-white disabled:opacity-40" x-text="saving ? 'Menyimpan…' : 'Konfirmasi pemilik'"></button>
        </fieldset>
    </div>
</section>
