@props([
    'href' => route('login'),
    'light' => false,
])

@php
    $logoClasses = $light
        ? 'border-white/20 bg-white/10 text-white shadow-lg shadow-slate-950/20'
        : 'border-brand-ink/10 bg-brand-ink text-white shadow-lg shadow-slate-950/10';
    $nameClasses = $light ? 'text-white' : 'text-slate-950';
    $taglineClasses = $light ? 'text-white/65' : 'text-slate-500';
@endphp

<a href="{{ $href }}" {{ $attributes->class(['inline-flex items-center gap-4 transition hover:opacity-90']) }}>
    <span class="{{ $logoClasses }} flex size-12 items-center justify-center rounded-2xl border text-lg font-semibold">
        T
    </span>

    <span class="flex min-w-0 flex-col">
        <span class="{{ $nameClasses }} font-display text-xl tracking-tight">Tenanto</span>
        <span class="{{ $taglineClasses }} text-[0.65rem] uppercase tracking-[0.28em]">{{ __('auth.brand_tagline') }}</span>
    </span>
</a>
