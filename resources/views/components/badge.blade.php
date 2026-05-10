@props(['color' => 'gray'])

@php
    $colors = [
        'indigo'  => 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
        'green'   => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'red'     => 'bg-red-500/10 text-red-400 border border-red-500/20',
        'yellow'  => 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20',
        'orange'  => 'bg-orange-500/10 text-orange-400 border border-orange-500/20',
        'blue'    => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        'gray'    => 'bg-gray-500/10 text-gray-400 border border-gray-500/20',
        'purple'  => 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
    ];
@endphp

<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $colors[$color] ?? $colors['gray'] }}">
    {{ $slot }}
</span>
