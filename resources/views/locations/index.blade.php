@extends('layouts.admin')
@section('title', 'Lokasi') @section('page-title', 'Daftar Lokasi')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Lokasi</h1>
            <p class="text-sm text-gray-400 mt-1">Titik patrol dan pos hadang.</p>
        </div>
        <a href="{{ route('locations.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Lokasi
        </a>
    </div>

    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-surface-600)]">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nama Lokasi</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Zona / Operasi</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Radius</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Min. Officer</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Koordinat Terkunci</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($locations as $loc)
                        <tr class="hover:bg-[var(--color-surface-700)] transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-medium text-white">{{ $loc->name }}</div>
                                @if($loc->address)<div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $loc->address }}</div>@endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-gray-300">{{ $loc->zone->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                    {{ $loc->zone->operation->name ?? '—' }}
                                    @if(($loc->zone->operation->status ?? '') === 'archived')
                                        <x-badge color="red">Archived</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-300">{{ $loc->radius_meters }}m</td>
                            <td class="px-5 py-4 text-gray-300">{{ $loc->minimum_officer }}</td>
                            <td class="px-5 py-4">
                                @if($loc->coords_locked)
                                    <x-badge color="orange">Terkunci</x-badge>
                                @else
                                    <x-badge color="gray">Bebas</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('locations.show', $loc) }}" class="p-1.5 text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                    <a href="{{ route('locations.edit', $loc) }}" class="p-1.5 text-gray-400 hover:text-[var(--color-accent)] hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">Belum ada lokasi. <a href="{{ route('locations.create') }}" class="text-[var(--color-accent)] hover:underline">Tambah lokasi pertama.</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($locations->hasPages())
            <div class="px-5 py-4 border-t border-[var(--color-surface-600)]">{{ $locations->links() }}</div>
        @endif
    </div>
</div>
@endsection
