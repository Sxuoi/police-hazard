import { officerFetch } from './api.js';

/**
 * Bypass request Alpine component.
 * States: form → pending (polling) → approved | denied | expired
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

        init() {
            // Extract bypassId from URL: /officer/bypass/{bypassId?}
            const parts = window.location.pathname.split('/');
            const lastPart = parts[parts.length - 1];

            if (lastPart && lastPart !== 'bypass') {
                this.bypassId = lastPart;
                this.state = 'pending';
                this.startPolling();
            }
        },

        async submitBypass() {
            if (this.officerNote.length < 20) return;

            this.submitting = true;
            this.error = null;

            try {
                const res = await officerFetch('POST', '/api/v1/officer/bypass-request', {
                    json: {
                        officer_note: this.officerNote,
                    },
                });

                if (!res) return; // Redirected due to auth

                if (res.ok || res.status === 201) {
                    const body = await res.json();
                    this.bypassId = body.data?.id || body.id;
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
                    const status = body.data?.status || body.status;

                    if (status === 'approved') {
                        this.state = 'approved';
                        this.stopPolling();
                    } else if (status === 'denied') {
                        this.reviewerNote = body.data?.reviewer_note || body.reviewer_note || null;
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
