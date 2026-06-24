@extends('layouts.admin')

@section('title', 'Peta Panas Kehadiran')
@section('page-title', 'Peta Panas Kehadiran (Heatmap)')

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
        /* Custom Legend Control */
        .legend-control {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid var(--color-surface-600);
            padding: 10px;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            line-height: 18px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .legend-color {
            width: 18px;
            height: 18px;
            float: left;
            margin-right: 8px;
            opacity: 0.7;
            border-radius: 4px;
        }
    </style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Filter Bar --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form id="heatmap-filters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" 
                       value="{{ now()->subDays(30)->format('Y-m-d') }}"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-all duration-200" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" 
                       value="{{ now()->format('Y-m-d') }}"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-all duration-200" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Level Satuan Kerja</label>
                <select id="saker_level" name="saker_level" 
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-all duration-200">
                    <option value="">Semua Level</option>
                    <option value="MABES">MABES</option>
                    <option value="POLDA">POLDA</option>
                    <option value="POLRESTABES">POLRESTABES</option>
                    <option value="POLSEK">POLSEK</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Jenis Operasi</label>
                <select id="operation_type" name="operation_type" 
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-all duration-200">
                    <option value="">Semua Jenis</option>
                    <option value="PH">PH (Police Hazard)</option>
                    <option value="PATROL">PATROL</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Map and Layer Controls Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        {{-- Left: Layer Controls --}}
        <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6 space-y-6">
            <div>
                <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4 border-b border-[var(--color-surface-600)] pb-2">Kontrol Layer Peta</h3>
                <div class="space-y-4">
                    {{-- Layer 1: Coverage --}}
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" id="layer-coverage" checked
                               class="w-4 h-4 rounded mt-1 text-[var(--color-accent)] focus:ring-[var(--color-accent)] bg-[var(--color-surface-700)] border-[var(--color-surface-500)]">
                        <div>
                            <span class="text-sm font-medium text-white group-hover:text-[var(--color-accent)] transition-colors">Cakupan Kehadiran</span>
                            <p class="text-xs text-gray-400 mt-0.5">Shading wilayah zona kerja (Choropleth). Semakin gelap/merah berarti tingkat kehadiran semakin rendah.</p>
                        </div>
                    </label>

                    {{-- Layer 2: Absences --}}
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" id="layer-absences" checked
                               class="w-4 h-4 rounded mt-1 text-[var(--color-accent)] focus:ring-[var(--color-accent)] bg-[var(--color-surface-700)] border-[var(--color-surface-500)]">
                        <div>
                            <span class="text-sm font-medium text-white group-hover:text-[var(--color-accent)] transition-colors">Kluster Keabsenan</span>
                            <p class="text-xs text-gray-400 mt-0.5">Kepadatan wilayah (Heatmap) yang mendeteksi titik patroli yang sering tidak dihadiri oleh petugas.</p>
                        </div>
                    </label>

                    {{-- Layer 3: Spoofing --}}
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" id="layer-spoofing" checked
                               class="w-4 h-4 rounded mt-1 text-[var(--color-accent)] focus:ring-[var(--color-accent)] bg-[var(--color-surface-700)] border-[var(--color-surface-500)]">
                        <div>
                            <span class="text-sm font-medium text-white group-hover:text-[var(--color-accent)] transition-colors">Insiden Spoofing</span>
                            <p class="text-xs text-gray-400 mt-0.5">Penanda titik merah yang menunjukkan laporan absensi yang terindikasi manipulasi GPS / Fake GPS.</p>
                        </div>
                    </label>

                    {{-- Layer 4: Density --}}
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" id="layer-density" checked
                               class="w-4 h-4 rounded mt-1 text-[var(--color-accent)] focus:ring-[var(--color-accent)] bg-[var(--color-surface-700)] border-[var(--color-surface-500)]">
                        <div>
                            <span class="text-sm font-medium text-white group-hover:text-[var(--color-accent)] transition-colors">Kepadatan Personel</span>
                            <p class="text-xs text-gray-400 mt-0.5">Dot density acak di sekitar lokasi tugas yang memvisualisasikan volume penugasan personel.</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-[var(--color-surface-600)] space-y-3">
                <button type="button" id="btn-refresh"
                        class="w-full py-2.5 px-4 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-medium rounded-xl transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                    <svg id="refresh-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>Segarkan Peta</span>
                </button>
            </div>
        </div>

        {{-- Right: Map Area --}}
        <div class="lg:col-span-3 h-[calc(100vh-20rem)] min-h-[500px] relative rounded-2xl overflow-hidden border border-[var(--color-surface-600)] shadow-lg z-0">
            <div id="heatmap-map" class="absolute inset-0"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for window.L to be available (defined asynchronously by Vite app.js module)
        function checkLeaflet(callback) {
            if (window.L) {
                callback();
            } else {
                setTimeout(() => checkLeaflet(callback), 50);
            }
        }

        checkLeaflet(() => {
            // Dynamically load local leaflet-heat.js which will attach to the globally defined window.L
            const script = document.createElement('script');
            script.src = "{{ asset('js/leaflet-heat.js') }}";
            script.onload = () => {
                initializeMapAndHeatmap();
            };
            document.head.appendChild(script);
        });

        function initializeMapAndHeatmap() {
            // Semarang Default Center
            const defaultLat = -7.0051453;
            const defaultLng = 110.4381254;

            // Initialize Map using window.L
            const map = L.map('heatmap-map').setView([defaultLat, defaultLng], 12);

            // Dark theme map tiles
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a>'
            }).addTo(map);

            // Map Legends
            const legend = L.control({ position: 'bottomright' });
            legend.onAdd = function() {
                const div = L.DomUtil.create('div', 'legend-control');
                div.innerHTML = `
                    <div class="font-semibold mb-2">Cakupan Kehadiran</div>
                    <div><span class="legend-color" style="background:#ef4444"></span> &lt; 50% (Rendah)</div>
                    <div><span class="legend-color" style="background:#f59e0b"></span> 50% - 90% (Sedang)</div>
                    <div><span class="legend-color" style="background:#10b981"></span> &gt; 90% (Sangat Baik)</div>
                `;
                return div;
            };
            legend.addTo(map);

            // Define Layer Groups
            const coverageGroup = L.layerGroup().addTo(map);
            const absencesGroup = L.layerGroup().addTo(map);
            const spoofingGroup = L.layerGroup().addTo(map);
            const densityGroup = L.layerGroup().addTo(map);

            // State variables
            let apiData = null;

            // Fetch Heatmap Data from API
            async function fetchHeatmapData() {
                const spinner = document.getElementById('refresh-spinner');
                const btn = document.getElementById('btn-refresh');

                spinner.classList.remove('hidden');
                btn.disabled = true;

                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const sakerLevel = document.getElementById('saker_level').value;
                const operationType = document.getElementById('operation_type').value;

                try {
                    const params = new URLSearchParams({
                        start_date: startDate,
                        end_date: endDate,
                    });
                    if (sakerLevel) params.append('saker_level', sakerLevel);
                    if (operationType) params.append('operation_type', operationType);

                    const response = await fetch(`/api/v1/admin/heatmap/data?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!response.ok) {
                        const errorJson = await response.json();
                        alert(errorJson.message || 'Gagal memuat data peta panas.');
                        return;
                    }

                    apiData = await response.json();
                } catch (error) {
                    console.error('Heatmap fetch error:', error);
                    alert('Terjadi kesalahan koneksi saat memuat data peta panas.');
                    return;
                } finally {
                    spinner.classList.add('hidden');
                    btn.disabled = false;
                }

                try {
                    renderLayers();
                } catch (error) {
                    console.error('Heatmap render error:', error);
                    alert('Terjadi kesalahan saat menggambar data peta panas: ' + error.message);
                }
            }

            // Render Active Layers
            function renderLayers() {
                if (!apiData) return;

                // Clear previous layer data
                coverageGroup.clearLayers();
                absencesGroup.clearLayers();
                spoofingGroup.clearLayers();
                densityGroup.clearLayers();

                // 1. Render Coverage (Choropleth polygons)
                if (document.getElementById('layer-coverage').checked && apiData.coverage) {
                    L.geoJSON(apiData.coverage, {
                        style: function(feature) {
                            const rate = feature.properties.attendance_rate;
                            let color = '#ef4444'; // Red for low
                            if (rate >= 90) {
                                color = '#10b981'; // Green for high
                            } else if (rate >= 50) {
                                color = '#f59e0b'; // Yellow for medium
                            }
                            return {
                                color: color,
                                fillColor: color,
                                fillOpacity: 0.35,
                                weight: 2,
                                dashArray: '3',
                            };
                        },
                        onEachFeature: function(feature, layer) {
                            const props = feature.properties;
                            const popupContent = `
                                <div class="p-2 min-w-[220px]">
                                    <div class="font-bold text-sm text-[var(--color-accent)] mb-1">${props.zone_name}</div>
                                    <div class="text-xs text-gray-400 mb-2">${props.saker_name}</div>
                                    <div class="text-xs border-t border-gray-600 pt-2 space-y-1">
                                        <div><span class="text-gray-400">Tingkat Kehadiran:</span> <span class="font-bold text-white">${props.attendance_rate}%</span></div>
                                        <div><span class="text-gray-400">Hari Hadir:</span> <span class="text-white">${props.attended_days} hari</span></div>
                                        <div><span class="text-gray-400">Total Hari Kerja:</span> <span class="text-white">${props.total_days} hari</span></div>
                                    </div>
                                </div>
                            `;
                            layer.bindPopup(popupContent);
                        }
                    }).addTo(coverageGroup);
                }

                // 2. Render Absences Heatmap
                if (document.getElementById('layer-absences').checked && apiData.absences && apiData.absences.length > 0) {
                    const heatPoints = apiData.absences.map(item => [item[0], item[1], item[2]]);
                    L.heatLayer(heatPoints, {
                        radius: 25,
                        blur: 15,
                        maxZoom: 17,
                        gradient: {0.4: 'blue', 0.65: 'lime', 1: 'red'}
                    }).addTo(absencesGroup);
                }

                // 3. Render Spoofing (Red point markers)
                if (document.getElementById('layer-spoofing').checked && apiData.spoofing) {
                    apiData.spoofing.forEach(item => {
                        const marker = L.circleMarker([item.lat, item.lng], {
                            radius: 8,
                            color: '#ef4444',
                            fillColor: '#ef4444',
                            fillOpacity: 0.9,
                            weight: 2,
                        }).addTo(spoofingGroup);

                        const signals = Object.keys(item.spoofing_signals).join(', ') || '-';
                        const time = new Date(item.checked_in_at).toLocaleString('id-ID', { hour12: false });

                        const popupContent = `
                            <div class="p-2 min-w-[240px]">
                                <div class="font-bold text-sm text-red-500 mb-1">🚨 Terindikasi GPS Spoofing</div>
                                <div class="text-xs text-white mb-2 font-semibold">${item.location_name}</div>
                                <div class="text-xs border-t border-gray-600 pt-2 space-y-1.5">
                                    <div><span class="text-gray-400">Petugas:</span> <span class="text-white">${item.officer_name} (${item.officer_nrp})</span></div>
                                    <div><span class="text-gray-400">Score Spoofing:</span> <span class="text-red-400 font-bold">${item.spoofing_score}</span></div>
                                    <div><span class="text-gray-400">Sinyal Spoofing:</span> <span class="text-yellow-400 font-mono text-[10px]">${signals}</span></div>
                                    <div><span class="text-gray-400">Waktu Absensi:</span> <span class="text-white">${time}</span></div>
                                </div>
                            </div>
                        `;
                        marker.bindPopup(popupContent);
                    });
                }

                // 4. Render Density (Dot density)
                if (document.getElementById('layer-density').checked && apiData.density) {
                    apiData.density.forEach(item => {
                        const count = item.assignment_count;
                        if (count === 0) return;

                        // Generate random offset points in a 15-meter radius around the location
                        for (let i = 0; i < count; i++) {
                            // Roughly 15-meter offset in degrees (~0.00013)
                            const offsetLat = (Math.random() - 0.5) * 0.00026;
                            const offsetLng = (Math.random() - 0.5) * 0.00026;

                            const dot = L.circleMarker([item.lat + offsetLat, item.lng + offsetLng], {
                                radius: 3.5,
                                color: '#6366f1',
                                fillColor: '#818cf8',
                                fillOpacity: 0.6,
                                weight: 0,
                            }).addTo(densityGroup);

                            const popupContent = `
                                <div class="p-1">
                                    <div class="font-bold text-xs text-white">${item.location_name}</div>
                                    <div class="text-[10px] text-gray-400 mt-1">Titik Kepadatan Penugasan</div>
                                </div>
                            `;
                            dot.bindPopup(popupContent);
                        }
                    });
                }
            }

            // Event Listeners for Filters
            document.getElementById('start_date').addEventListener('change', fetchHeatmapData);
            document.getElementById('end_date').addEventListener('change', fetchHeatmapData);
            document.getElementById('saker_level').addEventListener('change', fetchHeatmapData);
            document.getElementById('operation_type').addEventListener('change', fetchHeatmapData);
            document.getElementById('btn-refresh').addEventListener('click', fetchHeatmapData);

            // Event Listeners for Layer Checkboxes
            document.getElementById('layer-coverage').addEventListener('change', renderLayers);
            document.getElementById('layer-absences').addEventListener('change', renderLayers);
            document.getElementById('layer-spoofing').addEventListener('change', renderLayers);
            document.getElementById('layer-density').addEventListener('change', renderLayers);

            // Initial Fetch
            fetchHeatmapData();
        }
    });
</script>
@endpush
