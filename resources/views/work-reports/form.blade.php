<section x-data="dailyWorkReportData()" id="laporan-harian" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 p-5 sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-700">Laporan kerja harian</p>
                <h2 class="mt-1 text-lg sm:text-xl font-extrabold text-slate-900" x-text="dateLabel(date)"></h2>
                <p class="mt-1 text-xs sm:text-sm text-slate-500">
                    Centang HP yang kamu gunakan dan isi total <strong>menit</strong> pengerjaan di masing-masing HP.
                </p>
            </div>
            <span class="rounded-full px-3 py-1.5 text-xs font-semibold" 
                  :class="revision ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200'" 
                  x-text="revision ? 'Sudah dikirim' : 'Belum dikirim'"></span>
        </div>
    </div>

    <form @submit.prevent="submit()" class="space-y-6 p-5 sm:p-7">
        <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"></p>
        <p x-show="message" x-text="message" role="status" aria-live="polite" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 border border-emerald-200"></p>

        <fieldset :disabled="loading || saving" class="space-y-6 disabled:opacity-50">
            <legend class="sr-only">Isi laporan kerja harian</legend>

            <!-- Dynamic Mobile & Desktop Phones Grid -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-bold text-slate-800">
                        Pilih HP & Masukkan Durasi Menit:
                    </p>
                    <span class="text-xs text-slate-400" x-text="phones.length ? (phones.length + ' HP dipilih') : 'Belum ada HP dipilih'"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    <template x-for="(label, key) in options" :key="key">
                        <div :class="phones.includes(key) ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'" 
                             class="rounded-2xl border p-4 transition flex flex-col justify-between">
                            
                            <!-- Checkbox Toggle Header -->
                            <div class="flex items-center justify-between cursor-pointer" @click="togglePhone(key)">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" 
                                           :checked="phones.includes(key)" 
                                           @click.stop="togglePhone(key)" 
                                           class="h-5 w-5 rounded-md accent-emerald-600 cursor-pointer">
                                    <span class="text-sm font-extrabold text-slate-800" x-text="'HP ' + label"></span>
                                </div>
                                <span x-show="phones.includes(key)" class="text-[10px] font-bold text-emerald-700 bg-emerald-100/90 px-2 py-0.5 rounded-full">
                                    Dipakai
                                </span>
                            </div>

                            <!-- Duration Input Section (Expands dynamically when checked) -->
                            <div x-show="phones.includes(key)" x-transition class="mt-3 pt-3 border-t border-slate-200/80 space-y-2">
                                <label class="block text-xs font-semibold text-slate-600">
                                    Durasi pengerjaan (menit saja):
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="number" 
                                           min="1" 
                                           max="1440" 
                                           step="1" 
                                           inputmode="numeric" 
                                           placeholder="Contoh: 120" 
                                           :value="phoneDurations[key] || ''" 
                                           @input="setMinutes(key, $event.target.value)" 
                                           class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-base font-extrabold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <span class="text-xs font-bold text-slate-500 shrink-0">menit</span>
                                </div>

                                <!-- Fast Increment Buttons -->
                                <div class="flex items-center gap-1.5 pt-1">
                                    <span class="text-[10px] text-slate-400">Pilihan:</span>
                                    <button type="button" @click="setMinutes(key, 60)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[11px] font-semibold text-slate-700">60m</button>
                                    <button type="button" @click="setMinutes(key, 120)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[11px] font-semibold text-slate-700">120m</button>
                                    <button type="button" @click="setMinutes(key, 180)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[11px] font-semibold text-slate-700">180m</button>
                                    <button type="button" @click="setMinutes(key, 240)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[11px] font-semibold text-slate-700">240m</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Dynamic Live Total Accumulation Banner -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-50 to-emerald-50/50 border border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Akumulasi Total Durasi Hari Ini</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 font-mono" x-text="totalMinutes"></span>
                        <span class="text-sm sm:text-base font-bold text-slate-700">Menit</span>
                        <span class="text-xs text-slate-500" x-text="'(' + duration(totalMinutes) + ')'"></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" x-text="phones.length ? ('Terbagi di ' + phones.length + ' HP yang kamu gunakan') : 'Pilih minimal 1 HP di atas'"></p>
                </div>

                <div>
                    <template x-if="!isComplete">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            Pilih HP & isi menitnya
                        </span>
                    </template>
                    <template x-if="isComplete">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✓ Laporan Siap Dikirim
                        </span>
                    </template>
                </div>
            </div>

        </fieldset>

        <!-- Submit Controls -->
        <div class="flex flex-col justify-between gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
            <p class="text-xs text-slate-500" x-text="dirty ? 'Ada perubahan durasi yang belum disimpan.' : 'Satu laporan per hari. Masih bisa diperbarui jika ada penambahan jam rekaman.'"></p>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="reload()" :disabled="saving || loading" class="min-h-[44px] rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                    Muat ulang
                </button>
                <button type="submit" 
                        :disabled="saving || loading || !isComplete || !dirty" 
                        class="min-h-[44px] rounded-xl bg-emerald-600 hover:bg-emerald-700 px-6 text-sm font-bold text-white shadow-xs transition disabled:cursor-not-allowed disabled:opacity-40" 
                        x-text="saving ? 'Mengirim…' : (revision ? 'Perbarui Laporan' : 'Kirim Laporan')">
                </button>
            </div>
        </div>
    </form>

    <!-- Report History -->
    <div class="border-t border-slate-100 p-5 sm:p-7">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-bold text-slate-800 text-sm sm:text-base">Riwayat laporan bulanan</h3>
            <label>
                <span class="sr-only">Bulan riwayat laporan</span>
                <input type="month" x-model="month" @change="load(false)" :disabled="loading || saving" class="h-10 rounded-xl border border-slate-200 px-3 text-xs sm:text-sm">
            </label>
        </div>
        <div class="space-y-3">
            <template x-for="report in reports" :key="report.id">
                <article class="flex flex-col justify-between gap-2 rounded-xl bg-slate-50 p-4 sm:flex-row sm:items-center border border-slate-100">
                    <div>
                        <p class="text-sm font-bold text-slate-800" x-text="dateLabel(report.work_date)"></p>
                        <p class="mt-1 text-xs text-slate-600 font-medium" x-text="'Rincian: ' + phoneDurationsText(report)"></p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-sm font-extrabold text-emerald-800" x-text="report.total_duration_minutes + ' menit'"></p>
                        <p class="text-[11px] text-slate-400" x-text="duration(report.total_duration_minutes)"></p>
                    </div>
                </article>
            </template>
            <p x-show="!loading && !reports.length" class="py-4 text-center text-sm text-slate-500">Belum ada laporan pada bulan ini.</p>
        </div>
    </div>
</section>
