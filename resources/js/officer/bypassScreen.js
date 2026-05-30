import { officerFetch } from './api.js';

/**
 * Bypass request Alpine component.
 * States: form → pending (polling) → approved | denied | expired
 *
 * The bypass form re-uses the GPS + photo bundle the officer captured on
 * the failed check-in attempt. The check-in screen stashes that bundle in
 * sessionStorage under `ph_bypass_bundle` before redirecting here, so the
 * officer only needs to type the note.
 */
export function bypassScreen() {
    return {
        state: 'form',
        bypassId: null,
        officerNote: '',
        error: null,
        submitting: false,
        reviewerNote: null,
        pollInterval: null,
        bundle: null,
        photoPreview: null,
        bundleMissing: false,

        init() {
            // /officer/bypass/{bypassId?} — when an ID is in the URL, we are
            // polling an existing request; otherwise we render the form.
            const parts = window.location.pathname.split('/').filter(Boolean);
            const lastPart = parts[parts.length - 1];

            if (lastPart && lastPart !== 'bypass') {
                this.bypassId = lastPart;
                this.state = 'pending';
                this.startPolling();
                return;
            }

            // Read the stashed bundle from the failed check-in.
            const raw = sessionStorage.getItem('ph_bypass_bundle');
            if (!raw) {
                this.bundleMissing = true;
                return;
            }

            try {
                this.bundle = JSON.parse(raw);
                this.photoPreview = this.bundle.photo_data_url;
            } catch {
                this.bundleMissing = true;
            }
        },

        async submitBypass() {
            if (this.officerNote.length < 20) return;
            if (!this.bundle) {
                this.error = 'Data check-in tidak ditemukan. Silakan coba check-in terlebih dahulu.';
                return;
            }

            this.submitting = true;
            this.error = null;

            try {
                // Convert the stashed data URL back into a Blob so we can
                // attach it as the photo file in the multipart payload.
                const photoBlob = await this.dataUrlToBlob(this.bundle.photo_data_url);

                const formData = new FormData();
                formData.append('assignment_id', this.bundle.assignment_id);
                formData.append('reason_code', this.bundle.reason_code);
                formData.append('latitude', this.bundle.latitude);
                formData.append('longitude', this.bundle.longitude);
                formData.append('gps_accuracy', this.bundle.gps_accuracy);
                formData.append('gps_provider', this.bundle.gps_provider || 'gps');
                formData.append('mock_location', this.bundle.mock_location ? '1' : '0');
                formData.append('timestamp_device', this.bundle.timestamp_device);
                formData.append('officer_note', this.officerNote);
                formData.append('photo', photoBlob, 'bypass.jpg');

                const res = await officerFetch('POST', '/api/v1/officer/bypass-request', {
                    multipart: formData,
                });

                if (!res) return;

                if (res.ok || res.status === 201) {
                    const body = await res.json();
                    this.bypassId = body.id || body.data?.id;
                    sessionStorage.removeItem('ph_bypass_bundle');
                    this.state = 'pending';
                    this.startPolling();
                } else {
                    const body = await res.json().catch(() => ({}));
                    this.error = body.detail || body.message || 'Gagal mengirim permintaan bypass';
                }
            } catch (err) {
                this.error = 'Koneksi gagal. Periksa jaringan Anda.';
            } finally {
                this.submitting = false;
            }
        },

        async dataUrlToBlob(dataUrl) {
            const res = await fetch(dataUrl);
            return await res.blob();
        },

        startPolling() {
            this.pollStatus();
            this.pollInterval = setInterval(() => this.pollStatus(), 5000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        async pollStatus() {
            if (!this.bypassId) return;

            try {
                const res = await officerFetch('GET', `/api/v1/officer/bypass-request/${this.bypassId}`);
                if (!res) return;

                if (res.ok) {
                    const body = await res.json();
                    const status = body.status || body.data?.status;

                    if (status === 'approved') {
                        this.state = 'approved';
                        this.stopPolling();
                    } else if (status === 'denied') {
                        this.reviewerNote = body.reviewer_note || body.data?.reviewer_note || null;
                        this.state = 'denied';
                        this.stopPolling();
                    } else if (status === 'expired') {
                        this.state = 'expired';
                        this.stopPolling();
                    }
                    // else still pending, keep polling
                }
            } catch (err) {
                // Silently retry on next interval
            }
        },

        destroy() {
            this.stopPolling();
        },
    };
}
