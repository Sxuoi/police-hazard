@props(['title' => '', 'value' => '', 'icon' => '', 'color' => 'indigo', 'subtitle' => ''])

@php
    $bgColors = [
        'indigo' => 'bg-indigo-500/10',
        'green'  => 'bg-emerald-500/10',
        'red'    => 'bg-red-500/10',
        'yellow' => 'bg-yellow-500/10',
    ];
    $textColors = [
        'indigo' => 'text-indigo-400',
        'green'  => 'text-emerald-400',
        'red'    => 'text-red-400',
        'yellow' => 'text-yellow-400',
    ];
@endphp

<div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-5">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm text-gray-400">{{ $title }}</p>
            <p class="text-3xl font-bold text-white mt-1" {{ $attributes->only('x-text') }}>{{ $value }}</p>
            @if($subtitle)
                <p class="text-xs text-gray-500 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if($icon)
            <div class="w-10 h-10 rounded-xl {{ $bgColors[$color] ?? $bgColors['indigo'] }} flex items-center justify-center">
                <span class="{{ $textColors[$color] ?? $textColors['indigo'] }}">{!! $icon !!}</span>
            </div>
        @endif
    </div>
</div>
