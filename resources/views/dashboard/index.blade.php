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

    /* Filter select/input styling */
    .filter-select, .filter-input {
        appearance: none;
        padding: 0.5rem 0.75rem;
        background: var(--color-surface-700);
        border: 1px solid var(--color-surface-500);
        border-radius: 0.5rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: white;
        transition: border-color 0.15s, box-shadow 0.15s;
        width: 100%;
    }
    .filter-select:focus, .filter-input:focus {
        outline: none;
        border-color: var(--color-accent);
        box-shadow: 0 0 0 2px rgba(var(--color-accent-rgb, 99,102,241), 0.25);
    }
    .filter-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.25rem;
        padding-right: 2.25rem;
    }
    .filter-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        color: #9ca3af;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>
@endpush

@section('content')
<div x-data="dashboardData()" class="space-y-6">

    {{-- Filter Bar --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        {{-- Filter Header --}}
        <div class="flex justify-between items-center p-4 cursor-pointer" @click="filtersOpen = !filtersOpen">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                    <template x-if="activeFilterCount > 0">
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-[var(--color-accent)] text-white text-xs font-bold" x-text="activeFilterCount"></span>
                    </template>
                </div>
                <span class="text-sm text-gray-500">|</span>
                <div class="text-sm text-gray-400">
                    Data untuk tanggal <strong class="text-white" x-text="date"></strong>
                    <span x-show="isPolling" class="ml-2 inline-flex items-center gap-1 text-[var(--color-accent)] animate-pulse">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Live
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button x-show="activeFilterCount > 0" @click.stop="resetFilters()" class="text-xs text-red-400 hover:text-red-300 px-2 py-1 rounded-lg hover:bg-red-500/10 transition-colors cursor-pointer">
                    Reset
                </button>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="filtersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>

        {{-- Collapsible Filter Panel --}}
        <div x-show="filtersOpen" x-collapse>
            <div class="px-4 pb-4 pt-0 border-t border-[var(--color-surface-600)]">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 pt-4">
                    {{-- Tanggal --}}
                    <div>
                        <label class="filter-label" for="filter-date">Tanggal</label>
                        <input type="date" id="filter-date" x-model="date" class="filter-input">
                    </div>

                    {{-- Operasi --}}
                    <div>
                        <label class="filter-label" for="filter-operation">Operasi</label>
                        <select id="filter-operation" x-model="operationId" @change="onOperationChange()" class="filter-select">
                            <option value="">Semua Operasi</option>
                            @foreach($operations as $op)
                                <option value="{{ $op->id }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Zona (cascading) --}}
                    <div>
                        <label class="filter-label" for="filter-zone">Zona</label>
                        <select id="filter-zone" x-model="zoneId" class="filter-select" :disabled="zonesLoading">
                            <option value="">Semua Zona</option>
                            <template x-for="zone in zones" :key="zone.id">
                                <option :value="zone.id" x-text="zone.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="filter-label" for="filter-status">Status</label>
                        <select id="filter-status" x-model="statusFilter" class="filter-select">
                            <option value="">Semua Status</option>
                            <option value="full">Hadir Penuh</option>
                            <option value="partial">Hadir Sebagian</option>
                            <option value="missing">Tidak Hadir</option>
                            <option value="no_assignment">Tidak Ada Operasi</option>
                        </select>
                    </div>

                    {{-- Anggota --}}
                    <div>
                        <label class="filter-label" for="filter-officer">Anggota</label>
                        <div class="relative">
                            <input type="text" id="filter-officer" x-model="officerSearch" @keydown.enter="fetchData()" placeholder="Cari nama / NRP..." class="filter-input" style="padding-right: 2rem;">
                            <svg x-show="!officerSearch" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <button x-show="officerSearch" @click="officerSearch = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-2 mt-4 pt-3 border-t border-[var(--color-surface-600)]">
                    <button @click="resetFilters()" x-show="activeFilterCount > 0" class="px-4 py-2 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-[var(--color-surface-600)] transition-colors cursor-pointer">
                        Reset
                    </button>
                    <button @click="fetchData()" class="inline-flex items-center gap-2 px-5 py-2 bg-[var(--color-accent)] hover:bg-[var(--color-accent)]/80 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </div>
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
        operationId: '',
        zoneId: '',
        statusFilter: '',
        officerSearch: '',
        zones: [],
        zonesLoading: false,
        filtersOpen: false,
        metrics: {
            total_locations: {{ $metrics['total_locations'] ?? 0 }},
            full_attendance: {{ $metrics['full_attendance'] ?? 0 }},
            partial_attendance: {{ $metrics['partial_attendance'] ?? 0 }},
            missing_attendance: {{ $metrics['missing_attendance'] ?? 0 }}
        },
        map: null,
        markersGroup: null,
        allLocations: [],      // full dataset from API
        isPolling: true,
        pollInterval: null,
        
        get activeFilterCount() {
            let count = 0;
            if (this.operationId) count++;
            if (this.zoneId) count++;
            if (this.statusFilter) count++;
            if (this.officerSearch) count++;
            return count;
        },

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
                    return new L.DivIcon({ html: '<div><span>' + childCount + '</span></div>', className: 'marker-cluster marker-cluster-full', iconSize: new L.Point(40, 40) });
                }
            });
            
            this.map.addLayer(this.markersGroup);
        },

        resetFilters() {
            this.operationId = '';
            this.zoneId = '';
            this.statusFilter = '';
            this.officerSearch = '';
            this.zones = [];
            this.date = new Date().toISOString().split('T')[0];
            this.fetchData();
        },

        async onOperationChange() {
            this.zoneId = '';
            this.zones = [];

            if (this.operationId) {
                this.zonesLoading = true;
                try {
                    const response = await fetch(`/ajax/zones-by-operation?operation_id=${this.operationId}`);
                    if (response.ok) {
                        this.zones = await response.json();
                    }
                } catch (error) {
                    console.error('Error fetching zones:', error);
                } finally {
                    this.zonesLoading = false;
                }
            }
        },

        buildQueryString() {
            const params = new URLSearchParams();
            params.set('date', this.date);
            if (this.operationId) params.set('operation_id', this.operationId);
            if (this.zoneId) params.set('zone_id', this.zoneId);
            if (this.officerSearch) params.set('officer', this.officerSearch);
            return params.toString();
        },

        async fetchData(resetView = true) {
            try {
                const qs = this.buildQueryString();
                const response = await fetch(`/dashboard/map-data?${qs}`);
                if (!response.ok) throw new Error('Network response was not ok');
                
                this.allLocations = await response.json();
                this.applyStatusFilter(resetView);
            } catch (error) {
                console.error('Error fetching map data:', error);
            }
        },

        applyStatusFilter(resetView = true) {
            let filtered = this.allLocations;
            if (this.statusFilter) {
                filtered = this.allLocations.filter(loc => loc.status === this.statusFilter);
            }
            this.updateMapMarkers(filtered, resetView);
            this.updateMetrics(filtered);
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

