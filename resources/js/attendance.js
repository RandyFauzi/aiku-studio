const witaDate = () => new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const time = value => value ? new Date(value).toLocaleTimeString('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) + ' WITA' : '—';
const dateLabel = value => value ? new Date(value + 'T12:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '';
const mapUrl = row => 'https://www.openstreetmap.org/?mlat=' + row.latitude + '&mlon=' + row.longitude + '#map=18/' + row.latitude + '/' + row.longitude;

window.attendancePortalData = () => ({
    today: witaDate(),
    month: witaDate().slice(0, 7),
    selectedDate: witaDate(),
    schedule: [],
    attendances: [],
    todayAttendance: null,
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

    async init() {
        await this.load();

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
            if (!document.hidden && !this.checking) this.load();
        });

        setInterval(() => {
            if (witaDate() !== this.today && !this.checking) {
                this.month = witaDate().slice(0, 7);
                this.selectedDate = witaDate();
                this.load();
            }
        }, 60000);
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
                body: JSON.stringify({ qr_token: qrToken })
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

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Absensi Berhasil!',
                    text: data.message || 'Kehadiran Anda berhasil diverifikasi pada WiFi Studio.',
                    confirmButtonColor: '#009B7E'
                });
            }

            this.month = data.attendance.work_date.slice(0, 7);
            this.selectedDate = data.attendance.work_date;
            await this.load();
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
    async init() {
        await this.load();
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
