@extends('layouts.admin')
@section('title', 'Detail Lokasi') @section('page-title', 'Detail Lokasi')

@php
    // Pull raw coordinates via PostGIS (location.coordinates is geometry, not on the model directly)
    $coords = \Illuminate\Support\Facades\DB::selectOne(
        'SELECT ST_Y(coordinates::geometry) AS lat, ST_X(coordinates::geometry) AS lng FROM locations WHERE id = ?',
        [$location->id]
    );
    $lat = $coords?->lat;
    $lng = $coords?->lng;
@endphp

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-4 mb-2">
        <a href="{{ route('locations.index') }}" class="text-gray-400 hover:text-white transition-colors">← Lokasi</a>
        <span class="text-gray-600">/</span>
        <span class="text-gray-300">{{ $location->name }}</span>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: details + actions --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-xl font-bold text-white">{{ $location->name }}</h1>
                        @if($location->address)
                            <p class="text-gray-400 text-sm mt-1">{{ $location->address }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2 items-end">
                        @if($location->is_active)
                            <x-badge color="green">Aktif</x-badge>
                        @else
                            <x-badge color="gray">Nonaktif</x-badge>
                        @endif
                        @if($location->coords_locked)
                            <x-badge color="orange">Koord. Terkunci</x-badge>
                        @endif
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm pt-4 border-t border-[var(--color-surface-600)]">
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Zona</dt>
                        <dd class="text-white mt-1">{{ $location->zone->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Operasi</dt>
                        <dd class="text-white mt-1">{{ $location->zone->operation->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Radius</dt>
                        <dd class="text-white mt-1">{{ $location->radius_meters }} m</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Min. Officer</dt>
                        <dd class="text-white mt-1">{{ $location->minimum_officer }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Latitude</dt>
                        <dd class="text-white mt-1 font-mono text-xs">{{ $lat ? number_format($lat, 7) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Longitude</dt>
                        <dd class="text-white mt-1 font-mono text-xs">{{ $lng ? number_format($lng, 7) : '—' }}</dd>
                    </div>
                    @if($location->timezone ?? false)
                        <div class="col-span-2">
                            <dt class="text-gray-500 text-xs uppercase tracking-wide">Timezone</dt>
                            <dd class="text-white mt-1">{{ $location->timezone }}</dd>
                        </div>
                    @endif
                </dl>

                @if($location->description)
                    <div class="mt-4 pt-4 border-t border-[var(--color-surface-600)]">
                        <dt class="text-gray-500 text-xs uppercase tracking-wide mb-1">Deskripsi</dt>
                        <dd class="text-gray-300 text-sm">{{ $location->description }}</dd>
                    </div>
                @endif

                <div class="flex gap-3 mt-6 pt-6 border-t border-[var(--color-surface-600)]">
                    <a href="{{ route('locations.edit', $location) }}" class="px-4 py-2 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm rounded-xl transition-colors">
                        Edit
                    </a>
                    <a href="{{ route('locations.index') }}" class="px-4 py-2 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm rounded-xl transition-colors">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- Right: map --}}
        <div class="lg:col-span-2">
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-1 overflow-hidden">
                <div class="rounded-xl overflow-hidden" style="height: 600px;">
                    @if($lat && $lng)
                        <div id="location-detail-map" style="height: 100%;"
                             data-lat="{{ $lat }}"
                             data-lng="{{ $lng }}"
                             data-radius="{{ $location->radius_meters }}"
                             data-name="{{ $location->name }}"></div>
                    @else
                        <div class="h-full flex items-center justify-center text-gray-500 text-sm">
                            Koordinat lokasi belum tersedia.
                        </div>
                    @endif
                </div>
                <p class="text-xs text-gray-500 text-center py-2">
                    Lingkaran biru menunjukkan radius geofence ({{ $location->radius_meters }} m).
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($lat && $lng)
<script>
document.addEventListener('alpine:init', () => {
    const el = document.getElementById('location-detail-map');
    if (!el || typeof L === 'undefined') return;

    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    const radius = parseInt(el.dataset.radius, 10);
    const name = el.dataset.name;

    const map = L.map('location-detail-map').setView([lat, lng], 17);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map);

    // Marker
    L.marker([lat, lng], {
        icon: L.divIcon({
            className: '',
            html: '<div style="width:14px;height:14px;border-radius:9999px;background:#6366f1;border:3px solid #fff;box-shadow:0 0 0 2px rgba(99,102,241,.4);"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
        }),
    }).addTo(map).bindPopup('<strong>' + name + '</strong>');

    // Geofence circle
    L.circle([lat, lng], {
        radius: radius,
        color: '#6366f1',
        fillColor: '#6366f1',
        fillOpacity: 0.12,
        weight: 2,
    }).addTo(map);
});
</script>
@endif
@endpush
