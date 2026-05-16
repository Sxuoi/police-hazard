@extends('officer.layout')

@section('title', 'Detail Kehadiran')

@section('content')
<div class="p-4 space-y-4" x-data="officerHistoryShow" x-init="fetchDetail()">
    {{-- Back Button --}}
    <a href="/officer/history" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-white transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-[var(--color-accent)]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
    </template>

    {{-- Detail --}}
    <template x-if="!loading && record">
        <div class="space-y-4">
            {{-- Info Card --}}
            <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-4 space-y-3">
                <h2 class="text-lg font-semibold text-white" x-text="record.location_name"></h2>
                <div class="space-y-2 text-sm text-gray-400">
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="record.checked_in_at_formatted"></span>
                    </p>
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="record.distance_from_point ? record.distance_from_point + ' m dari lokasi' : 'Bypass'"></span>
                    </p>
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="record.verification_method === 'gps_photo' ? 'Verifikasi GPS + Foto' : 'Verifikasi Bypass'"></span>
                    </p>
                </div>
            </div>

            {{-- Photo --}}
            <template x-if="record.photo_url">
                <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] overflow-hidden">
                    <div class="relative">
                        <img
                            :src="record.photo_url"
                            alt="Foto check-in"
                            class="w-full aspect-[3/4] object-cover cursor-pointer"
                            @click="lightboxOpen = true"
                        >
                        <div class="absolute bottom-2 right-2 px-2 py-1 rounded bg-black/60 text-xs text-white">
                            Tap untuk memperbesar
                        </div>
                    </div>
                </div>
            </template>

            {{-- Photo Lightbox --}}
            <template x-if="lightboxOpen">
                <div
                    class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                    @click="lightboxOpen = false"
                >
                    <button class="absolute top-4 right-4 p-2 text-white hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <img :src="record.photo_url" alt="Foto check-in" class="max-w-full max-h-full object-contain">
                </div>
            </template>
        </div>
    </template>
</div>
@endsection
