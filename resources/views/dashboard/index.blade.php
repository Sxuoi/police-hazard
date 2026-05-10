@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Beranda')

@section('content')
<div class="space-y-6">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card
            title="Total Lokasi"
            value="—"
            color="indigo"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>'
        />
        <x-card
            title="Hadir Penuh"
            value="—"
            color="green"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />
        <x-card
            title="Hadir Sebagian"
            value="—"
            color="yellow"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />
        <x-card
            title="Tidak Hadir"
            value="—"
            color="red"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />
    </div>

    {{-- Map Placeholder --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-1 overflow-hidden" style="min-height: 500px;">
        <div id="dashboard-map" class="w-full rounded-xl" style="height: 500px; background: var(--color-surface-700);"></div>
    </div>

    {{-- Attendance Table Placeholder --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Detail Kehadiran</h3>
        <p class="text-sm text-gray-500">Pilih operasi dan tanggal untuk melihat data kehadiran.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Leaflet map centered on Jakarta
        if (typeof L !== 'undefined') {
            const map = L.map('dashboard-map').setView([-6.2088, 106.8456], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);
        }
    });
</script>
@endpush
