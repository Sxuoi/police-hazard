@extends('layouts.admin')
@section('title', 'Detail Anggota') @section('page-title', 'Detail Anggota')
@section('content')
<div class="space-y-6">

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('officers.index') }}" class="text-gray-400 hover:text-white transition-colors">← Anggota</a>
        <span class="text-gray-600">/</span>
        <span class="text-gray-300">{{ $officer->name }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main card --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-[var(--color-accent)]/20 flex items-center justify-center text-[var(--color-accent)] text-xl font-bold shrink-0">
                            {{ strtoupper(substr($officer->name, 0, 2)) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $officer->name }}</h1>
                            <div class="text-gray-400 text-sm font-mono mt-0.5">{{ $officer->nrp }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-badge color="{{ $officer->is_active ? 'green' : 'red' }}">
                            {{ $officer->is_active ? 'Aktif' : 'Nonaktif' }}
                        </x-badge>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm pt-4 border-t border-[var(--color-surface-600)]">
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Telepon</dt>
                        <dd class="text-white mt-1">{{ $officer->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Email</dt>
                        <dd class="text-white mt-1">{{ $officer->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Satfung</dt>
                        <dd class="text-white mt-1">{{ $officer->safung ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Satuan Kerja</dt>
                        <dd class="text-white mt-1">{{ $officer->saker->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Login Terakhir</dt>
                        <dd class="text-white mt-1">{{ $officer->last_login_at ? $officer->last_login_at->format('d M Y H:i') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase tracking-wide">Terdaftar Sejak</dt>
                        <dd class="text-white mt-1">{{ optional($officer->created_at)->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="flex gap-3 mt-6 pt-6 border-t border-[var(--color-surface-600)]">
                    <a href="{{ route('officers.edit', $officer) }}" class="px-4 py-2 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm rounded-xl transition-colors">
                        Edit
                    </a>
                    <a href="{{ route('officers.index') }}" class="px-4 py-2 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm rounded-xl transition-colors">
                        Kembali
                    </a>
                </div>
            </div>

            {{-- Assignments --}}
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Penugasan Aktif</h2>
                @php
                    $activeAssignments = $officer->assignments()
                        ->where('status', 'active')
                        ->with(['location', 'operation'])
                        ->latest()
                        ->get();
                @endphp
                @forelse($activeAssignments as $assignment)
                    <div class="flex items-center justify-between py-3 border-b border-[var(--color-surface-600)] last:border-0">
                        <div>
                            <div class="font-medium text-white">{{ $assignment->location->name ?? '—' }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $assignment->operation->name ?? '—' }}
                                · {{ $assignment->start_date->format('d M Y') }} — {{ $assignment->end_date ? $assignment->end_date->format('d M Y') : 'Sekarang' }}
                            </div>
                        </div>
                        <a href="{{ route('assignments.show', $assignment) }}" class="text-sm text-[var(--color-accent)] hover:underline">Detail →</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Tidak ada penugasan aktif saat ini.</p>
                @endforelse
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">
            <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
                <h3 class="text-sm font-semibold text-white mb-3">Aksi Cepat</h3>
                <div class="space-y-2">
                    <a href="{{ route('officers.edit', $officer) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Edit Anggota</a>
                    <a href="{{ route('assignments.create', ['officer_id' => $officer->id]) }}" class="block px-3 py-2 rounded-lg bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-sm text-gray-200 transition-colors">Buat Penugasan</a>
                </div>
            </div>
        </aside>
    </div>

</div>
@endsection
