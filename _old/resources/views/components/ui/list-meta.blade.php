@props([
    'label',
])

<div {{ $attributes->class('rounded-2xl border border-slate-200/70 bg-slate-50/80 px-3 py-3') }}>
    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $label }}</p>
    <div class="mt-1 text-sm text-slate-700">
        {{ $slot }}
    </div>
</div>
