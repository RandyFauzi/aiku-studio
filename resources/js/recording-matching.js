import Alpine from 'alpinejs';

Alpine.data('recordingMatching', () => ({
    open: false, loading: false, saving: false, error: '', reports: [], selected: [], chosen: [],
    reportDate: '', target: '', reason: '', requestId: 0, returnFocus: null,
    get options() {
        return this.reports.flatMap(report => report.phones.map(phone => ({ report, phone, key: `${report.id}:${phone.key}` })))
            .filter(option => this.chosen.every(record => option.phone.emails.includes(record.participant_email.trim().toLowerCase())));
    },
    get owner() { return this.options.find(option => option.key === this.target); },
    get selectedMinutes() { return this.chosen.reduce((sum, record) => sum + Number(record.duration_min || 0), 0).toFixed(2); },
    get unmatchedTasks() {
        const key = title => (title || '').trim().replace(/\s+/g, ' ').toLowerCase();
        const tasks = (this.owner?.phone.tasks || []).map(key);
        return tasks.length ? [...new Set(this.chosen.filter(record => !tasks.includes(key(record.task_title))).map(record => record.task_title || 'Tanpa judul'))] : [];
    },
    get canConfirm() {
        return !this.loading && !this.saving && this.owner?.report.can_confirm && this.selected.length > 0
            && this.selected.length === this.chosen.length && this.reason.trim().length >= 8
            && this.chosen.every(record => !record.assigned_contributor_id && !record.assigned_worker_name?.trim() && record.manual_payment_status !== 'Paid');
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
    async openFor(detail) {
        this.returnFocus = document.activeElement;
        this.selected = [...detail.ids]; this.chosen = detail.records; this.target = ''; this.reason = ''; this.error = '';
        this.reportDate = this.chosen[0]?.shift_group?.work_date || new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
        this.open = true;
        this.$nextTick(() => this.$refs.reportDate.focus());
        await this.load();
    },
    close() { if (!this.saving) { this.open = false; ++this.requestId; this.returnFocus?.focus(); } },
    trap(event) {
        if (event.key !== 'Tab') return;
        const nodes = [...this.$refs.dialog.querySelectorAll('button,input,select,textarea')].filter(el => !el.disabled && el.getClientRects().length);
        const first = nodes[0], last = nodes[nodes.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    },
    async load() {
        const requestId = ++this.requestId; this.error = ''; this.loading = true; this.target = ''; this.reports = [];
        try {
            const params = new URLSearchParams({ recording_date: this.reportDate, report_date: this.reportDate });
            const data = await this.json(`/api/recording-matching?${params}`);
            if (requestId === this.requestId) this.reports = data.reports;
        } catch (error) { if (requestId === this.requestId) this.error = error.message; }
        finally { if (requestId === this.requestId) this.loading = false; }
    },
    async confirmOwner() {
        if (!this.canConfirm) return;
        const owner = this.owner;
        const result = await window.Swal.fire({ title: 'Konfirmasi pemilik recording?',
            text: `${this.selected.length} recording (${this.selectedMinutes} menit) → ${owner.report.name}, laporan ${owner.report.work_date}, ${owner.phone.label}. Penandaan ini memengaruhi penghasilan.`,
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, konfirmasi pemilik', cancelButtonText: 'Periksa lagi', confirmButtonColor: '#315a27' });
        if (!result.isConfirmed || !this.canConfirm) return;
        this.saving = true; this.error = '';
        try {
            const data = await this.json('/api/recording-matching/confirm', { method: 'POST', body: JSON.stringify({
                report_id: owner.report.id, expected_revision: owner.report.revision, phone_key: owner.phone.key,
                record_ids: this.selected, reason: this.reason.trim(),
            }) });
            this.open = false;
            window.dispatchEvent(new CustomEvent('recording-owner-confirmed'));
            await window.Swal.fire({ icon: 'success', text: `${data.updated} recording dikonfirmasi milik ${owner.report.name}.`, timer: 2000 });
            this.returnFocus?.focus();
        } catch (error) { this.error = error.message; }
        finally { this.saving = false; }
    },
    time(value) {
        if (!value) return '—';
        const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value) ? value.replace(' ', 'T') + 'Z' : value;
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) return '—';
        return new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).format(date).replace(/(\d{2})\.(\d{2})$/, '$1:$2') + ' WITA';
    },
}));
