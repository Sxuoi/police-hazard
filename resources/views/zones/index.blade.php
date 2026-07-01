@extends('layouts.admin')
@section('title', 'Zona') @section('page-title', 'Daftar Zona')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Zona</h1>
            <p class="text-sm text-gray-400 mt-1">Kelompok lokasi dalam sebuah operasi.</p>
        </div>
        <a href="{{ route('zones.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Zona
        </a>
    </div>

    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-surface-600)]">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nama Zona</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Operasi</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Saker</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Lokasi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($zones as $zone)
                        <tr class="hover:bg-[var(--color-surface-700)] transition-colors">
                            <td class="px-5 py-4 font-medium text-white">{{ $zone->name }}</td>
                            <td class="px-5 py-4">
                                <div class="text-gray-300 text-sm">{{ $zone->operation->name ?? '—' }}</div>
                                @if($zone->operation)
                                    <div class="flex items-center gap-1 mt-1">
                                        <x-badge color="{{ $zone->operation->operation_type === 'PH' ? 'indigo' : 'blue' }}">{{ $zone->operation->operation_type }}</x-badge>
                                        @if($zone->operation->status === 'archived')
                                            <x-badge color="red">Archived</x-badge>
                                        @elseif($zone->operation->status === 'draft')
                                            <x-badge color="orange">Draft</x-badge>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4"><x-badge color="gray">{{ $zone->saker->code ?? '—' }}</x-badge></td>
                            <td class="px-5 py-4 text-gray-300">{{ $zone->locations_count ?? 0 }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('zones.show', $zone) }}" class="p-1.5 text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                    <a href="{{ route('zones.edit', $zone) }}" class="p-1.5 text-gray-400 hover:text-[var(--color-accent)] hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                    @if(($zone->locations_count ?? 0) === 0)
                                        <form method="POST" action="{{ route('zones.destroy', $zone) }}" onsubmit="return confirm('Hapus zona ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-[var(--color-surface-600)] rounded-lg transition-colors cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">Belum ada zona. <a href="{{ route('zones.create') }}" class="text-[var(--color-accent)] hover:underline">Buat zona pertama.</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($zones->hasPages())
            <div class="px-5 py-4 border-t border-[var(--color-surface-600)]">{{ $zones->links() }}</div>
        @endif
    </div>
</div>
@endsection
