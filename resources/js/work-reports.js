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
                    imageUrl: '/assets/object/Gif%20Terimakasih.webp',
                    imageWidth: 280,
                    imageAlt: 'Terimakasih sudah berkontribusi',
                    html: '<div style="padding: 6px 12px 2px;"><p style="font-size: 15px; font-weight: 700; color: #1e293b; line-height: 1.55; margin: 0;">Terimakasih sudah berkontribusi untuk masa depan hari ini, semangat rekaman besok!</p></div>',
                    confirmButtonText: 'Sama-sama',
                    confirmButtonColor: '#00B090',
                    customClass: {
                        popup: 'rounded-[32px] p-6 shadow-2xl border border-slate-100',
                        image: 'rounded-2xl mx-auto object-contain max-w-full',
                        confirmButton: 'rounded-full px-8 py-2.5 text-xs font-black tracking-wide shadow-sm'
                    }
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
    phoneOptions: {
        putih: 'Putih',
        pink: 'Pink',
        biru: 'Biru',
        hijau: 'Hijau',
        hitam: 'Hitam',
        pink_st: 'Pink ST'
    },
    editModalOpen: false,
    editingReport: null,
    editForm: {
        phones: [],
        phone_durations: {}
    },
    editError: '',
    savingEdit: false,
    deletingId: null,

    openEditModal(report) {
        this.editingReport = report;
        this.editError = '';
        const phones = Array.isArray(report.phones) ? [...report.phones] : [];
        const durations = {};
        if (report.phone_durations && typeof report.phone_durations === 'object') {
            for (const [k, v] of Object.entries(report.phone_durations)) {
                durations[k] = parseInt(v) || 0;
            }
        } else if (phones.length) {
            const split = Math.round((report.total_duration_minutes || 0) / phones.length);
            phones.forEach(p => { durations[p] = split; });
        }
        this.editForm = {
            phones: phones,
            phone_durations: durations
        };
        this.editModalOpen = true;
    },

    closeEditModal() {
        this.editModalOpen = false;
        this.editingReport = null;
        this.editError = '';
    },

    toggleEditPhone(key) {
        if (this.editForm.phones.includes(key)) {
            this.editForm.phones = this.editForm.phones.filter(p => p !== key);
            delete this.editForm.phone_durations[key];
        } else {
            this.editForm.phones.push(key);
            this.editForm.phone_durations[key] = this.editForm.phone_durations[key] || 60;
        }
    },

    calcEditTotal() {
        let sum = 0;
        for (const pk of this.editForm.phones) {
            sum += parseInt(this.editForm.phone_durations[pk]) || 0;
        }
        return sum;
    },

    async saveEditReport() {
        if (!this.editingReport || this.savingEdit) return;
        const total = this.calcEditTotal();
        if (total <= 0 || !this.editForm.phones.length) {
            this.editError = 'Pilih minimal satu HP dan isi menit durasinya.';
            return;
        }
        this.savingEdit = true;
        this.editError = '';
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/work-reports/' + this.editingReport.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    phones: this.editForm.phones,
                    phone_durations: this.editForm.phone_durations,
                    total_duration_minutes: total
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyimpan perubahan laporan');

            const idx = this.rows.findIndex(r => r.id === this.editingReport.id);
            if (idx !== -1 && data.report) {
                this.rows[idx] = data.report;
            }
            this.total = this.rows.reduce((s, r) => s + (parseInt(r.total_duration_minutes) || 0), 0);
            this.closeEditModal();

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Diperbarui',
                    text: 'Laporan kerja berhasil diperbarui.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            await this.reloadAssistOnly();
        } catch (e) {
            this.editError = e.message;
        } finally {
            this.savingEdit = false;
        }
    },

    async confirmDeleteReport(report) {
        if (!report || this.deletingId) return;
        const confirmMsg = `Hapus laporan kerja ${report.contributor_name} (${report.total_duration_minutes} menit)? Tindakan ini tidak dapat dibatalkan.`;
        
        let confirmed = false;
        if (window.Swal) {
            const result = await window.Swal.fire({
                title: 'Hapus Laporan Kerja?',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });
            confirmed = result.isConfirmed;
        } else {
            confirmed = window.confirm(confirmMsg);
        }

        if (!confirmed) return;

        this.deletingId = report.id;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/work-reports/' + report.id, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menghapus laporan');

            this.rows = this.rows.filter(r => r.id !== report.id);
            this.total = this.rows.reduce((s, r) => s + (parseInt(r.total_duration_minutes) || 0), 0);
            if (this.expandedReportId === report.id) {
                this.expandedReportId = null;
            }

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Terhapus',
                    text: 'Laporan kerja berhasil dihapus.',
                    timer: 1800,
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
            this.deletingId = null;
        }
    },
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
