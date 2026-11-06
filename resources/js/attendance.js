const witaDate = () => new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const time = value => value ? new Date(value).toLocaleTimeString('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) + ' WITA' : '—';
const dateLabel = value => value ? new Date(value + 'T12:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '';
const mapUrl = row => 'https://www.openstreetmap.org/?mlat=' + row.latitude + '&mlon=' + row.longitude + '#map=18/' + row.latitude + '/' + row.longitude;

window.attendancePortalData = () => ({
    activeTab: window.location.pathname.includes('/laporan') ? 'laporan' 
             : (window.location.pathname.includes('/jadwal') ? 'jadwal' 
             : (window.location.pathname.includes('/slip') ? 'slip' 
             : (window.location.pathname.includes('/profil') ? 'akun' : 'absensi'))),
    mobileSidebarOpen: false,
    todayReportSubmitted: null,
    today: witaDate(),
    month: witaDate().slice(0, 7),
    selectedDate: witaDate(),
    schedule: [],
    activeSchedule: null,
    attendances: [],
    todayAttendance: null,
    slips: [],
    liveSlip: null,
    currentPeriod: null,
    loadingSlips: false,
    downloadingSlipId: null,
    loading: true,
    checking: false,
    scannerOpen: false,
    scannerStarting: false,
    qrScannerInstance: null,
    error: '',
    message: '',
    time,
    dateLabel,
    mapUrl,

    // ==========================================
    // TIMER FEATURE (FOCUS & RECORDING TIMER)
    // ==========================================
    timer: {
        durationMinutes: 20,
        totalSeconds: 20 * 60,
        remainingSeconds: 20 * 60,
        isRunning: false,
        isPaused: false,
        timerId: null,
        endTime: null,
        modalOpen: false,
        showAlarmAlert: false,
        isAlarmPlaying: false,
        customMinutesInput: '',
        selectedPreset: 20, // 15, 20, 30, or 'custom'
        currentAudio: null,
        loopIndex: 0,
    },

    timerAudioSources: [
        '/audio/timer/alarm-1.mp3',
        '/audio/timer/alarm-2.mp3',
        '/audio/timer/alarm-3.mp3'
    ],

    get timerFormatted() {
        const total = Math.max(0, this.timer.remainingSeconds);
        const m = Math.floor(total / 60);
        const s = total % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    },

    get timerProgressPercent() {
        if (!this.timer.totalSeconds) return 0;
        const passed = this.timer.totalSeconds - this.timer.remainingSeconds;
        return Math.min(100, Math.max(0, (passed / this.timer.totalSeconds) * 100));
    },

    get timerStrokeDashoffset() {
        // Circumference 100 base for SVG stroke-dasharray="100, 100"
        return 100 - this.timerProgressPercent;
    },

    setTimerPreset(minutes) {
        if (this.timer.isRunning) {
            this.pauseTimer();
        }
        this.timer.durationMinutes = minutes;
        this.timer.totalSeconds = minutes * 60;
        this.timer.remainingSeconds = minutes * 60;
        this.timer.selectedPreset = minutes;
        this.timer.isPaused = false;
        this.timer.showAlarmAlert = false;
        this.stopAlarm();
    },

    setTimerManual(minutes) {
        const val = parseInt(minutes, 10);
        if (isNaN(val) || val <= 0) return;
        const clamped = Math.min(720, Math.max(1, val));
        if (this.timer.isRunning) {
            this.pauseTimer();
        }
        this.timer.durationMinutes = clamped;
        this.timer.totalSeconds = clamped * 60;
        this.timer.remainingSeconds = clamped * 60;
        this.timer.selectedPreset = 'custom';
        this.timer.customMinutesInput = clamped;
        this.timer.isPaused = false;
        this.timer.showAlarmAlert = false;
        this.stopAlarm();
    },

    toggleTimer() {
        if (this.timer.isRunning) {
            this.pauseTimer();
        } else {
            this.startTimer();
        }
    },

    startTimer() {
        if (this.timer.remainingSeconds <= 0) {
            this.timer.remainingSeconds = this.timer.totalSeconds;
        }

        // Prime audio to unlock browser autoplay restrictions
        this.primeAudio();

        // Ask for Notification permission if available
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }

        this.timer.isRunning = true;
        this.timer.isPaused = false;
        this.timer.showAlarmAlert = false;
        this.stopAlarm();

        this.timer.endTime = Date.now() + (this.timer.remainingSeconds * 1000);

        if (this.timer.timerId) {
            clearInterval(this.timer.timerId);
        }

        this.timer.timerId = setInterval(() => {
            const now = Date.now();
            const diffMs = this.timer.endTime - now;
            const remaining = Math.ceil(diffMs / 1000);

            if (remaining <= 0) {
                this.timer.remainingSeconds = 0;
                this.onTimerFinished();
            } else {
                this.timer.remainingSeconds = remaining;
            }
        }, 250);
    },

    pauseTimer() {
        if (this.timer.timerId) {
            clearInterval(this.timer.timerId);
            this.timer.timerId = null;
        }
        this.timer.isRunning = false;
        this.timer.isPaused = true;
    },

    resetTimer() {
        if (this.timer.timerId) {
            clearInterval(this.timer.timerId);
            this.timer.timerId = null;
        }
        this.timer.isRunning = false;
        this.timer.isPaused = false;
        this.timer.remainingSeconds = this.timer.totalSeconds;
        this.timer.showAlarmAlert = false;
        this.stopAlarm();
    },

    onTimerFinished() {
        if (this.timer.timerId) {
            clearInterval(this.timer.timerId);
            this.timer.timerId = null;
        }
        this.timer.isRunning = false;
        this.timer.isPaused = false;
        this.timer.remainingSeconds = 0;
        this.timer.showAlarmAlert = true;

        // Vibrate mobile device if supported
        if ('vibrate' in navigator) {
            try { navigator.vibrate([500, 250, 500, 250, 500, 250, 800]); } catch (e) {}
        }

        // Send browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification('AIKu Studio — Waktu Rekaman Selesai!', {
                    body: 'Waktu sesi rekaman sudah habis! Harap simpan file dan istirahat.',
                    icon: '/favicon.ico'
                });
            } catch (e) {}
        }

        // Play loud shuffled alarm audio sequence (loop 3x)
        this.playAlarmSequence();
    },

    primeAudio() {
        try {
            const testAudio = new Audio(this.timerAudioSources[0]);
            testAudio.volume = 0.01;
            const p = testAudio.play();
            if (p !== undefined) {
                p.then(() => {
                    testAudio.pause();
                    testAudio.currentTime = 0;
                }).catch(() => {});
            }
        } catch (e) {}
    },

    playAlarmSequence() {
        this.stopAlarm();
        this.timer.isAlarmPlaying = true;

        // Fisher-Yates shuffle algorithm
        const shuffle = (array) => {
            const arr = [...array];
            for (let i = arr.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [arr[i], arr[j]] = [arr[j], arr[i]];
            }
            return arr;
        };

        let currentLoop = 0;
        const maxLoops = 3;
        let shuffledQueue = shuffle(this.timerAudioSources);
        let trackIndex = 0;

        const playNext = () => {
            if (!this.timer.isAlarmPlaying) return;

            if (trackIndex >= shuffledQueue.length) {
                currentLoop++;
                if (currentLoop >= maxLoops) {
                    this.timer.isAlarmPlaying = false;
                    return;
                }
                // Reshuffle for subsequent loop
                shuffledQueue = shuffle(this.timerAudioSources);
                trackIndex = 0;
            }

            const audioSrc = shuffledQueue[trackIndex];
            trackIndex++;

            const audio = new Audio(audioSrc);
            audio.volume = 1.0;
            this.timer.currentAudio = audio;

            audio.onended = () => {
                if (this.timer.isAlarmPlaying) {
                    playNext();
                }
            };

            audio.onerror = (err) => {
                console.warn('Timer audio error:', err);
                if (this.timer.isAlarmPlaying) {
                    playNext();
                }
            };

            audio.play().catch(e => {
                console.warn('Timer audio autoplay blocked:', e);
                if (this.timer.isAlarmPlaying) {
                    playNext();
                }
            });
        };

        playNext();
    },

    stopAlarm() {
        this.timer.isAlarmPlaying = false;
        if (this.timer.currentAudio) {
            try {
                this.timer.currentAudio.pause();
                this.timer.currentAudio.currentTime = 0;
            } catch (e) {}
            this.timer.currentAudio = null;
        }
    },

    testAlarmSound() {
        this.stopAlarm();
        const randSrc = this.timerAudioSources[Math.floor(Math.random() * this.timerAudioSources.length)];
        const audio = new Audio(randSrc);
        audio.volume = 1.0;
        this.timer.currentAudio = audio;
        this.timer.isAlarmPlaying = true;
        audio.onended = () => {
            this.timer.isAlarmPlaying = false;
        };
        audio.play().catch(e => {
            alert('Tidak dapat memutar audio: ' + e.message);
        });
    },

    openTimerModal() {
        this.timer.modalOpen = true;
    },

    closeTimerModal() {
        this.timer.modalOpen = false;
    },

    formatRupiah(num) {
        return 'Rp ' + (Number(num) || 0).toLocaleString('id-ID');
    },

    get todaySchedule() {
        return this.activeSchedule || this.schedule.find(day => day.date === this.today) || { sessions: [] };
    },

    get weekDays() {
        const todayObj = new Date(this.today + 'T12:00:00');
        const dayOfWeek = (todayObj.getDay() + 6) % 7; // 0 = Mon, 6 = Sun
        const monday = new Date(todayObj);
        monday.setDate(todayObj.getDate() - dayOfWeek);

        const days = [];
        const names = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        for (let i = 0; i < 7; i++) {
            const d = new Date(monday);
            d.setDate(monday.getDate() + i);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;
            days.push({
                name: names[i],
                date: dateStr,
                dayNum: d.getDate(),
                isToday: dateStr === this.today,
                hasSession: (this.schedule.find(s => s.date === dateStr)?.sessions?.length || 0) > 0,
            });
        }
        return days;
    },

    get totalMonthSessions() {
        return this.schedule.reduce((acc, day) => acc + (day.sessions?.length || 0), 0);
    },

    get totalMonthAttendances() {
        return this.attendances.length;
    },

    setTab(tab) {
        const routes = {
            'absensi': '/me',
            'home': '/me',
            'laporan': '/me/laporan',
            'jadwal': '/me/jadwal',
            'slip': '/me/slip',
            'akun': '/me/profil',
            'profil': '/me/profil'
        };
        if (routes[tab] && window.location.pathname !== routes[tab]) {
            window.location.href = routes[tab];
            return;
        }
        if (!['absensi', 'laporan', 'jadwal', 'slip', 'akun'].includes(tab)) return;
        this.activeTab = tab;
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + tab);
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (tab === 'slip' && !this.slips.length && !this.liveSlip) {
            this.loadSlips();
        }
    },

    async loadSlips() {
        this.loadingSlips = true;
        try {
            const res = await fetch('/api/me/payslips', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('Gagal memuat slip gaji.');
            const data = await res.json();
            this.slips = data.slips || [];
            this.currentPeriod = data.current_period;
            this.liveSlip = data.live_slip;
        } catch (e) {
            console.error('Error loading payslips:', e);
        } finally {
            this.loadingSlips = false;
        }
    },

    openLiveSlip() {
        if (!this.liveSlip) return;
        const worker = encodeURIComponent(this.liveSlip.employee_name);
        const periodId = encodeURIComponent(this.currentPeriod?.id || '');
        window.open(`/slip/live/${worker}?periodId=${periodId}`, '_blank');
    },

    openSlip(slipId) {
        window.open(`/slip/${slipId}`, '_blank');
    },

    async downloadSlipPdf(slip) {
        if (this.downloadingSlipId) return;
        this.downloadingSlipId = slip.id;
        try {
            const res = await fetch(`/api/payslips/${slip.id}/export`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || data.error || 'Gagal membuat PDF');
            const a = document.createElement('a');
            a.href = data.url;
            a.download = data.fileName;
            a.click();
        } catch (err) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Unduh Gagal', text: err.message, confirmButtonColor: '#009B7E' });
            } else {
                alert(err.message);
            }
        } finally {
            this.downloadingSlipId = null;
        }
    },

    async downloadLiveSlipPdf() {
        if (!this.liveSlip || this.downloadingSlipId) return;
        this.downloadingSlipId = 'live';
        try {
            const worker = encodeURIComponent(this.liveSlip.employee_name);
            const periodId = encodeURIComponent(this.currentPeriod?.id || '');
            const res = await fetch(`/api/payroll/export-live/${worker}?periodId=${periodId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || data.error || 'Gagal membuat PDF');
            const a = document.createElement('a');
            a.href = data.url;
            a.download = data.fileName;
            a.click();
        } catch (err) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Unduh Gagal', text: err.message, confirmButtonColor: '#009B7E' });
            } else {
                alert(err.message);
            }
        } finally {
            this.downloadingSlipId = null;
        }
    },

    async init() {
        const initialHash = window.location.hash.replace('#', '');
        if (['absensi', 'laporan', 'jadwal', 'slip', 'akun'].includes(initialHash)) {
            this.activeTab = initialHash;
        } else if (initialHash === 'laporan-harian') {
            this.activeTab = 'laporan';
        }

        window.addEventListener('hashchange', () => {
            const h = window.location.hash.replace('#', '');
            if (['absensi', 'laporan', 'jadwal', 'slip', 'akun'].includes(h)) {
                this.activeTab = h;
            } else if (h === 'laporan-harian') {
                this.activeTab = 'laporan';
            }
        });

        window.addEventListener('report-status-updated', (e) => {
            if (e.detail && typeof e.detail.submitted === 'boolean') {
                this.todayReportSubmitted = e.detail.submitted;
            }
        });

        await Promise.all([this.load(), this.loadSlips(), this.loadReportStatus()]);

        // Auto-checkin if URL has ?qr_token=... (e.g. from native phone camera scanning poster)
        const urlParams = new URLSearchParams(window.location.search);
        const autoToken = urlParams.get('qr_token');
        if (autoToken) {
            window.history.replaceState({}, document.title, window.location.pathname);
            if (!this.todayAttendance) {
                await this.checkInWithQr(autoToken);
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                if (!this.checking) {
                    this.load();
                    this.loadReportStatus();
                }
                if (this.timer.isRunning && this.timer.endTime) {
                    const now = Date.now();
                    const diffMs = this.timer.endTime - now;
                    const remaining = Math.ceil(diffMs / 1000);
                    if (remaining <= 0) {
                        this.timer.remainingSeconds = 0;
                        this.onTimerFinished();
                    } else {
                        this.timer.remainingSeconds = remaining;
                    }
                }
            }
        });

        setInterval(() => {
            if (witaDate() !== this.today && !this.checking) {
                this.month = witaDate().slice(0, 7);
                this.selectedDate = witaDate();
                this.load();
                this.loadReportStatus();
            }
        }, 60000);
    },

    async loadReportStatus() {
        try {
            const response = await fetch('/api/me/work-reports?month=' + witaDate().slice(0, 7), {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Status laporan belum tersedia.');
            const data = await response.json();
            this.todayReportSubmitted = Boolean(data.today_report);
        } catch {
            this.todayReportSubmitted = null;
        }
    },

    async load() {
        this.loading = true;
        this.error = '';
        try {
            const response = await fetch('/api/me/attendance?month=' + this.month, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Data jadwal belum berhasil dimuat.');
            const data = await response.json();
            this.today = data.today;
            this.schedule = data.schedule;
            this.activeSchedule = data.active_schedule || null;
            this.attendances = data.attendances;
            this.todayAttendance = data.today_attendance;
            if (!this.selectedDate.startsWith(this.month)) this.selectedDate = this.month + '-01';
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },

    get selectedDay() {
        return this.schedule.find(day => day.date === this.selectedDate) || { sessions: [] };
    },

    get selectedAttendance() {
        return this.attendances.find(row => row.work_date === this.selectedDate);
    },

    get calendarCells() {
        const first = new Date(this.month + '-01T12:00:00');
        const cells = Array.from({ length: (first.getDay() + 6) % 7 }, (_, i) => ({ key: 'blank-' + i, date: null }));
        this.schedule.forEach(day => cells.push({
            ...day,
            key: day.date,
            attendance: this.attendances.find(row => row.work_date === day.date)
        }));
        while (cells.length % 7) cells.push({ key: 'end-' + cells.length, date: null });
        return cells;
    },

    async openScanner() {
        if (this.checking || this.todayAttendance) return;
        this.scannerOpen = true;
        this.scannerStarting = true;
        this.error = '';

        this.$nextTick(async () => {
            try {
                if (!window.Html5Qrcode) {
                    throw new Error('Scanner barcode belum siap. Silakan muat ulang halaman.');
                }
                const scanner = new window.Html5Qrcode('portal-qr-reader');
                this.qrScannerInstance = scanner;

                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    (decodedText) => {
                        this.handleQrScanned(decodedText);
                    },
                    (errorMessage) => {
                        // ignore intermediate camera frame parse errors
                    }
                );
            } catch (err) {
                console.error(err);
                await this.closeScanner();
                const msg = (err && err.name === 'NotAllowedError')
                    ? 'Izin kamera ditolak. Silakan izinkan akses kamera pada browser HP Anda.'
                    : ((err && err.message) || 'Gagal membuka kamera HP.');
                if (window.Swal) {
                    window.Swal.fire({ icon: 'warning', title: 'Kamera Tidak Tersedia', text: msg, confirmButtonColor: '#009B7E' });
                } else {
                    this.error = msg;
                }
            } finally {
                this.scannerStarting = false;
            }
        });
    },

    async closeScanner() {
        if (this.qrScannerInstance) {
            try {
                await this.qrScannerInstance.stop();
            } catch (e) {}
            try {
                this.qrScannerInstance.clear();
            } catch (e) {}
            this.qrScannerInstance = null;
        }
        this.scannerOpen = false;
        this.scannerStarting = false;
    },

    async handleQrScanned(decodedText) {
        await this.closeScanner();

        let token = (decodedText || '').trim();
        if (token.includes('qr_token=')) {
            try {
                const url = new URL(token);
                token = url.searchParams.get('qr_token') || token;
            } catch (e) {
                const match = token.match(/qr_token=([^&]+)/);
                if (match && match[1]) {
                    token = match[1];
                }
            }
        }

        await this.checkInWithQr(token);
    },

    async checkInWithQr(qrToken) {
        if (this.checking || this.todayAttendance) return;
        this.checking = true;
        this.error = '';
        this.message = 'Membaca koordinat GPS HP Anda…';

        const payload = {
            qr_token: qrToken,
            latitude: null,
            longitude: null,
            accuracy_m: null,
            captured_at: null,
        };

        // Capture GPS location concurrently
        if (navigator.geolocation) {
            try {
                // Try high accuracy first (outdoor / window) with 6s timeout
                const pos = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 6000,
                        maximumAge: 15000,
                    });
                });
                payload.latitude = pos.coords.latitude;
                payload.longitude = pos.coords.longitude;
                payload.accuracy_m = pos.coords.accuracy;
                payload.captured_at = new Date(pos.timestamp).toISOString();
            } catch (geoErr) {
                console.warn('High accuracy GPS error, trying fallback:', geoErr);
                if (geoErr && geoErr.code === 1) { // PERMISSION_DENIED
                    this.checking = false;
                    const permMsg = 'Akses Lokasi (GPS) ditolak pada browser HP Anda. Harap aktifkan izin lokasi agar absensi dapat dicatat bersama verifikasi WiFi Studio.';
                    this.error = permMsg;
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Izin Lokasi Dibutuhkan',
                            text: permMsg,
                            confirmButtonColor: '#009B7E'
                        });
                    }
                    return;
                }

                // Try low-accuracy fallback (indoor cell tower / wifi positioning) with 4s timeout
                try {
                    const fallbackPos = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: false,
                            timeout: 4000,
                            maximumAge: 60000,
                        });
                    });
                    payload.latitude = fallbackPos.coords.latitude;
                    payload.longitude = fallbackPos.coords.longitude;
                    payload.accuracy_m = fallbackPos.coords.accuracy;
                    payload.captured_at = new Date(fallbackPos.timestamp).toISOString();
                } catch (fallbackErr) {
                    console.warn('GPS indoor fallback unavailable:', fallbackErr);
                }
            }
        }

        this.message = 'Memvalidasi barcode & memeriksa jaringan WiFi Kantor…';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/api/me/attendance/qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (!response.ok) {
                const errorMsg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Absensi barcode gagal.');
                throw new Error(errorMsg);
            }

            this.todayAttendance = data.attendance;
            this.message = data.already_checked_in
                ? 'Anda sudah absen hari ini.'
                : 'Absensi masuk via Barcode & WiFi Studio berhasil!';

            this.month = data.attendance.work_date.slice(0, 7);
            this.selectedDate = data.attendance.work_date;
            await this.load();
            this.confirmAttendance(data);
        } catch (e) {
            this.message = '';
            this.error = e.message;
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal Absensi WiFi',
                    text: e.message,
                    confirmButtonColor: '#009B7E'
                });
            }
        } finally {
            this.checking = false;
        }
    },

    confirmAttendance(data) {
        window.dispatchEvent(new CustomEvent('attendance-recorded', {detail:{attendanceId:data.attendance.id,workDate:data.attendance.work_date}}));
        if (!document.querySelector('[data-record-streak]') && window.Swal) {
            window.Swal.fire({icon:data.already_checked_in?'info':'success',title:data.already_checked_in?'Sudah absen hari ini':'Absensi berhasil!',text:'Kehadiran kamu sudah tercatat.',confirmButtonColor:'#315d24'});
        }
    },

    async checkIn() {
        if (this.checking || this.todayAttendance) return;
        this.checking = true;
        this.error = '';
        this.message = 'Membaca GPS. Izinkan akses lokasi pada browser…';
        try {
            if (!navigator.geolocation) throw new Error('Browser ini belum mendukung GPS. Gunakan browser terbaru.');
            const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 20000,
                maximumAge: 0
            }));
            this.message = 'Lokasi terbaca. Menyimpan absensi…';
            const response = await fetch('/api/me/attendance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy_m: position.coords.accuracy,
                    captured_at: new Date(position.timestamp).toISOString()
                })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Absensi belum tersimpan.');
            this.todayAttendance = data.attendance;
            this.message = data.already_checked_in ? 'Kamu sudah absen hari ini.' : 'Absensi masuk berhasil disimpan.';
            this.month = data.attendance.work_date.slice(0, 7);
            this.selectedDate = data.attendance.work_date;
            await this.load();
            this.confirmAttendance(data);
        } catch (e) {
            this.message = '';
            this.error = e.code === 1 ? 'Izin lokasi ditolak. Izinkan lokasi pada pengaturan browser lalu tekan Masuk lagi.' : e.code === 2 ? 'Lokasi belum terbaca. Aktifkan GPS dan coba di tempat dengan sinyal lebih baik.' : e.code === 3 ? 'GPS membutuhkan waktu lebih lama. Silakan coba lagi.' : e.message;
        } finally {
            this.checking = false;
        }
    },
});

window.adminAttendanceData = () => ({
    date: witaDate(),
    rows: [],
    search: '',
    loading: true,
    error: '',
    time,
    dateLabel,
    mapUrl,

    // Manual Attendance Modal & Form State
    contributors: [],
    showManualModal: false,
    manualSaving: false,
    manualError: '',
    manualSuccess: '',
    manualForm: {
        contributor_id: '',
        work_date: witaDate(),
        time: '',
        notes: ''
    },

    async init() {
        await Promise.all([
            this.load(),
            this.fetchContributors()
        ]);
    },
    async fetchContributors() {
        try {
            const res = await fetch('/api/contributors');
            if (res.ok) {
                const data = await res.json();
                this.contributors = Array.isArray(data) ? data : (data.contributors || []);
            }
        } catch (e) {
            console.error('Gagal memuat kontributor:', e);
        }
    },
    openManualModal() {
        this.manualError = '';
        this.manualSuccess = '';

        let defaultTime = '08:00';
        try {
            defaultTime = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'Asia/Makassar',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).format(new Date());
        } catch (e) {}

        this.manualForm = {
            contributor_id: '',
            work_date: this.date || witaDate(),
            time: defaultTime,
            notes: ''
        };
        if (!this.contributors.length) {
            this.fetchContributors();
        }
        this.showManualModal = true;
    },
    closeManualModal() {
        if (this.manualSaving) return;
        this.showManualModal = false;
        this.manualError = '';
    },
    async saveManualAttendance() {
        if (!this.manualForm.contributor_id) {
            this.manualError = 'Silakan pilih kontributor terlebih dahulu.';
            return;
        }
        if (!this.manualForm.work_date) {
            this.manualError = 'Silakan pilih tanggal kerja absensi.';
            return;
        }

        this.manualSaving = true;
        this.manualError = '';
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        try {
            const res = await fetch('/api/attendance/manual', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify(this.manualForm)
            });

            const data = await res.json();

            if (!res.ok) {
                const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Gagal menyimpan absensi manual.');
                throw new Error(msg);
            }

            this.showManualModal = false;
            this.date = this.manualForm.work_date;
            await this.load();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message || 'Absensi manual berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                this.manualSuccess = data.message || 'Absensi manual berhasil disimpan.';
                setTimeout(() => { this.manualSuccess = ''; }, 4000);
            }
        } catch (e) {
            this.manualError = e.message;
        } finally {
            this.manualSaving = false;
        }
    },
    async deleteAttendance(row) {
        if (!confirm(`Hapus absensi ${row.contributor_name} pada tanggal ${row.work_date}?`)) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        try {
            const res = await fetch(`/api/attendance/${row.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                }
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Gagal menghapus absensi.');
            }

            await this.load();
        } catch (e) {
            alert(e.message);
        }
    },
    async load() {
        this.loading = true;
        this.error = '';
        try {
            const response = await fetch('/api/attendance?date=' + this.date);
            if (!response.ok) throw new Error('Absensi belum berhasil dimuat.');
            this.rows = (await response.json()).attendances;
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },
    get filtered() {
        return this.rows.filter(row => row.contributor_name.toLowerCase().includes(this.search.toLowerCase()));
    },
});
