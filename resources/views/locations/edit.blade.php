@extends('layouts.admin')
@section('title', 'Edit Lokasi') @section('page-title', 'Edit Lokasi: ' . $location->name)

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Form --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6"
         x-data="locationForm()">
        <form method="POST" action="{{ route('locations.update', $location->id) }}">
            @csrf
            @method('PUT')
            @if($errors->any())<div class="mb-6"><x-alert type="error">{{ $errors->first() }}</x-alert></div>@endif

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-2">Operasi & Zona</label>
                <div class="px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] rounded-xl text-gray-400">
                    {{ $location->zone->operation->name }} — {{ $location->zone->name }}
                </div>
            </div>

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Lokasi <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $location->name) }}" required maxlength="200"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="mb-5">
                <label for="address" class="block text-sm font-medium text-gray-300 mb-2">Alamat</label>
                <input type="text" id="address" name="address" value="{{ old('address', $location->address) }}" maxlength="500"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            {{-- Coordinates (Only editable if not locked) --}}
            @if(!$location->coords_locked)
                <input type="hidden" name="latitude"  id="lat_input"  x-model="lat" :value="lat" />
                <input type="hidden" name="longitude" id="lng_input"  x-model="lng" :value="lng" />

                <div class="mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span class="text-sm font-medium text-gray-300">Koordinat (klik peta untuk pin)</span>
                    <span class="text-red-400 text-sm">*</span>
                </div>
            @else
                <div class="mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-sm font-medium text-yellow-500">Koordinat Dikunci (Sudah ada absensi)</span>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Latitude</label>
                    <input type="text" readonly x-model="lat"
                           placeholder="—"
                           class="w-full px-3 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] rounded-lg text-sm text-[var(--color-accent)] font-mono opacity-80" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Longitude</label>
                    <input type="text" readonly x-model="lng"
                           placeholder="—"
                           class="w-full px-3 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] rounded-lg text-sm text-[var(--color-accent)] font-mono opacity-80" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="radius_meters" class="block text-sm font-medium text-gray-300 mb-2">Radius Geofence (m) <span class="text-red-400">*</span></label>
                    <input type="number" id="radius_meters" name="radius_meters" value="{{ old('radius_meters', $location->radius_meters) }}"
                           min="10" max="500" required x-model="radius" @input="updateCircle()"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="minimum_officer" class="block text-sm font-medium text-gray-300 mb-2">Min. Anggota <span class="text-red-400">*</span></label>
                    <input type="number" id="minimum_officer" name="minimum_officer" value="{{ old('minimum_officer', $location->minimum_officer) }}"
                           min="1" required
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('locations.show', $location->id) }}" class="flex-1 px-4 py-3 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm font-medium rounded-xl text-center transition-colors">Batal</a>
                <button type="submit" :disabled="!lat || !lng"
                        class="flex-1 px-4 py-3 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- Map --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-1 overflow-hidden">
        <div class="rounded-xl overflow-hidden" style="height: 600px;">
            <div id="location-pin-map" style="height: 100%;"></div>
        </div>
        <p class="text-xs text-gray-500 text-center py-2">
            @if($location->coords_locked)
                Peta hanya dapat dilihat (koordinat terkunci).
            @else
                Klik pada peta untuk mengubah koordinat lokasi.
            @endif
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function locationForm() {
    return {
        lat: '{{ old("latitude", $location->lat) }}',
        lng: '{{ old("longitude", $location->lng) }}',
        radius: {{ old('radius_meters', $location->radius_meters) }},
        isLocked: {{ $location->coords_locked ? 'true' : 'false' }},
        map: null,
        marker: null,
        circle: null,

        updateCircle() {
            if (this.circle && this.lat && this.lng) {
                this.circle.setRadius(parseInt(this.radius));
            }
        },

        init() {
            this.$nextTick(() => {
                let initLat = parseFloat(this.lat) || -7.2658;
                let initLng = parseFloat(this.lng) || 112.7500;

                this.map = L.map('location-pin-map').setView([initLat, initLng], 17);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
                }).addTo(this.map);

                if (this.lat && this.lng) {
                    this.placeMarker(parseFloat(this.lat), parseFloat(this.lng));
                }

                if (!this.isLocked) {
                    this.map.on('click', (e) => {
                        this.lat = e.latlng.lat.toFixed(7);
                        this.lng = e.latlng.lng.toFixed(7);
                        this.placeMarker(e.latlng.lat, e.latlng.lng);
                    });
                }
            });
        },

        placeMarker(lat, lng) {
            if (this.marker) this.map.removeLayer(this.marker);
            if (this.circle) this.map.removeLayer(this.circle);

            this.marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<div style="width:14px;height:14px;background:#6366f1;border:3px solid white;border-radius:50%;box-shadow:0 0 0 3px rgba(99,102,241,0.3)"></div>',
                    iconSize: [14, 14], iconAnchor: [7, 7],
                })
            }).addTo(this.map);

            this.circle = L.circle([lat, lng], {
                radius: parseInt(this.radius),
                color: '#6366f1', fillColor: '#6366f1', fillOpacity: 0.12, weight: 2,
            }).addTo(this.map);
        }
    };
}
</script>
@endpush
