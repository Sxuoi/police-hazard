@extends('layouts.admin')

@section('title', 'Detail Bypass')
@section('page-title', 'Detail Bypass')

@section('content')
<div class="space-y-6 max-w-5xl">
    {{-- Back link --}}
    <a href="{{ route('bypass-approvals.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar
    </a>

    {{-- Spoofing Advisory --}}
    @include('bypass-approvals._spoofing-panel', ['bypass' => $bypass])

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Officer Info --}}
        <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-5 space-y-4">
            <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Informasi Anggota</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Nama</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->officer?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">NRP</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->officer?->nrp ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Alasan Bypass</span>
                    <span class="text-white text-sm font-medium">{{ str_replace('_', ' ', $bypass->bypass_reason) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Status</span>
                    <span class="text-white text-sm font-medium">{{ ucfirst($bypass->status) }}</span>
                </div>
            </div>
        </div>

        {{-- Assignment Details --}}
        <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-5 space-y-4">
            <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Detail Penugasan</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Lokasi</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->assignment?->location?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Shift</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->assignment?->shift?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Tanggal</span>
                    <span class="text-white text-sm font-medium">
                        @if($bypass->assignment)
                            {{ $bypass->assignment->start_date->format('Y-m-d') }}
                            @if($bypass->assignment->end_date)
                                s/d {{ $bypass->assignment->end_date->format('Y-m-d') }}
                            @else
                                (Aktif)
                            @endif
                        @else
                            -
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Kedaluwarsa</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->expires_at?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Officer Note --}}
    <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-5 space-y-2">
        <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Catatan Anggota</h3>
        <p class="text-gray-200 text-sm">{{ $bypass->officer_note ?? '-' }}</p>
    </div>

    {{-- Comparison Map Placeholder --}}
    <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-5 space-y-4">
        <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Peta Perbandingan</h3>
        <div id="comparison-map" class="w-full h-64 rounded-lg bg-[var(--color-surface-700)] flex items-center justify-center text-gray-500 text-sm">
            {{-- Leaflet map will be mounted here --}}
            <span>Peta perbandingan lokasi</span>
        </div>
        @if($bypass->officer_latitude && $bypass->officer_longitude)
            <p class="text-xs text-gray-400">
                Koordinat anggota: {{ $bypass->officer_latitude }}, {{ $bypass->officer_longitude }}
            </p>
        @endif
    </div>

    {{-- Photo --}}
    @if($bypass->officer_photo_path)
    <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-5 space-y-4">
        <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Foto Anggota</h3>
        <div class="w-full max-w-sm rounded-lg overflow-hidden bg-[var(--color-surface-700)]">
            <img src="{{ Storage::disk(config('policehazard.photo.private_disk', 'local'))->temporaryUrl($bypass->officer_photo_path, now()->addMinutes(15)) }}" alt="Foto bypass" class="w-full h-auto object-cover" loading="lazy">
        </div>
    </div>
    @endif

    {{-- Decision Form (only for pending) --}}
    @if($bypass->status === 'pending')
        @include('bypass-approvals._decision-form', ['bypass' => $bypass])
    @else
        {{-- Show reviewer info for decided bypasses --}}
        <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-5 space-y-3">
            <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wide">Keputusan</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Reviewer</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->reviewer?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Tanggal Review</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 text-sm">Catatan</span>
                    <span class="text-white text-sm font-medium">{{ $bypass->reviewer_note ?? '-' }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
