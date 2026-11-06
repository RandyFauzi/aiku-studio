const copyPhoneTasks = value => Object.fromEntries(Object.entries(value || {}).map(([key, tasks]) => [key, [...tasks]]));
const today = () => new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const duration = value => {
    const mins = Number(value) || 0;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return (h > 0 ? h + ' jam ' : '') + m + ' menit (' + mins + 'm)';
};
const labels = { putih: 'Putih', pink: 'Pink', biru: 'Biru', hijau: 'Hijau', hitam: 'Hitam', pink_st: 'Pink ST', sendiri: 'Sendiri' };
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

let reportRewardAudio;
const unlockReportRewardAudio = () => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        reportRewardAudio ??= new AudioContext();
        if (reportRewardAudio.state === 'suspended') reportRewardAudio.resume().catch(() => {});
    } catch { /* Audio is optional and must not interrupt saving. */ }
};
const playReportCoin = () => {
    try {
        const context = reportRewardAudio;
        if (!context || context.state !== 'running') return;
        [988, 1976].forEach((frequency, index) => {
            const start = context.currentTime + index * 0.09;
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(frequency, start);
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(0.07, start + 0.008);
            gain.gain.exponentialRampToValueAtTime(0.001, start + 0.16);
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
            oscillator.start(start);
            oscillator.stop(start + 0.17);
        });
    } catch { /* Unsupported or blocked audio leaves the success popup intact. */ }
};

