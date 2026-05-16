@extends('officer.layout')

@section('title', 'Riwayat Kehadiran')

@section('content')
<div class="p-4 space-y-4" x-data="officerHistory" x-init="fetchHistory()">
    <h1 class="text-lg font-semibold text-white">Riwayat Kehadiran</h1>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-[var(--color-accent)]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
    </template>

    {{-- Empty State --}}
    <template x-if="!loading && records.length === 0">
        <div class="text-center py-12">
            <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-gray-400 text-sm">Belum ada riwayat kehadiran</p>
        </div>
    </template>

    {{-- History List --}}
    <div class="space-y-3" x-show="!loading && records.length > 0">
        <template x-for="record in records" :key="record.id">
            <a :href="'/officer/history/' + record.id" class="block rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-4 hover:border-[var(--color-accent)]/50 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="text-sm font-medium text-white" x-text="record.location_name"></h3>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="record.verification_method === 'gps_photo' ? 'bg-green-500/10 text-green-400' : 'bg-yellow-500/10 text-yellow-400'"
                        x-text="record.verification_method === 'gps_photo' ? 'GPS' : 'Bypass'"
                    ></span>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-400">
                    <span x-text="record.checked_in_at_formatted"></span>
                    <span>•</span>
                    <span x-text="record.distance_from_point ? record.distance_from_point + ' m' : '-'"></span>
                </div>
            </a>
        </template>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between" x-show="!loading && totalPages > 1">
        <button
            @click="prevPage()"
            :disabled="page <= 1"
            class="px-4 py-2 rounded-lg bg-[var(--color-surface-700)] text-sm text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-[var(--color-surface-600)] transition-colors"
        >
            Sebelumnya
        </button>
        <span class="text-sm text-gray-400" x-text="'Hal. ' + page + ' / ' + totalPages"></span>
        <button
            @click="nextPage()"
            :disabled="page >= totalPages"
            class="px-4 py-2 rounded-lg bg-[var(--color-surface-700)] text-sm text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-[var(--color-surface-600)] transition-colors"
        >
            Berikutnya
        </button>
    </div>
</div>
@endsection
