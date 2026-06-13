@extends('layouts.admin')

@section('title', 'Detail Penugasan')
@section('page-title', 'Detail Penugasan')

@section('content')
@php
    $assignment->loadMissing([
        'officer:id,name,nrp,saker_id',
        'officer.saker:id,code,name',
        'location:id,name,address,zone_id,radius_meters,saker_id',
        'location.zone:id,name',
        'shift:id,name,shift_start,shift_end,active_days',
        'operation:id,name,operation_type,status,start_time,end_time',
        'saker:id,code,name',
        'assignedSaker:id,code,name',
        'assignedBy:id,name,nrp',
        'attendances',
    ]);

    $statusColors = ['pending' => 'gray', 'active' => 'green', 'completed' => 'blue', 'cancelled' => 'red'];
    $opTypeColor = ($assignment->operation->operation_type ?? null) === 'PH' ? 'indigo' : 'blue';
    $latestAttendance = $assignment->attendances->sortByDesc('checked_in_at')->first();
@endphp

<div class="space-y-6">

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error">{{ $errors->first() }}</x-alert>
    @endif

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('assignments.index') }}" class="text-gray-400 hover:text-white transition-colors">← Penugasan</a>
        <span class="text-gray-600">/</span>
        <span class="text-gray-300">{{ \Illuminate\Support\Str::substr($assignment->id, 0, 8) }}…</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Header card --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $assignment->officer->name ?? 'Anggota tidak ditemukan' }}</h1>
                        <p class="text-sm text-gray-400 mt-1">NRP {{ $assignment->officer->nrp ?? '—' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-badge color="{{ $statusColors[$assignment->status] ?? 'gray' }}">
                            {{ ucfirst($assignment->status) }}
                        </x-badge>
                        @if($assignment->operation)
                            <x-badge color="{{ $opTypeColor }}">{{ $assignment->operation->operation_type }}</x-badge>
                        @endif
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Tanggal Penugasan</dt>
                        <dd class="text-white mt-1">{{ \Carbon\Carbon::parse($assignment->assignment_date)->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Shift</dt>
                        <dd class="text-white mt-1">
                            @if($assignment->shift)
                                {{ $assignment->shift->name }}
                                <span class="text-xs text-gray-400 ml-1">({{ \Illuminate\Support\Str::substr($assignment->shift->shift_start, 0, 5) }} – {{ \Illuminate\Support\Str::substr($assignment->shift->shift_end, 0, 5) }})</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Operasi</dt>
                        <dd class="text-white mt-1">
                            @if($assignment->operation)
                                <a href="{{ route('operations.show', $assignment->operation) }}" class="hover:underline">{{ $assignment->operation->name }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Lokasi</dt>
                        <dd class="text-white mt-1">
                            @if($assignment->location)
                                <a href="{{ route('locations.show', $assignment->location) }}" class="hover:underline">{{ $assignment->location->name }}</a>
                                @if($assignment->location->zone)
                                    <span class="text-xs text-gray-400 ml-1">/ {{ $assignment->location->zone->name }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Saker Anggota</dt>
                        <dd class="text-white mt-1">{{ $assignment->saker->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Saker Penugas</dt>
                        <dd class="text-white mt-1">{{ $assignment->assignedSaker->code ?? $assignment->saker->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Ditugaskan Oleh</dt>
                        <dd class="text-white mt-1">{{ $assignment->assignedBy->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dibuat</dt>
                        <dd class="text-white mt-1">{{ optional($assignment->created_at)->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                @if($assignment->notes)
                    <div class="mt-6 pt-6 border-t border-[var(--color-surface-600)]">
                        <dt class="text-xs text-gray-500 uppercase tracking-wider mb-1">Catatan</dt>
                        <dd class="text-sm text-gray-300">{{ $assignment->notes }}</dd>
                    </div>
                @endif

                @if($assignment->status === 'cancelled' && $assignment->cancel_reason ?? false)
                    <div class="mt-6 pt-6 border-t border-[var(--color-surface-600)]">
                        <dt class="text-xs text-red-400 uppercase tracking-wider mb-1">Alasan Pembatalan</dt>
                        <dd class="text-sm text-gray-300">{{ $assignment->cancel_reason }}</dd>
                    </div>
                @endif
            </div>

            {{-- Attendance history --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Riwayat Kehadiran</h2>
                    <span class="text-xs text-gray-500">{{ $assignment->attendances->count() }} catatan</span>
                </div>
                @if($assignment->attendances->isEmpty())
                    <p class="text-sm text-gray-500">Belum ada check-in untuk penugasan ini.</p>
                @else
                    <div class="space-y-3">
                        @foreach($assignment->attendances->sortByDesc('checked_in_at') as $att)
                            <div class="flex items-start justify-between py-3 border-b border-[var(--color-surface-600)] last:border-0">
                                <div>
                                    <div class="text-sm text-white">{{ optional($att->checked_in_at)->format('d M Y H:i:s') }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        Jarak: {{ number_format((float) $att->distance_from_point, 1) }} m
                                        @if($att->is_manual_bypass)
                                            <span class="text-yellow-400">· Bypass</span>
                                        @endif
                                    </div>
                                </div>
                                @php
                                    $attStatusColor = match($att->status) {
                                        'verified' => 'green',
                                        'flagged' => 'yellow',
                                        'rejected' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-badge color="{{ $attStatusColor }}">{{ ucfirst($att->status) }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            {{-- Latest check-in summary --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Status Kehadiran</h3>
                @if($latestAttendance)
                    <div class="space-y-2 text-sm">
                        <div class="text-white">Sudah check-in</div>
                        <div class="text-xs text-gray-400">
                            {{ optional($latestAttendance->checked_in_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                @else
                    <div class="text-sm text-gray-400">Belum check-in</div>
                @endif
            </div>

            {{-- Cancel form --}}
            @if($assignment->status === 'active' || $assignment->status === 'pending')
                <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                    <h3 class="text-sm font-semibold text-white mb-3">Batalkan Penugasan</h3>
                    <form method="POST" action="{{ route('assignments.cancel', $assignment) }}" onsubmit="return confirm('Batalkan penugasan ini?')" class="space-y-3">
                        @csrf
                        <textarea
                            name="reason"
                            rows="3"
                            minlength="10"
                            required
                            placeholder="Alasan pembatalan (min. 10 karakter)..."
                            class="w-full px-3 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] resize-none"
                        ></textarea>
                        <button type="submit" class="w-full px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm rounded-xl border border-red-500/20 transition-colors cursor-pointer">
                            Batalkan
                        </button>
                    </form>
                </div>
            @endif

            {{-- Quick actions --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Aksi Cepat</h3>
                <div class="space-y-2">
                    <a href="{{ route('assignments.index') }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Kembali ke Daftar</a>
                    @if($assignment->location)
                        <a href="{{ route('locations.show', $assignment->location) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Lihat Lokasi</a>
                    @endif
                    @if($assignment->operation)
                        <a href="{{ route('operations.show', $assignment->operation) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Lihat Operasi</a>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
