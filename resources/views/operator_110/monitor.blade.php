@extends('layouts.admin')

@section('title', 'Peta Pantauan 110')
@section('page-title', 'Peta Pantauan Global 110')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Custom Leaflet Dark/Sleek Theme Adjustments */
        .leaflet-popup-content-wrapper {
            background: var(--color-surface-800);
            color: #fff;
            border: 1px solid var(--color-surface-600);
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.5);
        }
        .leaflet-popup-tip {
            background: var(--color-surface-800);
            border: 1px solid var(--color-surface-600);
        }
        .custom-pulse-marker {
            width: 16px;
            height: 16px;
            background: #ef4444; /* red-500 */
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 rgba(239, 68, 68, 0.4);
            animation: pulse 2s infinite;
        }
        .custom-yellow-marker {
            width: 16px;
            height: 16px;
            background: #eab308; /* yellow-500 */
            border-radius: 50%;
            border: 3px solid #fff;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
@endpush

@section('content')
<div class="h-[calc(100vh-12rem)] min-h-[500px] relative rounded-xl overflow-hidden border border-[var(--color-surface-600)] shadow-lg z-0">
    <div id="global-map" class="absolute inset-0"></div>

    <!-- Map Overlay Controls (Top Right) -->
    <div class="absolute top-4 right-4 z-[400] bg-[var(--color-surface-800)]/90 backdrop-blur border border-[var(--color-surface-600)] p-4 rounded-xl shadow-lg w-64">
        <h4 class="text-white font-bold mb-3 border-b border-[var(--color-surface-600)] pb-2 text-sm">Status Laporan Aktif</h4>
        
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500 animate-pulse border border-white"></span>
                    <span class="text-xs text-gray-300">Butuh Penanganan</span>
                </div>
                <span class="text-xs font-bold text-white">{{ $activeReports->where('status', 'Butuh penanganan')->count() }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-500 border border-white"></span>
                    <span class="text-xs text-gray-300">Sedang Penanganan</span>
                </div>
                <span class="text-xs font-bold text-white">{{ $activeReports->where('status', 'Sedang penanganan')->count() }}</span>
            </div>
            
            <div class="pt-3 mt-3 border-t border-[var(--color-surface-600)]">
                <p class="text-xs text-gray-500 italic text-center">Total: {{ $activeReports->count() }} Laporan Berjalan</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Semarang Default Center
        const defaultLat = -7.0051453;
        const defaultLng = 110.4381254;

        const map = L.map('global-map').setView([defaultLat, defaultLng], 12);

        // Dark theme map tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);

        const activeReports = @json($activeReports);
        const markers = [];

        activeReports.forEach(report => {
            if (report.lat && report.lng) {
                // Determine icon based on status
                const isUrgent = report.status === 'Butuh penanganan';
                const className = isUrgent ? 'custom-pulse-marker' : 'custom-yellow-marker';
                
                const customIcon = L.divIcon({
                    className: className,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });

                const marker = L.marker([report.lat, report.lng], { icon: customIcon }).addTo(map);
                
                const popupContent = `
                    <div class="p-1">
                        <div class="font-bold text-sm mb-1 text-blue-400">${report.no_tiketing}</div>
                        <div class="text-xs text-gray-300 mb-2">${report.jenis_gangguan}</div>
                        <div class="text-xs border-t border-gray-600 pt-2 mb-2">
                            <div class="mb-1"><span class="text-gray-400">Waktu:</span> ${report.waktu_dilaporkan ? new Date(report.waktu_dilaporkan).toLocaleString('id-ID') : '-'}</div>
                            <div><span class="text-gray-400">Unit:</span> ${report.unit ? report.unit.nama_unit : '-'}</div>
                        </div>
                        <a href="/operator-110/${report.id}" class="text-xs bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded w-full inline-block text-center transition">Lihat Detail</a>
                    </div>
                `;

                marker.bindPopup(popupContent);
                markers.push([report.lat, report.lng]);
            }
        });

        // Fit map bounds to show all markers if they exist
        if (markers.length > 0) {
            map.fitBounds(markers, { padding: [50, 50], maxZoom: 16 });
        }
    });
</script>
@endpush
