@extends('layouts.admin')

@section('title', 'Manajemen Komando')
@section('page-title', 'Kelola Saker / Admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-white tracking-tight">Daftar Saker</h3>
            <p class="text-sm text-gray-400 mt-1">Hierarki di bawah komando Anda.</p>
        </div>
        <a href="{{ route('sakers.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-all shadow-lg shadow-[var(--color-accent)]/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Admin
        </a>
    </div>

    <div class="bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-300">
                <thead class="bg-[var(--color-surface-700)] text-gray-200 font-medium">
                    <tr>
                        <th class="px-6 py-4">Kode Saker</th>
                        <th class="px-6 py-4">Nama Saker</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4">Email Login</th>
                        <th class="px-6 py-4">Induk (Parent)</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($sakers as $saker)
                        <tr class="hover:bg-[var(--color-surface-700)]/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-400">
                                {{ $saker->code }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-white">{{ $saker->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    {{ $saker->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $saker->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($saker->parent)
                                    <span class="text-gray-300">{{ $saker->parent->name }}</span>
                                @else
                                    <span class="text-gray-500 italic">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('sakers.edit', $saker->id) }}" class="p-1.5 text-gray-400 hover:text-[var(--color-accent)] hover:bg-[var(--color-surface-600)] rounded-lg transition-colors" title="Edit Saker">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('sakers.destroy', $saker->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus Saker ini? Semua data terkait (officer, penugasan) mungkin akan bermasalah jika tidak ditangani dengan benar.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-[var(--color-surface-600)] rounded-lg transition-colors cursor-pointer" title="Hapus Saker">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Belum ada satuan kerja yang dikelola.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
