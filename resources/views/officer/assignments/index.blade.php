@extends('officer.layout')

@section('title', 'Penugasan')

@section('content')
<div class="p-4 space-y-4" x-data="officerAssignments" x-init="fetchAssignments()">
    {{-- Date Switcher --}}
    <div class="flex items-center justify-between">
        <button @click="prevDay()" class="p-2 rounded-lg bg-[var(--color-surface-700)] text-gray-300 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="text-center">
            <p class="text-sm font-medium text-white" x-text="formatDate(selectedDate)"></p>
            <p class="text-xs text-gray-400" x-show="isToday()" x-cloak>Hari ini</p>
        </div>
        <button @click="nextDay()" class="p-2 rounded-lg bg-[var(--color-surface-700)] text-gray-300 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-[var(--color-accent)]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
    </template>

    {{-- Empty State --}}
    <template x-if="!loading && assignments.length === 0">
        <div class="text-center py-12">
            <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p class="text-gray-400 text-sm">Tidak ada penugasan untuk tanggal ini</p>
        </div>
    </template>

    {{-- Assignment Cards --}}
    <div class="space-y-3" x-show="!loading && assignments.length > 0">
        <template x-for="assignment in assignments" :key="assignment.id">
            <a :href="'/officer/assignments/' + assignment.id" class="block rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-4 hover:border-[var(--color-accent)]/50 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="text-sm font-medium text-white" x-text="assignment.location_name"></h3>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusBadgeClass(assignment.status)"
                        x-text="statusLabel(assignment.status)"
                    ></span>
                </div>
                <div class="space-y-1">
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="assignment.shift_label"></span>
                    </p>
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-text="assignment.zone_name"></span>
                    </p>
                </div>
            </a>
        </template>
    </div>
</div>
@endsection
