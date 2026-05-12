@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Beranda')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<style>
    /* Custom Cluster Colors based on attendance status */
    .marker-cluster-full { background-color: rgba(34, 197, 94, 0.6); }
    .marker-cluster-full div { background-color: rgba(34, 197, 94, 0.9); color: white; }
    .marker-cluster-partial { background-color: rgba(234, 179, 8, 0.6); }
    .marker-cluster-partial div { background-color: rgba(234, 179, 8, 0.9); color: white; }
    .marker-cluster-missing { background-color: rgba(239, 68, 68, 0.6); }
    .marker-cluster-missing div { background-color: rgba(239, 68, 68, 0.9); color: white; }
    .marker-cluster-no_assignment { background-color: rgba(107, 114, 128, 0.6); }
    .marker-cluster-no_assignment div { background-color: rgba(107, 114, 128, 0.9); color: white; }
</style>
@endpush

@section('content')
<div x-data="dashboardData()" class="space-y-6">

    {{-- Filters --}}
    <div class="flex justify-between items-center bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-4">
        <div class="text-sm text-gray-400">
            Data real-time untuk tanggal <strong class="text-white" x-text="date"></strong>
            <span x-show="isPolling" class="ml-2 inline-flex items-center gap-1 text-[var(--color-accent)] animate-pulse">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Live
            </span>
        </div>
        <div class="flex gap-2">
            <input type="date" x-model="date" @change="fetchData" class="px-3 py-1.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card
            title="Total Lokasi"
            value="—"
            x-text="metrics.total_locations"
            color="indigo"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>'
        />
        <x-card
            title="Hadir Penuh"
            value="—"
            x-text="metrics.full_attendance"
            color="green"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />
        <x-card
            title="Hadir Sebagian"
            value="—"
            x-text="metrics.partial_attendance"
            color="yellow"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />
        <x-card
            title="Tidak Hadir"
            value="—"
            x-text="metrics.missing_attendance"
            color="red"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />
    </div>

    {{-- Map Container --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-1 overflow-hidden relative" style="min-height: 500px;">
        <div id="dashboard-map" class="w-full rounded-xl z-0" style="height: 500px; background: var(--color-surface-700);" wire:ignore></div>
        
        {{-- Legend --}}
        <div class="absolute bottom-4 left-4 bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-3 rounded-xl shadow-lg z-[1000] text-xs">
            <h4 class="font-semibold text-white mb-2">Status Kehadiran</h4>
            <div class="space-y-1">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500"></span><span class="text-gray-300">Hadir Penuh</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500"></span><span class="text-gray-300">Hadir Sebagian</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span><span class="text-gray-300">Tidak Hadir</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-500"></span><span class="text-gray-300">Tidak Ada Operasi</span></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardData', () => ({
        date: '{{ $date }}',
        metrics: {
            total_locations: {{ $metrics['total_locations'] ?? 0 }},
            full_attendance: {{ $metrics['full_attendance'] ?? 0 }},
            partial_attendance: {{ $metrics['partial_attendance'] ?? 0 }},
            missing_attendance: {{ $metrics['missing_attendance'] ?? 0 }}
        },
        map: null,
        markersGroup: null,
        isPolling: true,
        pollInterval: null,
        
        init() {
            this.initMap();
            this.fetchData();
            
            // Poll every 30 seconds
            this.pollInterval = setInterval(() => {
                this.fetchData(false); // false = don't reset map view
            }, 30000);
        },

        initMap() {
            this.map = L.map('dashboard-map').setView([-6.9932, 110.4203], 12); // Semarang default
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(this.map);

            this.markersGroup = L.markerClusterGroup({
                iconCreateFunction: function(cluster) {
                    var childCount = cluster.getChildCount();
                    // Determine dominant status logic here if needed, or just default to blue
                    var c = ' marker-cluster-';
                    // We'll just use primary blue for now for clusters, or we can aggregate status.
                    // For simplicity, let's just make it a generic cluster.
                    return new L.DivIcon({ html: '<div><span>' + childCount + '</span></div>', className: 'marker-cluster marker-cluster-full', iconSize: new L.Point(40, 40) });
                }
            });
            
            this.map.addLayer(this.markersGroup);
        },

        async fetchData(resetView = true) {
            try {
                const response = await fetch(`/dashboard/map-data?date=${this.date}`);
                if (!response.ok) throw new Error('Network response was not ok');
                
                const locations = await response.json();
                this.updateMapMarkers(locations, resetView);
                this.updateMetrics(locations);
            } catch (error) {
                console.error('Error fetching map data:', error);
            }
        },

        updateMapMarkers(locations, resetView) {
            this.markersGroup.clearLayers();
            
            if (!locations.length) return;
            
            const bounds = [];
            
            locations.forEach(loc => {
                if (loc.lat && loc.lng) {
                    const statusColors = {
                        'full': '#22c55e', // green
                        'partial': '#eab308', // yellow
                        'missing': '#ef4444', // red
                        'no_assignment': '#6b7280' // gray
                    };
                    
                    const color = statusColors[loc.status] || statusColors['no_assignment'];
                    
                    const markerHtml = `
                        <div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>
                    `;
                    
                    const icon = L.divIcon({
                        html: markerHtml,
                        className: '',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });

                    const marker = L.marker([loc.lat, loc.lng], { icon: icon });
                    
                    const popupContent = `
                        <div class="text-sm min-w-[200px]">
                            <h4 class="font-bold text-gray-900 border-b pb-1 mb-2">${loc.name}</h4>
                            <p class="text-gray-600 text-xs mb-2">${loc.address || 'Tanpa alamat'}</p>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Status:</span>
                                <span class="font-medium px-2 py-0.5 rounded-full text-xs text-white" style="background-color: ${color}">
                                    ${loc.status === 'full' ? 'Hadir Penuh' : (loc.status === 'partial' ? 'Hadir Sebagian' : (loc.status === 'missing' ? 'Tidak Hadir' : 'Tidak Ada Opr.'))}
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-gray-500">Hadir:</span>
                                <span class="font-medium text-gray-900">${loc.present} / ${loc.total > 0 ? loc.min : 0}</span>
                            </div>
                            <div class="mt-3 text-right">
                                <a href="/locations/${loc.id}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Lihat Detail &rarr;</a>
                            </div>
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                    this.markersGroup.addLayer(marker);
                    
                    bounds.push([loc.lat, loc.lng]);
                }
            });
            
            if (resetView && bounds.length > 0) {
                this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
            }
        },

        updateMetrics(locations) {
            let full = 0, partial = 0, missing = 0;
            
            locations.forEach(loc => {
                if (loc.total > 0) {
                    if (loc.status === 'full') full++;
                    else if (loc.status === 'partial') partial++;
                    else missing++;
                }
            });
            
            this.metrics = {
                total_locations: locations.length,
                full_attendance: full,
                partial_attendance: partial,
                missing_attendance: missing
            };
        }
    }));
});
</script>
@endpush
