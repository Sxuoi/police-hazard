<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan 110 - Pamapta</title>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 text-gray-800" x-data="pamaptaForm()">

<div class="max-w-md mx-auto bg-white min-h-screen relative shadow-lg">

    <!-- Navbar -->
    <div class="bg-blue-800 text-white p-4 text-center sticky top-0 z-40">
        <h1 class="text-xl font-bold">Laporan 110</h1>
        <p class="text-sm">Tiket: {{ $report->no_tiketing }}</p>
    </div>

    <!-- Locking Overlay (Tiba di TKP) -->
    @if($report->status === 'Butuh penanganan')
    <div x-show="showTibaModal" class="fixed inset-0 z-50 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center">
        <!-- Bulat Modal -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-900 rounded-full shadow-[0_0_40px_rgba(37,99,235,0.4)] text-white w-80 h-80 flex flex-col items-center justify-center border-4 border-white/50">
            
            <!-- Icon Lokasi -->
            <div class="bg-white/20 p-5 rounded-full mb-6">
                <svg class="w-20 h-20 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            
            <!-- Tombol Mendatangi TKP -->
            <button @click="arriveAtTkp" :disabled="loadingArrive" class="bg-white text-blue-900 hover:bg-gray-100 font-bold py-3 px-6 rounded-full text-base transition shadow-xl flex justify-center items-center min-w-[200px]">
                <span x-show="!loadingArrive">MENDATANGI TKP</span>
                <span x-show="loadingArrive" class="animate-spin h-6 w-6 border-4 border-blue-900 border-t-transparent rounded-full" style="display: none;"></span>
            </button>
            
        </div>
    </div>
    @endif

    <!-- Content Form -->
    <div class="p-4" :class="{ 'blur-sm pointer-events-none': showTibaModal }">

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4 text-sm">{{ session('error') }}</div>
        @endif

        @if($report->status === 'Sudah penanganan' && !$isUnlocked)
            <div class="bg-yellow-100 border border-yellow-300 p-4 rounded-lg mb-6">
                <h3 class="font-bold text-yellow-800 mb-2">Form Terkunci (Read-Only)</h3>
                <p class="text-sm text-yellow-700 mb-4">Laporan ini sudah diselesaikan. Masukkan Kode Tiketing untuk mengedit.</p>
                <form action="{{ route('pamapta.report.unlock', $report->token) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="kode_tiketing" placeholder="Kode Tiketing" class="border p-2 rounded flex-1">
                    <button type="submit" class="bg-yellow-600 text-white px-4 rounded font-bold">Buka</button>
                </form>
            </div>
        @endif

        <div class="mb-6">
            <h2 class="font-bold text-lg mb-2 border-b pb-1">Detail Gangguan</h2>
            <p class="text-sm"><span class="font-semibold text-gray-600">Jenis:</span> {{ $report->jenis_gangguan }}</p>
            <p class="text-sm"><span class="font-semibold text-gray-600">TKP:</span> {{ $report->tempat_kejadian }}</p>
        </div>

        <!-- Map Section -->
        <div class="mb-6">
            <h2 class="font-bold text-lg mb-2 border-b pb-1">Lokasi Terkini</h2>
            <div class="relative rounded-lg overflow-hidden border bg-gray-200">
                <div id="map" class="h-48 w-full z-10"></div>
                <!-- Custom GPS Button on map -->
                <button @click="updateLocation(false)" type="button" class="absolute bottom-4 right-4 z-[400] bg-white p-2 rounded-full shadow-lg border border-gray-300 hover:bg-gray-50 focus:outline-none">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.196 1.99-.555 2.914m4.242 3.146a13.945 13.945 0 01-2.906 3.44m2.906-3.44a13.945 13.945 0 002.906-3.44M12 11V3"></path></svg>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2" id="alamat_teks">{{ $report->alamat_aktual_110 ?? 'Mencari alamat...' }}</p>
        </div>

        <form action="{{ route('pamapta.report.complete', $report->token) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <fieldset {{ (!$isUnlocked) ? 'disabled' : '' }}>
                <!-- Hidden inputs for final lat/lng set upon complete submit -->
                <input type="hidden" name="lat" id="final_lat">
                <input type="hidden" name="lng" id="final_lng">
                <input type="hidden" name="alamat" id="final_alamat">
                <input type="hidden" name="action_type" id="action_type" value="complete">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nama Pamapta</label>
                        <input type="text" name="nama_pamapta" value="{{ old('nama_pamapta', $report->nama_pamapta) }}" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">NRP Pamapta</label>
                        <input type="text" name="nrp_pamapta" value="{{ old('nrp_pamapta', $report->nrp_pamapta) }}" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Uraian Kejadian</label>
                        <textarea name="uraian_kejadian" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[100px]" required>{{ old('uraian_kejadian', $report->uraian_kejadian) }}</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-1">Modus Operandi</label>
                        <textarea name="modus_operandi" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('modus_operandi', $report->modus_operandi) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Korban</label>
                        <textarea name="korban" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('korban', $report->korban) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Pelaku</label>
                        <textarea name="pelaku" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('pelaku', $report->pelaku) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Sanksi-sanksi</label>
                        <textarea name="sanksi_sanksi" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('sanksi_sanksi', $report->sanksi_sanksi) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Motif</label>
                        <textarea name="motif" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('motif', $report->motif) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Alat yang Digunakan</label>
                        <textarea name="alat_yang_digunakan" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('alat_yang_digunakan', $report->alat_yang_digunakan) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Kerugian</label>
                        <textarea name="kerugian" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('kerugian', $report->kerugian) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Bukti yang Dapat Disita</label>
                        <textarea name="bukti_yang_dapat_disita" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('bukti_yang_dapat_disita', $report->bukti_yang_dapat_disita) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Tindakan Kepolisian</label>
                        <textarea name="tindakan_kepolisian" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[80px]">{{ old('tindakan_kepolisian', $report->tindakan_kepolisian) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Keterangan Lain</label>
                        <textarea name="keterangan_lain" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500 outline-none disabled:bg-gray-100 min-h-[60px]">{{ old('keterangan_lain', $report->keterangan_lain) }}</textarea>
                    </div>

                    @if($isUnlocked && $report->status !== 'Sudah penanganan')
                    <!-- Foto is required on complete -->
                    <!-- Alpine.js variable to track selected file -->
                    <div x-data="{ 
                        showSourceModal: false, 
                        fileName: '',
                        openCamera() {
                            this.$refs.fotoInput.setAttribute('capture', 'environment');
                            this.$refs.fotoInput.click();
                            this.showSourceModal = false;
                        },
                        openGallery() {
                            this.$refs.fotoInput.removeAttribute('capture');
                            this.$refs.fotoInput.click();
                            this.showSourceModal = false;
                        },
                        fileSelected(event) {
                            if (event.target.files.length > 0) {
                                this.fileName = event.target.files[0].name;
                            } else {
                                this.fileName = '';
                            }
                        }
                    }">
                        <label class="block text-sm font-semibold mb-2">Foto Dokumentasi</label>
                        
                        <!-- Hidden File Input -->
                        <input type="file" name="foto" x-ref="fotoInput" accept="image/*" class="hidden" @change="fileSelected" {{ $report->bukti_foto_path ? '' : 'required' }}>
                        
                        <!-- Custom Button -->
                        <button type="button" @click="showSourceModal = true" class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold py-3 px-4 border border-blue-300 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Ambil Dokumentasi
                        </button>

                        <!-- File Name Preview -->
                        <div x-show="fileName" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 text-green-700 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>File dipilih: <span x-text="fileName" class="font-bold"></span></span>
                        </div>

                        <!-- Source Selection Modal -->
                        <div x-show="showSourceModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                            <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden" @click.away="showSourceModal = false">
                                <div class="p-5 border-b border-gray-100">
                                    <h3 class="text-lg font-bold text-gray-800 text-center">Pilih Sumber Foto</h3>
                                </div>
                                <div class="p-4 grid grid-cols-2 gap-4">
                                    <button type="button" @click="openCamera" class="flex flex-col items-center justify-center gap-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 p-6 rounded-xl transition">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span class="font-semibold text-sm">Kamera</span>
                                    </button>
                                    <button type="button" @click="openGallery" class="flex flex-col items-center justify-center gap-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-700 p-6 rounded-xl transition">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span class="font-semibold text-sm">Galeri File</span>
                                    </button>
                                </div>
                                <div class="p-3 bg-gray-50 border-t border-gray-100">
                                    <button type="button" @click="showSourceModal = false" class="w-full py-2 text-sm text-gray-500 font-medium hover:text-gray-800 transition">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <button type="button" @click="submitReport('draft')" class="w-full bg-gray-600 hover:bg-gray-500 text-white font-bold py-3 px-2 rounded-lg shadow-md flex justify-center items-center text-sm transition">
                            <span x-show="!loadingDraft">SIMPAN SEMENTARA</span>
                            <span x-show="loadingDraft" class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                        </button>
                        <button type="button" @click="submitReport('complete')" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-2 rounded-lg shadow-md flex justify-center items-center text-sm transition">
                            <span x-show="!loadingComplete">SELESAIKAN LAPORAN</span>
                            <span x-show="loadingComplete" class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                        </button>
                    </div>
                    <!-- hidden actual submit -->
                    <button type="submit" id="actual_submit_btn" class="hidden"></button>
                    @endif
                </div>
            </fieldset>
            
            @if($report->bukti_foto_path)
            <div class="mt-6 border-t pt-4">
                <h3 class="font-bold text-sm mb-2 text-gray-700">Foto Dokumentasi Tersimpan</h3>
                <img src="{{ Storage::url($report->bukti_foto_path) }}" alt="Bukti Foto" class="rounded-lg shadow-md w-full">
            </div>
            @endif

        </form>

    </div>
