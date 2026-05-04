@props([
    'href' => null,
    'icon' => null,
    'type' => 'button',
    'variant' => 'secondary',
    'wireNavigate' => false,
])

@php
    $classes = [
        'inline-flex min-h-11 touch-manipulation items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-mint/35 disabled:cursor-wait disabled:opacity-70',
        'bg-brand-ink text-white hover:bg-slate-900' => $variant === 'primary',
        'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' => $variant === 'secondary',
        'bg-slate-50 text-slate-700 hover:bg-white' => $variant === 'soft',
        'border border-amber-200 bg-white text-slate-800 shadow-sm hover:bg-amber-100 focus:ring-amber-400/40' => $variant === 'warning',
    ];
@endphp

@if (filled($href))
    <a href="{{ $href }}" @if ($wireNavigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        @if (filled($icon))
            <x-dynamic-component :component="$icon" class="size-4 shrink-0" />
        @endif

        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if (filled($icon))
            <x-dynamic-component :component="$icon" class="size-4 shrink-0" />
        @endif

        {{ $slot }}
    </button>
@endif
