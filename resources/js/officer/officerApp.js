import Alpine from 'alpinejs';
import { officerFetch } from './api.js';
import { formatLocationTime } from './formatLocationTime.js';
import { checkinScreen } from './checkinScreen.js';
import { bypassScreen } from './bypassScreen.js';

/**
 * Register all officer Alpine components.
 */
export function registerOfficerComponents() {
    // ── Root app state ──────────────────────────────────────────────
    Alpine.data('officerApp', () => ({
        token: sessionStorage.getItem('ph_token'),
        tokenExpiresAt: sessionStorage.getItem('ph_token_exp'),
        officer: JSON.parse(sessionStorage.getItem('ph_officer') || 'null'),

        init() {
            this.wireHttpsGuard();
            this.wireTokenExpiryWatcher();
        },

        wireHttpsGuard() {
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                window.location.href = window.location.href.replace('http:', 'https:');
            }
        },

        wireTokenExpiryWatcher() {
            if (!this.tokenExpiresAt) return;
            const expiresAt = new Date(this.tokenExpiresAt).getTime();
            const now = Date.now();
            if (now >= expiresAt) {
                this.clearSession();
                window.location.href = '/officer/login';
            }
        },

        clearSession() {
            sessionStorage.removeItem('ph_token');
            sessionStorage.removeItem('ph_token_exp');
            sessionStorage.removeItem('ph_officer');
            this.token = null;
            this.officer = null;
        },

        async logout() {
            await officerFetch('POST', '/api/v1/auth/logout');
            this.clearSession();
            window.location.href = '/officer/login';
        },
    }));

    // ── Login screen ────────────────────────────────────────────────
    Alpine.data('officerLogin', () => ({
        nrp: '',
        password: '',
        loading: false,
        error: null,

        async submit() {
            this.loading = true;
            this.error = null;

            try {
                const res = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ nrp: this.nrp, password: this.password }),
                });

                const body = await res.json();

                if (res.ok) {
                    sessionStorage.setItem('ph_token', body.token);
                    sessionStorage.setItem('ph_token_exp', body.token_expires_at);
                    sessionStorage.setItem('ph_officer', JSON.stringify(body.officer));
                    window.location.href = '/officer/assignments';
                } else {
                    this.error = body.detail || body.message || 'Login gagal. Periksa NRP dan password.';
                }
            } catch (err) {
                console.error('Login Error:', err);
                this.error = 'Koneksi gagal. Periksa jaringan Anda.';
            } finally {
                this.loading = false;
            }
        },
    }));

    // ── Assignments index ───────────────────────────────────────────
    Alpine.data('officerAssignments', () => ({
        assignments: [],
        loading: false,
        selectedDate: new Date().toISOString().split('T')[0],

        async fetchAssignments() {
            this.loading = true;
            try {
                const res = await officerFetch('GET', `/api/v1/officer/assignments?date=${this.selectedDate}`);
                if (!res) return;
                if (res.ok) {
                    const body = await res.json();
                    this.assignments = body.data || [];
                }
            } catch (err) {
                // Silent fail
            } finally {
                this.loading = false;
            }
        },

        prevDay() {
            const d = new Date(this.selectedDate);
            d.setDate(d.getDate() - 1);
            const minDate = new Date();
            minDate.setDate(minDate.getDate() - 7);
            if (d >= minDate) {
                this.selectedDate = d.toISOString().split('T')[0];
                this.fetchAssignments();
            }
        },

        nextDay() {
            const d = new Date(this.selectedDate);
            d.setDate(d.getDate() + 1);
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 7);
            if (d <= maxDate) {
                this.selectedDate = d.toISOString().split('T')[0];
                this.fetchAssignments();
            }
        },

        isToday() {
            return this.selectedDate === new Date().toISOString().split('T')[0];
        },

        formatDate(dateStr) {
            return new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(new Date(dateStr));
        },

        statusBadgeClass(status) {
            switch (status) {
                case 'attended': return 'bg-green-500/10 text-green-400';
                case 'not_attended': return 'bg-red-500/10 text-red-400';
                case 'pending': return 'bg-gray-500/10 text-gray-400';
                default: return 'bg-gray-500/10 text-gray-400';
            }
        },

        statusLabel(status) {
            switch (status) {
                case 'attended': return 'Hadir';
                case 'not_attended': return 'Belum';
                case 'pending': return 'Pending';
                default: return status;
            }
        },
    }));

    // ── Assignment show ─────────────────────────────────────────────
    Alpine.data('officerAssignmentShow', () => ({
        assignment: null,
        loading: false,
        distance: null,
        watchId: null,
        gpsError: null,
        map: null,
        officerMarker: null,

        async fetchAssignment() {
            this.loading = true;
            const id = window.location.pathname.split('/').pop();
            try {
                const res = await officerFetch('GET', `/api/v1/officer/assignments/${id}`);
                if (!res) {
                    this.loading = false;
                    return;
                }
                if (res.ok) {
                    const body = await res.json();
                    this.assignment = body.data || body;
                    this.loading = false;
                    // Wait for the Alpine template to render the #officer-minimap
                    // div before we touch it with Leaflet.
                    await this.$nextTick();
                    this.renderMap();
                    this.startWatchingPosition();
                } else {
                    this.loading = false;
                }
            } catch (err) {
                this.loading = false;
            }
        },

        renderMap() {
            if (!window.L || !this.assignment) return;
            const coords = this.assignment.location_coordinates;
            if (!coords || coords.lat == null || coords.lng == null) return;

            const el = document.getElementById('officer-minimap');
            if (!el || this.map) return;

            this.map = window.L.map(el, {
                zoomControl: false,
                attributionControl: false,
                dragging: true,
                scrollWheelZoom: false,
            }).setView([coords.lat, coords.lng], 17);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(this.map);

            // Location marker + geofence circle.
            window.L.marker([coords.lat, coords.lng]).addTo(this.map)
                .bindPopup(this.assignment.location_name || 'Lokasi');

            const radius = this.assignment.location_radius_meters || 50;
            window.L.circle([coords.lat, coords.lng], {
                radius,
                color: '#3b82f6',
                fillColor: '#3b82f6',
                fillOpacity: 0.15,
                weight: 2,
            }).addTo(this.map);

            // Force Leaflet to recompute size in case the container was
            // hidden/zero-height when the tile layer attached.
            setTimeout(() => this.map?.invalidateSize(), 100);
        },

        updateOfficerMarker(lat, lng) {
            if (!this.map || !window.L) return;
            if (this.officerMarker) {
                this.officerMarker.setLatLng([lat, lng]);
            } else {
                this.officerMarker = window.L.circleMarker([lat, lng], {
                    radius: 8,
                    color: '#22c55e',
                    fillColor: '#22c55e',
                    fillOpacity: 0.8,
                    weight: 2,
                }).addTo(this.map).bindPopup('Posisi Anda');
            }
        },

        startWatchingPosition() {
            if (!navigator.geolocation || !this.assignment) return;

            // Geolocation API requires a secure context (HTTPS or localhost).
            // Fail loudly so the officer knows why the distance never updates.
            const host = window.location.hostname;
            const localhostOk = host === 'localhost' || host === '127.0.0.1';
            if (!window.isSecureContext && !localhostOk) {
                this.gpsError = 'GPS membutuhkan HTTPS atau localhost';
                return;
            }

            // API returns coordinates as { lat, lng } under location_coordinates,
            // not as flat latitude/longitude on the assignment.
            const coords = this.assignment.location_coordinates;
            if (!coords || coords.lat == null || coords.lng == null) return;

            this.watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    this.gpsError = null;
                    this.distance = this.calculateDistance(
                        pos.coords.latitude,
                        pos.coords.longitude,
                        coords.lat,
                        coords.lng
                    );
                    this.updateOfficerMarker(pos.coords.latitude, pos.coords.longitude);
                },
                (err) => {
                    if (err.code === 1) this.gpsError = 'Izin lokasi ditolak';
                    else if (err.code === 2) this.gpsError = 'Posisi tidak tersedia';
                    else if (err.code === 3) this.gpsError = 'Timeout GPS';
                },
                { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
            );
        },

        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        },

        statusBadgeClass(status) {
            switch (status) {
                case 'attended': return 'bg-green-500/10 text-green-400';
                case 'not_attended': return 'bg-red-500/10 text-red-400';
                default: return 'bg-gray-500/10 text-gray-400';
            }
        },

        statusLabel(status) {
            switch (status) {
                case 'attended': return 'Hadir';
                case 'not_attended': return 'Belum';
                default: return status;
            }
        },

        destroy() {
            if (this.watchId !== null) {
                navigator.geolocation.clearWatch(this.watchId);
            }
        },
    }));

    // ── Check-in screen ─────────────────────────────────────────────
    Alpine.data('checkinScreen', checkinScreen);

    // ── Bypass screen ───────────────────────────────────────────────
    Alpine.data('bypassScreen', bypassScreen);

    // ── History index ───────────────────────────────────────────────
    Alpine.data('officerHistory', () => ({
        records: [],
        loading: false,
        page: 1,
        totalPages: 1,

        async fetchHistory() {
            this.loading = true;
            try {
                const res = await officerFetch('GET', `/api/v1/officer/attendance/history?page=${this.page}`);
                if (!res) return;
                if (res.ok) {
                    const body = await res.json();
                    this.records = body.data || [];
                    this.totalPages = body.meta?.last_page || body.last_page || 1;
                }
            } catch (err) {
                // Silent fail
            } finally {
                this.loading = false;
            }
        },

        prevPage() {
            if (this.page > 1) {
                this.page--;
                this.fetchHistory();
            }
        },

        nextPage() {
            if (this.page < this.totalPages) {
                this.page++;
                this.fetchHistory();
            }
        },
    }));

    // ── History show ────────────────────────────────────────────────
    Alpine.data('officerHistoryShow', () => ({
        record: null,
        loading: false,
        lightboxOpen: false,

        async fetchDetail() {
            this.loading = true;
            const id = window.location.pathname.split('/').pop();
            try {
                const res = await officerFetch('GET', `/api/v1/officer/attendance/${id}`);
                if (!res) return;
                if (res.ok) {
                    const body = await res.json();
                    this.record = body.data || body;
                }
            } catch (err) {
                // Silent fail
            } finally {
                this.loading = false;
            }
        },
    }));
}
