@props([
    'light' => false,
])

@php
    $badgeClass = $light
        ? 'border-white/20 bg-white/10 text-white'
        : 'border-slate-200 bg-slate-100 text-slate-900';
    $titleClass = $light ? 'text-white' : 'text-slate-950';
    $taglineClass = $light ? 'text-white/65' : 'text-slate-500';
@endphp

<span {{ $attributes->class('inline-flex items-center gap-4') }}>
    <span class="flex size-12 items-center justify-center rounded-2xl border text-lg font-semibold {{ $badgeClass }}">
        T
    </span>

    <span class="flex flex-col">
        <span class="font-display text-xl tracking-tight {{ $titleClass }}">Tenanto</span>
        <span class="text-xs uppercase tracking-[0.24em] {{ $taglineClass }}">{{ __('auth.brand_tagline') }}</span>
    </span>
</span>
