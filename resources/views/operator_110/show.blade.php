@extends('layouts.admin')

@section('title', 'Detail Laporan 110: ' . $report->no_tiketing)
@section('page-title', 'Detail Laporan 110')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-[var(--color-surface-800)] p-6 rounded-xl border border-[var(--color-surface-600)] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h3 class="text-2xl font-bold text-white">{{ $report->no_tiketing }}</h3>
                <span class="px-2.5 py-1 rounded-md text-xs font-bold inline-flex items-center gap-1.5
                    {{ $report->status === 'Butuh penanganan' ? 'bg-red-500/20 text-red-400' : '' }}
                    {{ $report->status === 'Sedang penanganan' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                    {{ $report->status === 'Sudah penanganan' ? 'bg-green-500/20 text-green-400' : '' }}">
                    @if($report->status === 'Butuh penanganan')
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                    @elseif($report->status === 'Sedang penanganan')
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                    @else
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                    @endif
                    {{ $report->status }}
                </span>
            </div>
            <p class="text-gray-400 text-sm">Dilaporkan pada: {{ $report->waktu_dilaporkan ? $report->waktu_dilaporkan->format('d F Y H:i') : '-' }}</p>
        </div>
        
        <div>
            <a href="{{ $waLink ?? '#' }}" target="_blank" class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-5 py-2.5 rounded-lg font-bold transition-all shadow-lg shadow-[#25D366]/20">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Teruskan ke {{ $report->unit->nama_unit ?? 'Unit' }}
            </a>
            <p class="text-xs text-gray-500 mt-2 text-center">Via WhatsApp (+{{ preg_replace('/[^0-9]/', '', $report->unit->no_wa ?? '') }})</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informasi Detail -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-[var(--color-surface-800)] p-6 rounded-xl border border-[var(--color-surface-600)] shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-[var(--color-surface-600)] pb-2">Informasi Laporan</h4>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Nama Pelapor</p>
                            <p class="text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg">{{ $report->nama_pelapor ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">No HP Pelapor</p>
                            <p class="text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg">
                                {{ $report->no_hp_pelapor ?? '-' }} 
                                @if($report->jenis_no_hp_pelapor)
                                    <span class="text-xs text-blue-400">({{ $report->jenis_no_hp_pelapor }})</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Jenis Gangguan</p>
                        <p class="text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg">{{ $report->jenis_gangguan }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Waktu Kejadian</p>
                            <p class="text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg">{{ $report->waktu_kejadian ? $report->waktu_kejadian->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Waktu Mendatangi TKP</p>
                            <p class="text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg">{{ $report->waktu_mendatangi_tkp ? $report->waktu_mendatangi_tkp->format('d/m/Y H:i:s') : 'Belum mendatangi' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tempat Kejadian Perkara (TKP) - Deskripsi</p>
                        <p class="text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg">{{ $report->tempat_kejadian }}</p>
                    </div>
                </div>
            </div>

            <!-- Dokumentasi Hasil Penanganan -->
            <div class="bg-[var(--color-surface-800)] p-6 rounded-xl border border-[var(--color-surface-600)] shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-[var(--color-surface-600)] pb-2">Hasil Penanganan Pamapta</h4>
                
                @if($report->status === 'Sudah penanganan')
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-full border-b border-[var(--color-surface-600)] pb-2 mb-2">
                                <p class="text-sm text-gray-500 mb-1">Petugas Pamapta</p>
                                <p class="text-white font-medium">{{ $report->nama_pamapta ?? '-' }} (NRP: {{ $report->nrp_pamapta ?? '-' }})</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Uraian Kejadian</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->uraian_kejadian ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Tindakan Kepolisian</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->tindakan_kepolisian ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Modus Operandi</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->modus_operandi ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Korban</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->korban ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Pelaku</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->pelaku ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Sanksi-sanksi</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->sanksi_sanksi ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Motif</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->motif ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Alat yang Digunakan</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->alat_yang_digunakan ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Kerugian</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->kerugian ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Bukti yang Dapat Disita</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->bukti_yang_dapat_disita ?? '-' }}</p>
                            </div>

                            <div class="col-span-full">
                                <p class="text-sm text-gray-500 mb-1">Keterangan Lain</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->keterangan_lain ?? '-' }}</p>
                            </div>
                        </div>
                        
                        @if($report->bukti_foto_path)
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-2">Foto Dokumentasi (Watermarked)</p>
                                <a href="{{ asset('storage/' . $report->bukti_foto_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $report->bukti_foto_path) }}" alt="Foto Penanganan" class="rounded-lg w-full max-h-96 object-cover border border-[var(--color-surface-600)] hover:opacity-90 transition cursor-pointer">
                                </a>
                            </div>
                        @else
                            <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-6 flex flex-col items-center justify-center text-gray-500">
                                <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p>Tidak ada foto yang dilampirkan.</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex items-center justify-center h-32 bg-[var(--color-surface-900)] rounded-lg border border-dashed border-[var(--color-surface-600)]">
                        <p class="text-gray-500 text-sm">Petugas belum menyelesaikan penanganan laporan ini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Map -->
        <div class="space-y-6">
            <div class="bg-[var(--color-surface-800)] p-6 rounded-xl border border-[var(--color-surface-600)] shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-[var(--color-surface-600)] pb-2">Lokasi Terkini</h4>
                
                @if($report->lat && $report->lng)
                    <div class="rounded-lg overflow-hidden border border-[var(--color-surface-600)] h-64 relative z-0">
                        <div id="map" class="absolute inset-0"></div>
                    </div>
                    <div class="mt-3 flex gap-2 text-xs text-gray-400">
                        <span class="bg-[var(--color-surface-900)] px-2 py-1 rounded">Lat: {{ number_format($report->lat, 6) }}</span>
                        <span class="bg-[var(--color-surface-900)] px-2 py-1 rounded">Lng: {{ number_format($report->lng, 6) }}</span>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-[var(--color-surface-600)]">
                        <p class="text-xs text-gray-500 mb-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Alamat Aktual Kedatangan
                        </p>
                        <p class="text-sm text-white font-medium bg-[var(--color-surface-900)] p-3 rounded-lg border border-[var(--color-surface-600)] leading-relaxed">
                            {{ $report->alamat_aktual_110 ?? 'Nama alamat tidak tersedia' }}
                        </p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-64 bg-[var(--color-surface-900)] rounded-lg border border-dashed border-[var(--color-surface-600)] text-gray-500">
                        <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm">Lokasi belum dikirim oleh petugas.</p>
                    </div>
                @endif
            </div>
            
            <div class="bg-[var(--color-surface-800)] p-6 rounded-xl border border-[var(--color-surface-600)] shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-[var(--color-surface-600)] pb-2">Status Link Akses Pamapta</h4>
                <div class="bg-[var(--color-surface-900)] p-3 rounded-lg break-all">
                    <p class="text-xs text-blue-400 font-mono">{{ route('pamapta.report.show', ['token' => $report->token]) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($report->lat && $report->lng)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $report->lat }};
        const lng = {{ $report->lng }};
        const title = "{{ addslashes($report->no_tiketing) }}";

        const map = L.map('map').setView([lat, lng], 16);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(`<b>${title}</b><br>Lokasi Terkini Petugas`).openPopup();
    });
</script>
@endif
@endpush
