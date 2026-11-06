import Alpine from 'alpinejs';

Alpine.data('workerSlipArchive', () => ({
    rows: [], loading: true, error: '', downloading: null,
    init() { this.load(); },
    async load() {
        this.loading = true; this.error = '';
        try {
            const response = await fetch('/api/me/payslips', { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Arsip slip belum dapat dimuat.');
            this.rows = (await response.json()).slips;
        } catch (error) { this.rows = []; this.error = error.message; }
        finally { this.loading = false; }
    },
    amount(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0); },
    async download(slip) {
        if (this.downloading) return;
        this.downloading = slip.id; this.error = '';
        try {
            const response = await fetch(`/api/payslips/${slip.id}/export`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Dokumen belum dapat diunduh.');
            const link = document.createElement('a'); link.href = data.url; link.download = data.fileName; link.click();
        } catch (error) { this.error = error.message; }
        finally { this.downloading = null; }
    },
}));
