<section x-data="dailyWorkReportData()" id="laporan-harian" class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-xs">
    <!-- Header -->
    <div class="border-b border-slate-100 p-5 sm:p-6 flex items-center justify-between gap-3 bg-white">
        <div class="flex items-center gap-3">
            <button type="button" @click="$dispatch('close-report-form')" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-bold transition" aria-label="Tutup form laporan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight">Input Laporan Kerja</h3>
                <p class="text-xs font-bold text-slate-600 font-mono mt-0.5" x-text="dateLabel(date)"></p>
            </div>
        </div>

        <span class="rounded-full px-3.5 py-1 text-xs font-black"
              :class="revision ? 'bg-emerald-100 text-emerald-950 border border-emerald-300' : 'bg-amber-100 text-amber-950 border border-amber-300'"
              x-text="revision ? '✓ Sudah Dikirim' : 'Belum Dikirim'"></span>
    </div>

    <form @submit.prevent="submit()" class="space-y-6 p-5 sm:p-6 bg-white">
        <p x-show="error" x-text="error" role="alert" class="rounded-2xl bg-red-50 p-4 text-xs font-bold text-red-800 border border-red-200"></p>
        <p x-show="message" x-text="message" role="status" aria-live="polite" class="rounded-2xl bg-emerald-50 p-4 text-xs font-bold text-emerald-900 border border-emerald-300"></p>

        <fieldset :disabled="loading || saving" class="space-y-5 disabled:opacity-50">
            <legend class="sr-only">Input durasi pengerjaan</legend>

            <!-- Section Label -->
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-700">Pilih HP & Durasi Menit</span>
                <span class="text-xs font-bold"
                      :class="phones.length ? 'text-emerald-900 bg-emerald-100 px-3 py-1 rounded-full font-extrabold border border-emerald-200' : 'text-slate-500'"
                      x-text="phones.length ? phones.length + ' HP Dipilih' : 'Pilih HP di bawah'"></span>
            </div>

            <!-- Dynamic Phones Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 portal-phone-grid">
                <template x-for="(label, key) in options" :key="key">
                    <div :class="phones.includes(key) ? 'border-2 border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20 shadow-xs' : 'border border-slate-200 hover:border-slate-300 bg-white shadow-2xs'"
                         class="rounded-3xl p-4 transition flex flex-col justify-between">

                        <!-- Toggle Header -->
                        <button type="button" class="flex flex-wrap gap-2 items-center justify-between w-full cursor-pointer select-none" @click="togglePhone(key)" :aria-pressed="phones.includes(key)">
                            <div class="flex items-center gap-2.5">
                                <div class="w-5 h-5 rounded-md border flex items-center justify-center transition"
                                     :class="phones.includes(key) ? 'bg-emerald-600 border-emerald-600 text-white font-black' : 'border-slate-300 bg-white'">
                                    <svg x-show="phones.includes(key)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-sm font-black text-slate-900" x-text="'HP ' + label"></span>
                            </div>
                            <span x-show="phones.includes(key)" class="text-[10px] font-black text-emerald-900 bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                Dipakai
                            </span>
                        </button>

                        <!-- Duration Input Section (Expands when selected) -->
                        <div x-show="phones.includes(key)" x-transition class="mt-3.5 pt-3.5 border-t border-slate-200 space-y-2.5">
                            <div class="flex items-center gap-2">
                                <input type="number"
                                       :aria-label="'Durasi HP ' + label + ' dalam menit'"
                                       min="1"
                                       max="1440"
                                       step="1"
                                       inputmode="numeric"
                                       placeholder="Contoh: 120"
                                       :value="phoneDurations[key] || ''"
                                       @input="setMinutes(key, $event.target.value)"
                                       class="w-full bg-white border-2 border-slate-300 rounded-2xl px-3.5 py-2 text-sm sm:text-base font-black text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none font-mono">
                                <span class="text-xs font-bold text-slate-600 shrink-0">menit</span>
                            </div>

                            <!-- Fast Increment Pill Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="setMinutes(key, 60)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[11px] font-bold text-slate-700 border border-slate-200 transition">60m</button>
                                <button type="button" @click="setMinutes(key, 120)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[11px] font-bold text-slate-700 border border-slate-200 transition">120m</button>
                                <button type="button" @click="setMinutes(key, 180)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[11px] font-bold text-slate-700 border border-slate-200 transition">180m</button>
                                <button type="button" @click="setMinutes(key, 240)" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 active:scale-95 text-[11px] font-bold text-slate-700 border border-slate-200 transition">240m</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Dynamic Live Total Accumulation Banner (High-Contrast Lime & Mint Design) -->
            <div class="rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-2 border-lime-300/90 shadow-sm relative overflow-hidden"
                 style="background: linear-gradient(115deg, #dcfce7 0%, #d9f99d 55%, #bef264 100%);">
                
                <div class="relative z-10">
                    <span class="text-[11px] font-black uppercase tracking-wider text-[#1e3c16] block">
                        Akumulasi Durasi Hari Ini
                    </span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl sm:text-4xl font-black text-[#0f240c] font-mono tracking-tight" x-text="totalMinutes"></span>
                        <span class="text-sm sm:text-base font-black text-[#0f240c]">Menit</span>
                        <span class="text-xs sm:text-sm font-extrabold text-[#2d4e22]" x-text="'(' + duration(totalMinutes) + ')'"></span>
                    </div>
                </div>

                <div class="relative z-10">
                    <template x-if="!isComplete">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-black bg-slate-900 text-lime-300 shadow-sm border border-slate-800">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            Pilih HP & Isi Menit
                        </span>
                    </template>
                    <template x-if="isComplete">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-black bg-[#14532d] text-emerald-100 shadow-sm border border-emerald-800">
                            <svg class="w-3.5 h-3.5 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Laporan Siap Dikirim
                        </span>
                    </template>
                </div>
            </div>

        </fieldset>

        <!-- Submit Controls -->
        <div class="space-y-3 pt-2">
            <button type="submit"
                    :disabled="saving || loading || !isComplete || !dirty"
                    class="w-full py-4 rounded-2xl font-black text-sm sm:text-base tracking-wide transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    :class="dirty && isComplete ? 'bg-[#154634] hover:bg-[#0e3325] text-white shadow-lg shadow-emerald-950/20 active:scale-[0.99] cursor-pointer' : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed'"
                    x-text="saving ? 'Menyimpan…' : (revision ? 'Perbarui Laporan' : 'Kirim Laporan Kerja')">
            </button>

            <div class="flex items-center justify-between text-xs font-semibold text-slate-600 px-1">
                <span class="flex items-center gap-1.5" :class="dirty ? 'text-amber-800 font-bold' : 'text-slate-600'">
                    <span class="w-2 h-2 rounded-full" :class="dirty ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></span>
                    <span x-text="dirty ? 'Ada perubahan belum disimpan' : 'Semua data tersimpan'"></span>
                </span>
                <button type="button" @click="reload()" :disabled="saving || loading" class="font-extrabold text-slate-700 hover:text-slate-900 hover:underline transition cursor-pointer">
                    Batalkan Perubahan
                </button>
            </div>
        </div>
    </form>

    <!-- Report History -->
    <div class="border-t border-slate-100 p-5 sm:p-6 bg-slate-50/60 rounded-b-[32px]">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h4 class="font-black text-slate-900 text-sm sm:text-base">Riwayat Laporan Bulanan</h4>
                <p class="text-xs text-slate-500 font-medium">Daftar laporan kerja yang telah Anda laporkan</p>
            </div>
            <input type="month" 
                   aria-label="Bulan riwayat laporan" 
                   x-model="month" 
                   @change="load(false)" 
                   :disabled="loading || saving" 
                   class="h-9 rounded-xl border-2 border-slate-200 px-3 text-xs font-bold text-slate-800 bg-white shadow-2xs focus:border-emerald-500 focus:outline-none">
        </div>
        <div class="space-y-2.5">
            <template x-for="report in reports" :key="report.id">
                <div class="flex items-center justify-between p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition">
                    <div>
                        <p class="text-xs sm:text-sm font-extrabold text-slate-900" x-text="dateLabel(report.work_date)"></p>
                        <p class="text-[11px] text-slate-600 font-mono font-medium mt-0.5" x-text="phoneDurationsText(report)"></p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs sm:text-sm font-black text-emerald-800 font-mono block" x-text="report.total_duration_minutes + ' menit'"></span>
                        <span class="text-[11px] font-bold text-slate-500 block" x-text="duration(report.total_duration_minutes)"></span>
                    </div>
                </div>
            </template>
            <p x-show="!loading && !reports.length" class="py-6 text-center text-xs sm:text-sm text-slate-500 font-medium">Belum ada laporan pada bulan ini.</p>
        </div>
    </div>
</section>
