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
        facingMode: 'user',

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

        openCamera() {
            this.state = 'waiting_for_photo';
        },

        triggerCameraInput() {
            const input = document.getElementById('cameraInput');
            if (input) input.click();
        },

        async handleCapture(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.state = 'compressing_photo';
            this.errorMessage = '';

            try {
                const qualityCheck = await this.analyzePhotoQuality(file);
                if (!qualityCheck.valid) {
                    this.errorMessage = qualityCheck.reason;
                    this.state = 'waiting_for_photo';
                    return;
                }

                const compressedBlob = await this.compressImage(file, 1200, 0.7);
                this.photoBlob = compressedBlob;
                if (this.photoDataUrl) {
                    URL.revokeObjectURL(this.photoDataUrl);
                }
                this.photoDataUrl = URL.createObjectURL(compressedBlob);
                
                this.state = 'photo_preview';
            } catch (err) {
                console.error('Compression failed:', err);
                this.errorMessage = 'Gagal memproses foto. Silakan coba lagi.';
                this.state = 'waiting_for_photo';
            } finally {
                event.target.value = '';
            }
        },

        analyzePhotoQuality(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        // Shrink to 300x300 max for fast analysis
                        const canvas = document.createElement('canvas');
                        const maxSize = 300;
                        let w = img.width, h = img.height;
                        if (w > h) {
                            if (w > maxSize) { h = Math.round(h * maxSize / w); w = maxSize; }
                        } else {
                            if (h > maxSize) { w = Math.round(w * maxSize / h); h = maxSize; }
                        }
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);
                        const imageData = ctx.getImageData(0, 0, w, h);
                        const data = imageData.data;

                        // 1. Darkness Check (95th percentile)
                        const brightnesses = [];
                        for (let i = 0; i < data.length; i += 4) {
                            // Luminance formula
                            const brightness = 0.299 * data[i] + 0.587 * data[i+1] + 0.114 * data[i+2];
                            brightnesses.push(brightness);
                        }
                        brightnesses.sort((a, b) => a - b);
                        const p95Index = Math.floor(brightnesses.length * 0.95);
                        const p95Brightness = brightnesses[p95Index];

                        if (p95Brightness < 15) {
                            resolve({ valid: false, reason: 'Foto terlalu gelap atau kamera tertutup. Silakan cari tempat lebih terang dan coba lagi.' });
                            return;
                        }

                        // 2. Blur Check (Laplacian Variance)
                        const gray = new Uint8Array(w * h);
                        for (let i = 0; i < w * h; i++) {
                            gray[i] = 0.299 * data[i*4] + 0.587 * data[i*4+1] + 0.114 * data[i*4+2];
                        }

                        // 3x3 Laplacian kernel
                        const laplacian = new Int32Array(w * h);
                        let sum = 0;
                        for (let y = 1; y < h - 1; y++) {
                            for (let x = 1; x < w - 1; x++) {
                                const idx = y * w + x;
                                const val = 
                                    gray[(y - 1) * w + x] +
                                    gray[(y + 1) * w + x] +
                                    gray[y * w + (x - 1)] +
                                    gray[y * w + (x + 1)] -
                                    4 * gray[idx];
                                laplacian[idx] = val;
                                sum += val;
                            }
                        }
                        const mean = sum / ((w - 2) * (h - 2));
                        let varianceSum = 0;
                        for (let y = 1; y < h - 1; y++) {
                            for (let x = 1; x < w - 1; x++) {
                                const val = laplacian[y * w + x];
                                varianceSum += Math.pow(val - mean, 2);
                            }
                        }
                        const variance = varianceSum / ((w - 2) * (h - 2));

                        // Very conservative threshold to avoid rejecting night photos
                        if (variance < 20) {
                            resolve({ valid: false, reason: 'Foto terlalu buram (blur). Harap pegang kamera dengan stabil dan ambil foto ulang.' });
                            return;
                        }

                        resolve({ valid: true });
                    };
                    img.onerror = () => resolve({ valid: false, reason: 'Gagal memproses foto. Silakan ambil foto ulang.' });
                    img.src = e.target.result;
                };
                reader.onerror = () => resolve({ valid: false, reason: 'Gagal memuat file foto.' });
                reader.readAsDataURL(file);
            });
        },

        compressImage(file, maxWidth, quality) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let w = img.width, h = img.height;
                        if (w > maxWidth) {
                            h = Math.round(h * maxWidth / w);
                            w = maxWidth;
                        }
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);

                        const drawTextAndResolve = () => {
                            // Draw bottom banner
                            const bannerHeight = 110;
                            ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
                            ctx.fillRect(0, h - bannerHeight, w, bannerHeight);

                            // Draw text
                            ctx.fillStyle = '#ffffff';
                            ctx.font = 'bold 20px Arial';
                            ctx.textAlign = 'left';
                            ctx.textBaseline = 'middle';
                            
                            const now = new Date();
                            const dateStr = now.toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric'}) + ' ' + 
                                            now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false});
                            const latStr = this.latitude ? this.latitude.toFixed(6) : '-';
                            const lngStr = this.longitude ? this.longitude.toFixed(6) : '-';
                            const accStr = this.accuracy ? this.accuracy.toFixed(0) : '-';
                            
                            ctx.fillText(`Waktu: ${dateStr}`, 20, h - 85);
                            ctx.fillText(`Lokasi: ${latStr}, ${lngStr}`, 20, h - 55);
                            ctx.fillText(`Akurasi GPS: ${accStr} meter`, 20, h - 25);

                            canvas.toBlob(
                                (blob) => blob ? resolve(blob) : reject(new Error('toBlob failed')),
                                'image/jpeg',
                                quality
                            );
                        };

                        const logo = new Image();
                        logo.onload = () => {
                            // Draw logo at top left with padding
                            const logoWidth = 100;
                            const logoHeight = (logo.height / logo.width) * logoWidth;
                            ctx.drawImage(logo, 20, 20, logoWidth, logoHeight);
                            drawTextAndResolve();
                        };
                        logo.onerror = () => {
                            drawTextAndResolve();
                        };
                        logo.src = '/images/logo_libas.png';
                    };
                    img.onerror = reject;
                    img.src = e.target.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        retakePhoto() {
            this.photoBlob = null;
            if (this.photoDataUrl) {
                URL.revokeObjectURL(this.photoDataUrl);
                this.photoDataUrl = null;
            }
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

    };
}
