@extends('layouts.admin')
@section('title', 'Anggota') @section('page-title', 'Daftar Anggota')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Anggota</h1>
            <p class="text-sm text-gray-400 mt-1">Daftar officer yang terdaftar di satuan kerja Anda.</p>
        </div>
        <a href="{{ route('officers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Anggota
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / NRP..."
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] w-64" />
        <button type="submit" class="px-4 py-2 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white text-sm rounded-xl transition-colors cursor-pointer">Cari</button>
    </form>

    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-surface-600)]">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Anggota</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">NRP</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Satfung</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Saker</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($officers as $officer)
                        <tr class="hover:bg-[var(--color-surface-700)] transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[var(--color-accent)]/10 flex items-center justify-center text-sm font-medium text-[var(--color-accent)] shrink-0">
                                        {{ strtoupper(substr($officer->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-white">{{ $officer->name }}</div>
                                        @if($officer->phone)<div class="text-xs text-gray-400">{{ $officer->phone }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-mono text-gray-300 text-sm">{{ $officer->nrp }}</td>
                            <td class="px-5 py-4 text-gray-400 text-sm">{{ $officer->safung ?? '—' }}</td>
                            <td class="px-5 py-4"><x-badge color="gray">{{ $officer->saker->code ?? '—' }}</x-badge></td>
                            <td class="px-5 py-4">
                                <x-badge color="{{ $officer->is_active ? 'green' : 'red' }}">
                                    {{ $officer->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('officers.show', $officer) }}" class="p-1.5 text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                    <a href="{{ route('officers.edit', $officer) }}" class="p-1.5 text-gray-400 hover:text-[var(--color-accent)] hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">Belum ada anggota terdaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($officers->hasPages())
            <div class="px-5 py-4 border-t border-[var(--color-surface-600)]">{{ $officers->links() }}</div>
        @endif
    </div>
</div>
@endsection
