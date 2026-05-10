@extends('layouts.admin')

@section('title', 'Operasi')
@section('page-title', 'Daftar Operasi')

@section('content')
<div class="space-y-6" x-data="{ archiving: null }">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Operasi</h1>
            <p class="text-sm text-gray-400 mt-1">Kelola semua operasi keamanan di satuan kerja Anda.</p>
        </div>
        <a href="{{ route('operations.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Operasi
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nama operasi..."
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] w-64" />

        <select name="status" class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
            <option value="">Semua Status</option>
            @foreach(['draft','active','suspended','completed','archived'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>

        <select name="type" class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
            <option value="">Semua Tipe</option>
            <option value="PH" @selected(request('type') === 'PH')>PH</option>
            <option value="PATROL" @selected(request('type') === 'PATROL')>Patrol</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white text-sm rounded-xl transition-colors cursor-pointer">
            Filter
        </button>
        @if(request()->hasAny(['search','status','type']))
            <a href="{{ route('operations.index') }}" class="px-4 py-2 text-gray-400 hover:text-white text-sm transition-colors">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-surface-600)]">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nama</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipe</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Zona</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($operations as $op)
                        <tr class="hover:bg-[var(--color-surface-700)] transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-medium text-white">{{ $op->name }}</div>
                                @if($op->description)
                                    <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $op->description }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <x-badge color="{{ $op->operation_type === 'PH' ? 'indigo' : 'blue' }}">
                                    {{ $op->operation_type }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4 text-gray-300">
                                {{ \Carbon\Carbon::parse($op->start_date)->format('d M Y') }}
                                @if($op->end_date)
                                    — {{ \Carbon\Carbon::parse($op->end_date)->format('d M Y') }}
                                @else
                                    <span class="text-gray-500">— tanpa batas</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusColors = ['draft'=>'gray','active'=>'green','suspended'=>'yellow','completed'=>'blue','archived'=>'purple'];
                                @endphp
                                <x-badge color="{{ $statusColors[$op->status] ?? 'gray' }}">
                                    {{ ucfirst($op->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4 text-gray-300">
                                {{ $op->zones_count ?? 0 }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('operations.show', $op) }}"
                                       class="p-1.5 text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"
                                       title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @if(!in_array($op->status, ['archived']))
                                        <a href="{{ route('operations.edit', $op) }}"
                                           class="p-1.5 text-gray-400 hover:text-[var(--color-accent)] hover:bg-[var(--color-surface-600)] rounded-lg transition-colors"
                                           title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <button @click="archiving = '{{ $op->id }}'"
                                                class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-[var(--color-surface-600)] rounded-lg transition-colors cursor-pointer"
                                                title="Arsipkan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8m-9 4v4m4-4v4"/></svg>
                                        </button>
                                    @endif
                                </div>

                                {{-- Archive confirmation form (hidden, triggered by Alpine) --}}
                                <form x-show="archiving === '{{ $op->id }}'" method="POST"
                                      action="{{ route('operations.archive', $op) }}"
                                      class="hidden" x-ref="archiveForm_{{ $op->id }}">
                                    @csrf
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                                Belum ada operasi. <a href="{{ route('operations.create') }}" class="text-[var(--color-accent)] hover:underline">Buat operasi pertama.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($operations->hasPages())
            <div class="px-5 py-4 border-t border-[var(--color-surface-600)]">
                {{ $operations->links() }}
            </div>
        @endif
    </div>

    {{-- Archive confirm modal --}}
    <div x-show="archiving !== null" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" style="display:none;">
        <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6 max-w-sm w-full mx-4">
            <h3 class="text-lg font-semibold text-white mb-2">Arsipkan Operasi?</h3>
            <p class="text-sm text-gray-400 mb-6">Operasi yang diarsipkan tidak dapat diaktifkan kembali. Pastikan semua penugasan telah selesai.</p>
            <div class="flex gap-3">
                <button @click="archiving = null" class="flex-1 px-4 py-2 bg-[var(--color-surface-600)] text-white rounded-xl text-sm hover:bg-[var(--color-surface-500)] transition-colors cursor-pointer">
                    Batal
                </button>
                @foreach($operations as $op)
                    <button x-show="archiving === '{{ $op->id }}'"
                            @click="document.querySelector('[x-ref=\'archiveForm_{{ $op->id }}\']').submit()"
                            class="flex-1 px-4 py-2 bg-red-500/80 hover:bg-red-500 text-white rounded-xl text-sm transition-colors cursor-pointer">
                        Ya, Arsipkan
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
