<tr class="hover:bg-[var(--color-surface-700)] transition-colors">
    <td class="px-4 py-3">
        <div class="flex flex-col">
            <span class="text-white font-medium">{{ $bypass->officer?->name ?? '-' }}</span>
            <span class="text-xs text-gray-400">{{ $bypass->officer?->nrp ?? '-' }}</span>
        </div>
    </td>
    <td class="px-4 py-3">
        @php
            $reasonColors = [
                'OUTSIDE_GEOFENCE'      => 'bg-yellow-500/10 text-yellow-400',
                'OUTSIDE_SHIFT_WINDOW'  => 'bg-blue-500/10 text-blue-400',
                'SPOOFING_REJECTED'     => 'bg-red-500/10 text-red-400',
            ];
            $color = $reasonColors[$bypass->bypass_reason] ?? 'bg-gray-500/10 text-gray-400';
        @endphp
        <span class="inline-flex px-2 py-1 rounded-md text-xs font-medium {{ $color }}">
            {{ str_replace('_', ' ', $bypass->bypass_reason) }}
        </span>
    </td>
    <td class="px-4 py-3">
        @php
            $statusColors = [
                'pending'  => 'bg-amber-500/10 text-amber-400',
                'approved' => 'bg-green-500/10 text-green-400',
                'denied'   => 'bg-red-500/10 text-red-400',
                'expired'  => 'bg-gray-500/10 text-gray-400',
            ];
            $sColor = $statusColors[$bypass->status] ?? 'bg-gray-500/10 text-gray-400';
        @endphp
        <span class="inline-flex px-2 py-1 rounded-md text-xs font-medium {{ $sColor }}">
            {{ ucfirst($bypass->status) }}
        </span>
    </td>
    <td class="px-4 py-3 text-gray-300">
        {{ $bypass->created_at?->format('d/m/Y H:i') ?? '-' }}
    </td>
    <td class="px-4 py-3 text-gray-300">
        {{ $bypass->expires_at?->format('d/m/Y H:i') ?? '-' }}
    </td>
    <td class="px-4 py-3">
        <a href="{{ route('bypass-approvals.show', $bypass->id) }}" class="text-[var(--color-accent)] hover:underline text-sm">
            Detail
        </a>
    </td>
</tr>
