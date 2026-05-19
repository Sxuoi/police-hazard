@extends('officer.layout')

@section('title', 'Detail Penugasan')

@section('content')
<div class="p-4 space-y-4" x-data="officerAssignmentShow" x-init="fetchAssignment()">
    {{-- Back Button --}}
    <a href="/officer/assignments" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-white transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-[var(--color-accent)]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
    </template>

    {{-- Assignment Detail --}}
    <template x-if="!loading && assignment">
        <div class="space-y-4">
            {{-- Header Card --}}
            <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-4">
                <div class="flex items-start justify-between mb-3">
                    <h2 class="text-lg font-semibold text-white" x-text="assignment.location_name"></h2>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusBadgeClass(assignment.status)"
                        x-text="statusLabel(assignment.status)"
                    ></span>
                </div>
                <div class="space-y-2 text-sm text-gray-400">
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="assignment.shift_name"></span>
                    </p>
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="assignment.zone_name"></span>
                    </p>
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="assignment.assignment_date"></span>
                    </p>
                </div>
            </div>

            {{-- Mini Map Placeholder --}}
            <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] overflow-hidden">
                <div id="officer-minimap" class="h-48 bg-[var(--color-surface-700)]"></div>
            </div>

            {{-- Distance Indicator --}}
            <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-400">Jarak ke lokasi</span>
                    <span class="text-sm font-medium" :class="distance !== null && distance <= (assignment.location_radius_meters || 100) ? 'text-green-400' : 'text-yellow-400'">
                        <template x-if="distance !== null">
                            <span x-text="distance.toFixed(0) + ' m'"></span>
                        </template>
                        <template x-if="distance === null">
                            <span class="text-gray-500">Menghitung...</span>
                        </template>
                    </span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-[var(--color-surface-600)] overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="distance !== null && distance <= (assignment.location_radius_meters || 100) ? 'bg-green-500' : 'bg-yellow-500'"
                        :style="'width: ' + Math.min(100, Math.max(5, distance !== null ? (1 - distance / 500) * 100 : 0)) + '%'"
                    ></div>
                </div>
            </div>

            {{-- Check-in Button --}}
            <a
                :href="'/officer/checkin/' + assignment.assignment_id"
                class="block w-full py-4 rounded-xl bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-semibold text-center transition-colors"
                x-show="assignment.status !== 'attended'"
            >
                Check-In
            </a>

            <div
                x-show="assignment.status === 'attended'"
                class="block w-full py-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 font-semibold text-center"
            >
                ✓ Sudah Check-In
            </div>
        </div>
    </template>
</div>
@endsection
