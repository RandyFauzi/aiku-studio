window.liveQrCard = () => ({
    loading: true,
    config: {},
    currentIp: '',
    qrUrl: '',
    copied: false,
    canvasId: 'attendance-page-qr-canvas',

    async init(customCanvasId = 'attendance-page-qr-canvas') {
        this.canvasId = customCanvasId;
        await this.load();
        window.addEventListener('wifi-config-updated', () => {
            this.load();
        });
    },

    async load() {
        this.loading = true;
        try {
            const res = await fetch('/api/attendance/wifi-config', {
                headers: { Accept: 'application/json' }
            });
            if (!res.ok) throw new Error('Gagal memuat QR');
            const data = await res.json();
            this.config = data.config || {};
            if (!Array.isArray(this.config.wifi_ips)) {
                this.config.wifi_ips = [];
            }
            this.currentIp = data.current_ip || '';
            this.qrUrl = data.qr_url || '';
            this.$nextTick(() => this.drawQr());
        } catch (e) {
            console.error('liveQrCard load error:', e);
        } finally {
            this.loading = false;
        }
    },

    drawQr() {
        if (!this.qrUrl) return;
        const canvas = document.getElementById(this.canvasId);
        if (!canvas) return;

        let attempts = 0;
        const render = () => {
            if (window.QRCode && canvas) {
                window.QRCode.toCanvas(canvas, this.qrUrl, {
                    width: 200,
                    margin: 1,
                    color: {
                        dark: '#0f172a',
                        light: '#ffffff'
                    }
                }, (error) => {
                    if (error) console.error('QR Render Error:', error);
                });
            } else if (attempts < 20) {
                attempts++;
                setTimeout(render, 100);
            }
        };
        render();
    },

    downloadPng() {
        const canvas = document.getElementById(this.canvasId);
        if (!canvas) return;
        const link = document.createElement('a');
        const studioSlug = (this.config.studio_name || 'AIKu-Studio').toLowerCase().replace(/[^a-z0-9]/g, '-');
        link.download = `Barcode-Absensi-${studioSlug}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    },

    downloadSvg() {
        if (!window.QRCode || !this.qrUrl) return;
        window.QRCode.toString(this.qrUrl, { type: 'svg', margin: 1 }, (err, svgString) => {
            if (err) return;
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const studioSlug = (this.config.studio_name || 'AIKu-Studio').toLowerCase().replace(/[^a-z0-9]/g, '-');
            a.href = url;
            a.download = `Barcode-Absensi-${studioSlug}.svg`;
            a.click();
            URL.revokeObjectURL(url);
        });
    },

    copyQrUrl() {
        if (!this.qrUrl) return;
        navigator.clipboard.writeText(this.qrUrl).then(() => {
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2500);
        });
    },

    async setOfficeIpNow() {
        if (!this.currentIp) return;
        this.loading = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/attendance/wifi-config', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    wifi_ips: [this.currentIp],
                    wifi_check_enabled: true,
                    studio_name: this.config.studio_name || 'AIKu Studio',
                    regenerate_qr: false
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyimpan IP WiFi');
            this.config = data.config || {};
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'WiFi Kantor Terkunci!',
                    text: `IP ${this.currentIp} berhasil didaftarkan sebagai WiFi resmi studio.`,
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            window.dispatchEvent(new CustomEvent('wifi-config-updated'));
        } catch (e) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Gagal', text: e.message });
            }
        } finally {
            this.loading = false;
        }
    }
});

window.barcodeWifiModal = () => ({
    open: false,
    tab: 'qr', // 'qr' | 'wifi'
    loading: false,
    saving: false,
    config: {
        wifi_ips: [],
        wifi_check_enabled: true,
        qr_secret: '',
        studio_name: 'AIKu Studio',
        studio_address: 'Jl. Hikmah Banua VI No.144, Pemurus Luar, Kec. Banjarmasin Tim, Kota Banjarmasin, Kalimantan Selatan 70654',
        studio_lat: -3.3445020,
        studio_lng: 114.6325970,
        geofence_radius_m: 150,
    },
    currentIp: '',
    qrUrl: '',
    newIpInput: '',
    copied: false,

    async init() {
        window.addEventListener('open-barcode-modal', (e) => {
            if (e.detail && e.detail.tab) {
                this.tab = e.detail.tab;
            }
            this.openModal();
        });
    },

    async openModal() {
        this.open = true;
        await this.loadConfig();
        this.$nextTick(() => {
            this.renderQrCode();
        });
    },

    closeModal() {
        this.open = false;
    },

    async loadConfig() {
        this.loading = true;
        try {
            const res = await fetch('/api/attendance/wifi-config', {
                headers: { Accept: 'application/json' }
            });
            if (!res.ok) throw new Error('Gagal memuat konfigurasi WiFi & Barcode');
            const data = await res.json();
            this.config = data.config || {};
            if (!Array.isArray(this.config.wifi_ips)) {
                this.config.wifi_ips = [];
            }
            this.currentIp = data.current_ip || '';
            this.qrUrl = data.qr_url || '';
            this.$nextTick(() => this.renderQrCode());
        } catch (e) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Error', text: e.message });
            }
        } finally {
            this.loading = false;
        }
    },

    renderQrCode() {
        if (!this.qrUrl) return;
        const canvas = document.getElementById('admin-barcode-canvas');
        if (!canvas) return;

        let attempts = 0;
        const draw = () => {
            if (window.QRCode && canvas) {
                window.QRCode.toCanvas(canvas, this.qrUrl, {
                    width: 256,
                    margin: 2,
                    color: {
                        dark: '#0f172a',
                        light: '#ffffff'
                    }
                }, (error) => {
                    if (error) console.error('QR Render Error:', error);
                });
            } else if (attempts < 20) {
                attempts++;
                setTimeout(draw, 100);
            }
        };
        draw();
    },

    downloadPng() {
        const canvas = document.getElementById('admin-barcode-canvas');
        if (!canvas) return;
        const link = document.createElement('a');
        const studioSlug = (this.config.studio_name || 'AIKu-Studio').toLowerCase().replace(/[^a-z0-9]/g, '-');
        link.download = `Barcode-Absensi-${studioSlug}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    },

    downloadSvg() {
        if (!window.QRCode || !this.qrUrl) return;
        window.QRCode.toString(this.qrUrl, { type: 'svg', margin: 2 }, (err, svgString) => {
            if (err) return;
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const studioSlug = (this.config.studio_name || 'AIKu-Studio').toLowerCase().replace(/[^a-z0-9]/g, '-');
            a.href = url;
            a.download = `Barcode-Absensi-${studioSlug}.svg`;
            a.click();
            URL.revokeObjectURL(url);
        });
    },

    copyQrUrl() {
        if (!this.qrUrl) return;
        navigator.clipboard.writeText(this.qrUrl).then(() => {
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2500);
        });
    },

    addCurrentIp() {
        if (!this.currentIp) return;
        if (!this.config.wifi_ips.includes(this.currentIp)) {
            this.config.wifi_ips.push(this.currentIp);
        }
    },

    addSubnetDynamic() {
        if (!this.currentIp) return;
        const parts = this.currentIp.trim().split('.');
        if (parts.length >= 2) {
            const subnetWildcard = `${parts[0]}.${parts[1]}.*.*`;
            if (!this.config.wifi_ips.includes(subnetWildcard)) {
                this.config.wifi_ips.push(subnetWildcard);
            }
        }
    },

    addManualIp() {
        const ip = (this.newIpInput || '').trim();
        if (!ip) return;
        if (!this.config.wifi_ips.includes(ip)) {
            this.config.wifi_ips.push(ip);
        }
        this.newIpInput = '';
    },

    removeIp(index) {
        this.config.wifi_ips.splice(index, 1);
    },

    setOfficialStudioLocation() {
        this.config.studio_lat = -3.3445020;
        this.config.studio_lng = 114.6325970;
        this.config.geofence_radius_m = 150;
        this.config.studio_address = 'Jl. Hikmah Banua VI No.144, Pemurus Luar, Kec. Banjarmasin Tim, Kota Banjarmasin, Kalimantan Selatan 70654';
        if (window.Swal) {
            window.Swal.fire({
                icon: 'success',
                title: 'Koordinat Studio AIKu Disetel',
                text: 'Koordinat resmi Studio AIKu (-3.3445020, 114.6325970) berhasil dimuat. Klik Simpan Pengaturan WiFi untuk menerapkan.',
                timer: 2500,
                showConfirmButton: false
            });
        }
    },

    async captureAdminCoordinates() {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung GPS');
            return;
        }
        try {
            const pos = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });
            this.config.studio_lat = Number(pos.coords.latitude.toFixed(7));
            this.config.studio_lng = Number(pos.coords.longitude.toFixed(7));
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Titik Lokasi Terbaca',
                    text: `Koordinat ${this.config.studio_lat}, ${this.config.studio_lng} berhasil diambil. Klik 'Simpan Pengaturan' untuk menerapkan.`,
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        } catch (e) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Gagal Membaca GPS',
                    text: 'Pastikan GPS HP/Laptop aktif dan izinkan akses lokasi pada browser.',
                    confirmButtonColor: '#009B7E'
                });
            }
        }
    },

    async saveSettings(regenerate = false) {
        if (regenerate) {
            if (window.Swal) {
                const confirmed = await window.Swal.fire({
                    title: 'Regenerasi Barcode Absensi?',
                    text: 'Barcode lama yang sudah dicetak di dinding studio tidak akan berlaku lagi setelah diganti. Lanjutkan?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#009B7E',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Buat Barcode Baru',
                    cancelButtonText: 'Batal'
                });
                if (!confirmed.isConfirmed) return;
            } else if (!confirm('Regenerasi barcode absensi? Barcode lama akan kedaluwarsa.')) {
                return;
            }
        }

        this.saving = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/attendance/wifi-config', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    wifi_ips: this.config.wifi_ips,
                    wifi_check_enabled: !!this.config.wifi_check_enabled,
                    studio_name: this.config.studio_name || 'AIKu Studio',
                    studio_lat: (this.config.studio_lat !== null && this.config.studio_lat !== '' && !isNaN(this.config.studio_lat)) ? Number(this.config.studio_lat) : null,
                    studio_lng: (this.config.studio_lng !== null && this.config.studio_lng !== '' && !isNaN(this.config.studio_lng)) ? Number(this.config.studio_lng) : null,
                    geofence_radius_m: (this.config.geofence_radius_m !== null && this.config.geofence_radius_m !== '' && !isNaN(this.config.geofence_radius_m)) ? Number(this.config.geofence_radius_m) : 150,
                    regenerate_qr: regenerate
                })
            });

            const data = await res.json();
            if (!res.ok) {
                const errorMsg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menyimpan pengaturan');
                throw new Error(errorMsg);
            }

            this.config = data.config || {};
            this.currentIp = data.current_ip || this.currentIp;
            this.qrUrl = data.qr_url || this.qrUrl;
            this.$nextTick(() => this.renderQrCode());

            // Dispatch global event so on-page live QR cards refresh immediately
            window.dispatchEvent(new CustomEvent('wifi-config-updated'));

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message || 'Pengaturan Barcode & WiFi berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } catch (e) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Gagal', text: e.message });
            } else {
                alert(e.message);
            }
        } finally {
            this.saving = false;
        }
    }
});
