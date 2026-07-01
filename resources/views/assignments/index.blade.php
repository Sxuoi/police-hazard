@extends('layouts.admin')

@section('title', 'Penugasan')
@section('page-title', 'Daftar Penugasan')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Penugasan</h1>
            <p class="text-sm text-gray-400 mt-1">Kelola penugasan personel ke lokasi operasi.</p>
        </div>
        <a href="{{ route('assignments.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Penugasan
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama / NRP..."
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />

        <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}"
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />

        <select name="operation_id" class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] max-w-[200px]">
            <option value="">Semua Operasi</option>
            @foreach($operations as $op)
                <option value="{{ $op->id }}" @selected(request('operation_id') === $op->id)>{{ $op->name }}</option>
            @endforeach
        </select>

        <select name="status" class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
            <option value="">Semua Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white text-sm rounded-xl transition-colors cursor-pointer">
            Filter
        </button>
        @if(request()->hasAny(['operation_id','status','search']) || request('date') !== now()->format('Y-m-d'))
            <a href="{{ route('assignments.index') }}" class="px-4 py-2 text-gray-400 hover:text-white text-sm transition-colors">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-surface-600)]">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Anggota</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Lokasi & Operasi</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tanggal & Waktu</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($assignments as $assignment)
                        <tr class="hover:bg-[var(--color-surface-700)] transition-colors">
                             <td class="px-5 py-4">
                                <div class="font-medium text-white">{{ $assignment->officer->name ?? '-' }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $assignment->officer->nrp ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-white">{{ $assignment->location->name ?? '-' }}</div>
                                <div class="text-xs text-[var(--color-accent)] mt-0.5">{{ $assignment->operation->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-gray-300">
                                    {{ $assignment->start_date->format('d M Y') }}
                                    @if($assignment->end_date)
                                        s/d {{ $assignment->end_date->format('d M Y') }}
                                    @else
                                        (Aktif)
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">Waktu: {{ substr($assignment->operation->start_time ?? '', 0, 5) }} - {{ $assignment->operation->end_time ? substr($assignment->operation->end_time, 0, 5) : '23:59' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusColors = ['active'=>'green','completed'=>'blue','cancelled'=>'red'];
                                @endphp
                                <x-badge color="{{ $statusColors[$assignment->status] ?? 'gray' }}">
                                    {{ ucfirst($assignment->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('assignments.show', $assignment) }}" class="text-[var(--color-accent)] hover:underline text-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                Belum ada penugasan yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($assignments->hasPages())
            <div class="px-5 py-4 border-t border-[var(--color-surface-600)]">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
