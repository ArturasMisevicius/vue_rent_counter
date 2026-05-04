@props([
    'href' => null,
    'tone' => 'muted',
    'wireNavigate' => false,
])

@php
    $classes = [
        'rounded-3xl border px-5 py-5',
        'border-slate-200 bg-slate-50' => $tone === 'muted',
        'border-slate-200 bg-white' => $tone === 'white',
        'border-emerald-200/70 bg-white shadow-sm' => $tone === 'success',
        'border-amber-200 bg-amber-50/70' => $tone === 'warning',
        'transition hover:border-slate-300 hover:bg-white focus:outline-none focus:ring-2 focus:ring-brand-mint/35' => filled($href),
    ];
@endphp

@if (filled($href))
    <a href="{{ $href }}" @if ($wireNavigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <article {{ $attributes->class($classes) }}>
        {{ $slot }}
    </article>
@endif
