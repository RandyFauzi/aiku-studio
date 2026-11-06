import Alpine from 'alpinejs';

// Shared read-only entry point for any recording button/page.
export async function getRecordingOwnerRecommendations(recordId, { signal } = {}) {
    const response = await fetch(`/api/studio-recordings/${encodeURIComponent(recordId)}/recommendations`, { signal, headers: { Accept: 'application/json' } });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(response.status === 401 ? 'Sesi berakhir. Masuk kembali.' : (data.message || 'Rekomendasi belum dapat dimuat.'));
    return data;
}

window.AIKU = { ...window.AIKU, getRecordingOwnerRecommendations };

Alpine.data('recordingWorkerRecommendations', recordId => ({
    recordId, loading: false, saving: false, error: '', candidates: [], message: '', ambiguous: false, lastAttempt: 0, controller: null, mobileTab: 'manual',
    async load(force = false) {
        if (this.loading || (!force && Date.now() - this.lastAttempt < 15000)) return;
        this.lastAttempt = Date.now(); this.loading = true; this.error = '';
        this.controller?.abort(); this.controller = new AbortController();
        try {
            const data = await getRecordingOwnerRecommendations(this.recordId, { signal: this.controller.signal });
            this.candidates = data.candidates; this.message = data.message; this.ambiguous = data.ambiguous;
        } catch (error) { if (error.name !== 'AbortError') this.error = error.message; }
        finally { this.loading = false; }
    },
    destroy() { this.controller?.abort(); },
    async confirmCandidate(candidate) {
        if (!candidate.can_confirm || this.saving) return;
        const answer = await window.Swal.fire({ title: 'Tandai pemilik recording?',
            text: `${candidate.name} · ${candidate.phone_label} · laporan ${candidate.work_date}. Periksa kecocokan sebelum mengonfirmasi karena penandaan memengaruhi penghasilan.`,
            icon: 'question', showCancelButton: true, confirmButtonText: 'Konfirmasi pemilik', cancelButtonText: 'Periksa lagi' });
        if (!answer.isConfirmed) return;
        this.saving = true; this.error = '';
        try {
            const reasons = Object.values(candidate.signals).filter(signal => signal.matched).map(signal => signal.label).join('; ');
            const response = await fetch('/api/recording-matching/confirm', { method: 'POST', headers: {
                Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }, body: JSON.stringify({ report_id: candidate.report_id, expected_revision: candidate.report_revision,
                phone_key: candidate.phone_key, record_ids: [this.recordId], reason: 'Rekomendasi diperiksa admin: ' + reasons }) });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Penandaan belum tersimpan.');
            this.activePopover = null;
            window.dispatchEvent(new CustomEvent('recording-owner-confirmed'));
            await window.Swal.fire({ icon: 'success', text: `Recording ditandai milik ${candidate.name}.`, timer: 1800 });
        } catch (error) { this.error = error.message; }
        finally { this.saving = false; }
    },
}));
