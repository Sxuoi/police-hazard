@extends('layouts.admin')

@section('title', 'Bypass Approvals')
@section('page-title', 'Bypass Approvals')

@section('content')
<div class="space-y-6">
    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('bypass-approvals.index') }}" class="flex flex-wrap items-end gap-4 p-4 rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)]">
        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-400">Status</label>
            <select name="status" class="rounded-lg bg-[var(--color-surface-700)] border-[var(--color-surface-600)] text-sm text-gray-200 px-3 py-2">
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="denied" @selected(($filters['status'] ?? '') === 'denied')>Denied</option>
                <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Expired</option>
                <option value="" @selected(($filters['status'] ?? '') === '')>Semua</option>
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-400">Alasan Bypass</label>
            <select name="bypass_reason" class="rounded-lg bg-[var(--color-surface-700)] border-[var(--color-surface-600)] text-sm text-gray-200 px-3 py-2">
                <option value="">Semua</option>
                <option value="OUTSIDE_GEOFENCE" @selected(($filters['bypass_reason'] ?? '') === 'OUTSIDE_GEOFENCE')>Di Luar Geofence</option>
                <option value="OUTSIDE_SHIFT_WINDOW" @selected(($filters['bypass_reason'] ?? '') === 'OUTSIDE_SHIFT_WINDOW')>Di Luar Shift</option>
                <option value="SPOOFING_REJECTED" @selected(($filters['bypass_reason'] ?? '') === 'SPOOFING_REJECTED')>Spoofing Ditolak</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 rounded-lg bg-[var(--color-accent)] text-white text-sm font-medium hover:bg-[var(--color-accent)]/90 transition-colors cursor-pointer">
            Filter
        </button>
    </form>

    {{-- Table --}}
    <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-400 uppercase bg-[var(--color-surface-700)]">
                    <tr>
                        <th class="px-4 py-3">Anggota</th>
                        <th class="px-4 py-3">Alasan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dibuat</th>
                        <th class="px-4 py-3">Kedaluwarsa</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($bypasses as $bypass)
                        @include('bypass-approvals._row', ['bypass' => $bypass])
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada permintaan bypass.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bypasses->hasPages())
            <div class="px-4 py-3 border-t border-[var(--color-surface-600)]">
                {{ $bypasses->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
