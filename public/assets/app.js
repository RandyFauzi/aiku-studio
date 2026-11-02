// Formatters
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const formatPeriod = (period) => {
    if (!period) return '-';
    let p = period.replace(/^Training\s*\((.*)\)$/i, '$1');
    // Simplify "18 Sep 2026 - 20 Sep 2026" -> "18 – 20 Sep 2026"
    p = p.replace(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})\s*-\s*(\d{1,2})\s+\2\s+\3/, '$1 – $4 $2 $3');
    return p;
};

// Shared Sidebar Persistence Helper
const getInitialSidebarOpen = () => {
    try {
        const saved = localStorage.getItem('aiku_sidebar_open');
        if (saved !== null) {
            return saved === 'true';
        }
    } catch (e) {}
    return typeof window !== 'undefined' ? window.innerWidth >= 768 : true;
};

// Alpine Data
document.addEventListener('alpine:init', () => {
    
    // Executive & Operations Dashboard Logic
    Alpine.data('executiveDashboardData', () => ({
        sidebarOpen: getInitialSidebarOpen(),
        loading: true,
        syncing: false,
        isExporting: null,
        slips: [],
        recordingsStats: {
            totalRecords: 0,
            totalMinutes: 0,
            billableMinutes: 0,
            rejectedMinutes: 0,
            pendingMinutes: 0,
            assignedCount: 0,
            unassignedCount: 0,
            belumRekapCount: 0
        },
        contributors: [],
        syncStatus: null,
        formatRupiah,
        formatDate,
        formatTime,
        formatPeriod,

        async init() {
            await this.loadAllDashboardData();
        },

        async loadAllDashboardData() {
            this.loading = true;
            try {
                const [slipsRes, recRes, contRes, syncRes] = await Promise.all([
                    fetch('/api/slips').then(r => r.json()).catch(() => []),
                    fetch('/api/studio-recordings?limit=1').then(r => r.json()).catch(() => ({ stats: {} })),
                    fetch('/api/contributors').then(r => r.json()).catch(() => []),
                    fetch('/api/studio-recordings/sync-status').then(r => r.json()).catch(() => null)
                ]);

                this.slips = Array.isArray(slipsRes) ? slipsRes : [];
                if (recRes && recRes.stats) {
                    this.recordingsStats = { ...this.recordingsStats, ...recRes.stats };
                }
                this.contributors = Array.isArray(contRes) ? contRes : [];
                this.syncStatus = syncRes;
            } catch (e) {
                console.error("Failed to load executive dashboard data:", e);
            } finally {
                this.loading = false;
            }
        },

        get totalPayrollPaid() {
            return this.slips.reduce((sum, s) => sum + (parseFloat(s.total_thp) || 0), 0);
        },

        get totalSlipsCount() {
            return this.slips.length;
        },

        get billableHours() {
            return ((this.recordingsStats.billableMinutes || 0) / 60).toFixed(1);
        },

        get totalRecordedHours() {
            return ((this.recordingsStats.totalMinutes || 0) / 60).toFixed(1);
        },

        get rejectedHours() {
            return ((this.recordingsStats.rejectedMinutes || 0) / 60).toFixed(1);
        },

        get pendingHours() {
            return ((this.recordingsStats.pendingMinutes || 0) / 60).toFixed(1);
        },

        get billablePercentage() {
            if (!this.recordingsStats.totalMinutes) return 0;
            return ((this.recordingsStats.billableMinutes / this.recordingsStats.totalMinutes) * 100).toFixed(1);
        },

        get rejectedPercentage() {
            if (!this.recordingsStats.totalMinutes) return 0;
            return ((this.recordingsStats.rejectedMinutes / this.recordingsStats.totalMinutes) * 100).toFixed(1);
        },

        get pendingPercentage() {
            if (!this.recordingsStats.totalMinutes) return 0;
            return ((this.recordingsStats.pendingMinutes / this.recordingsStats.totalMinutes) * 100).toFixed(1);
        },

        get assignedPercentage() {
            if (!this.recordingsStats.totalRecords) return 0;
            return ((this.recordingsStats.assignedCount / this.recordingsStats.totalRecords) * 100).toFixed(1);
        },

        get recentSlips() {
            return [...this.slips]
                .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
                .slice(0, 5);
        },

        get topContributors() {
            const workerMap = {};
            this.slips.forEach(s => {
                const name = s.employee_name || 'Unknown';
                if (!workerMap[name]) {
                    workerMap[name] = {
                        name,
                        approvedHours: 0,
                        loggedHours: 0,
                        thp: 0,
                        slipCount: 0
                    };
                }
                workerMap[name].approvedHours += (parseFloat(s.total_approved_hours) || 0);
                workerMap[name].loggedHours += (parseFloat(s.total_logged_hours) || 0);
                workerMap[name].thp += (parseFloat(s.total_thp) || 0);
                workerMap[name].slipCount += 1;
            });
            return Object.values(workerMap).sort((a, b) => b.approvedHours - a.approvedHours).slice(0, 6);
        },

        getWorkerInitials(name) {
            if (!name) return '??';
            const parts = name.trim().split(/\s+/);
            if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        },

        async triggerSyncNow() {
            if (this.syncing) return;
            this.syncing = true;
            try {
                const res = await fetch('/api/studio-recordings/sync-now', { method: 'POST' });
                const data = await res.json();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sinkronisasi Berhasil',
                        text: data.message || 'Data rekaman studio berhasil disinkronkan.',
                        confirmButtonColor: '#00B090'
                    });
                }
                await this.loadAllDashboardData();
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Sinkronisasi',
                        text: e.message || 'Terjadi kesalahan saat sinkronisasi studio.',
                        confirmButtonColor: '#00B090'
                    });
                }
            } finally {
                this.syncing = false;
            }
        },

        async exportPdf(id) {
            this.isExporting = id;
            try {
                const response = await fetch(`/api/payslips/${id}/export`);
                const result = await response.json();
                if (result.success) {
                    window.open('' + result.url, '_blank');
                    this.loadAllDashboardData();
                } else {
                    alert('Gagal generate PDF: ' + result.error);
                }
            } catch (err) {
                console.error(err);
                alert('Gagal terhubung ke server');
            } finally {
                this.isExporting = null;
            }
        }
    }));

    // Dashboard Logic (Dynamic Realtime Payroll & Payslip History)
    Alpine.data('dashboardData', () => ({
        sidebarOpen: getInitialSidebarOpen(),
        isLoading: true,
        isExporting: null,
        
        // Cut-off Periods & Active Selection
        periods: [],
        selectedPeriodId: '',
        selectedPeriodObj: null,
        searchQuery: '',
        statusFilter: 'all', // 'all', 'sudah', 'belum'
        updatingStatusWorker: null,

        // Dynamic Live Payroll Data (Cut-off 22 - 21)
        liveStats: {
            totalWorkers: 0,
            totalSudah: 0,
            totalBelum: 0,
            totalThp: 0,
            totalApprovedHours: 0,
            totalRejectedHours: 0,
            totalLoggedHours: 0
        },
        liveWorkers: [],

        // Daily Breakdown Modal State
        showDailyModal: false,
        selectedWorkerDaily: null,

        // Legacy / Training Database Slips (Untouched id 1-9)
        trainingSlips: [],

        formatRupiah,
        formatDate,
        formatTime,
        formatPeriod,

        async init() {
            // Watch sidebarOpen in dashboardData
            this.$watch('sidebarOpen', val => { try { localStorage.setItem('aiku_sidebar_open', String(val)); } catch(e) {} });
            await this.fetchPeriods();
        },

        get isTrainingView() {
            return this.selectedPeriodId === 'training_2026';
        },

        get filteredLiveWorkers() {
            let list = this.liveWorkers;
            if (this.statusFilter === 'sudah') {
                list = list.filter(w => w.status === 'sudah');
            } else if (this.statusFilter === 'belum') {
                list = list.filter(w => w.status !== 'sudah');
            }
            if (!this.searchQuery || !this.searchQuery.trim()) return list;
            const q = this.searchQuery.toLowerCase().trim();
            return list.filter(w => w.worker_name && w.worker_name.toLowerCase().includes(q));
        },

        get filteredTrainingSlips() {
            let list = this.trainingSlips;
            if (this.statusFilter === 'sudah') {
                list = list.filter(s => s.status === 'sudah');
            } else if (this.statusFilter === 'belum') {
                list = list.filter(s => s.status !== 'sudah');
            }
            if (!this.searchQuery || !this.searchQuery.trim()) return list;
            const q = this.searchQuery.toLowerCase().trim();
            return list.filter(s => s.employee_name && s.employee_name.toLowerCase().includes(q));
        },

        get countSudah() {
            if (this.isTrainingView) {
                return this.trainingSlips.filter(s => s.status === 'sudah').length;
            }
            return this.liveWorkers.filter(w => w.status === 'sudah').length;
        },

        get countBelum() {
            if (this.isTrainingView) {
                return this.trainingSlips.filter(s => s.status !== 'sudah').length;
            }
            return this.liveWorkers.filter(w => w.status !== 'sudah').length;
        },

        get currentStats() {
            if (this.isTrainingView) {
                let totalThp = 0;
                let totalLogged = 0;
                let totalAppr = 0;
                let totalRej = 0;
                let totalPend = 0;

                this.trainingSlips.forEach(slip => {
                    totalThp += (slip.total_thp || 0);
                    totalAppr += (parseFloat(slip.total_approved_hours) || 0);
                    
                    let logged = parseFloat(slip.total_logged_hours) || 0;
                    let appr = parseFloat(slip.total_approved_hours) || 0;
                    totalLogged += logged;
                    
                    let slipPendingMins = 0;
                    if (slip.evidence_data) {
                        try {
                            const evData = typeof slip.evidence_data === 'string' ? JSON.parse(slip.evidence_data) : slip.evidence_data;
                            evData.forEach(ev => {
                                slipPendingMins += parseFloat(ev.pending_mins) || 0;
                            });
                        } catch(e) {}
                    }
                    
                    const slipPendingHours = slipPendingMins / 60;
                    const slipRejectedHours = (logged - appr) - slipPendingHours;

                    totalPend += slipPendingHours;
                    totalRej += (slipRejectedHours > 0 ? slipRejectedHours : 0);
                });

                return {
                    thp: totalThp,
                    logged: totalLogged.toFixed(2),
                    approved: totalAppr.toFixed(2),
                    rejected: totalRej.toFixed(2),
                    pending: totalPend.toFixed(2),
                    count: this.trainingSlips.length
                };
            }

            return {
                thp: this.liveStats.totalThp || 0,
                logged: (this.liveStats.totalLoggedHours || 0).toFixed(2),
                approved: (this.liveStats.totalApprovedHours || 0).toFixed(2),
                rejected: (this.liveStats.totalRejectedHours || 0).toFixed(2),
                pending: '0.00',
                count: this.liveStats.totalWorkers || 0
            };
        },

        async fetchPeriods() {
            this.isLoading = true;
            try {
                const response = await fetch('/api/payroll/periods');
                const data = await response.json();
                this.periods = data.periods || [];
                this.selectedPeriodId = data.defaultPeriodId || (this.periods[0] ? this.periods[0].id : '');
                await this.loadSelectedPeriodData();
            } catch (err) {
                console.error("Error fetching payroll periods:", err);
                this.isLoading = false;
            }
        },

        async onPeriodChange() {
            await this.loadSelectedPeriodData();
        },

        async selectPeriod(periodId) {
            this.selectedPeriodId = periodId;
            await this.loadSelectedPeriodData();
        },

        async loadSelectedPeriodData() {
            this.isLoading = true;
            this.selectedPeriodObj = this.periods.find(p => p.id === this.selectedPeriodId) || null;
            try {
                if (this.isTrainingView) {
                    const response = await fetch('/api/slips');
                    const data = await response.json();
                    this.trainingSlips = Array.isArray(data) ? data : [];
                } else {
                    const response = await fetch(`/api/payroll/live-summary?periodId=${encodeURIComponent(this.selectedPeriodId)}`);
                    const data = await response.json();
                    this.liveStats = data.stats || { totalWorkers: 0, totalThp: 0, totalApprovedHours: 0, totalRejectedHours: 0, totalLoggedHours: 0 };
                    this.liveWorkers = data.workers || [];
                }
            } catch (error) {
                console.error("Error loading payroll period data:", error);
            } finally {
                this.isLoading = false;
            }
        },

        openDailyModal(worker) {
            this.selectedWorkerDaily = worker;
            this.showDailyModal = true;
        },

        closeDailyModal() {
            this.showDailyModal = false;
            this.selectedWorkerDaily = null;
        },

        openLiveSlip(workerName) {
            const url = `/slip/live/${encodeURIComponent(workerName)}?periodId=${encodeURIComponent(this.selectedPeriodId)}`;
            window.open(url, '_blank');
        },

        async setWorkerStatus(worker, newStatus) {
            if (worker.status === newStatus) return;
            const prevStatus = worker.status;
            worker.status = newStatus;
            this.updatingStatusWorker = worker.worker_name;

            const periodName = (this.selectedPeriodObj && this.selectedPeriodObj.name) 
                ? this.selectedPeriodObj.name 
                : '22 Sep 2026 - 21 Okt 2026';

            try {
                const res = await fetch('/api/payroll/status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        worker_name: worker.worker_name,
                        period: periodName,
                        status: newStatus
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    worker.status = prevStatus;
                    alert('Gagal update status: ' + (data.error || 'Terjadi kesalahan'));
                } else {
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: newStatus === 'sudah' ? 'success' : 'info',
                            title: `Status ${worker.worker_name}: ${newStatus.toUpperCase()}`
                        });
                    }
                }
            } catch (err) {
                worker.status = prevStatus;
                console.error(err);
                alert('Gagal terhubung ke server');
            } finally {
                this.updatingStatusWorker = null;
            }
        },

        async setSlipStatus(slip, newStatus) {
            if (slip.status === newStatus) return;
            const prevStatus = slip.status;
            slip.status = newStatus;

            try {
                const res = await fetch(`/api/payslips/${slip.id}/status`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await res.json();
                if (!data.success) {
                    slip.status = prevStatus;
                    alert('Gagal update status slip: ' + (data.error || 'Terjadi kesalahan'));
                } else {
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: newStatus === 'sudah' ? 'success' : 'info',
                            title: `Status Slip #${slip.id}: ${newStatus.toUpperCase()}`
                        });
                    }
                }
            } catch (err) {
                slip.status = prevStatus;
                console.error(err);
                alert('Gagal terhubung ke server');
            }
        },

        async exportPdf(id) {
            this.isExporting = id;
            try {
                const response = await fetch(`/api/payslips/${id}/export`);
                const result = await response.json();
                if (result.success && result.url) {
                    const a = document.createElement('a');
                    a.href = '' + result.url;
                    a.download = result.fileName || 'Payslip.pdf';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    this.loadSelectedPeriodData();
                } else {
                    alert('Gagal generate PDF: ' + (result.error || 'Terjadi kesalahan sistem'));
                }
            } catch (err) {
                console.error(err);
                alert('Gagal terhubung ke server');
            } finally {
                this.isExporting = null;
            }
        },

        async sendEmail(id) {
            const slip = this.trainingSlips ? this.trainingSlips.find(s => s.id === id) : null;
            if (!slip) return;

            let emailTarget = 'Tidak ada email';
            try {
                const cRes = await fetch('/api/contributors');
                const contributorsList = await cRes.json();
                const contributor = contributorsList.find(c => c.nama === slip.employee_name);
                if (contributor && contributor.email) {
                    emailTarget = contributor.email;
                }
            } catch(e) {}

            const result = await Swal.fire({
                title: 'Kirim Slip via Email?',
                html: `Kirim payslip <b>${slip.period}</b> beserta lampiran PDF-nya ke <br/><strong class="text-brand">${emailTarget}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Kirim Sekarang!',
                cancelButtonText: 'Batal'
            });

            if (!result.isConfirmed) return;

            if (emailTarget === 'Tidak ada email' || emailTarget === '') {
                Swal.fire('Gagal', 'Kontributor ini belum memiliki alamat email yang valid di Database.', 'error');
                return;
            }

            Swal.fire({
                title: 'Menyiapkan PDF...',
                text: 'Sedang men-generate dokumen PDF Slip...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch(`/api/payslips/${slip.id}/email`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: emailTarget })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Email belum dikirim. Periksa konfigurasi SMTP.');
                Swal.fire('Terkirim!', 'Slip PDF berhasil dikirim melalui SMTP.', 'success');
            } catch (error) {
                console.error("Error sending email:", error);
                Swal.fire('Gagal!', error.message || 'Gagal mengirim email.', 'error');
            }
        },

        async deleteSlip(id) {
            const result = await Swal.fire({
                title: 'Hapus Payslip?',
                text: "Payslip dan seluruh log gambar di dalamnya akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            });

            if (!result.isConfirmed) return;

            try {
                await fetch(`/api/payslips/${id}`, { method: 'DELETE' });
                
                Swal.fire({
                    title: 'Terhapus!',
                    text: 'Payslip berhasil dihapus.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                this.loadSelectedPeriodData();
            } catch (error) {
                console.error("Error deleting slip:", error);
                Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus payslip.', 'error');
            }
        }
    }));

    // Profit & Finance Logic (Dynamic Cut-Off Cycle & Training Archive)
    Alpine.data('profitData', () => ({
        sidebarOpen: getInitialSidebarOpen(),
        isLoading: true,
        
        // Period Management
        periods: [],
        selectedPeriodId: '',
        selectedPeriodObj: null,
        searchQuery: '',

        // Dynamic Live Cycle Data (Cut-off 22 - 21)
        liveStats: {
            totalWorkers: 0,
            totalThp: 0,
            totalApprovedHours: 0,
            totalRejectedHours: 0,
            totalLoggedHours: 0
        },
        liveWorkers: [],

        // Training Slips (Historical DB Slips 1-9)
        trainingSlips: [],

        // Interactive Financial Settings (persisted in localStorage)
        rateMode: localStorage.getItem('aiku_rate_mode') || 'USD',
        clientRateUsd: parseFloat(localStorage.getItem('aiku_client_rate_usd')) || 5.0,
        kursUsd: parseFloat(localStorage.getItem('aiku_kurs_usd')) || 16000,
        clientRateIdr: parseFloat(localStorage.getItem('aiku_client_rate_idr')) || 80000,
        operationalExpense: parseFloat(localStorage.getItem('aiku_op_expense')) || 0,

        formatRupiah,
        formatDate,
        formatTime,
        formatPeriod,

        async init() {
            try {
                const res = await fetch('/api/finance/settings');
                const data = await res.json();
                if (res.ok && data.settings) Object.assign(this, data.settings);
            } catch (e) { console.error('Gagal memuat pengaturan profit', e); }
            // Watch sidebarOpen in profitData
            this.$watch('sidebarOpen', val => { try { localStorage.setItem('aiku_sidebar_open', String(val)); } catch(e) {} });
            await this.fetchPeriods();
        },

        get isTrainingView() {
            return this.selectedPeriodId === 'training_2026';
        },

        saveSettings() {
            localStorage.setItem('aiku_rate_mode', this.rateMode);
            localStorage.setItem('aiku_client_rate_usd', this.clientRateUsd);
            localStorage.setItem('aiku_kurs_usd', this.kursUsd);
            localStorage.setItem('aiku_client_rate_idr', this.clientRateIdr);
            localStorage.setItem('aiku_op_expense', this.operationalExpense);
            clearTimeout(this._saveFinanceTimer);
            this._saveFinanceTimer = setTimeout(async () => {
                try {
                    const res = await fetch('/api/finance/settings', { method: 'POST', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({rateMode: this.rateMode, clientRateUsd: this.clientRateUsd, kursUsd: this.kursUsd,
                            clientRateIdr: this.clientRateIdr, operationalExpense: this.operationalExpense}) });
                    if (!res.ok) throw new Error('Pengaturan profit belum tersimpan di server.');
                } catch (error) { Swal.fire('Gagal menyimpan', error.message, 'error'); }
            }, 500);
        },

        resetSettings() {
            this.rateMode = 'USD';
            this.clientRateUsd = 5.0;
            this.kursUsd = 16000;
            this.clientRateIdr = 80000;
            this.operationalExpense = 0;
            this.saveSettings();
        },

        get effectiveClientRateIdr() {
            if (this.rateMode === 'USD') {
                return (parseFloat(this.clientRateUsd) || 0) * (parseFloat(this.kursUsd) || 0);
            }
            return parseFloat(this.clientRateIdr) || 0;
        },

        async fetchPeriods() {
            this.isLoading = true;
            try {
                const response = await fetch('/api/payroll/periods');
                const data = await response.json();
                this.periods = data.periods || [];
                this.selectedPeriodId = data.defaultPeriodId || (this.periods[0] ? this.periods[0].id : '');
                await this.loadSelectedPeriodData();
            } catch (err) {
                console.error('Error fetching payroll periods:', err);
                this.isLoading = false;
            }
        },

        async onPeriodChange() {
            await this.loadSelectedPeriodData();
        },

        async selectPeriod(periodId) {
            this.selectedPeriodId = periodId;
            await this.loadSelectedPeriodData();
        },

        async loadSelectedPeriodData() {
            this.isLoading = true;
            this.selectedPeriodObj = this.periods.find(p => p.id === this.selectedPeriodId) || null;
            try {
                if (this.isTrainingView) {
                    const response = await fetch('/api/slips');
                    const data = await response.json();
                    this.trainingSlips = Array.isArray(data) ? data : [];
                } else {
                    const response = await fetch(`/api/payroll/live-summary?periodId=${encodeURIComponent(this.selectedPeriodId)}`);
                    const data = await response.json();
                    this.liveStats = data.stats || { totalWorkers: 0, totalThp: 0, totalApprovedHours: 0, totalRejectedHours: 0, totalLoggedHours: 0 };
                    this.liveWorkers = data.workers || [];
                }
            } catch (error) {
                console.error('Error loading profit data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        get profitSummary() {
            const rate = this.effectiveClientRateIdr;
            let totalApprovedHours = 0;
            let totalLoggedHours = 0;
            let totalRejectedHours = 0;
            let totalPayrollCost = 0;
            let totalPendingHours = 0;
            let totalTasksCount = 0;
            let contributorsCount = 0;

            if (this.isTrainingView) {
                this.trainingSlips.forEach(slip => {
                    const apprHours = parseFloat(slip.total_approved_hours) || 0;
                    const loggedHours = parseFloat(slip.total_logged_hours) || 0;
                    totalApprovedHours += apprHours;
                    totalLoggedHours += loggedHours;
                    totalPayrollCost += (slip.total_thp || 0);

                    let pendingMins = 0;
                    let tasksInSlip = 0;
                    if (slip.evidence_data) {
                        try {
                            const evData = typeof slip.evidence_data === 'string' ? JSON.parse(slip.evidence_data) : slip.evidence_data;
                            if (Array.isArray(evData)) {
                                evData.forEach(ev => {
                                    if (ev.tasks && Array.isArray(ev.tasks)) {
                                        tasksInSlip += ev.tasks.length;
                                        ev.tasks.forEach(t => {
                                            pendingMins += (parseFloat(t.pending_mins) || 0);
                                        });
                                    } else {
                                        tasksInSlip += 1;
                                        pendingMins += (parseFloat(ev.pending_mins) || 0);
                                    }
                                });
                            }
                        } catch(e) {}
                    }
                    const pHours = pendingMins / 60;
                    totalPendingHours += pHours;
                    totalTasksCount += tasksInSlip;
                    const rejHours = (loggedHours - apprHours) - pHours;
                    totalRejectedHours += (rejHours > 0 ? rejHours : 0);
                });
                contributorsCount = this.trainingSlips.length;
            } else {
                totalApprovedHours = this.liveStats.totalApprovedHours || 0;
                totalLoggedHours = this.liveStats.totalLoggedHours || 0;
                totalRejectedHours = this.liveStats.totalRejectedHours || 0;
                totalPayrollCost = this.liveStats.totalThp || 0;
                contributorsCount = this.liveStats.totalWorkers || 0;

                this.liveWorkers.forEach(w => {
                    totalPendingHours += (w.pending_hours || 0);
                    totalTasksCount += (w.total_tasks || 0);
                });
            }

            const grossRevenue = Math.round(totalApprovedHours * rate);
            const grossProfit = grossRevenue - totalPayrollCost;
            const opEx = parseFloat(this.operationalExpense) || 0;
            const netProfit = grossProfit - opEx;
            const profitMarginPct = grossRevenue > 0 ? ((netProfit / grossRevenue) * 100).toFixed(1) : '0.0';
            const payrollRatioPct = grossRevenue > 0 ? Math.min(100, ((totalPayrollCost / grossRevenue) * 100)).toFixed(1) : '0.0';
            const opExRatioPct = grossRevenue > 0 ? Math.min(100, ((opEx / grossRevenue) * 100)).toFixed(1) : '0.0';
            const profitRatioPct = grossRevenue > 0 ? Math.max(0, (100 - parseFloat(payrollRatioPct) - parseFloat(opExRatioPct))).toFixed(1) : '0.0';

            const potentialPendingRevenue = Math.round(totalPendingHours * rate);
            const avgProfitPerHour = totalApprovedHours > 0 ? Math.round(netProfit / totalApprovedHours) : 0;
            const qcEfficiencyPct = totalLoggedHours > 0 ? ((totalApprovedHours / totalLoggedHours) * 100).toFixed(1) : '0.0';

            return {
                approvedHours: totalApprovedHours.toFixed(2),
                loggedHours: totalLoggedHours.toFixed(2),
                rejectedHours: totalRejectedHours.toFixed(2),
                pendingHours: totalPendingHours.toFixed(2),
                totalTasks: totalTasksCount,
                contributorsCount,
                grossRevenue,
                totalPayrollCost,
                grossProfit,
                netProfit,
                profitMarginPct,
                payrollRatioPct,
                opExRatioPct,
                profitRatioPct,
                potentialPendingRevenue,
                avgProfitPerHour,
                qcEfficiencyPct,
                opEx
            };
        },

        get contributorProfitList() {
            const rate = this.effectiveClientRateIdr;
            let list = [];

            if (this.isTrainingView) {
                list = this.trainingSlips.map(slip => {
                    const apprHours = parseFloat(slip.total_approved_hours) || 0;
                    const loggedHours = parseFloat(slip.total_logged_hours) || 0;
                    const grossRevenue = Math.round(apprHours * rate);
                    const payrollCost = slip.total_thp || 0;
                    const profit = grossRevenue - payrollCost;
                    const marginPct = grossRevenue > 0 ? ((profit / grossRevenue) * 100).toFixed(1) : '0.0';

                    return {
                        id: slip.id,
                        name: slip.employee_name || 'Unknown',
                        period: slip.period || 'Training',
                        apprHours: apprHours.toFixed(2),
                        loggedHours: loggedHours.toFixed(2),
                        rejectedHours: Math.max(0, loggedHours - apprHours).toFixed(2),
                        pendingHours: '0.00',
                        totalTasks: '-',
                        workDays: '-',
                        grossRevenue,
                        payrollCost,
                        profit,
                        marginPct: parseFloat(marginPct),
                        isLive: false,
                        actionUrl: `/slip/${slip.id}`
                    };
                });
            } else {
                list = this.liveWorkers.map((w, idx) => {
                    const apprHours = parseFloat(w.approved_hours) || 0;
                    const loggedHours = parseFloat(w.logged_hours) || 0;
                    const rejHours = parseFloat(w.rejected_hours) || 0;
                    const pendHours = parseFloat(w.pending_hours) || 0;
                    const grossRevenue = Math.round(apprHours * rate);
                    const payrollCost = w.thp || 0;
                    const profit = grossRevenue - payrollCost;
                    const marginPct = grossRevenue > 0 ? ((profit / grossRevenue) * 100).toFixed(1) : '0.0';

                    return {
                        id: idx + 1,
                        name: w.worker_name || 'Unknown',
                        period: this.selectedPeriodObj ? this.selectedPeriodObj.name : 'Siklus Berjalan',
                        apprHours: apprHours.toFixed(2),
                        loggedHours: loggedHours.toFixed(2),
                        rejectedHours: rejHours.toFixed(2),
                        pendingHours: pendHours.toFixed(2),
                        totalTasks: w.total_tasks || 0,
                        workDays: w.work_days_count || 0,
                        grossRevenue,
                        payrollCost,
                        profit,
                        marginPct: parseFloat(marginPct),
                        isLive: true,
                        actionUrl: `/slip/live/${encodeURIComponent(w.worker_name)}?periodId=${encodeURIComponent(this.selectedPeriodId)}`
                    };
                });
            }

            // Apply search filter if query is typed
            if (this.searchQuery && this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase().trim();
                list = list.filter(c => c.name && c.name.toLowerCase().includes(q));
            }

            return list.sort((a, b) => b.profit - a.profit);
        },

        get topContributor() {
            const list = this.contributorProfitList;
            return list.length > 0 ? list[0] : null;
        }
    }));

    // Contributors List Logic
    Alpine.data('contributorsListData', () => ({
        sidebarOpen: getInitialSidebarOpen(),
        contributors: [],
        isLoading: true,
        showModal: false,
        isEditing: false,
        formData: { id: null, nama: '', no_wa: '', email: '', domisili: '', shift: '', hp: '', no_rek: '' },

        init() {
            // Watch sidebarOpen in contributorsListData
            this.$watch('sidebarOpen', val => { try { localStorage.setItem('aiku_sidebar_open', String(val)); } catch(e) {} });
        },
        
        async fetchContributors() {
            this.isLoading = true;
            try {
                const response = await fetch('/api/contributors');
                const data = await response.json();
                this.contributors = data;
            } catch (error) {
                console.error("Error fetching contributors:", error);
            } finally {
                this.isLoading = false;
            }
        },

        openModal(contributor = null) {
            if (contributor) {
                this.isEditing = true;
                this.formData = { ...contributor };
            } else {
                this.isEditing = false;
                this.formData = { id: null, nama: '', no_wa: '', email: '', domisili: '', shift: '', hp: '', no_rek: '' };
            }
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
        },
        
        async saveContributor() {
            try {
                const method = this.isEditing ? 'PUT' : 'POST';
                const url = this.isEditing ? `/api/contributors/${this.formData.id}` : '/api/contributors';
                
                await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formData)
                });
                
                this.closeModal();
                this.fetchContributors();
            } catch (error) {
                console.error("Error saving contributor:", error);
            }
        },
        
        async deleteContributor(id) {
            if (!confirm('Are you sure you want to delete this contributor?')) return;
            try {
                await fetch(`/api/contributors/${id}`, { method: 'DELETE' });
                this.fetchContributors();
            } catch (error) {
                console.error("Error deleting contributor:", error);
            }
        }
    }));

    // Create/Edit Form Logic
    Alpine.data('createSlipData', () => ({
        sidebarOpen: getInitialSidebarOpen(),
        slipId: new URLSearchParams(window.location.search).get('id'),
        contributors: [],
        periods: [],
        payrollPeriods: [],
        defaultPeriod: null,
        isLoadingEvidence: false,
        formData: {
            employee_name: '',
            period: '',
            bonus: 0,
            evidence: [
                { date: '', total_video_mins: '', approved_mins: '', rejected_mins: '', pending_mins: '', notes: '', image_url: '', is_uploading: false, is_open: true, tasks: [{ task_name: '', approved_mins: '', rejected_mins: '', pending_mins: '', notes: '' }] }
            ]
        },
        isSubmitting: false,
        message: '',
        isSuccess: false,

        async init() {
            // 1. Fetch periods from backend
            try {
                const pRes = await fetch('/api/payroll/periods');
                if (pRes.ok) {
                    const pData = await pRes.json();
                    this.payrollPeriods = pData.periods || [];
                    this.defaultPeriod = this.payrollPeriods.find(p => p.id === pData.defaultPeriodId) || this.payrollPeriods[0];
                    this.periods = this.payrollPeriods.filter(p => !p.isTraining).map(p => p.name);
                }
            } catch (err) {
                console.error("Error fetching periods from API:", err);
            }

            if (!this.periods || this.periods.length === 0) {
                this.periods = this.generatePeriods();
            }

            // 2. Fetch contributors
            try {
                const response = await fetch('/api/contributors');
                const data = await response.json();
                this.contributors = data;
            } catch (error) {
                console.error("Error fetching contributors:", error);
            }

            const urlParams = new URLSearchParams(window.location.search);
            const workerParam = urlParams.get('worker');
            const periodParam = urlParams.get('period');
            const periodIdParam = urlParams.get('periodId');

            // Default period if creating new slip
            if (!this.slipId && !this.formData.period) {
                if (periodParam) {
                    this.formData.period = periodParam;
                } else if (this.defaultPeriod && !this.defaultPeriod.isTraining) {
                    this.formData.period = this.defaultPeriod.name;
                } else if (this.periods.length > 0) {
                    this.formData.period = this.periods[0];
                }
            }

            // Case A: Editing existing saved slip by ID (e.g. ?id=1)
            if (this.slipId && !isNaN(parseInt(this.slipId)) && !String(this.slipId).startsWith('LIVE-')) {
                await this.loadSavedSlip(this.slipId);
            }
            // Case B: Worker param passed or LIVE- slipId
            else if (workerParam || (this.slipId && String(this.slipId).startsWith('LIVE-'))) {
                const targetWorker = workerParam || String(this.slipId).replace(/^LIVE-/, '');
                this.formData.employee_name = targetWorker;
                if (periodParam) this.formData.period = periodParam;
                await this.loadWorkerEvidence(targetWorker, this.formData.period, periodIdParam);
            }
        },

        async loadSavedSlip(slipId) {
            try {
                const res = await fetch(`/api/payslips/${slipId}`);
                if (res.ok) {
                    const data = await res.json();
                    this.formData.employee_name = data.employee_name;
                    this.formData.period = data.period;
                    this.formData.bonus = data.bonus !== undefined ? data.bonus : 0;
                    if (data.evidence_data && data.evidence_data.length > 0) {
                        this.formData.evidence = data.evidence_data.map((ev, i) => {
                            if (!ev.tasks || !Array.isArray(ev.tasks) || ev.tasks.length === 0) {
                                const a = parseFloat(ev.approved_mins) || 0;
                                const r = parseFloat(ev.rejected_mins) || 0;
                                const p = parseFloat(ev.pending_mins) || 0;
                                ev.tasks = [{
                                    task_name: ev.task_name || 'Daily Video Log',
                                    approved_mins: a,
                                    rejected_mins: r,
                                    pending_mins: p,
                                    notes: ev.notes || ''
                                }];
                            }
                            ev.is_open = (i === 0);
                            this.syncDayTotals(ev);
                            return ev;
                        });
                    }
                }
            } catch (error) {
                console.error("Error fetching slip data:", error);
            }
        },

        async loadWorkerEvidence(workerName = null, periodName = null, periodId = null) {
            const worker = workerName || this.formData.employee_name;
            const period = periodName || this.formData.period;
            if (!worker) return;

            this.isLoadingEvidence = true;
            try {
                let url = `/api/payroll/worker-evidence?worker=${encodeURIComponent(worker)}`;
                if (periodId) url += `&periodId=${encodeURIComponent(periodId)}`;
                if (period) url += `&period=${encodeURIComponent(period)}`;

                const res = await fetch(url);
                if (res.ok) {
                    const data = await res.json();
                    if (data.existingSlipId) {
                        this.slipId = data.existingSlipId;
                    } else if (!this.slipId || String(this.slipId).startsWith('LIVE-')) {
                        this.slipId = null;
                    }
                    if (data.period) this.formData.period = data.period;
                    if (data.bonus !== undefined) this.formData.bonus = data.bonus;
                    if (data.evidence && data.evidence.length > 0) {
                        this.formData.evidence = data.evidence.map((ev, i) => {
                            ev.is_open = (i === 0);
                            this.syncDayTotals(ev);
                            return ev;
                        });
                    } else {
                        // Empty placeholder day if no tasks recorded in cut-off
                        this.formData.evidence = [
                            { date: new Date().toISOString().split('T')[0], total_video_mins: 0, approved_mins: 0, rejected_mins: 0, pending_mins: 0, notes: '', is_uploading: false, is_open: true, tasks: [{ task_name: '', approved_mins: '', rejected_mins: '', pending_mins: '', notes: '' }] }
                        ];
                    }
                }
            } catch (error) {
                console.error("Error fetching live worker evidence:", error);
            } finally {
                this.isLoadingEvidence = false;
            }
        },

        async onWorkerChange() {
            if (this.formData.employee_name) {
                await this.loadWorkerEvidence();
            }
        },

        async onPeriodSelect(periodLabel) {
            this.formData.period = periodLabel;
            if (this.formData.employee_name) {
                await this.loadWorkerEvidence();
            }
        },

        generatePeriods() {
            const periodsList = [];
            const today = new Date();
            let year = today.getFullYear();
            let month = today.getMonth();

            const monthNamesIndo = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

            for (let i = 1; i >= -5; i--) {
                let endM = month + i;
                let endY = year;

                while (endM > 11) { endM -= 12; endY++; }
                while (endM < 0) { endM += 12; endY--; }

                let startM = endM - 1;
                let startY = endY;
                if (startM < 0) { startM = 11; startY--; }

                const label = `22 ${monthNamesIndo[startM]} ${startY} - 21 ${monthNamesIndo[endM]} ${endY}`;
                periodsList.push(label);
            }
            return periodsList;
        },

        addEvidence() {
            let defaultDate = '';
            if (this.formData.evidence.length > 0) {
                defaultDate = this.formData.evidence[this.formData.evidence.length - 1].date;
            } else {
                defaultDate = new Date().toISOString().split('T')[0];
            }
            const newDay = {
                date: defaultDate,
                is_open: true,
                notes: '',
                tasks: [
                    { task_name: '', approved_mins: '', rejected_mins: '', pending_mins: '', notes: '' }
                ]
            };
            this.syncDayTotals(newDay);
            this.formData.evidence.push(newDay);
        },

        addTaskToDay(dayIndex) {
            const day = this.formData.evidence[dayIndex];
            if (!day.tasks) day.tasks = [];
            day.tasks.push({ task_name: '', approved_mins: '', rejected_mins: '', pending_mins: '', notes: '' });
            day.is_open = true;
            this.syncDayTotals(day);
        },

        removeTaskFromDay(dayIndex, taskIndex) {
            const day = this.formData.evidence[dayIndex];
            if (!day || !day.tasks) return;
            if (day.tasks.length <= 1) {
                day.tasks[0] = { task_name: '', approved_mins: '', rejected_mins: '', pending_mins: '', notes: '' };
            } else {
                day.tasks.splice(taskIndex, 1);
            }
            this.syncDayTotals(day);
        },

        syncDayTotals(day) {
            if (!day.tasks || !Array.isArray(day.tasks)) return;
            let a = 0, r = 0, p = 0;
            day.tasks.forEach(t => {
                a += parseFloat(t.approved_mins) || 0;
                r += parseFloat(t.rejected_mins) || 0;
                p += parseFloat(t.pending_mins) || 0;
            });
            day.approved_mins = Number(a.toFixed(2));
            day.rejected_mins = Number(r.toFixed(2));
            day.pending_mins = Number(p.toFixed(2));
            day.total_video_mins = Number((a + r + p).toFixed(2));
        },

        async removeEvidence(index) {
            const result = await Swal.fire({
                title: 'Hapus Hari Ini?',
                text: "Apakah Anda yakin ingin menghapus seluruh log hari ini beserta seluruh task di dalamnya?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus Hari!',
                cancelButtonText: 'Batal'
            });
            
            if (result.isConfirmed) {
                this.formData.evidence.splice(index, 1);
            }
        },

        minsToHours(mins) {
            const m = parseFloat(mins);
            if (isNaN(m) || m <= 0) return '0.00 h';
            return (m / 60).toFixed(2) + ' h';
        },

        getTaskStatus(ev) {
            this.syncDayTotals(ev);
            const appr = parseFloat(ev.approved_mins) || 0;
            const rej = parseFloat(ev.rejected_mins) || 0;
            const pend = parseFloat(ev.pending_mins) || 0;
            const total = (parseFloat(ev.total_video_mins) || (appr + rej + pend));

            if (total === 0) return { label: 'Belum diisi', color: 'text-gray-400', bg: 'bg-gray-100', border: 'border-gray-200' };
            if (appr > 0 && rej === 0 && pend === 0) return { label: 'Disetujui penuh', color: 'text-emerald-700', bg: 'bg-emerald-50', border: 'border-emerald-200' };
            if (appr > 0 && rej > 0) return { label: 'Disetujui sebagian', color: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-200' };
            if (appr === 0 && rej > 0) return { label: 'Ditolak', color: 'text-rose-700', bg: 'bg-rose-50', border: 'border-rose-200' };
            if (pend > 0 && appr === 0 && rej === 0) return { label: 'Sedang Ditinjau', color: 'text-yellow-700', bg: 'bg-yellow-50', border: 'border-yellow-200' };
            return { label: 'Disetujui sebagian', color: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-200' };
        },

        getProgressBarWidths(ev) {
            this.syncDayTotals(ev);
            const appr = parseFloat(ev.approved_mins) || 0;
            const rej = parseFloat(ev.rejected_mins) || 0;
            const pend = parseFloat(ev.pending_mins) || 0;
            const total = (parseFloat(ev.total_video_mins) || (appr + rej + pend));
            if (!total || total <= 0) return { approved: '0%', rejected: '0%', pending: '0%' };

            return {
                approved: Math.min(100, Math.max(0, (appr / total) * 100)).toFixed(1) + '%',
                rejected: Math.min(100, Math.max(0, (rej / total) * 100)).toFixed(1) + '%',
                pending: Math.min(100, Math.max(0, (pend / total) * 100)).toFixed(1) + '%'
            };
        },

        calculateApprovedHours() {
            let totalMins = 0;
            this.formData.evidence.forEach(ev => {
                this.syncDayTotals(ev);
                if (ev.approved_mins) {
                    totalMins += parseFloat(ev.approved_mins);
                }
            });
            return (totalMins / 60).toFixed(2);
        },

        calculateTHP() {
            // THP is base rate * approved exact minutes / 60 + manual bonus
            let totalMins = 0;
            this.formData.evidence.forEach(ev => {
                this.syncDayTotals(ev);
                if (ev.approved_mins) {
                    totalMins += parseFloat(ev.approved_mins) || 0;
                }
            });
            const base = Math.round((totalMins / 60) * window.AIKU_CONFIG.hourlyRate);
            const b = parseFloat(this.formData.bonus) || 0;
            return Math.round(base + b);
        },

        formatRupiah,

        async submitForm() {
            this.isSubmitting = true;
            this.message = '';
            
            try {
                const method = this.slipId ? 'PUT' : 'POST';
                const url = this.slipId 
                    ? `/api/payslips/${this.slipId}` 
                    : `/api/payslips`;

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    const slipId = result.id || (result.data && result.data.id);
                    this.isSuccess = true;
                    this.message = this.slipId ? 'Slip updated successfully! Redirecting...' : 'Slip saved successfully! Redirecting...';
                    setTimeout(() => {
                        window.location.href = '/slip/' + slipId;
                    }, 1000);
                } else {
                    this.isSuccess = false;
                    this.message = result.error || 'Failed to save slip.';
                }
            } catch (error) {
                this.isSuccess = false;
                this.message = 'Network error occurred.';
                console.error(error);
            } finally {
                this.isSubmitting = false;
            }
        },

        async exportPdfFromEdit() {
            if (!this.slipId) return;
            try {
                const response = await fetch(`/api/payslips/${this.slipId}/export`);
                const result = await response.json();
                if (result.success) {
                    window.open('' + result.url, '_blank');
                } else {
                    alert('Gagal generate PDF: ' + result.error);
                }
            } catch (err) {
                console.error(err);
                alert('Gagal terhubung ke server');
            }
        }
    }));
});