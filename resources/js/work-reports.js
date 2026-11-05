const today = () => new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const duration = value => {
    const mins = Number(value) || 0;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return (h > 0 ? h + ' jam ' : '') + m + ' menit (' + mins + 'm)';
};
const labels = { putih: 'Putih', pink: 'Pink', biru: 'Biru', hijau: 'Hijau', hitam: 'Hitam', pink_st: 'Pink ST' };
const phoneNames = phones => (phones || []).map(phone => labels[phone] || phone).join(', ');
const phoneDurationsText = report => {
    if (report.phone_durations && Object.keys(report.phone_durations).length > 0) {
        return Object.entries(report.phone_durations)
            .map(([k, m]) => (labels[k] || k) + ': ' + m + 'm')
            .join(' · ');
    }
    return phoneNames(report.phones);
};
const dateLabel = date => new Date(date + 'T12:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

window.dailyWorkReportData = () => ({
    date: today(),
    month: today().slice(0, 7),
    phones: [],
    phoneDurations: {},
    options: labels,
    revision: 0,
    reports: [],
    loading: true,
    saving: false,
    error: '',
    message: '',
    baseline: '',
    duration,
    phoneNames,
    phoneDurationsText,
    dateLabel,

    get totalMinutes() {
        return this.phones.reduce((sum, key) => sum + (Number(this.phoneDurations[key]) || 0), 0);
    },

    get isComplete() {
        return this.phones.length > 0 && this.phones.every(k => (Number(this.phoneDurations[k]) || 0) > 0);
    },

    get dirty() {
        if (this.baseline === '') return false;
        const current = JSON.stringify([[...this.phones].sort(), this.phoneDurations]);
        return current !== this.baseline;
    },

    togglePhone(key) {
        if (this.phones.includes(key)) {
            this.phones = this.phones.filter(k => k !== key);
            delete this.phoneDurations[key];
        } else {
            this.phones.push(key);
            if (!this.phoneDurations[key] || Number(this.phoneDurations[key]) <= 0) {
                this.phoneDurations[key] = 60; // Default 60 menit per HP
            }
        }
    },

    setMinutes(key, val) {
        const num = Math.max(0, parseInt(val, 10) || 0);
        this.phoneDurations[key] = num;
    },

    addMinutes(key, delta) {
        const cur = Number(this.phoneDurations[key]) || 0;
        this.phoneDurations[key] = Math.max(0, cur + delta);
    },

    async init() {
        await this.load(true);
        window.addEventListener('beforeunload', event => {
            if (this.dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },

    async load(reset = false) {
        this.loading = true;
        this.error = '';
        try {
            const response = await fetch('/api/me/work-reports?month=' + this.month, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Laporan belum berhasil dimuat.');
            const data = await response.json();
            this.reports = data.reports;
            this.options = data.phone_options || labels;

            if (reset) {
                const report = data.today_report;
                this.date = data.today;
                this.phones = [...(report?.phones || [])];
                this.phoneDurations = {};

                if (report?.phone_durations && typeof report.phone_durations === 'object') {
                    this.phoneDurations = { ...report.phone_durations };
                } else if (this.phones.length > 0 && report?.total_duration_minutes) {
                    const splitMins = Math.round(report.total_duration_minutes / this.phones.length);
                    this.phones.forEach(pk => {
                        this.phoneDurations[pk] = splitMins;
                    });
                }

                this.revision = report?.revision || 0;
                this.baseline = JSON.stringify([[...this.phones].sort(), this.phoneDurations]);
            }

            const isSubmitted = !!(data.today_report && Number(data.today_report.revision) > 0);
            window.dispatchEvent(new CustomEvent('report-status-updated', {
                detail: { submitted: isSubmitted, revision: data.today_report?.revision || 0 }
            }));
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },

    async reload() {
        if (this.dirty && !window.confirm('Muat ulang dan buang perubahan laporan yang belum disimpan?')) return;
        this.message = '';
        await this.load(true);
    },

    async submit() {
        if (this.loading || this.saving || !this.isComplete) return;
        this.saving = true;
        this.error = '';
        this.message = '';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch('/api/me/work-report', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    work_date: this.date,
                    total_duration_minutes: this.totalMinutes,
                    phones: this.phones,
                    phone_durations: this.phoneDurations,
                    expected_revision: this.revision
                })
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Laporan belum tersimpan.');
            }

            const report = data.report;
            this.revision = report.revision;
            this.phones = [...(report.phones || [])];
            this.phoneDurations = { ...(report.phone_durations || this.phoneDurations) };
            this.baseline = JSON.stringify([[...this.phones].sort(), this.phoneDurations]);
            this.message = 'Laporan kerja tanggal ' + dateLabel(this.date) + ' berhasil disimpan (' + this.totalMinutes + ' menit).';

            window.dispatchEvent(new CustomEvent('report-status-updated', {
                detail: { submitted: true, revision: this.revision }
            }));

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Laporan Tersimpan!',
                    text: `Total ${this.totalMinutes} menit pada ${this.phones.length} HP berhasil dicatat.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            await this.load(false);
        } catch (e) {
            this.error = e.message;
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: e.message });
            }
        } finally {
            this.saving = false;
        }
    },
});

window.adminWorkReportsData = () => ({
    date: today(),
    rows: [],
    search: '',
    loading: true,
    assistLoading: false,
    assistData: [],
    expandedReportId: null,
    expandedPhoneKey: null,
    error: '',
    total: 0,
    assigning: false,
    duration,
    phoneNames,
    phoneDurationsText,
    dateLabel,

    async init() {
        await this.load();
    },

    async load() {
        this.loading = true;
        this.error = '';
        try {
            const [repRes, assistRes] = await Promise.all([
                fetch('/api/work-reports?date=' + this.date),
                fetch('/api/work-reports/assist-matches?date=' + this.date)
            ]);

            if (!repRes.ok) throw new Error('Rekap laporan belum berhasil dimuat.');
            const repData = await repRes.json();
            this.rows = repData.reports || [];
            this.total = repData.total_duration_minutes || 0;

            if (assistRes.ok) {
                const assistJson = await assistRes.json();
                this.assistData = assistJson.assist_data || [];
            }
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },

    async reloadAssistOnly() {
        this.assistLoading = true;
        try {
            const assistRes = await fetch('/api/work-reports/assist-matches?date=' + this.date);
            if (assistRes.ok) {
                const assistJson = await assistRes.json();
                this.assistData = assistJson.assist_data || [];
            }
        } catch (e) {
            console.error('Assist reload error:', e);
        } finally {
            this.assistLoading = false;
        }
    },

    getAssistForReport(reportId) {
        return this.assistData.find(a => a.report_id === reportId) || null;
    },

    toggleExpandReport(reportId) {
        this.expandedReportId = this.expandedReportId === reportId ? null : reportId;
    },

    async assignCandidates(contributorId, workerName, recordIds) {
        if (!recordIds || !recordIds.length || this.assigning) return;
        this.assigning = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/work-reports/assist-assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    contributor_id: contributorId,
                    worker_name: workerName,
                    record_ids: recordIds
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menandai rekaman');

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Ditandai!',
                    text: `${data.updated_count} rekaman berhasil ditugaskan ke ${workerName}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            await this.reloadAssistOnly();
        } catch (e) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Gagal', text: e.message });
            } else {
                alert(e.message);
            }
        } finally {
            this.assigning = false;
        }
    },

    async unassignRecords(recordIds) {
        if (!recordIds || !recordIds.length || this.assigning) return;
        if (!window.confirm(`Lepas penugasan ${recordIds.length} rekaman ini kembali menjadi unassigned?`)) return;

        this.assigning = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/work-reports/assist-unassign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ record_ids: recordIds })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal membatalkan penugasan');

            await this.reloadAssistOnly();
        } catch (e) {
            alert(e.message);
        } finally {
            this.assigning = false;
        }
    },

    get filtered() {
        return this.rows.filter(row => row.contributor_name.toLowerCase().includes(this.search.toLowerCase()));
    },
});
