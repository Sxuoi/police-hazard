{{-- Red advisory banner for SPOOFING_REJECTED cases --}}
@if($bypass->bypass_reason === 'SPOOFING_REJECTED')
<div class="rounded-xl border-2 border-red-500/50 bg-red-500/5 p-5 space-y-4">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center">
            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div>
            <h4 class="text-red-400 font-semibold text-sm uppercase tracking-wide">
                VERIFIKASI SINYAL SPOOFING SEBELUM PERSETUJUAN
            </h4>
            <p class="text-red-300/70 text-xs mt-0.5">
                Permintaan ini ditolak karena deteksi spoofing. Periksa metadata perangkat dengan cermat.
            </p>
        </div>
    </div>

    @if($bypass->officer_device_metadata)
        <div class="space-y-2">
            <p class="text-xs text-gray-400 font-medium uppercase">Metadata Perangkat</p>
            <div class="rounded-lg bg-[var(--color-surface-900)] p-4 overflow-x-auto">
                <pre class="text-xs text-gray-300 whitespace-pre-wrap">{{ json_encode($bypass->officer_device_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif
</div>
@endif
