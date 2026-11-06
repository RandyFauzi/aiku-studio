import Alpine from 'alpinejs';

const dateKey = value => new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(value));

Alpine.data('myRecordings', () => ({
    month: dateKey(new Date()).slice(0, 7), search: '', rows: [], loading: false, error: '', page: 1, lastPage: 1,
    summary: { total_recordings: 0, total_minutes: 0 }, requestId: 0,
    init() { this.load(); },
    get groups() {
        const groups = [];
        for (const row of this.rows) {
            const key = dateKey(row.recorded_at);
            if (!groups.length || groups[groups.length - 1].date !== key) groups.push({ date: key, rows: [] });
            groups[groups.length - 1].rows.push(row);
        }
        return groups;
    },
    async load(reset = true) {
        if (reset) this.page = 1;
        const id = ++this.requestId; this.loading = true; this.error = '';
        try {
            const params = new URLSearchParams({ month: this.month, search: this.search, page: this.page });
            const response = await fetch('/api/me/recordings?' + params, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Riwayat rekaman belum dapat dimuat.');
            if (id !== this.requestId) return;
            this.rows = data.recordings; this.summary = data.summary; this.lastPage = data.pagination.last_page;
        } catch (error) { if (id === this.requestId) { this.rows = []; this.summary = { total_recordings: 0, total_minutes: 0 }; this.error = error.message; } }
        finally { if (id === this.requestId) this.loading = false; }
    },
    minutes(value) { return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' menit'; },
    day(value) { return new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(value + 'T12:00:00+08:00')); },
    at(value) { return new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(value)).replace('.', ':') + ' WITA'; },
    status(value) { return ({ ready_for_review: 'Menunggu pemeriksaan', pending: 'Menunggu pemeriksaan', processing: 'Sedang diproses', processed: 'Selesai diproses', completed: 'Selesai diproses', failed: 'Perlu diperiksa' })[value] || 'Dalam proses'; },
    statusClass(value) {
        if (['processed', 'completed'].includes(value)) return 'bg-emerald-50 text-emerald-800';
        if (['ready_for_review', 'pending'].includes(value)) return 'bg-amber-50 text-amber-800';
        if (value === 'processing') return 'bg-blue-50 text-blue-800';
        if (value === 'failed') return 'bg-red-50 text-red-800';
        return 'bg-slate-100 text-slate-600';
    },
}));