</div>

<script>
function pamaptaForm() {
    return {
        showTibaModal: {{ $report->status === 'Butuh penanganan' ? 'true' : 'false' }},
        loadingArrive: false,
        loadingDraft: false,
        loadingComplete: false,
        map: null,
        marker: null,
        currentLat: null,
        currentLng: null,
        currentAlamat: '{{ $report->alamat_aktual_110 ?? "" }}',

        init() {
            let lat = -6.200000;
            let lng = 106.816666;
            
            this.map = L.map('map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(this.map);
            
            this.marker = L.marker([lat, lng]).addTo(this.map);
            
            if(!this.showTibaModal) {
                this.updateLocation(true);
            }
        },

        async getGeolocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation tidak didukung di browser ini.'));
                }
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });
        },

        async fetchAddress(lat, lng) {
            try {
                let res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                let data = await res.json();
                return data.display_name || 'Alamat tidak ditemukan';
            } catch(e) {
                return 'Alamat tidak ditemukan';
            }
        },

        async updateMapUi(lat, lng) {
            this.currentLat = lat;
            this.currentLng = lng;
            this.map.setView([lat, lng], 17);
            this.marker.setLatLng([lat, lng]);
            this.currentAlamat = await this.fetchAddress(lat, lng);
            document.getElementById('alamat_teks').innerText = this.currentAlamat;
        },

        async arriveAtTkp() {
            this.loadingArrive = true;
            try {
                const pos = await this.getGeolocation();
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                
                await this.updateMapUi(lat, lng);

                let res = await fetch('{{ route('pamapta.report.arrive', $report->token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        lat: lat,
                        lng: lng,
                        alamat: this.currentAlamat
                    })
                });

                if(res.ok) {
                    this.showTibaModal = false;
                } else {
                    alert('Gagal menghubungi server.');
                }
            } catch(e) {
                let errorMsg = e.message ? e.message : 'Kesalahan tidak diketahui.';
                if (e.code === 1) errorMsg = 'Izin lokasi ditolak oleh pengguna.';
                if (e.code === 2) errorMsg = 'Informasi lokasi tidak tersedia (kemungkinan sensor GPS bermasalah).';
                if (e.code === 3) errorMsg = 'Waktu permintaan lokasi habis (timeout).';
                
                alert('Gagal mendapatkan lokasi. Detail: ' + errorMsg);
                console.error(e);
            } finally {
                this.loadingArrive = false;
            }
        },

        async updateLocation(silent = false) {
            try {
                const pos = await this.getGeolocation();
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                
                await this.updateMapUi(lat, lng);

                if(!silent && !this.showTibaModal) {
                    await fetch('{{ route('pamapta.report.location', $report->token) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            lat: lat,
                            lng: lng
                        })
                    });
                }
            } catch(e) {
                if(!silent) alert('Gagal mendapatkan lokasi GPS terbaru.');
            }
        },

        async submitReport(type) {
            const form = document.getElementById('actual_submit_btn').closest('form');

            if (type === 'draft') {
                document.getElementById('action_type').value = 'draft';
                // Hapus atribut required agar HTML5 validasi tidak menghalangi simpan draft
                form.querySelectorAll('[required]').forEach(el => {
                    el.dataset.wasRequired = 'true';
                    el.removeAttribute('required');
                });
            } else {
                document.getElementById('action_type').value = 'complete';
                // Kembalikan atribut required jika sebelumnya dihapus
                form.querySelectorAll('[data-was-required="true"]').forEach(el => {
                    el.setAttribute('required', 'required');
                });
            }

            // Validasi HTML bawaan (HTML5 validation) sebelum menyalakan spinner
            if (!form.checkValidity()) {
                form.reportValidity(); // Memunculkan tooltip peringatan field kosong dari browser
                return; // Stop function agar spinner tidak menyala
            }

            // Jika lolos validasi, nyalakan spinner
            if (type === 'draft') {
                this.loadingDraft = true;
            } else {
                this.loadingComplete = true;
            }

            try {
                const pos = await this.getGeolocation();
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const alamat = await this.fetchAddress(lat, lng);

                document.getElementById('final_lat').value = lat;
                document.getElementById('final_lng').value = lng;
                document.getElementById('final_alamat').value = alamat;

                document.getElementById('actual_submit_btn').click();
            } catch(e) {
                alert('Gagal memverifikasi lokasi terakhir. Pastikan GPS aktif!');
                this.loadingComplete = false;
                this.loadingDraft = false;
            }
        }
    }
}
</script>
</body>
</html>
