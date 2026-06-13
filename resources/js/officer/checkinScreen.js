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
                this.assertSecureContext();
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

        assertSecureContext() {
            // Geolocation API only works on HTTPS or localhost/127.0.0.1.
            // window.isSecureContext is the canonical check. Fail fast with a
            // clear message so the officer doesn't sit through a 30s timeout.
            if (window.isSecureContext) return;

            const host = window.location.hostname;
            if (host === 'localhost' || host === '127.0.0.1') return;

            throw new Error(
                'Browser memblokir GPS karena halaman tidak menggunakan HTTPS. ' +
                'Akses lewat https:// atau gunakan localhost.'
            );
        },

        acquireGPS() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation tidak didukung oleh browser ini'));
                    return;
                }

                const handleError = (err) => {
                    let msg = 'Gagal mendapatkan lokasi GPS';
                    if (err.code === 1) {
                        msg = 'Izin lokasi ditolak. Aktifkan akses lokasi untuk situs ini di pengaturan browser.';
                    } else if (err.code === 2) {
                        msg = 'Posisi tidak tersedia. Pastikan GPS perangkat aktif dan ada sinyal.';
                    } else if (err.code === 3) {
                        msg = 'Timeout mendapatkan lokasi. Coba di luar ruangan dengan sinyal GPS yang lebih baik.';
                    }
                    reject(new Error(msg));
                };

                // Three-step fallback chain so laptops with slow Windows
                // Location Service still succeed:
                //   1. High accuracy, accept cache up to 60s old (10s timeout).
                //   2. Low accuracy (network / wifi), accept cache up to 5min (15s timeout).
                //   3. watchPosition — sometimes returns a cached fix instantly
                //      where getCurrentPosition keeps spinning.
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    (err) => {
                        if (err.code === 1) {
                            handleError(err);
                            return;
                        }
                        navigator.geolocation.getCurrentPosition(
                            resolve,
                            (err2) => {
                                if (err2.code === 1) {
                                    handleError(err2);
                                    return;
                                }
                                let watchId = null;
                                const watchTimeout = setTimeout(() => {
                                    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                                    handleError(err2);
                                }, 10000);
                                watchId = navigator.geolocation.watchPosition(
                                    (pos) => {
                                        clearTimeout(watchTimeout);
                                        navigator.geolocation.clearWatch(watchId);
                                        resolve(pos);
                                    },
                                    () => { /* ignore, let timeout decide */ },
                                    { enableHighAccuracy: false, maximumAge: 600000 }
                                );
                            },
                            { enableHighAccuracy: false, timeout: 15000, maximumAge: 300000 }
                        );
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
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

                // Wait one tick so the <template x-if="state === 'camera_open'">
                // actually renders the <video> element, then attach the stream
                // and explicitly call play() (some browsers, especially in
                // privacy modes, won't autoplay even with the autoplay attr).
                await this.$nextTick();
                await this.attachStreamToVideo();
            } catch (err) {
                this.stopStream();
                this.errorMessage = err && err.name === 'NotAllowedError'
                    ? 'Izin kamera ditolak. Aktifkan kamera di pengaturan browser.'
                    : 'Tidak dapat mengakses kamera: ' + (err?.message || 'unknown');
                this.state = 'error';
            }
        },

        async attachStreamToVideo() {
            // The <template x-if> may need a render cycle or two before the
            // video element exists. Poll briefly so we don't lose the stream.
            for (let i = 0; i < 10; i++) {
                const video = this.$refs.video;
                if (video) {
                    video.srcObject = this.stream;
                    video.muted = true;
                    video.playsInline = true;
                    try { await video.play(); } catch { /* ignore */ }
                    return;
                }
                await new Promise(r => setTimeout(r, 50));
            }
        },

        async capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            if (!video || !canvas) return;

            // If the video isn't actually playing yet, the captured frame
            // will be 0x0 / blank. Wait for it briefly before snapping.
            if (!video.videoWidth || !video.videoHeight) {
                for (let i = 0; i < 20; i++) {
                    if (video.videoWidth && video.videoHeight) break;
                    await new Promise(r => setTimeout(r, 100));
                }
            }
            if (!video.videoWidth || !video.videoHeight) {
                this.errorMessage = 'Kamera belum siap. Coba lagi.';
                this.state = 'error';
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            // Build the data URL (for preview) and the Blob (for upload)
            // BEFORE switching state, and wait for the Blob to actually be
            // produced — otherwise submit fires with photoBlob still null.
            this.photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);
            this.photoBlob = await new Promise((resolve) => {
                canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.85);
            });

            this.stopStream();
            this.state = 'photo_preview';
        },

        retakePhoto() {
            this.photoDataUrl = null;
            this.photoBlob = null;
            this.openCamera();
        },

        async submitCheckin() {
            if (!this.photoBlob) {
                this.errorMessage = 'Foto belum siap. Silakan ambil foto kembali.';
                this.state = 'error';
                return;
            }

            this.state = 'submitting';

            try {
                const formData = new FormData();
                formData.append('assignment_id', this.assignmentId);
                formData.append('latitude', this.latitude);
                formData.append('longitude', this.longitude);
                formData.append('gps_accuracy', this.accuracy);
                formData.append('gps_provider', 'gps');
                formData.append('mock_location', '0');
                formData.append('timestamp_device', new Date().toISOString());
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

                    // If bypass is offered, stash the rejected bundle so the
                    // bypass screen can reuse the same GPS + photo without
                    // making the officer recapture them.
                    if (this.bypassEligible) {
                        await this.stashBypassBundle(body.reason_code);
                    }

                    this.state = 'error';
                }
            } catch (err) {
                this.errorMessage = 'Koneksi gagal. Periksa jaringan Anda.';
                this.state = 'error';
            }
        },

        async stashBypassBundle(reasonCode) {
            // Convert the photo blob to a data URL so it survives the
            // navigation to /officer/bypass.
            const photoDataUrl = await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.readAsDataURL(this.photoBlob);
            });

            sessionStorage.setItem('ph_bypass_bundle', JSON.stringify({
                assignment_id: this.assignmentId,
                reason_code: reasonCode || 'OUTSIDE_GEOFENCE',
                latitude: this.latitude,
                longitude: this.longitude,
                gps_accuracy: this.accuracy,
                gps_provider: 'gps',
                mock_location: false,
                timestamp_device: new Date().toISOString(),
                photo_data_url: photoDataUrl,
            }));
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        },
    };
}
