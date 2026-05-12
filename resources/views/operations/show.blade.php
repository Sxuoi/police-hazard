@extends('layouts.admin')
@section('title', 'Detail Operasi') @section('page-title', 'Detail Operasi')
@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4 mb-2">
        <a href="{{ route('operations.index') }}" class="text-gray-400 hover:text-white transition-colors">← Operasi</a>
        <span class="text-gray-600">/</span>
        <span class="text-gray-300">{{ $operation->name }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $operation->name }}</h1>
                        @if($operation->description)<p class="text-gray-400 mt-1">{{ $operation->description }}</p>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        @php $statusColors = ['draft'=>'gray','active'=>'green','suspended'=>'yellow','completed'=>'blue','archived'=>'purple']; @endphp
                        <x-badge color="{{ $statusColors[$operation->status] ?? 'gray' }}">{{ ucfirst($operation->status) }}</x-badge>
                        <x-badge color="{{ $operation->operation_type === 'PH' ? 'indigo' : 'blue' }}">{{ $operation->operation_type }}</x-badge>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <div><dt class="text-gray-500">Waktu Mulai</dt><dd class="text-white mt-1">{{ substr($operation->start_time, 0, 5) }}</dd></div>
                    <div><dt class="text-gray-500">Waktu Selesai</dt><dd class="text-white mt-1">{{ $operation->end_time ? substr($operation->end_time, 0, 5) : 'Tanpa batas' }}</dd></div>
                    <div><dt class="text-gray-500">Zona</dt><dd class="text-white mt-1">{{ $operation->zones_count ?? 0 }}</dd></div>
                    <div><dt class="text-gray-500">Total Penugasan</dt><dd class="text-white mt-1">{{ $operation->assignments_count ?? 0 }}</dd></div>
                </dl>

                @if(!in_array($operation->status, ['archived']))
                    <div class="flex gap-3 mt-6 pt-6 border-t border-[var(--color-surface-600)]">
                        <a href="{{ route('operations.edit', $operation) }}" class="px-4 py-2 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm rounded-xl transition-colors">Edit</a>
                        <form method="POST" action="{{ route('operations.archive', $operation) }}" onsubmit="return confirm('Arsipkan operasi ini?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm rounded-xl border border-red-500/20 transition-colors cursor-pointer">Arsipkan</button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Zones list --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Zona</h2>
                    <a href="{{ route('zones.create') }}" class="text-sm text-[var(--color-accent)] hover:underline">+ Tambah Zona</a>
                </div>
                @forelse($operation->zones as $zone)
                    <div class="flex items-center justify-between py-3 border-b border-[var(--color-surface-600)] last:border-0">
                        <div>
                            <div class="font-medium text-white">{{ $zone->name }}</div>
                            <div class="text-xs text-gray-400">{{ $zone->locations_count ?? 0 }} lokasi</div>
                        </div>
                        <a href="{{ route('zones.show', $zone) }}" class="text-sm text-[var(--color-accent)] hover:underline">Detail →</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada zona di operasi ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
