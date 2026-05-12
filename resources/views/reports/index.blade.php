@extends('layouts.admin')

@section('title', 'Laporan Rekapitulasi')
@section('page-title', 'Rekapitulasi Penugasan')

@section('content')
<div class="space-y-6">

    {{-- Filter Form --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Operasi</label>
                <select name="operation_id" class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                    <option value="">Semua Operasi</option>
                    @foreach($operations as $op)
                        <option value="{{ $op->id }}" @selected(request('operation_id') === $op->id)>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                    <option value="">Semua Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="attended" @selected(request('status') === 'attended')>Hadir</option>
                    <option value="missed" @selected(request('status') === 'missed')>Absen</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-3 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white rounded-xl font-medium transition-colors">
                    Filter
                </button>
                <button type="submit" formaction="{{ route('reports.export') }}" class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl font-medium transition-colors flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    CSV
                </button>
            </div>
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[var(--color-surface-700)] text-gray-300 text-sm border-b border-[var(--color-surface-600)]">
                        <th class="p-4 font-medium">Tanggal</th>
                        <th class="p-4 font-medium">Petugas</th>
                        <th class="p-4 font-medium">Lokasi & Shift</th>
                        <th class="p-4 font-medium">Status</th>
                        <th class="p-4 font-medium">Waktu Hadir</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($assignments as $assignment)
                        <tr class="border-b border-[var(--color-surface-600)]/50 hover:bg-[var(--color-surface-700)]/50 transition-colors">
                            <td class="p-4">
                                <div class="text-white">{{ $assignment->assignment_date->format('d M Y') }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-medium text-white">{{ $assignment->officer->name ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ $assignment->officer->nrp ?? '-' }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-white">{{ $assignment->location->name ?? '-' }}</div>
                                <div class="text-xs text-[var(--color-accent)]">{{ $assignment->shift->name ?? '-' }}</div>
                            </td>
                            <td class="p-4">
                                @if($assignment->status === 'attended')
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">Hadir</span>
                                @elseif($assignment->status === 'missed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Tidak Hadir</span>
                                @elseif($assignment->status === 'cancelled')
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-500/10 text-gray-400 border border-gray-500/20">Batal</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Pending</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-400">
                                {{ $assignment->attended_at ? $assignment->attended_at->format('H:i:s') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400">
                                Belum ada data penugasan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assignments->hasPages())
            <div class="p-4 border-t border-[var(--color-surface-600)]">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
