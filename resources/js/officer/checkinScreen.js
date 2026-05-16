import { officerFetch } from './api.js';

/**
 * Check-in state machine Alpine component.
 * States: idle → acquiring_gps → camera_open → photo_preview → submitting → success | error
 */
export function checkinScreen() {
    return {
        state: 'idle',
        assignmentId: null,
        latitude: null,
        longitude: null,
        accuracy: null,
        photoDataUrl: null,
        photoBlob: null,
        errorMessage: '',
        bypassEligible: false,
        bypassId: null,
        stream: null,

        init() {
            // Extract assignmentId from URL: /officer/checkin/{assignmentId}
            const parts = window.location.pathname.split('/');
            this.assignmentId = parts[parts.length - 1];
        },

        async startCheckin() {
            this.state = 'acquiring_gps';
            this.errorMessage = '';

            try {
                const position = await this.acquireGPS();
                this.latitude = position.coords.latitude;
                this.longitude = position.coords.longitude;
                this.accuracy = position.coords.accuracy;
                await this.openCamera();
            } catch (err) {
                this.errorMessage = err.message || 'Gagal mendapatkan lokasi GPS';
                this.state = 'error';
            }
        },

        acquireGPS() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation tidak didukung oleh browser ini'));
                    return;
                }

                const timeoutId = setTimeout(() => {
                    reject(new Error('Timeout: GPS tidak dapat diperoleh dalam 30 detik'));
                }, 30000);

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        clearTimeout(timeoutId);
                        resolve(pos);
                    },
                    (err) => {
                        clearTimeout(timeoutId);
                        let msg = 'Gagal mendapatkan lokasi GPS';
                        if (err.code === 1) msg = 'Izin lokasi ditolak. Aktifkan GPS di pengaturan browser.';
                        if (err.code === 2) msg = 'Posisi tidak tersedia';
                        if (err.code === 3) msg = 'Timeout mendapatkan lokasi';
                        reject(new Error(msg));
                    },
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
                );
            });
        },

        async openCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } },
                    audio: false,
                });
                this.state = 'camera_open';

                // Wait for DOM to render, then attach stream
                this.$nextTick(() => {
                    const video = this.$refs.video;
                    if (video) {
                        video.srcObject = this.stream;
                    }
                });
            } catch (err) {
                this.stopStream();
                this.errorMessage = 'Izin kamera ditolak. Aktifkan kamera di pengaturan browser.';
                this.state = 'error';
            }
        },

        capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            if (!video || !canvas) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            this.photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);

            // Convert to blob
            canvas.toBlob((blob) => {
                this.photoBlob = blob;
            }, 'image/jpeg', 0.85);

            this.stopStream();
            this.state = 'photo_preview';
        },

        retakePhoto() {
            this.photoDataUrl = null;
            this.photoBlob = null;
            this.openCamera();
        },

        async submitCheckin() {
            this.state = 'submitting';

            try {
                const formData = new FormData();
                formData.append('assignment_id', this.assignmentId);
                formData.append('latitude', this.latitude);
                formData.append('longitude', this.longitude);
                formData.append('gps_accuracy', this.accuracy);
                formData.append('photo', this.photoBlob, 'checkin.jpg');

                const res = await officerFetch('POST', '/api/v1/officer/checkin', {
                    multipart: formData,
                });

                if (!res) return; // Redirected due to auth

                if (res.ok) {
                    this.state = 'success';
                } else {
                    const body = await res.json().catch(() => ({}));
                    this.errorMessage = body.detail || body.message || 'Check-in gagal';
                    this.bypassEligible = body.bypass_eligible || false;
                    this.bypassId = body.bypass_id || null;
                    this.state = 'error';
                }
            } catch (err) {
                this.errorMessage = 'Koneksi gagal. Periksa jaringan Anda.';
                this.state = 'error';
            }
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        },
    };
}
