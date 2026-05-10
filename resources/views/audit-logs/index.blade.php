@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('page-title', 'Sistem Audit Trail')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Audit Logs</h1>
            <p class="text-sm text-gray-400 mt-1">Laporan aktivitas dan perubahan data dalam sistem.</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
               
        <span class="text-gray-400 self-center">-</span>
               
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />

        <input type="text" name="event_type" value="{{ request('event_type') }}" placeholder="Event Type (e.g., ASSIGNMENT_CREATED)"
               class="px-4 py-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] w-64" />

        <button type="submit" class="px-4 py-2 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white text-sm rounded-xl transition-colors cursor-pointer">
            Filter
        </button>
        @if(request()->hasAny(['event_type','date_from','date_to']))
            <a href="{{ route('audit-logs.index') }}" class="px-4 py-2 text-gray-400 hover:text-white text-sm transition-colors">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-surface-600)]">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Waktu</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Event Type</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aktor</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Entity</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Changes / Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-surface-600)]">
                    @forelse($logs as $log)
                        <tr class="hover:bg-[var(--color-surface-700)] transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap text-gray-300">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-5 py-4">
                                <x-badge color="indigo">
                                    {{ $log->event_type }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-white">{{ $log->actor->name ?? 'System' }}</div>
                                @if($log->actor)
                                    <div class="text-xs text-gray-400">{{ $log->actor->nrp }}</div>
                                @endif
                                <div class="text-xs text-gray-500 mt-1">{{ $log->ip_address }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-mono text-xs text-gray-400">{{ class_basename($log->entity_type) }}</div>
                                <div class="text-xs text-gray-500 mt-1 truncate max-w-[150px]" title="{{ $log->entity_id }}">{{ $log->entity_id }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if($log->old_values || $log->new_values || $log->metadata)
                                    <details class="group">
                                        <summary class="text-[var(--color-accent)] hover:underline cursor-pointer text-xs font-medium">Lihat Detail</summary>
                                        <div class="mt-2 p-3 bg-[var(--color-surface-900)] rounded-lg text-xs font-mono overflow-x-auto">
                                            @if($log->old_values)
                                                <div class="mb-2"><span class="text-gray-500 block mb-1">Old:</span> <pre class="text-red-400">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre></div>
                                            @endif
                                            @if($log->new_values)
                                                <div class="mb-2"><span class="text-gray-500 block mb-1">New:</span> <pre class="text-green-400">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre></div>
                                            @endif
                                            @if($log->metadata)
                                                <div><span class="text-gray-500 block mb-1">Metadata:</span> <pre class="text-blue-400">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre></div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                Belum ada log audit.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-[var(--color-surface-600)]">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
