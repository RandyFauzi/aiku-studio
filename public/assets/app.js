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

const payrollError = (data) => Object.values(data.errors || {}).flat().join(' ') || data.message || data.error || 'Permintaan gagal.';

// Shared Sidebar Persistence Helper
const getInitialSidebarOpen = () => {
    if (typeof window !== 'undefined' && window.innerWidth < 768) {
        return false;
    }
    try {
        const saved = localStorage.getItem('aiku_sidebar_open');
        if (saved !== null) {
            return saved === 'true';
        }
    } catch (e) {}
    return true;
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

        // Performance Mode: 'monthly' (Siklus 22-21) vs 'all_time'
        performanceTab: 'monthly',
        periods: [],
        currentPeriod: null,
        selectedPeriodId: '',
        monthlyStats: {
            totalWorkers: 0,
            totalThp: 0,
            totalApprovedHours: 0,
            totalRejectedHours: 0,
            totalLoggedHours: 0,
            totalPendingHours: 0,
            totalSudah: 0,
            totalBelum: 0
        },
        monthlyWorkers: [],
        studioTargets: {
            period: { id: '', name: 'Siklus 16 - 15', total_days: 30, days_elapsed: 0, days_remaining: 30, is_current: true },
            daily: { date: '', target_hours: 80, actual_hours: 0, logged_hours: 0, remaining_hours: 80, percent: 0, bar_percent: 0, achieved: false },
            monthly: { target_hours: 2150, actual_hours: 0, logged_hours: 0, remaining_hours: 2150, expected_hours_to_date: 0, recommended_daily_pace: 80, percent: 0, bar_percent: 0, achieved: false, on_track: false }
        },

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
                const [periodsRes, slipsRes, recRes, contRes, syncRes, targetRes] = await Promise.all([
                    fetch('/api/payroll/periods').then(r => r.json()).catch(() => ({ periods: [], currentPeriod: null })),
                    fetch('/api/slips').then(r => r.json()).catch(() => []),
                    fetch('/api/studio-recordings?limit=1').then(r => r.json()).catch(() => ({ stats: {} })),
                    fetch('/api/contributors').then(r => r.json()).catch(() => []),
                    fetch('/api/studio-recordings/sync-status').then(r => r.json()).catch(() => null),
                    fetch('/api/studio-targets').then(r => r.json()).catch(() => null)
                ]);
                if (targetRes && targetRes.daily && targetRes.monthly) {
                    this.studioTargets = targetRes;
                }

                this.periods = (periodsRes && periodsRes.periods) ? periodsRes.periods : [];
                this.currentPeriod = (periodsRes && periodsRes.currentPeriod) ? periodsRes.currentPeriod : null;
                this.selectedPeriodId = (periodsRes && periodsRes.defaultPeriodId) 
                    ? periodsRes.defaultPeriodId 
                    : (this.currentPeriod ? this.currentPeriod.id : (this.periods[0] ? this.periods[0].id : ''));

                this.slips = Array.isArray(slipsRes) ? slipsRes : [];
                if (recRes && recRes.stats) {
                    this.recordingsStats = { ...this.recordingsStats, ...recRes.stats };
                }
                this.contributors = Array.isArray(contRes) ? contRes : [];
                this.syncStatus = syncRes;

                if (this.selectedPeriodId) {
                    await this.loadMonthlyData(this.selectedPeriodId);
                }
            } catch (e) {
                console.error("Failed to load executive dashboard data:", e);
            } finally {
                this.loading = false;
            }
        },

        async loadMonthlyData(periodId) {
            try {
                const res = await fetch(`/api/payroll/live-summary?periodId=${encodeURIComponent(periodId)}`);
                if (res.ok) {
                    const data = await res.json();
                    this.monthlyStats = data.stats || {
                        totalWorkers: 0, totalThp: 0, totalApprovedHours: 0, totalRejectedHours: 0, totalLoggedHours: 0, totalPendingHours: 0, totalSudah: 0, totalBelum: 0
                    };
                    this.monthlyWorkers = data.workers || [];
                }
            } catch (err) {
                console.error("Failed to load monthly payroll live summary:", err);
            }
        },

        async changePeriod(periodId) {
            this.selectedPeriodId = periodId;
            this.loading = true;
            await Promise.all([
                this.loadMonthlyData(periodId),
                fetch(`/api/studio-targets?periodId=${encodeURIComponent(periodId)}`)
                    .then(r => r.ok ? r.json() : null)
                    .then(d => { if (d && d.daily && d.monthly) this.studioTargets = d; })
                    .catch(() => {})
            ]);
            this.loading = false;
        },

        get activePeriodObj() {
            if (!this.selectedPeriodId) return this.currentPeriod;
            return this.periods.find(p => p.id === this.selectedPeriodId) || this.currentPeriod;
        },

        get activePeriodName() {
            return this.activePeriodObj ? this.activePeriodObj.name : 'Siklus 22 - 21';
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

        // Monthly Contributors (Cut-off 22 - 21)
        get monthlyContributors() {
            if (!this.monthlyWorkers || this.monthlyWorkers.length === 0) return [];
            
            const sorted = [...this.monthlyWorkers].sort((a, b) => (parseFloat(b.approved_hours) || 0) - (parseFloat(a.approved_hours) || 0));
            const maxHours = sorted.length > 0 ? (parseFloat(sorted[0].approved_hours) || 1) : 1;
            const totalHours = sorted.reduce((sum, w) => sum + (parseFloat(w.approved_hours) || 0), 0) || 1;

            return sorted.map((w, idx) => {
                const appHours = parseFloat(w.approved_hours) || 0;
                return {
                    rank: idx + 1,
                    name: w.worker_name,
                    approvedHours: appHours,
                    loggedHours: parseFloat(w.logged_hours) || 0,
                    rejectedHours: parseFloat(w.rejected_hours) || 0,
                    pendingHours: parseFloat(w.pending_hours) || 0,
                    thp: parseFloat(w.thp) || 0,
                    bonus: parseFloat(w.bonus) || 0,
                    carryOver: parseFloat(w.pending_carry_over_amount) || 0,
                    tasksCount: parseInt(w.total_tasks) || 0,
                    status: w.status,
                    hasSlip: Boolean(w.has_slip),
                    barPercentage: Math.max(8, Math.min(100, Math.round((appHours / maxHours) * 100))),
                    sharePercentage: totalHours > 0 ? ((appHours / totalHours) * 100).toFixed(1) : '0.0'
                };
            });
        },

        // All-Time Contributors (Akumulasi Slip Gaji Terbit)
        get allTimeContributors() {
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

            const sorted = Object.values(workerMap).sort((a, b) => b.approvedHours - a.approvedHours);
            const maxHours = sorted.length > 0 ? (sorted[0].approvedHours || 1) : 1;
            const totalHours = sorted.reduce((sum, w) => sum + w.approvedHours, 0) || 1;

            return sorted.map((w, idx) => ({
                rank: idx + 1,
                name: w.name,
                approvedHours: w.approvedHours,
                loggedHours: w.loggedHours,
                thp: w.thp,
                slipCount: w.slipCount,
                tasksCount: 0,
                status: 'sudah',
                hasSlip: true,
                barPercentage: Math.max(8, Math.min(100, Math.round((w.approvedHours / maxHours) * 100))),
                sharePercentage: totalHours > 0 ? ((w.approvedHours / totalHours) * 100).toFixed(1) : '0.0'
            }));
        },

        get currentContributors() {
            return this.performanceTab === 'monthly' ? this.monthlyContributors : this.allTimeContributors;
        },

        get topContributors() {
            return this.currentContributors.slice(0, 6);
        },

        get topContributorsTotalShare() {
            const top3 = this.topContributors.slice(0, 3);
            const total = top3.reduce((sum, c) => sum + parseFloat(c.sharePercentage || 0), 0);
            return total.toFixed(1);
        },

        // Metrik Bulanan Terpilih
        get monthlyApprovedHours() {
            return (parseFloat(this.monthlyStats.totalApprovedHours) || 0).toFixed(1);
        },

        get monthlyLoggedHours() {
            return (parseFloat(this.monthlyStats.totalLoggedHours) || 0).toFixed(1);
        },

        get monthlyApprovalRate() {
            const logged = parseFloat(this.monthlyStats.totalLoggedHours) || 0;
            const approved = parseFloat(this.monthlyStats.totalApprovedHours) || 0;
            if (logged <= 0) return '0.0';
            return ((approved / logged) * 100).toFixed(1);
        },

        get monthlyRejectedHours() {
            return (parseFloat(this.monthlyStats.totalRejectedHours) || 0).toFixed(1);
        },

        get monthlyPendingHours() {
            return (parseFloat(this.monthlyStats.totalPendingHours) || 0).toFixed(1);
        },

        get monthlyThp() {
            return parseFloat(this.monthlyStats.totalThp) || 0;
        },

        get monthlyWorkersCount() {
            return parseInt(this.monthlyStats.totalWorkers) || 0;
        },

        get monthlySudahCount() {
            return parseInt(this.monthlyStats.totalSudah) || 0;
        },

        get monthlyBelumCount() {
            return parseInt(this.monthlyStats.totalBelum) || 0;
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

        // View Mode: 'live' (rekap otomatis dinamis) vs 'saved' (slip tersimpan di database)
        viewMode: 'live',
        savedPeriodScope: 'current', // 'current' (sesuai cut-off terpilih) | 'all' (semua slip di DB)

        // Dynamic Live Payroll Data (Cut-off 22 - 21 / 22 Okt - 15 Nov)
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
        investorShareTotal: 0,
        investorBillableHours: 0,
        investorDeviceCount: 0,

        // Daily Breakdown Modal State
        showDailyModal: false,
        selectedWorkerDaily: null,

        // Saved Database Payslips (Both historical training & newly generated official slips)
        trainingSlips: [],

        formatRupiah,
        formatDate,
        formatTime,
        formatPeriod,

        matchesPeriod(slipPeriod, periodObj) {
            if (!slipPeriod || !periodObj) return false;
            const s = String(slipPeriod).toLowerCase().trim();
            const pName = String(periodObj.name || '').toLowerCase().trim();
            if (s === pName) return true;
            if (periodObj.isTraining || periodObj.id === 'training_2026') {
                return s.includes('training') || s.includes('18 sep') || s.includes('sep 2026');
            }
            const cleanName = pName.replace(/\s*\(.*?\)/g, '').trim();
            if (s === cleanName) return true;
            const parts = cleanName.split('-').map(x => x.trim());
            if (parts.length === 2) {
                if (s.includes(parts[0]) && s.includes(parts[1])) return true;
                const p0NoYear = parts[0].replace(/\b\d{4}\b/, '').trim();
                const p1NoYear = parts[1].replace(/\b\d{4}\b/, '').trim();
                if (p0NoYear && p1NoYear && s.includes(p0NoYear) && s.includes(p1NoYear)) return true;
            }
            return false;
        },

        async init() {
            // Watch sidebarOpen in dashboardData
            this.$watch('sidebarOpen', val => { try { if (window.innerWidth >= 768) { localStorage.setItem('aiku_sidebar_open', String(val)); }; } catch(e) {} });
            await this.fetchPeriods();
            // Prefetch slips to show count badges on tabs
            try {
                const res = await fetch('/api/slips');
                const data = await res.json();
                if (Array.isArray(data)) this.trainingSlips = data;
            } catch(e) {}
        },

        get isTrainingView() {
            return this.viewMode === 'saved' || this.selectedPeriodId === 'training_2026';
        },

        get isHistoricalTrainingSelected() {
            return this.selectedPeriodId === 'training_2026';
        },

        get scopedTrainingSlips() {
            if (!Array.isArray(this.trainingSlips)) return [];
            if (this.isHistoricalTrainingSelected) {
                return this.trainingSlips.filter(s => !s.superseded_by_id && this.matchesPeriod(s.period, this.selectedPeriodObj || { id: 'training_2026', isTraining: true }));
            }
            if (this.savedPeriodScope === 'all') {
                return this.trainingSlips.filter(s => !s.superseded_by_id);
            }
            return this.trainingSlips.filter(s => !s.superseded_by_id && this.matchesPeriod(s.period, this.selectedPeriodObj));
        },

        get countSlipsCurrentPeriod() {
            if (!Array.isArray(this.trainingSlips)) return 0;
            return this.trainingSlips.filter(s => !s.superseded_by_id && this.matchesPeriod(s.period, this.selectedPeriodObj)).length;
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
            let list = this.scopedTrainingSlips;
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
                return this.scopedTrainingSlips.filter(s => s.status === 'sudah').length;
            }
            return this.liveWorkers.filter(w => w.status === 'sudah').length;
        },

        get countBelum() {
            if (this.isTrainingView) {
                return this.scopedTrainingSlips.filter(s => s.status !== 'sudah').length;
            }
            return this.liveWorkers.filter(w => w.status !== 'sudah').length;
        },

        get hasSlipConflicts() {
            return this.isTrainingView ? this.scopedTrainingSlips.some(s => s.duplicate_count > 1) : (this.liveStats.conflictWorkers || 0) > 0;
        },

        get currentStats() {
            if (this.isTrainingView) {
                let totalThp = 0;
                let totalLogged = 0;
                let totalAppr = 0;
                let totalRej = 0;
                let totalPend = 0;

                this.scopedTrainingSlips.filter(s => s.duplicate_count <= 1).forEach(slip => {
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
                    count: this.scopedTrainingSlips.length
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

        async switchViewMode(mode) {
            this.viewMode = mode;
            if (mode === 'live' && this.selectedPeriodId === 'training_2026') {
                const currentOrRecent = this.periods.find(p => p.isCurrent) || this.periods.find(p => !p.isTraining);
                if (currentOrRecent) {
                    this.selectedPeriodId = currentOrRecent.id;
                }
            }
            await this.loadSelectedPeriodData();
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
            if (this.selectedPeriodId === 'training_2026') {
                this.viewMode = 'saved';
            }
            await this.loadSelectedPeriodData();
        },

        async selectPeriod(periodId) {
            this.selectedPeriodId = periodId;
            if (this.selectedPeriodId === 'training_2026') {
                this.viewMode = 'saved';
            }
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
                    this.investorShareTotal = data.investor_share?.total_investor_profit_idr || 0;
                    this.investorBillableHours = data.investor_share?.total_billable_hours || 0;
                    this.investorDeviceCount = data.investor_share?.device_count || 0;
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
            if (newStatus === 'sudah') {
                const confirmation = await Swal.fire({ title: 'Finalisasi pembayaran?', text: 'Estimasi live ' + formatRupiah(worker.thp) + ' termasuk bonus dan carry-over akan disimpan sebagai snapshot terkunci. Periksa angka sebelum melanjutkan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Finalisasi pembayaran', cancelButtonText: 'Batal' });
                if (!confirmation.isConfirmed) return;
            }
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
                        status: newStatus,
                        expected_thp: newStatus === 'sudah' ? worker.thp : null
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    worker.status = prevStatus;
                    alert(payrollError(data));
                } else {
                    await this.loadSelectedPeriodData();
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
                    alert(payrollError(data));
                } else {
                    await this.loadSelectedPeriodData();
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

        async resolveDuplicates(item) {
            const result = await Swal.fire({
                title: 'Gunakan slip #' + item.id + ' sebagai slip utama?',
                text: 'THP ' + formatRupiah(item.total_thp) + ', bonus ' + formatRupiah(item.bonus || 0) + ', carry-over ' + formatRupiah(item.pending_carry_over_amount || 0) + '. Versi lain diarsipkan tanpa menggabungkan nominal atau menghapus dokumen.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Pilih slip ini', cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
            try {
                const res = await fetch(`/api/payslips/${item.id}/resolve-duplicates`, { method: 'POST' });
                const data = await res.json();
                if (!res.ok) throw new Error(payrollError(data));
                await this.loadSelectedPeriodData();
            } catch (error) {
                await Swal.fire({ icon: 'error', title: 'Belum dapat direkonsiliasi', text: error.message });
            }
        },

        async deleteSlip(id) {
            const result = await Swal.fire({
                title: 'Hapus Payslip?',
                text: "Hapus slip draft ini? Slip final dan arsip rekonsiliasi dilindungi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/api/payslips/${id}`, { method: 'DELETE' });
                const data = await response.json();
                if (!response.ok) {
                    await Swal.fire({ icon: 'error', title: 'Slip tidak dihapus', text: payrollError(data) });
                    return;
                }
                
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
        pinUnlocked: false,
        pinInput: '',
        pinError: '',
        pinVerifying: false,
        
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
        investorShareTotal: 0,
        investorBillableHours: 0,
        investorDeviceCount: 0,

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
            // Watch sidebarOpen in profitData
            this.$watch('sidebarOpen', val => { try { if (window.innerWidth >= 768) { localStorage.setItem('aiku_sidebar_open', String(val)); }; } catch(e) {} });
            this.$nextTick(() => this.$refs.profitPinInput?.focus());
        },

        async verifyPin() {
            const pin = (this.pinInput || '').trim();
            if (!pin) {
                this.pinError = 'Masukkan PIN terlebih dahulu.';
                return;
            }
            this.pinVerifying = true;
            this.pinError = '';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const res = await fetch('/api/profit/verify-pin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ pin })
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'PIN tidak valid.');
                }
                this.pinUnlocked = true;
                this.pinInput = '';
                await this.loadProtectedProfitData();
            } catch (e) {
                this.pinError = e.message || 'PIN salah.';
                this.pinInput = '';
                this.$nextTick(() => this.$refs.profitPinInput?.focus());
            } finally {
                this.pinVerifying = false;
            }
        },

        async loadProtectedProfitData() {
            try {
                const res = await fetch('/api/finance/settings');
                const data = await res.json();
                if (res.ok && data.settings) Object.assign(this, data.settings);
            } catch (e) { console.error('Gagal memuat pengaturan profit', e); }
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
                    this.investorShareTotal = data.investor_share?.total_investor_profit_idr || 0;
                    this.investorBillableHours = data.investor_share?.total_billable_hours || 0;
                    this.investorDeviceCount = data.investor_share?.device_count || 0;
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
            const investorShare = this.isTrainingView ? 0 : (parseFloat(this.investorShareTotal) || 0);
            const netProfit = grossProfit - opEx - investorShare;
            const profitMarginPct = grossRevenue > 0 ? ((netProfit / grossRevenue) * 100).toFixed(1) : '0.0';
            const payrollRatioPct = grossRevenue > 0 ? Math.min(100, ((totalPayrollCost / grossRevenue) * 100)).toFixed(1) : '0.0';
            const opExRatioPct = grossRevenue > 0 ? Math.min(100, ((opEx / grossRevenue) * 100)).toFixed(1) : '0.0';
            const investorRatioPct = grossRevenue > 0 ? Math.min(100, ((investorShare / grossRevenue) * 100)).toFixed(1) : '0.0';
            const profitRatioPct = grossRevenue > 0 ? Math.max(0, (100 - parseFloat(payrollRatioPct) - parseFloat(opExRatioPct) - parseFloat(investorRatioPct))).toFixed(1) : '0.0';

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
                investorShare,
                netProfit,
                profitMarginPct,
                payrollRatioPct,
                opExRatioPct,
                investorRatioPct,
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
        colorPickerId: null,
        customHexColor: '#00b090',
        formData: { id: null, nama: '', no_wa: '', email: '', domisili: '', shift: '', hp: '', no_rek: '', role: 'contributor', badge_color: '' },

        badgeColorOptions: [
            { key: 'rose', label: 'Merah Mawar', bg: '#ffe4e6', text: '#be123c', border: '#fda4af', dot: '#f43f5e' },
            { key: '#dc2626', label: 'Merah Terang', bg: '#fee2e2', text: '#b91c1c', border: '#fca5a5', dot: '#dc2626' },
            { key: '#ea580c', label: 'Coral', bg: '#ffedd5', text: '#c2410c', border: '#fdba74', dot: '#ea580c' },
            { key: 'orange', label: 'Oranye', bg: '#ffedd5', text: '#c2410c', border: '#fdba74', dot: '#f97316' },
            { key: 'amber', label: 'Kuning Emas', bg: '#fef3c7', text: '#b45309', border: '#fcd34d', dot: '#f59e0b' },
            { key: '#ca8a04', label: 'Mustard', bg: '#fef9c3', text: '#854d0e', border: '#fde047', dot: '#ca8a04' },
            { key: 'lime', label: 'Hijau Lime', bg: '#ecfccb', text: '#4d7c0f', border: '#bef264', dot: '#84cc16' },
            { key: '#16a34a', label: 'Hijau Daun', bg: '#dcfce7', text: '#15803d', border: '#86efac', dot: '#16a34a' },
            { key: 'emerald', label: 'Emerald', bg: '#d1fae5', text: '#047857', border: '#6ee7b7', dot: '#10b981' },
            { key: 'teal', label: 'Tosca', bg: '#ccfbf1', text: '#0f766e', border: '#5eead4', dot: '#14b8a6' },
            { key: 'cyan', label: 'Cyan', bg: '#cffafe', text: '#0e7490', border: '#67e8f9', dot: '#06b6d4' },
            { key: 'sky', label: 'Biru Langit', bg: '#e0f2fe', text: '#0369a1', border: '#7dd3fc', dot: '#0ea5e9' },
            { key: 'blue', label: 'Biru Royal', bg: '#dbeafe', text: '#1d4ed8', border: '#93c5fd', dot: '#3b82f6' },
            { key: '#1e3a8a', label: 'Biru Navy', bg: '#dbeafe', text: '#1e3a8a', border: '#93c5fd', dot: '#1e40af' },
            { key: 'indigo', label: 'Indigo', bg: '#e0e7ff', text: '#4338ca', border: '#a5b4fc', dot: '#6366f1' },
            { key: 'violet', label: 'Violet', bg: '#ede9fe', text: '#6d28d9', border: '#c4b5fd', dot: '#8b5cf6' },
            { key: 'purple', label: 'Ungu', bg: '#f3e8ff', text: '#7e22ce', border: '#d8b4fe', dot: '#a855f7' },
            { key: 'fuchsia', label: 'Magenta', bg: '#fae8ff', text: '#a21caf', border: '#f0abfc', dot: '#d946ef' },
            { key: 'pink', label: 'Pink', bg: '#fce7f3', text: '#be185d', border: '#f9a8d4', dot: '#ec4899' },
            { key: '#92400e', label: 'Coklat Kayu', bg: '#fef3c7', text: '#78350f', border: '#fcd34d', dot: '#92400e' },
            { key: '#7c2d12', label: 'Terakota', bg: '#ffedd5', text: '#7c2d12', border: '#fdba74', dot: '#9a3412' },
            { key: '#4d7c0f', label: 'Olive', bg: '#ecfccb', text: '#3f6212', border: '#bef264', dot: '#4d7c0f' },
            { key: '#334155', label: 'Charcoal', bg: '#e2e8f0', text: '#1e293b', border: '#94a3b8', dot: '#334155' },
            { key: 'slate', label: 'Abu Netral', bg: '#f1f5f9', text: '#334155', border: '#cbd5e1', dot: '#64748b' },
        ],

        getBadgeColorMeta(colorKey) {
            const preset = this.badgeColorOptions.find(o => o.key.toLowerCase() === (colorKey || '').toLowerCase());
            if (preset) return preset;
            if (typeof colorKey === 'string' && /^#[0-9a-f]{6}$/i.test(colorKey)) {
                return this.hexToBadgeMeta(colorKey);
            }
            return this.badgeColorOptions[this.badgeColorOptions.length - 1];
        },

        hexToBadgeMeta(hex) {
            const clean = hex.replace('#', '');
            const r = parseInt(clean.substring(0, 2), 16);
            const g = parseInt(clean.substring(2, 4), 16);
            const b = parseInt(clean.substring(4, 6), 16);
            const darkR = Math.max(0, Math.round(r * 0.62));
            const darkG = Math.max(0, Math.round(g * 0.62));
            const darkB = Math.max(0, Math.round(b * 0.62));
            return {
                key: '#' + clean.toLowerCase(),
                label: '#' + clean.toUpperCase(),
                bg: `rgba(${r}, ${g}, ${b}, 0.14)`,
                text: `rgb(${darkR}, ${darkG}, ${darkB})`,
                border: `rgba(${r}, ${g}, ${b}, 0.42)`,
                dot: '#' + clean.toLowerCase(),
            };
        },

        openQuickColorPicker(contributor) {
            if (this.colorPickerId === contributor.id) {
                this.colorPickerId = null;
                return;
            }
            const meta = this.getBadgeColorMeta(contributor.badge_color);
            this.customHexColor = meta.dot || '#00b090';
            this.colorPickerId = contributor.id;
        },

        // Performance & Evaluation State
        studioSummary: null,
        isLoadingSummary: false,
        perfModalOpen: false,
        isLoadingPerf: false,
        selectedPerf: null,
        perfError: '',
        perfContributor: null,
        perfRequestId: 0,
        perfLookup: {},

        // Search & Filter State
        search: '',
        filterRole: 'all',
        filterGrade: 'all',

        get filteredContributors() {
            const q = (this.search || '').trim().toLowerCase();
            const role = this.filterRole;
            const grade = this.filterGrade;

            return this.contributors.filter(c => {
                if (role !== 'all' && (c.role || 'contributor') !== role) {
                    return false;
                }

                if (grade !== 'all') {
                    const perf = this.perfLookup[c.id];
                    if (!perf || perf.grade !== grade) {
                        return false;
                    }
                }

                if (!q) return true;

                const nama = (c.nama || '').toLowerCase();
                const email = (c.email || '').toLowerCase();
                const wa = (c.no_wa || '').toLowerCase();
                const domisili = (c.domisili || '').toLowerCase();
                const shift = (c.shift || '').toLowerCase();
                const hp = (c.hp || '').toLowerCase();
                const noRek = (c.no_rek || '').toLowerCase();
                const idStr = String(c.id || '');

                return nama.includes(q)
                    || email.includes(q)
                    || wa.includes(q)
                    || domisili.includes(q)
                    || shift.includes(q)
                    || hp.includes(q)
                    || noRek.includes(q)
                    || idStr.includes(q);
            });
        },

        init() {
            // Watch sidebarOpen in contributorsListData
            this.$watch('sidebarOpen', val => { try { if (window.innerWidth >= 768) { localStorage.setItem('aiku_sidebar_open', String(val)); }; } catch(e) {} });
            this.fetchContributors();
            this.fetchStudioSummary();
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

        async fetchStudioSummary() {
            this.isLoadingSummary = true;
            try {
                const response = await fetch('/api/contributors/studio-summary');
                if (response.ok) {
                    const data = await response.json();
                    this.studioSummary = data.studio || null;
                    const lookup = {};
                    (data.contributors_summary || []).forEach(item => {
                        lookup[item.id] = item;
                    });
                    this.perfLookup = lookup;
                }
            } catch (error) {
                console.error("Error fetching studio summary:", error);
            } finally {
                this.isLoadingSummary = false;
            }
        },

        async openPerfModal(contributor) {
            if (!contributor?.id) return;
            const requestId = ++this.perfRequestId;
            this.perfModalOpen = true;
            this.isLoadingPerf = true;
            this.selectedPerf = null;
            this.perfError = '';
            this.perfContributor = contributor;
            try {
                const response = await fetch(`/api/contributors/${contributor.id}/performance`, {headers: {Accept: 'application/json'}});
                const data = await response.json().catch(() => null);
                if (!response.ok || !data?.contributor || !data.recordings || !data.attendance || !data.work_reports || !data.evaluation) {
                    throw new Error('Analisis kinerja belum dapat dimuat. Silakan coba lagi.');
                }
                if (requestId === this.perfRequestId && this.perfModalOpen) this.selectedPerf = data;
            } catch (error) {
                if (requestId === this.perfRequestId && this.perfModalOpen) this.perfError = 'Analisis kinerja belum dapat dimuat. Silakan coba lagi.';
            } finally {
                if (requestId === this.perfRequestId) this.isLoadingPerf = false;
            }
        },

        closePerfModal() {
            this.perfRequestId++;
            this.perfModalOpen = false;
            this.isLoadingPerf = false;
            this.selectedPerf = null;
            this.perfError = '';
        },

        openModal(contributor = null) {
            if (contributor) {
                this.isEditing = true;
                this.formData = { ...contributor, role: contributor.role || 'contributor', badge_color: contributor.badge_color || '' };
            } else {
                this.isEditing = false;
                this.formData = { id: null, nama: '', no_wa: '', email: '', domisili: '', shift: '', hp: '', no_rek: '', role: 'contributor', badge_color: '' };
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
                
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: this.isEditing ? 'Data kontributor & role berhasil disimpan.' : 'Kontributor baru berhasil ditambahkan.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                }
                this.closeModal();
                this.fetchContributors();
                this.fetchStudioSummary();
            } catch (error) {
                console.error("Error saving contributor:", error);
            }
        },

        async quickUpdateColor(contributor, colorKey) {
            if (!contributor?.id) return;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const res = await fetch(`/api/contributors/${contributor.id}/color`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ badge_color: colorKey || null }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal mengubah warna.');
                contributor.badge_color = data.badge_color;
                this.colorPickerId = null;
                if (window.Swal) {
                    window.Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Warna ${contributor.nama} diperbarui`,
                        timer: 1600,
                        showConfirmButton: false,
                    });
                }
            } catch (error) {
                console.error('Error updating color:', error);
            }
        },

        async quickToggleRole(contributor) {
            const newRole = contributor.role === 'admin' ? 'contributor' : 'admin';
            const roleName = newRole === 'admin' ? 'Admin' : 'Pekerja (Contributor)';

            const confirmed = window.Swal
                ? (await window.Swal.fire({
                    title: 'Ubah Role Akun?',
                    text: `Apakah Anda yakin ingin mengubah role "${contributor.nama}" menjadi ${roleName}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#00B090',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Ubah Role',
                    cancelButtonText: 'Batal'
                })).isConfirmed
                : confirm(`Ubah role "${contributor.nama}" menjadi ${roleName}?`);

            if (!confirmed) return;

            try {
                const res = await fetch(`/api/contributors/${contributor.id}/role`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ role: newRole })
                });
                if (res.ok) {
                    contributor.role = newRole;
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Role Berhasil Diubah',
                            text: `Role "${contributor.nama}" sekarang adalah ${roleName}.`,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    }
                    this.fetchContributors();
                    this.fetchStudioSummary();
                }
            } catch (error) {
                console.error("Error updating role:", error);
            }
        },
        
        async deleteContributor(id) {
            const confirmed = window.Swal
                ? (await window.Swal.fire({
                    title: 'Hapus Kontributor?',
                    text: 'Apakah Anda yakin ingin menghapus kontributor ini? Akses login terkait akan dinonaktifkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                })).isConfirmed
                : confirm('Are you sure you want to delete this contributor?');

            if (!confirmed) return;

            try {
                await fetch(`/api/contributors/${id}`, { method: 'DELETE' });
                this.fetchContributors();
                this.fetchStudioSummary();
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
        isLocked: false,
        savedTotalThp: null,
        loadError: false,
        evidenceRequest: 0,
        pendingError: '',
        isLoadingCarryOver: false,
        pendingCarryOver: { available: false, total_amount: 0, total_hours: 0, total_mins: 0, tasks: [] },
        formData: {
            employee_name: '',
            period: '',
            bonus: 0,
            pending_carry_over_amount: 0,
            pending_carry_over_hours: 0,
            pending_carry_over_data: null,
            include_pending_carry_over: false,
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

            // Always check pending carry over on startup if both fields present
            if (this.formData.employee_name && this.formData.period && !this.formData.pending_carry_over_amount) {
                await this.checkPendingCarryOver();
            }

            // Watch for changes on employee_name or period
            this.$watch('formData.employee_name', async (val) => {
                if (val && this.formData.period) {
                    await this.checkPendingCarryOver();
                }
            });
            this.$watch('formData.period', async (val) => {
                if (val && this.formData.employee_name) {
                    await this.checkPendingCarryOver();
                }
            });
        },

        async loadSavedSlip(slipId) {
            try {
                const res = await fetch(`/api/payslips/${slipId}`);
                if (!res.ok) throw new Error(payrollError(await res.json()));
                if (res.ok) {
                    const data = await res.json();
                    this.isLocked = !!(data.status === 'sudah' || (data.is_locked && data.status !== 'belum') || data.superseded_by_id);
                    this.savedTotalThp = data.total_thp;
                    this.formData.employee_name = data.employee_name;
                    this.formData.period = data.period;
                    this.formData.bonus = data.bonus !== undefined ? data.bonus : 0;
                    if (data.pending_carry_over_amount > 0) {
                        this.formData.pending_carry_over_amount = data.pending_carry_over_amount;
                        this.formData.pending_carry_over_hours = data.pending_carry_over_hours || 0;
                        this.formData.pending_carry_over_data = data.pending_carry_over_data || [];
                        this.formData.include_pending_carry_over = true;
                        this.pendingCarryOver = {
                            available: true,
                            total_amount: data.pending_carry_over_amount,
                            total_hours: data.pending_carry_over_hours || 0,
                            tasks: data.pending_carry_over_data || []
                        };
                    } else {
                        await this.checkPendingCarryOver();
                    }
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
                this.loadError = true;
                this.message = error.message;
                console.error("Error fetching slip data:", error);
            }
        },

        async loadWorkerEvidence(workerName = null, periodName = null, periodId = null, forceRefresh = false) {
            const worker = workerName || this.formData.employee_name;
            const period = periodName || this.formData.period;
            if (!worker) return;

            if (this.isLocked && forceRefresh) return;
            const requestId = ++this.evidenceRequest;
            this.isLoadingEvidence = true;
            this.loadError = false;
            try {
                let url = `/api/payroll/worker-evidence?worker=${encodeURIComponent(worker)}`;
                if (periodId) url += `&periodId=${encodeURIComponent(periodId)}`;
                if (period) url += `&period=${encodeURIComponent(period)}`;
                if (forceRefresh) url += `&refresh=1`;

                const res = await fetch(url);
                if (!res.ok) throw new Error(payrollError(await res.json()));
                if (res.ok) {
                    const data = await res.json();
                    if (requestId !== this.evidenceRequest) return;
                    this.isLocked = !!data.is_locked;
                    this.savedTotalThp = data.total_thp ?? null;
                    this.formData.pending_carry_over_amount = 0;
                    this.formData.pending_carry_over_hours = 0;
                    this.formData.pending_carry_over_data = null;
                    this.formData.include_pending_carry_over = false;
                    this.slipId = data.existingSlipId || null;
                    if (data.period) this.formData.period = data.period;
                    if (data.bonus !== undefined) this.formData.bonus = data.bonus;
                    if (data.pending_carry_over_amount > 0) {
                        this.formData.pending_carry_over_amount = data.pending_carry_over_amount;
                        this.formData.pending_carry_over_hours = data.pending_carry_over_hours || 0;
                        this.formData.pending_carry_over_data = data.pending_carry_over_data || [];
                        this.formData.include_pending_carry_over = true;
                        this.pendingCarryOver = {
                            available: true,
                            total_amount: data.pending_carry_over_amount,
                            total_hours: data.pending_carry_over_hours || 0,
                            tasks: data.pending_carry_over_data || []
                        };
                    } else {
                        await this.checkPendingCarryOver();
                    }
                    if (requestId !== this.evidenceRequest) return;
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
                if (requestId !== this.evidenceRequest) return;
                this.loadError = true;
                this.message = error.message;
                console.error("Error fetching live worker evidence:", error);
            } finally {
                if (requestId === this.evidenceRequest) this.isLoadingEvidence = false;
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

        async checkPendingCarryOver() {
            if (!this.formData.employee_name || !this.formData.period) return;
            this.isLoadingCarryOver = true;
            this.pendingError = '';
            const context = [this.formData.employee_name, this.formData.period, this.slipId].join('|');
            try {
                let url = `/api/payroll/pending-carry-over?worker=${encodeURIComponent(this.formData.employee_name)}&period=${encodeURIComponent(this.formData.period)}`;
                if (this.slipId && !String(this.slipId).startsWith('LIVE-')) {
                    url += `&exclude_slip_id=${encodeURIComponent(this.slipId)}`;
                }
                const res = await fetch(url);
                if (!res.ok) throw new Error(payrollError(await res.json()));
                if (res.ok) {
                    const data = await res.json();
                    if (context !== [this.formData.employee_name, this.formData.period, this.slipId].join('|')) return;
                    this.pendingCarryOver = data;
                }
            } catch (err) {
                this.pendingError = 'Saldo pending gagal dimuat. Coba muat ulang; nilai Rp0 belum dapat dipastikan.';
                console.error("Error checking pending carry over:", err);
            } finally {
                this.isLoadingCarryOver = false;
            }
        },

        onToggleCarryOverCheckbox() {
            if (this.formData.include_pending_carry_over && this.pendingCarryOver && this.pendingCarryOver.available) {
                this.formData.pending_carry_over_amount = this.pendingCarryOver.total_amount;
                this.formData.pending_carry_over_hours = this.pendingCarryOver.total_hours;
                this.formData.pending_carry_over_data = this.pendingCarryOver.tasks;
            } else {
                this.formData.include_pending_carry_over = false;
                this.formData.pending_carry_over_amount = 0;
                this.formData.pending_carry_over_hours = 0;
                this.formData.pending_carry_over_data = null;
            }
        },

        togglePendingCarryOver() {
            this.formData.include_pending_carry_over = !this.formData.include_pending_carry_over;
            this.onToggleCarryOverCheckbox();
        },

        calculateTHP() {
            if (this.isLocked && this.savedTotalThp !== null) return this.savedTotalThp;
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
            const co = this.formData.include_pending_carry_over ? (parseFloat(this.formData.pending_carry_over_amount) || 0) : 0;
            return Math.round(base + b + co);
        },

        formatRupiah,

        async submitForm() {
            if (this.isSubmitting || this.isLocked || this.loadError || this.isLoadingEvidence || this.isSuccess) return;
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
                    const wasExisting = !!this.slipId;
                    this.slipId = slipId;
                    this.message = wasExisting ? 'Slip updated successfully! Redirecting...' : 'Slip saved successfully! Redirecting...';
                    setTimeout(() => {
                        window.location.href = '/slip/' + slipId;
                    }, 1000);
                } else {
                    this.isSuccess = false;
                    this.message = payrollError(result);
                    this.$nextTick(() => this.$refs.payrollMessage?.focus());
                }
            } catch (error) {
                this.isSuccess = false;
                this.message = error.message || 'Gagal menyimpan slip.';
                this.$nextTick(() => this.$refs.payrollMessage?.focus());
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

    // Investor & Aset HP Analytics Logic
    Alpine.data('investorAnalyticsData', () => ({
        sidebarOpen: getInitialSidebarOpen(),
        isLoading: true,
        saving: false,
        periods: [],
        selectedPeriodId: '',
        selectedPeriodObj: null,
        searchQuery: '',

        totals: {
            investor_count: 0,
            active_investor_count: 0,
            device_count: 0,
            total_tasks: 0,
            total_billable_hours: 0,
            total_logged_hours: 0,
            total_pending_hours: 0,
            total_rejected_hours: 0,
            total_investor_profit_idr: 0,
            total_paid_idr: 0,
            total_unpaid_idr: 0,
        },
        investors: [],
        dailyUtilization: [],
        availableDevices: [],

        // Modals
        showInvestorModal: false,
        isEditingInvestor: false,
        investorForm: {
            id: null, code: '', name: '', no_wa: '', email: '',
            bank_name: '', no_rek: '', account_holder: '', is_active: true, notes: ''
        },

        showContractModal: false,
        isEditingContract: false,
        contractTargetInvestor: null,
        contractForm: {
            id: null, device_mode: 'new', device_key: '', new_device_label: '',
            device_code: '', rate_per_hour_idr: 10000, rate_basis: 'billable',
            effective_from: '', effective_to: '', is_active: true, notes: ''
        },

        selectedInvestorDetail: null,
        showDetailModal: false,

        showAccountModal: false,
        accountTargetInvestor: null,
        accountForm: { email: '', username: '', password: '', is_active: true },

        formatRupiah,

        get filteredInvestors() {
            const q = (this.searchQuery || '').trim().toLowerCase();
            if (!q) return this.investors;
            return this.investors.filter(inv => {
                const hay = [
                    inv.code, inv.name, inv.no_wa, inv.email,
                    ...(inv.contracts || []).map(c => `${c.device_code} ${c.device_label} ${c.active_email || ''}`)
                ].join(' ').toLowerCase();
                return hay.includes(q);
            });
        },

        get maxDailyProfit() {
            if (!this.dailyUtilization.length) return 1;
            return Math.max(1, ...this.dailyUtilization.map(d => d.profit_idr || 0));
        },

        async init() {
            this.$watch('sidebarOpen', val => {
                try { if (window.innerWidth >= 768) localStorage.setItem('aiku_sidebar_open', String(val)); } catch (e) {}
            });
            await this.loadPeriods();
        },

        async loadPeriods() {
            this.isLoading = true;
            try {
                const res = await fetch('/api/payroll/periods');
                const data = await res.json();
                const allPeriods = (data.periods || []).filter(p => !p.isTraining);
                this.periods = allPeriods;
                this.selectedPeriodId = data.defaultPeriodId && allPeriods.some(p => p.id === data.defaultPeriodId)
                    ? data.defaultPeriodId
                    : (allPeriods[0]?.id || '');
                await this.loadAnalytics();
            } catch (e) {
                console.error('Gagal memuat periode investor:', e);
                this.isLoading = false;
            }
        },

        async selectPeriod(periodId) {
            this.selectedPeriodId = periodId;
            await this.loadAnalytics();
        },

        async loadAnalytics() {
            if (!this.selectedPeriodId) return;
            this.isLoading = true;
            this.selectedPeriodObj = this.periods.find(p => p.id === this.selectedPeriodId) || null;
            try {
                const res = await fetch(`/api/investors/analytics?periodId=${encodeURIComponent(this.selectedPeriodId)}`, {
                    headers: { Accept: 'application/json' }
                });
                const data = await res.json();
                this.totals = data.totals || this.totals;
                this.investors = data.investors || [];
                this.dailyUtilization = data.daily_utilization || [];
                this.availableDevices = data.available_devices || [];
                if (this.selectedInvestorDetail) {
                    const refreshed = this.investors.find(i => i.id === this.selectedInvestorDetail.id);
                    if (refreshed) this.selectedInvestorDetail = refreshed;
                }
            } catch (e) {
                console.error('Gagal memuat analitik investor:', e);
            } finally {
                this.isLoading = false;
            }
        },

        openInvestorModal(inv = null) {
            if (inv) {
                this.isEditingInvestor = true;
                this.investorForm = {
                    id: inv.id,
                    code: inv.code || '',
                    name: inv.name || '',
                    no_wa: inv.no_wa || '',
                    email: inv.email || '',
                    bank_name: inv.bank_name || '',
                    no_rek: inv.no_rek || '',
                    account_holder: inv.account_holder || '',
                    is_active: inv.is_active !== false,
                    notes: inv.notes || '',
                };
            } else {
                this.isEditingInvestor = false;
                this.investorForm = {
                    id: null, code: '', name: '', no_wa: '', email: '',
                    bank_name: '', no_rek: '', account_holder: '', is_active: true, notes: ''
                };
            }
            this.showInvestorModal = true;
        },

        async saveInvestor() {
            this.saving = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const url = this.isEditingInvestor ? `/api/investors/${this.investorForm.id}` : '/api/investors';
                const method = this.isEditingInvestor ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(this.investorForm)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(', ') || 'Gagal menyimpan investor.');
                this.showInvestorModal = false;
                await this.loadAnalytics();
                window.Swal?.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1800, showConfirmButton: false });
            } catch (e) {
                window.Swal ? window.Swal.fire({ icon: 'error', title: 'Gagal', text: e.message }) : alert(e.message);
            } finally {
                this.saving = false;
            }
        },

        async deleteInvestor(inv) {
            const ok = window.Swal
                ? (await window.Swal.fire({
                    title: 'Hapus Investor?',
                    text: `Data ${inv.name} beserta kontrak HP-nya akan dihapus.`,
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
                })).isConfirmed
                : confirm(`Hapus investor ${inv.name}?`);
            if (!ok) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(`/api/investors/${inv.id}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            const data = await res.json();
            if (res.ok) {
                await this.loadAnalytics();
                window.Swal?.fire({ icon: 'success', title: 'Terhapus', text: data.message, timer: 1600, showConfirmButton: false });
            }
        },

        openContractModal(inv, contract = null) {
            this.contractTargetInvestor = inv;
            if (contract) {
                this.isEditingContract = true;
                this.contractForm = {
                    id: contract.id,
                    device_mode: 'existing',
                    device_key: contract.device_key,
                    new_device_label: '',
                    device_code: contract.device_code || '',
                    rate_per_hour_idr: contract.rate_per_hour_idr ?? 10000,
                    rate_basis: contract.rate_basis || 'billable',
                    effective_from: contract.effective_from || '',
                    effective_to: contract.effective_to || '',
                    is_active: contract.is_active !== false,
                    notes: contract.notes || '',
                };
            } else {
                this.isEditingContract = false;
                const nextIdx = (inv.contracts?.length || 0) + 1;
                const suggestedCode = `${inv.code}-HP${nextIdx}`;
                this.contractForm = {
                    id: null,
                    device_mode: 'new',
                    device_key: '',
                    new_device_label: suggestedCode,
                    device_code: suggestedCode,
                    rate_per_hour_idr: 10000,
                    rate_basis: 'billable',
                    effective_from: '',
                    effective_to: '',
                    is_active: true,
                    notes: '',
                };
            }
            this.showContractModal = true;
        },

        async saveContract() {
            this.saving = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const payload = { ...this.contractForm };
                if (!this.isEditingContract && payload.device_mode === 'new') {
                    payload.device_key = '';
                }
                const url = this.isEditingContract
                    ? `/api/investors/contracts/${this.contractForm.id}`
                    : `/api/investors/${this.contractTargetInvestor.id}/contracts`;
                const method = this.isEditingContract ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(', ') || 'Gagal menyimpan unit HP.');
                this.showContractModal = false;
                await this.loadAnalytics();
                window.Swal?.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1800, showConfirmButton: false });
            } catch (e) {
                window.Swal ? window.Swal.fire({ icon: 'error', title: 'Gagal', text: e.message }) : alert(e.message);
            } finally {
                this.saving = false;
            }
        },

        async deleteContract(contract) {
            const ok = window.Swal
                ? (await window.Swal.fire({
                    title: 'Lepas Kontrak HP?',
                    text: `Kontrak unit ${contract.device_code} akan dihapus dari investor ini.`,
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'Ya, Lepas', cancelButtonText: 'Batal'
                })).isConfirmed
                : confirm(`Lepas kontrak HP ${contract.device_code}?`);
            if (!ok) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(`/api/investors/contracts/${contract.id}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            if (res.ok) {
                await this.loadAnalytics();
            }
        },

        async togglePayout(inv) {
            const nextStatus = inv.payout_status === 'paid' ? 'pending' : 'paid';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(`/api/investors/${inv.id}/payout`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ period_id: this.selectedPeriodId, status: nextStatus })
            });
            const data = await res.json();
            if (res.ok) {
                await this.loadAnalytics();
                window.Swal?.fire({ icon: 'success', title: 'Status Diperbarui', text: data.message, timer: 1800, showConfirmButton: false });
            }
        },

        openDetail(inv) {
            this.selectedInvestorDetail = inv;
            this.showDetailModal = true;
        },

        openAccountModal(inv) {
            this.accountTargetInvestor = inv;
            const acc = inv.login_account;
            this.accountForm = {
                email: acc?.email || inv.email || '',
                username: acc?.name || inv.name || '',
                password: '',
                is_active: acc ? Boolean(acc.is_active) : true,
            };
            this.showAccountModal = true;
        },

        async saveAccount() {
            if (!this.accountTargetInvestor) return;
            this.saving = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const res = await fetch(`/api/investors/${this.accountTargetInvestor.id}/account`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(this.accountForm)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(', ') || 'Gagal menyimpan akun login.');
                this.showAccountModal = false;
                await this.loadAnalytics();
                window.Swal?.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1800, showConfirmButton: false });
            } catch (e) {
                window.Swal ? window.Swal.fire({ icon: 'error', title: 'Gagal', text: e.message }) : alert(e.message);
            } finally {
                this.saving = false;
            }
        },

        async toggleAccountStatus(inv) {
            if (!inv?.login_account) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(`/api/investors/${inv.id}/account/toggle`, {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            if (res.ok) {
                this.showAccountModal = false;
                await this.loadAnalytics();
                window.Swal?.fire({ icon: 'success', title: 'Status Login Diperbarui', text: data.message, timer: 1800, showConfirmButton: false });
            }
        },

        copyInvestorReport(inv) {
            const periodName = this.selectedPeriodObj?.name || this.selectedPeriodId;
            let text = `*LAPORAN PROFIT INVESTOR HP — AIKU STUDIO*\n`;
            text += `Investor: *${inv.name}* (${inv.code})\n`;
            text += `Periode: ${periodName}\n`;
            text += `Status Bayar: ${inv.payout_status === 'paid' ? 'SUDAH DIBAYAR' : 'MENUNGGU PEMBAYARAN'}\n\n`;
            text += `*RINCIAN UNIT HP:*\n`;
            (inv.contracts || []).forEach(c => {
                text += `• *${c.device_code}* (${c.device_label})\n`;
                text += `  Tarif: ${this.formatRupiah(c.rate_per_hour_idr)}/jam (${c.rate_basis === 'duration' ? 'Total Rekam' : 'Billable'})\n`;
                text += `  Jam Billable: ${c.billable_hours} jam (${c.tasks} task, Approval ${c.approval_rate}%)\n`;
                text += `  Pendapatan: *${this.formatRupiah(c.profit_idr)}*\n`;
            });
            text += `\n*TOTAL BAGI HASIL:* *${this.formatRupiah(inv.profit_idr)}*\n`;
            if (inv.bank_name || inv.no_rek) {
                text += `Rekening: ${inv.bank_name || ''} ${inv.no_rek || ''} ${inv.account_holder ? '(' + inv.account_holder + ')' : ''}\n`;
            }

            navigator.clipboard?.writeText(text.trim()).then(() => {
                window.Swal?.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Laporan WA investor disalin!', timer: 1800, showConfirmButton: false });
            });
        }
    }));
});