window.dailyWorkReportData = () => ({
    date: today(),
    month: today().slice(0, 7),
    phones: [],
    phoneDurations: {},
    phoneEmails: {},
    phoneTasks: {},
    options: labels,
    revision: 0,
    reports: [],
    todayReports: [],
    todayTotalMinutes: 0,
    activeReportId: null,
    shiftLabel: '',
    notes: '',
    reportFormOpen: false,
    loading: true,
    saving: false,
    error: '',
    message: '',
    baseline: '',
    duration,
    phoneNames,
    phoneDurationsText,
    dateLabel,

    reportTimestamp(value) {
        if (!value) return '—';
        const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value) ? value.replace(' ', 'T') + 'Z' : value;
        const d = new Date(normalized);
        if (Number.isNaN(d.getTime())) return value;
        return d.toLocaleTimeString('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit' }) + ' WITA';
    },

    get totalMinutes() {
        return this.phones.reduce((sum, key) => sum + (Number(this.phoneDurations[key]) || 0), 0);
    },

    get isComplete() {
        return this.phones.length > 0 && this.phones.every(k => (Number(this.phoneDurations[k]) || 0) > 0 && (this.phoneTasks[k] || []).length > 0);
    },

    get dirty() {
        if (this.baseline === '') return false;
        const current = JSON.stringify([[...this.phones].sort(), this.phoneDurations, this.phoneEmails, this.phoneTasks, this.shiftLabel]);
        return current !== this.baseline;
    },

    suggestShiftLabel() {
        const count = (this.todayReports || []).length;
        if (count === 0) return 'Shift 1 (Pagi)';
        if (count === 1) return 'Shift 2 (Malam)';
        return 'Sesi #' + (count + 1);
    },

    openNewReport(customShift = '') {
        this.activeReportId = null;
        this.shiftLabel = customShift || this.suggestShiftLabel();
        this.notes = '';
        this.phones = [];
        this.phoneDurations = {};
        this.phoneEmails = {};
        this.phoneTasks = {};
        this.revision = 0;
        this.baseline = JSON.stringify([[], {}, {}, {}, this.shiftLabel]);
        this.error = '';
        this.message = '';
        this.reportFormOpen = true;
    },

    editReport(report) {
        if (!report) return;
        this.activeReportId = report.id;
        this.shiftLabel = report.shift_label || '';
        this.notes = report.notes || '';
        this.phones = [...(report.phones || [])];
        this.phoneDurations = { ...(report.phone_durations || {}) };
        this.phoneEmails = { ...(report.phone_emails || {}) };
        this.phoneTasks = copyPhoneTasks(report.phone_tasks);

        if (this.phones.length > 0 && (!report.phone_durations || Object.keys(report.phone_durations).length === 0)) {
            const splitMins = Math.round((report.total_duration_minutes || 0) / this.phones.length);
            this.phones.forEach(pk => {
                if (!this.phoneDurations[pk]) this.phoneDurations[pk] = splitMins;
            });
        }

        this.revision = report.revision || 0;
        this.baseline = JSON.stringify([[...this.phones].sort(), this.phoneDurations, this.phoneEmails, this.phoneTasks, this.shiftLabel]);
        this.error = '';
        this.message = '';
        this.reportFormOpen = true;
    },

    async deleteReport(report) {
        if (!report) return;
        const name = report.shift_label ? ('"' + report.shift_label + '"') : ('Laporan #' + report.id);
        if (!window.confirm('Hapus ' + name + ' (' + report.total_duration_minutes + ' menit)?')) return;
        this.loading = true;
        this.error = '';
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/me/work-reports/' + report.id, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menghapus laporan.');
            if (this.activeReportId === report.id) {
                this.reportFormOpen = false;
                this.activeReportId = null;
            }
            await this.load(false);
        } catch (e) {
            this.error = e.message;
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: e.message });
            }
        } finally {
            this.loading = false;
        }
    },

    togglePhone(key) {
        if (this.phones.includes(key)) {
            this.phones = this.phones.filter(k => k !== key);
            delete this.phoneDurations[key];
            delete this.phoneEmails[key];
            delete this.phoneTasks[key];
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
            this.reports = data.reports || [];
            this.todayReports = data.today_reports || (data.today_report ? [data.today_report] : []);
            this.todayTotalMinutes = data.today_total_minutes ?? this.todayReports.reduce((s, r) => s + (r.total_duration_minutes || 0), 0);
            this.options = data.phone_options || labels;
            this.date = data.today;

            if (reset) {
                if (this.activeReportId) {
                    const existing = this.todayReports.find(r => r.id === this.activeReportId);
                    if (existing) {
                        this.editReport(existing);
                    } else {
                        this.activeReportId = null;
                        this.reportFormOpen = false;
                    }
                }
            }

            const hasSubmitted = this.todayReports.length > 0;
            window.dispatchEvent(new CustomEvent('report-status-updated', {
                detail: { submitted: hasSubmitted, totalReports: this.todayReports.length, totalMinutes: this.todayTotalMinutes }
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
        if (this.activeReportId) {
            const existing = this.todayReports.find(r => r.id === this.activeReportId);
            if (existing) this.editReport(existing);
        } else {
            this.openNewReport();
        }
    },

    async submit() {
        if (this.loading || this.saving || !this.isComplete) return;
        unlockReportRewardAudio();
        this.saving = true;
        this.error = '';
        this.message = '';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const payload = {
                work_date: this.date,
                total_duration_minutes: this.totalMinutes,
                phones: this.phones,
                phone_durations: this.phoneDurations,
                phone_emails: this.phoneEmails,
                phone_tasks: this.phoneTasks,
                shift_label: this.shiftLabel,
                notes: this.notes,
                expected_revision: this.revision
            };

            if (this.activeReportId) {
                payload.report_id = this.activeReportId;
            } else {
                payload.is_new = true;
            }

            const response = await fetch('/api/me/work-report', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Laporan belum tersimpan.');
            }

            const report = data.report;
            this.revision = report.revision;
            this.activeReportId = report.id;
            this.shiftLabel = report.shift_label || this.shiftLabel;
            this.phones = [...(report.phones || [])];
            this.phoneDurations = { ...(report.phone_durations || this.phoneDurations) };
            this.phoneEmails = { ...(report.phone_emails || {}) };
            this.phoneTasks = copyPhoneTasks(report.phone_tasks);
            this.baseline = JSON.stringify([[...this.phones].sort(), this.phoneDurations, this.phoneEmails, this.phoneTasks, this.shiftLabel]);
            this.message = 'Laporan kerja ' + (report.shift_label ? ('(' + report.shift_label + ') ') : '') + 'berhasil disimpan (' + this.totalMinutes + ' menit).';

            this.reportFormOpen = false;

            window.dispatchEvent(new CustomEvent('report-status-updated', {
                detail: { submitted: true, revision: this.revision }
            }));

            if (window.Swal) {
                window.Swal.fire({
                    imageUrl: '/assets/object/Gif%20Terimakasih.webp',
                    imageWidth: 280,
                    imageAlt: 'Terimakasih sudah berkontribusi',
                    html: '<div style="padding: 6px 12px 2px;"><p style="font-size: 15px; font-weight: 700; color: #1e293b; line-height: 1.55; margin: 0;">Terimakasih sudah berkontribusi untuk masa depan hari ini, semangat rekaman!</p></div>',
                    confirmButtonText: 'Sama-sama',
                    confirmButtonColor: '#00B090',
                    didOpen: playReportCoin,
                    customClass: {
                        popup: 'rounded-[32px] p-6 shadow-2xl border border-slate-100',
                        image: 'rounded-2xl mx-auto object-contain max-w-full',
                        confirmButton: 'rounded-full px-8 py-2.5 text-xs font-black tracking-wide shadow-sm'
                    }
                });
            } else {
                playReportCoin();
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
    reportTimestamp(value) {
        if (!value) return '—';
        const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value) ? value.replace(' ', 'T') + 'Z' : value;
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) return '—';
        return new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).format(date).replace(/(\d{2})\.(\d{2})$/, '$1:$2') + ' WITA';
    },
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
        pink_st: 'Pink ST', sendiri: 'Sendiri'
    },
    phonesInfo: {},
    detailModalOpen: false,
    selectedReport: null,
    verifyingStreak: false,
    streakReviewMessage: '',
    streakReviewError: '',

    getPhoneInfo(pkey) {
        return pkey === 'sendiri' ? null : this.phonesInfo[pkey] || null;
    },

    formatPhoneName(pkey) {
        if (!pkey) return 'HP';
        const clean = String(pkey).toLowerCase().trim();
        if (this.phoneOptions && this.phoneOptions[clean]) {
            return 'HP ' + this.phoneOptions[clean];
        }
        const map = {
            putih: 'Putih',
            pink: 'Pink',
            biru: 'Biru',
            hijau: 'Hijau',
            hitam: 'Hitam',
            pink_st: 'Pink ST', sendiri: 'Sendiri'
        };
        return 'HP ' + (map[clean] || (clean.charAt(0).toUpperCase() + clean.slice(1)));
    },

    getReportPhoneEmail(report, pkey) {
        if (report?.phone_emails && report.phone_emails[pkey]) {
            return report.phone_emails[pkey];
        }
        const pInfo = this.getPhoneInfo(pkey);
        return pInfo?.active_email || '';
    },

    getReportPhoneAccount(report, pkey) {
        if (report?.phone_emails && report.phone_emails[pkey]) {
            return report.phone_emails[pkey];
        }
        const pInfo = this.getPhoneInfo(pkey);
        return pInfo?.active_account_name || pInfo?.active_email || '';
    },

    isPhoneEmailOverridden(report, pkey) {
        return Boolean(report?.phone_emails && report.phone_emails[pkey]);
    },

    openDetailModal(report) {
        this.streakReviewMessage = '';
        this.streakReviewError = '';
        this.selectedReport = report;
        this.detailModalOpen = true;
    },

    async verifyRecordStreak() {
        if (!this.selectedReport || this.verifyingStreak) return;
        const answer = await window.Swal.fire({title:'Verifikasi Daily Record?',text:'Pastikan bukti rekaman laporan ini sudah diperiksa. Ini hanya mengesahkan streak, tidak mengubah QA atau payroll.',icon:'question',showCancelButton:true,confirmButtonText:'Sudah diperiksa',cancelButtonText:'Batal'});
        if (!answer.isConfirmed) return;
        this.verifyingStreak = true; this.streakReviewError = ''; this.streakReviewMessage = '';
        try {
            const response = await fetch('/api/work-reports/'+this.selectedReport.id+'/streak-verify', {method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify({expected_revision:this.selectedReport.revision})});
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Verifikasi belum tersimpan.');
            this.streakReviewMessage = 'Rekaman disahkan untuk Daily Record. Perubahan isi laporan memerlukan verifikasi ulang.';
        } catch(e) { this.streakReviewError = e.message; } finally { this.verifyingStreak = false; }
    },

    closeDetailModal() {
        this.detailModalOpen = false;
        this.selectedReport = null;
    },
    createModalOpen: false,
    contributorsList: [],
    createForm: {
        contributor_id: '',
        work_date: today(),
        phones: [],
        phone_durations: {},
        phone_tasks: {},
        phone_emails: {}
    },
    createError: '',
    savingCreate: false,

    async fetchContributors() {
        try {
            const res = await fetch('/api/contributors');
            if (res.ok) {
                const data = await res.json();
                this.contributorsList = Array.isArray(data) ? data : (data.contributors || []);
            }
        } catch (e) {
            console.error('Gagal memuat kontributor', e);
        }
    },

    openCreateModal() {
        this.createError = '';
        const initialEmails = {};
        const pKeys = this.phoneOptions ? Object.keys(this.phoneOptions) : ['putih', 'pink', 'biru', 'hijau', 'hitam', 'pink_st'];
        pKeys.forEach(pk => {
            const pInfo = this.getPhoneInfo(pk);
            initialEmails[pk] = pInfo?.active_email || '';
        });
        this.createForm = {
            contributor_id: '',
            work_date: this.date || today(),
            shift_label: 'Shift 1 (Pagi)',
            phones: [],
            phone_durations: {},
            phone_tasks: {},
            phone_emails: initialEmails
        };
        if (!this.contributorsList.length) {
            this.fetchContributors();
        }
        this.createModalOpen = true;
    },

    closeCreateModal() {
        this.createModalOpen = false;
        this.createError = '';
    },

    toggleCreatePhone(key) {
        if (this.createForm.phones.includes(key)) {
            this.createForm.phones = this.createForm.phones.filter(p => p !== key);
            delete this.createForm.phone_durations[key];
            delete this.createForm.phone_tasks[key];
        } else {
            this.createForm.phones.push(key);
            this.createForm.phone_durations[key] = this.createForm.phone_durations[key] || 60;
            if (!this.createForm.phone_emails[key]) {
                const pInfo = this.getPhoneInfo(key);
                this.createForm.phone_emails[key] = pInfo?.active_email || '';
            }
        }
    },

    setCreateMinutes(key, val) {
        const mins = parseInt(val) || 0;
        if (mins > 0) {
            if (!this.createForm.phones.includes(key)) {
                this.createForm.phones.push(key);
            }
            this.createForm.phone_durations[key] = mins;
        } else {
            this.createForm.phone_durations[key] = '';
        }
    },

    calcCreateTotal() {
        let sum = 0;
        for (const pk of this.createForm.phones) {
            sum += parseInt(this.createForm.phone_durations[pk]) || 0;
        }
        return sum;
    },

    async saveCreateReport() {
        if (this.savingCreate) return;
        if (!this.createForm.contributor_id) {
            this.createError = 'Pilih kontributor terlebih dahulu.';
            return;
        }
        if (!this.createForm.work_date) {
            this.createError = 'Pilih tanggal kerja laporan.';
            return;
        }
        const total = this.calcCreateTotal();
        if (total <= 0 || !this.createForm.phones.length) {
            this.createError = 'Pilih minimal satu HP dan tentukan durasi menitnya.';
            return;
        }
        this.savingCreate = true;
        this.createError = '';
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/work-reports', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    contributor_id: parseInt(this.createForm.contributor_id),
                    work_date: this.createForm.work_date,
                    shift_label: this.createForm.shift_label || null,
                    allow_multiple: true,
                    is_new: true,
                    phones: this.createForm.phones,
                    phone_durations: this.createForm.phone_durations,
                    phone_emails: this.createForm.phone_emails,
                    phone_tasks: this.createForm.phone_tasks,
                    total_duration_minutes: total
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Gagal membuat laporan');

            const targetDate = this.createForm.work_date;
            this.closeCreateModal();

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Laporan Berhasil Dibuat',
                    text: data.message || 'Laporan kerja berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            if (this.date === targetDate) {
                await this.load();
            } else {
                this.date = targetDate;
                await this.load();
            }
        } catch (e) {
            this.createError = e.message;
        } finally {
            this.savingCreate = false;
        }
    },

    editModalOpen: false,
    editingReport: null,
    editForm: {
        phones: [],
        phone_durations: {},
        phone_tasks: {},
        phone_emails: {}
    },
    editError: '',
    savingEdit: false,
    deletingId: null,

    openEditModal(report) {
        this.editingReport = report;
        this.editError = '';
        const phones = Array.isArray(report.phones) ? [...report.phones] : [];
        const durations = {};
        const emails = {};
        if (report.phone_durations && typeof report.phone_durations === 'object') {
            for (const [k, v] of Object.entries(report.phone_durations)) {
                durations[k] = parseInt(v) || 0;
            }
        } else if (phones.length) {
            const split = Math.round((report.total_duration_minutes || 0) / phones.length);
            phones.forEach(p => { durations[p] = split; });
        }

        const pKeys = this.phoneOptions ? Object.keys(this.phoneOptions) : ['putih', 'pink', 'biru', 'hijau', 'hitam', 'pink_st'];
        pKeys.forEach(p => {
            if (report.phone_emails && report.phone_emails[p]) {
                emails[p] = report.phone_emails[p];
            } else {
                const pInfo = this.getPhoneInfo(p);
                emails[p] = pInfo?.active_email || '';
            }
        });

        this.editForm = {
            shift_label: report.shift_label || '',
            phones: phones,
            phone_durations: durations,
            phone_tasks: copyPhoneTasks(report.phone_tasks),
            phone_emails: emails
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
            delete this.editForm.phone_tasks[key];
        } else {
            this.editForm.phones.push(key);
            this.editForm.phone_durations[key] = this.editForm.phone_durations[key] || 60;
            if (!this.editForm.phone_emails[key]) {
                const pInfo = this.getPhoneInfo(key);
                this.editForm.phone_emails[key] = pInfo?.active_email || '';
            }
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
                    shift_label: this.editForm.shift_label || null,
                    phones: this.editForm.phones,
                    phone_durations: this.editForm.phone_durations,
                    phone_emails: this.editForm.phone_emails,
                    phone_tasks: this.editForm.phone_tasks,
                    total_duration_minutes: total
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyimpan perubahan laporan');

            const idx = this.rows.findIndex(r => r.id === this.editingReport.id);
            if (idx !== -1 && data.report) {
                this.rows[idx] = data.report;
            }
            if (this.selectedReport && this.selectedReport.id === this.editingReport.id && data.report) {
                this.selectedReport = data.report;
            }
            this.total = this.rows.reduce((s, r) => s + (parseInt(r.total_duration_minutes) || 0), 0);
            this.closeEditModal();
            this.reloadAssistOnly();

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
            if (repData.phone_options) {
                this.phoneOptions = repData.phone_options;
            }
            if (repData.phones_info) {
                this.phonesInfo = repData.phones_info;
            }
            if (repData.contributors && Array.isArray(repData.contributors)) {
                this.contributorsList = repData.contributors;
            } else if (!this.contributorsList.length) {
                this.fetchContributors();
            }

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
