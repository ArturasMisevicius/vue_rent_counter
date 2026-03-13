@props(['label', 'value', 'valueColor' => 'text-slate-900'])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-indigo-50 shadow-md shadow-slate-200/60']) }}>
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-sky-400"></div>
    <div class="px-5 py-6">
        <p class="truncate text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</p>
        <p class="mt-2 text-3xl font-semibold tracking-tight {{ $valueColor }}">{{ $value }}</p>
    </div>
</div>
