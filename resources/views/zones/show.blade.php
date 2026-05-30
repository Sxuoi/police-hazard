@extends('layouts.admin')
@section('title', 'Detail Zona') @section('page-title', 'Detail Zona')
@section('content')
<div class="space-y-6">

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('zones.index') }}" class="text-gray-400 hover:text-white transition-colors">← Zona</a>
        <span class="text-gray-600">/</span>
        <span class="text-gray-300">{{ $zone->name }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Header card --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $zone->name }}</h1>
                        @if($zone->description)
                            <p class="text-gray-400 mt-1">{{ $zone->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <x-badge color="{{ $zone->is_active ? 'green' : 'gray' }}">
                            {{ $zone->is_active ? 'Aktif' : 'Nonaktif' }}
                        </x-badge>
                        @if($zone->operation)
                            <x-badge color="{{ $zone->operation->operation_type === 'PH' ? 'indigo' : 'blue' }}">
                                {{ $zone->operation->operation_type }}
                            </x-badge>
                        @endif
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Operasi</dt>
                        <dd class="text-white mt-1">
                            @if($zone->operation)
                                <a href="{{ route('operations.show', $zone->operation) }}" class="hover:underline">
                                    {{ $zone->operation->name }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Saker</dt>
                        <dd class="text-white mt-1">{{ $zone->saker->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Total Lokasi</dt>
                        <dd class="text-white mt-1">{{ $zone->locations_count ?? $zone->locations()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dibuat</dt>
                        <dd class="text-white mt-1">{{ optional($zone->created_at)->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="flex gap-3 mt-6 pt-6 border-t border-[var(--color-surface-600)]">
                    <a href="{{ route('zones.edit', $zone) }}" class="px-4 py-2 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm rounded-xl transition-colors">Edit</a>
                    @if(($zone->locations_count ?? $zone->locations()->count()) === 0)
                        <form method="POST" action="{{ route('zones.destroy', $zone) }}" onsubmit="return confirm('Hapus zona ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm rounded-xl border border-red-500/20 transition-colors cursor-pointer">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Locations under this zone --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Lokasi</h2>
                    <a href="{{ route('locations.create', ['zone_id' => $zone->id]) }}" class="text-sm text-[var(--color-accent)] hover:underline">+ Tambah Lokasi</a>
                </div>
                @forelse($zone->locations as $location)
                    <div class="flex items-center justify-between py-3 border-b border-[var(--color-surface-600)] last:border-0">
                        <div>
                            <div class="font-medium text-white">{{ $location->name }}</div>
                            <div class="text-xs text-gray-400">
                                Radius {{ $location->radius_meters }}m
                                @if($location->address)· {{ \Illuminate\Support\Str::limit($location->address, 80) }}@endif
                            </div>
                        </div>
                        <a href="{{ route('locations.show', $location) }}" class="text-sm text-[var(--color-accent)] hover:underline">Detail →</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada lokasi di zona ini.</p>
                @endforelse
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Aksi Cepat</h3>
                <div class="space-y-2">
                    <a href="{{ route('zones.edit', $zone) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Edit Zona</a>
                    <a href="{{ route('locations.create', ['zone_id' => $zone->id]) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Tambah Lokasi</a>
                    @if($zone->operation)
                        <a href="{{ route('operations.show', $zone->operation) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Lihat Operasi</a>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
