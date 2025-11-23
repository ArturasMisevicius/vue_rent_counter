@props(['label', 'value', 'icon' => null])

<div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/90 shadow-lg shadow-slate-200/60 backdrop-blur-sm">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400"></div>
    <div class="p-5">
        <div class="flex items-center gap-4">
            @if($icon)
                <div class="flex-shrink-0 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white shadow-glow">
                    {{ $icon }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">
                    {{ $label }}
                </p>
                <p class="mt-1 text-3xl font-semibold text-slate-900 font-display leading-tight">
                    {{ $value }}
                </p>
            </div>
        </div>
    </div>
</div>
