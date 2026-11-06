import Alpine from 'alpinejs';

Alpine.data('recordingMatching', () => ({
    open: true, loading: false, saving: false, error: '', message: '', groups: [], reports: [], selected: [],
    recordingDate: '', reportDate: '', email: '', target: '', reason: '', page: 1, lastPage: 1, total: 0, requestId: 0,
    init() {
        const date = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
        this.recordingDate = date; this.reportDate = date;
        this.load();
    },
    get options() {
        return this.reports.flatMap(report => report.phones.map(phone => ({ report, phone, key: `${report.id}:${phone.key}` })));
    },
    get owner() { return this.options.find(option => option.key === this.target); },
    get chosen() { return this.groups.flatMap(group => group.recordings).filter(record => this.selected.includes(record.record_id)); },
    get selectedMinutes() { return this.chosen.reduce((sum, record) => sum + Number(record.duration_min || 0), 0).toFixed(2); },
    get eligible() {
        const emails = this.owner?.phone.emails || [];
        return this.chosen.every(record => emails.includes(record.participant_email.trim().toLowerCase()));
    },
    get canConfirm() {
        return !this.loading && !this.saving && this.owner?.report.can_confirm && this.selected.length > 0 && this.eligible && this.reason.trim().length >= 8;
    },
    async json(url, options = {}) {
        const response = await fetch(url, { credentials: 'same-origin', ...options, headers: {
            Accept: 'application/json', 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', ...options.headers,
        } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Permintaan gagal. Coba muat ulang.');
        return data;
    },
    async load(reset = true) {
        const requestId = ++this.requestId;
        if (reset) this.page = 1;
        this.selected = []; this.error = ''; this.loading = true;
        try {
            const params = new URLSearchParams({ recording_date: this.recordingDate, report_date: this.reportDate, page: this.page, email: this.email });
            const data = await this.json(`/api/recording-matching?${params}`);
            if (requestId !== this.requestId) return;
            this.groups = data.groups; this.reports = data.reports; this.total = data.total; this.lastPage = data.last_page;
            if (!this.owner) this.target = '';
        } catch (error) {
            if (requestId === this.requestId) { this.error = error.message; this.groups = []; this.reports = []; }
        } finally { if (requestId === this.requestId) this.loading = false; }
    },
    chooseTarget(key) { this.target = key; this.selected = []; this.reason = ''; this.message = ''; },
    selectable(record) {
        return !record.assigned_contributor_id && !record.assigned_worker_name?.trim() && record.manual_payment_status !== 'Paid'
            && !!this.owner?.phone.emails.includes(record.participant_email.trim().toLowerCase());
    },
    toggle(record) {
        if (!this.selectable(record) || this.saving) return;
        this.selected = this.selected.includes(record.record_id) ? this.selected.filter(id => id !== record.record_id) : [...this.selected, record.record_id];
    },
    async confirmOwner() {
        if (!this.canConfirm) return;
        const owner = this.owner;
        const result = await window.Swal.fire({ title: 'Konfirmasi pemilik recording?',
            text: `${this.selected.length} recording (${this.selectedMinutes} menit) → ${owner.report.name}, laporan ${owner.report.work_date}, ${owner.phone.label}. Penandaan ini memengaruhi penghasilan.`,
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, konfirmasi pemilik', cancelButtonText: 'Periksa lagi', confirmButtonColor: '#315a27' });
        if (!result.isConfirmed || !this.canConfirm) return;
        this.saving = true; this.error = ''; this.message = '';
        try {
            const data = await this.json('/api/recording-matching/confirm', { method: 'POST', body: JSON.stringify({
                report_id: owner.report.id, expected_revision: owner.report.revision, phone_key: owner.phone.key,
                record_ids: this.selected, reason: this.reason.trim(),
            }) });
            this.message = `${data.updated} recording dikonfirmasi milik ${owner.report.name}.`;
            this.reason = ''; await this.load(false);
            window.dispatchEvent(new CustomEvent('recording-owner-confirmed'));
        } catch (error) { this.error = error.message; }
        finally { this.saving = false; }
    },
    time(value) {
        if (!value) return '—';
        const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value) ? value.replace(' ', 'T') + 'Z' : value;
        return new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(normalized));
    },
}));
