<section x-data="dailyWorkReportData()" id="laporan-harian" class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xs">
    <!-- Header -->
    <div class="border-b border-slate-100 p-5 sm:p-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Laporan Harian</span>
                <span class="text-xs font-bold text-slate-500 font-mono" x-text="dateLabel(date)"></span>
            </div>
            <h3 class="mt-1 text-lg sm:text-xl font-black text-slate-900">Pilih HP & Masukkan Durasi</h3>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-extrabold" 
              :class="revision ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-900 border border-amber-200'" 
              x-text="revision ? '✓ Sudah Dikirim' : 'Belum Dikirim'"></span>
    </div>

    <form @submit.prevent="submit()" class="space-y-6 p-5 sm:p-6">
        <p x-show="error" x-text="error" role="alert" class="rounded-2xl bg-red-50 p-4 text-xs font-semibold text-red-700 border border-red-200"></p>
        <p x-show="message" x-text="message" role="status" aria-live="polite" class="rounded-2xl bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 border border-emerald-200"></p>

        <fieldset :disabled="loading || saving" class="space-y-5 disabled:opacity-50">
            <legend class="sr-only">Input durasi pengerjaan</legend>

            <!-- Phones Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <template x-for="(label, key) in options" :key="key">
                    <div :class="phones.includes(key) ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'" 
                         class="rounded-2xl border p-3.5 transition flex flex-col justify-between">
                        
                        <!-- Checkbox Toggle Header -->
                        <div class="flex items-center justify-between cursor-pointer select-none" @click="togglePhone(key)">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" 
                                       :checked="phones.includes(key)" 
                                       @click.stop="togglePhone(key)" 
                                       class="h-4 w-4 rounded-md accent-emerald-600 cursor-pointer">
                                <span class="text-sm font-black text-slate-800" x-text="'HP ' + label"></span>
                            </div>
                            <span x-show="phones.includes(key)" class="text-[10px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                Digunakan
                            </span>
                        </div>

                        <!-- Duration Input Section -->
                        <div x-show="phones.includes(key)" x-transition class="mt-3 pt-3 border-t border-slate-200/70 space-y-2">
                            <div class="flex items-center gap-2">
                                <input type="number" 
                                       min="1" 
                                       max="1440" 
                                       step="1" 
                                       inputmode="numeric" 
                                       placeholder="Menit" 
                                       :value="phoneDurations[key] || ''" 
                                       @input="setMinutes(key, $event.target.value)" 
                                       class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-sm font-black text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none font-mono">
                                <span class="text-xs font-bold text-slate-400 shrink-0">menit</span>
                            </div>

                            <!-- Fast Increment Pill Chips -->
                            <div class="flex items-center gap-1.5 pt-0.5">
                                <button type="button" @click="setMinutes(key, 60)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-[10px] font-bold text-slate-700 transition">60m</button>
                                <button type="button" @click="setMinutes(key, 120)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-[10px] font-bold text-slate-700 transition">120m</button>
                                <button type="button" @click="setMinutes(key, 180)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-[10px] font-bold text-slate-700 transition">180m</button>
                                <button type="button" @click="setMinutes(key, 240)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-[10px] font-bold text-slate-700 transition">240m</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Dynamic Live Total Accumulation Banner -->
            <div class="rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Durasi Hari Ini</span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="text-2xl sm:text-3xl font-black text-white font-mono" x-text="totalMinutes"></span>
                        <span class="text-xs font-bold text-slate-300">Menit</span>
                        <span class="text-xs text-emerald-400 font-semibold" x-text="'(' + duration(totalMinutes) + ')'"></span>
                    </div>
                </div>

                <div>
                    <template x-if="!isComplete">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-amber-300 border border-white/10">
                            Pilih HP & Masukkan Menit
                        </span>
                    </template>
                    <template x-if="isComplete">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ✓ Siap Dikirim
                        </span>
                    </template>
                </div>
            </div>

        </fieldset>

        <!-- Submit Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <span class="text-[11px] text-slate-400" x-text="dirty ? '● Ada perubahan belum disimpan' : 'Tersimpan'"></span>
            <div class="flex items-center gap-2">
                <button type="button" @click="reload()" :disabled="saving || loading" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" 
                        :disabled="saving || loading || !isComplete || !dirty" 
                        class="px-6 py-2.5 rounded-2xl bg-[#18181B] hover:bg-slate-800 active:scale-98 text-xs sm:text-sm font-black text-white shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed" 
                        x-text="saving ? 'Mengirim…' : (revision ? 'Perbarui Laporan' : 'Kirim Laporan')">
                </button>
            </div>
        </div>
    </form>

    <!-- Report History -->
    <div class="border-t border-slate-100 p-5 sm:p-6 bg-slate-50/50">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h4 class="font-black text-slate-900 text-sm">Riwayat Bulanan</h4>
            <input type="month" x-model="month" @change="load(false)" :disabled="loading || saving" class="h-8 rounded-xl border border-slate-200 px-2.5 text-xs font-bold text-slate-700 bg-white shadow-2xs">
        </div>
        <div class="space-y-2">
            <template x-for="report in reports" :key="report.id">
                <div class="flex items-center justify-between p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                    <div>
                        <p class="text-xs font-bold text-slate-900" x-text="dateLabel(report.work_date)"></p>
                        <p class="text-[10px] text-slate-500 font-mono mt-0.5" x-text="phoneDurationsText(report)"></p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black text-emerald-800 font-mono block" x-text="report.total_duration_minutes + 'm'"></span>
                        <span class="text-[10px] text-slate-400 block" x-text="duration(report.total_duration_minutes)"></span>
                    </div>
                </div>
            </template>
            <p x-show="!loading && !reports.length" class="py-4 text-center text-xs text-slate-400">Belum ada laporan pada bulan ini.</p>
        </div>
    </div>
</section>
