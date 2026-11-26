<section class="grid grid-cols-1 md:grid-cols-2 gap-4"
         x-data="studioTargetsWidget()"
         x-init="load()"
         aria-label="Target Harian dan Bulanan Studio">
    <!-- Card 1: Target Harian Studio -->
    <div class="studio-target-card bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md hover:border-sky-200 flex flex-col justify-between">
        <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-sky-100/50 blur-2xl pointer-events-none"></div>
        <div class="relative">
            <div class="flex items-center justify-between gap-2 mb-2.5">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Target Harian Studio</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                      :class="data.daily.achieved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-sky-50 text-sky-700 border-sky-200'">
                    <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="data.daily.achieved ? 'bg-emerald-500' : 'bg-sky-500'"></span>
                    <span x-text="loading ? '…' : (data.daily.percent + '%')"></span>
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" x-text="loading ? '…' : data.daily.actual_hours"></span>
                <span class="text-xs font-bold text-slate-400" x-text="'/ ' + data.daily.target_hours + ' Jam'"></span>
            </div>
            <div class="mt-3 studio-target-track" role="progressbar" :aria-valuenow="data.daily.bar_percent" aria-valuemin="0" aria-valuemax="100">
                <div class="studio-target-fill"
                     :class="data.daily.achieved ? 'bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-500' : 'bg-gradient-to-r from-sky-500 via-cyan-400 to-indigo-500'"
                     :style="'width:' + data.daily.bar_percent + '%'"></div>
            </div>
        </div>
        <div class="relative mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Sisa hari ini: <strong class="text-slate-700 font-bold" x-text="data.daily.remaining_hours + 'h'"></strong></span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                  :class="data.daily.achieved ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-sky-50 text-sky-700 border-sky-100'"
                  x-text="data.daily.achieved ? 'Target Tercapai' : 'Progres Hari Ini'"></span>
        </div>
    </div>

    <!-- Card 2: Target Bulanan Studio (2.150 Jam Cut-Off 16-15) -->
    <div class="studio-target-card bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md hover:border-amber-200 flex flex-col justify-between">
        <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-amber-100/50 blur-2xl pointer-events-none"></div>
        <div class="relative">
            <div class="flex items-center justify-between gap-2 mb-2.5">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Target Bulanan Studio</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                      :class="data.monthly.achieved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (data.monthly.on_track ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-amber-50 text-amber-700 border-amber-200')">
                    <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="data.monthly.achieved ? 'bg-emerald-500' : (data.monthly.on_track ? 'bg-teal-500' : 'bg-amber-500'"></span>
                    <span x-text="loading ? '…' : (data.monthly.percent + '%')"></span>
                </span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" x-text="loading ? '…' : Number(data.monthly.actual_hours || 0).toLocaleString('id-ID')"></span>
                <span class="text-xs font-bold text-slate-400" x-text="'/ ' + Number(data.monthly.target_hours || 2150).toLocaleString('id-ID') + ' Jam'"></span>
            </div>
            <div class="mt-3 studio-target-track" role="progressbar" :aria-valuenow="data.monthly.bar_percent" aria-valuemin="0" aria-valuemax="100">
                <div class="studio-target-fill"
                     :class="data.monthly.achieved ? 'bg-gradient-to-r from-emerald-500 via-lime-400 to-emerald-500' : (data.monthly.on_track ? 'bg-gradient-to-r from-[#00B090] via-emerald-400 to-teal-500' : 'bg-gradient-to-r from-amber-500 via-orange-400 to-amber-500')"
                     :style="'width:' + data.monthly.bar_percent + '%'"></div>
            </div>
        </div>
        <div class="relative mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span x-text="data.period.name + ' (' + data.monthly.remaining_hours + 'h sisa)'"></span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                  :class="data.monthly.achieved ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (data.monthly.on_track ? 'bg-teal-50 text-teal-700 border-teal-100' : 'bg-amber-50 text-amber-700 border-amber-100')"
                  x-text="data.monthly.achieved ? 'Target Tembus' : (data.monthly.on_track ? 'On Track' : 'Pace ' + data.monthly.recommended_daily_pace + 'h/hr')"></span>
        </div>
    </div>
</section>

<script>
    if (typeof window.studioTargetsWidget !== 'function') {
        window.studioTargetsWidget = () => ({
            loading: true,
            data: {
                period: { name: 'Siklus 16 – 15' },
                daily: { target_hours: 71.7, actual_hours: 0, remaining_hours: 71.7, percent: 0, bar_percent: 0, achieved: false },
                monthly: { target_hours: 2150, actual_hours: 0, remaining_hours: 2150, recommended_daily_pace: 71.7, percent: 0, bar_percent: 0, achieved: false, on_track: false }
            },
            async load() {
                try {
                    const res = await fetch('/api/studio-targets', { headers: { Accept: 'application/json' } });
                    if (res.ok) {
                        const json = await res.json();
                        if (json?.daily && json?.monthly) this.data = json;
                    }
                } catch (e) {
                } finally {
                    this.loading = false;
                }
            }
        });
    }
</script>
