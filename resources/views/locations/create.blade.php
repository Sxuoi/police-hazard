@extends('layouts.admin')
@section('title', 'Tambah Lokasi') @section('page-title', 'Tambah Lokasi Baru')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Form --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6"
         x-data="locationForm()">
        <form method="POST" action="{{ route('locations.store') }}">
            @csrf
            @if($errors->any())<div class="mb-6"><x-alert type="error">{{ $errors->first() }}</x-alert></div>@endif

            {{-- Operation → Zone cascade --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-2">Operasi <span class="text-red-400">*</span></label>
                <select @change="fetchZones($event.target.value)"
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
                    <option value="">Pilih operasi...</option>
                    @foreach($operations as $op)
                        <option value="{{ $op->id }}">{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label for="zone_id" class="block text-sm font-medium text-gray-300 mb-2">Zona <span class="text-red-400">*</span></label>
                <select id="zone_id" name="zone_id" required x-model="selectedZone"
                        :disabled="zones.length === 0"
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] disabled:opacity-50">
                    <option value="">Pilih zona...</option>
                    <template x-for="z in zones" :key="z.id">
                        <option :value="z.id" x-text="z.name"></option>
                    </template>
                </select>
            </div>

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Lokasi <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="200"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="mb-5">
                <label for="address" class="block text-sm font-medium text-gray-300 mb-2">
                    Alamat
                    <span x-show="geocoding" class="ml-1 text-xs text-[var(--color-accent)]">
                        (mencari alamat…)
                    </span>
                </label>
                <input type="text" id="address" name="address" value="{{ old('address') }}" maxlength="500"
                       x-model="address" @input="addressTouched = true"
                       placeholder="Klik peta untuk mengisi otomatis"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            {{-- Hidden coordinate inputs populated by map --}}
            <input type="hidden" name="latitude"  id="lat_input"  x-model="lat" :value="lat" />
            <input type="hidden" name="longitude" id="lng_input"  x-model="lng" :value="lng" />

            <div class="mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                <span class="text-sm font-medium text-gray-300">Koordinat (klik peta untuk pin)</span>
                <span class="text-red-400 text-sm">*</span>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Latitude</label>
                    <input type="text" readonly x-model="lat"
                           placeholder="—"
                           class="w-full px-3 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] rounded-lg text-sm text-[var(--color-accent)] font-mono" />
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Longitude</label>
                    <input type="text" readonly x-model="lng"
                           placeholder="—"
                           class="w-full px-3 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] rounded-lg text-sm text-[var(--color-accent)] font-mono" />
                </div>
            </div>

            <div class="mb-5" x-data="padalSearch()" @click.outside="open = false">
                <label class="block text-sm font-medium text-gray-300 mb-2">Perwira Pengendali (PADAL)</label>
                <input type="hidden" name="padal_id" :value="selectedId" />
                <div class="relative">
                    <input type="text" x-model="query" @focus="open = true" @input="open = true"
                           :placeholder="selectedName || '— Cari nama atau NRP —'"
                           :class="selectedId ? 'text-white' : 'text-gray-500'"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                    <button type="button" x-show="selectedId" @click.prevent="clear()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div x-show="open && filtered().length > 0" x-transition
                         class="absolute z-50 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl shadow-lg">
                        <template x-for="o in filtered()" :key="o.id">
                            <div @click="select(o)" class="px-4 py-2.5 hover:bg-[var(--color-surface-600)] cursor-pointer transition-colors">
                                <span class="text-white text-sm" x-text="o.name"></span>
                                <span class="text-gray-400 text-xs ml-2" x-text="o.nrp"></span>
                            </div>
                        </template>
                    </div>
                    <div x-show="open && query.length > 0 && filtered().length === 0" x-transition
                         class="absolute z-50 left-0 right-0 mt-1 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl shadow-lg px-4 py-3 text-gray-500 text-sm">
                        Tidak ditemukan
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="radius_meters" class="block text-sm font-medium text-gray-300 mb-2">Radius Geofence (m) <span class="text-red-400">*</span></label>
                    <input type="number" id="radius_meters" name="radius_meters" value="{{ old('radius_meters', 100) }}"
                           min="10" max="500" required x-model="radius" @input="updateCircle()"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="minimum_officer" class="block text-sm font-medium text-gray-300 mb-2">Min. Anggota <span class="text-red-400">*</span></label>
                    <input type="number" id="minimum_officer" name="minimum_officer" value="{{ old('minimum_officer', 1) }}"
                           min="1" required
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('locations.index') }}" class="flex-1 px-4 py-3 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm font-medium rounded-xl text-center transition-colors">Batal</a>
                <button type="submit" :disabled="!lat || !lng"
                        class="flex-1 px-4 py-3 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    Simpan Lokasi
                </button>
            </div>
        </form>
    </div>

    {{-- Map --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-1 overflow-hidden" x-ref="mapPanel">
        <div class="rounded-xl overflow-hidden" style="height: 600px;">
            <div id="location-pin-map" style="height: 100%;"></div>
        </div>
        <p class="text-xs text-gray-500 text-center py-2">Klik pada peta untuk menentukan koordinat lokasi.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function locationForm() {
    return {
        lat: '{{ old("latitude", "") }}',
        lng: '{{ old("longitude", "") }}',
        radius: {{ old('radius_meters', 100) }},
        zones: [],
        selectedZone: '{{ old("zone_id", "") }}',
        address: @json(old('address', '')),
        addressTouched: @json(! empty(old('address'))),
        geocoding: false,
        geocodeAbort: null,
        geocodeTimer: null,
        map: null,
        marker: null,
        circle: null,

        fetchZones(operationId) {
            if (!operationId) { this.zones = []; return; }
            fetch(`{{ route('ajax.zones-by-operation') }}?operation_id=${operationId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(r => r.json()).then(data => { this.zones = data; });
        },

        updateCircle() {
            if (this.circle && this.lat && this.lng) {
                this.circle.setRadius(parseInt(this.radius));
            }
        },

        init() {
            this.$nextTick(() => {
                this.map = L.map('location-pin-map').setView([-7.2658, 112.7500], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
                }).addTo(this.map);

                // Place existing marker if editing
                if (this.lat && this.lng) {
                    this.placeMarker(parseFloat(this.lat), parseFloat(this.lng));
                }

                this.map.on('click', (e) => {
                    this.lat = e.latlng.lat.toFixed(7);
                    this.lng = e.latlng.lng.toFixed(7);
                    this.placeMarker(e.latlng.lat, e.latlng.lng);
                    this.reverseGeocode(e.latlng.lat, e.latlng.lng);
                });
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

            this.map.setView([lat, lng], 17);
        },

        // Reverse geocode via OpenStreetMap Nominatim. Free, no API key.
        // Skips when the user has already typed an address. Debounced + cancellable
        // so rapid clicks don't queue stale requests or hammer the public service.
        reverseGeocode(lat, lng) {
            if (this.addressTouched && this.address.trim().length > 0) return;
            if (this.geocodeAbort) this.geocodeAbort.abort();
            if (this.geocodeTimer) clearTimeout(this.geocodeTimer);

            this.geocodeTimer = setTimeout(() => {
                this.geocoding = true;
                this.geocodeAbort = new AbortController();

                const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=id`;
                fetch(url, {
                    signal: this.geocodeAbort.signal,
                    headers: { 'Accept': 'application/json' },
                })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (data && data.display_name) {
                            this.address = data.display_name;
                            // Keep the input synced so a subsequent submit picks it up
                            const input = document.getElementById('address');
                            if (input) input.value = data.display_name;
                        }
                    })
                    .catch(() => { /* aborted or network error — silent */ })
                    .finally(() => {
                        this.geocoding = false;
                        this.geocodeAbort = null;
                    });
            }, 350);
        }
    };
}

function padalSearch() {
    const officers = @json($officers->map(fn($o) => ['id' => $o->id, 'name' => $o->name, 'nrp' => $o->nrp]));
    const oldId = @json(old('padal_id', ''));
    const preset = oldId ? officers.find(o => o.id === oldId) : null;

    return {
        officers,
        query: '',
        open: false,
        selectedId: preset?.id || '',
        selectedName: preset ? `${preset.name} (${preset.nrp})` : '',

        filtered() {
            const q = this.query.toLowerCase().trim();
            if (!q) return this.officers.slice(0, 20);
            return this.officers.filter(o =>
                o.name.toLowerCase().includes(q) || o.nrp.toLowerCase().includes(q)
            );
        },

        select(o) {
            this.selectedId = o.id;
            this.selectedName = `${o.name} (${o.nrp})`;
            this.query = '';
            this.open = false;
        },

        clear() {
            this.selectedId = '';
            this.selectedName = '';
            this.query = '';
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    // Alpine handles init via x-init — nothing extra needed
});
</script>
@endpush
