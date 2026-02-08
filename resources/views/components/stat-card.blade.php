@props(['label' => null, 'title' => null, 'value', 'icon' => null, 'color' => 'blue', 'href' => null])

@if($href)
<a href="{{ $href }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
@endif
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/90 shadow-lg shadow-slate-200/60 backdrop-blur-sm transition duration-200 @if($href) group-hover:-translate-y-0.5 group-hover:shadow-xl @endif">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ match ($color) {
            'green' => 'from-green-500 to-emerald-400',
            'red' => 'from-red-500 to-rose-400',
            'yellow' => 'from-yellow-500 to-amber-400',
            default => 'from-indigo-500 to-sky-400',
        } }}"></div>
        <div class="p-5">
            <div class="flex items-center gap-4">
                @if($icon)
                    <div class="flex-shrink-0 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ match ($color) {
                        'green' => 'from-green-500 to-emerald-400',
                        'red' => 'from-red-500 to-rose-400',
                        'yellow' => 'from-yellow-500 to-amber-400',
                        default => 'from-indigo-500 to-sky-400',
                    } }} text-white shadow-glow">
                        {{ $icon }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">
                        {{ $label ?? $title }}
                    </p>
                    <p class="mt-1 text-3xl font-semibold text-slate-900 font-display leading-tight">
                        {{ $value }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@if($href)
</a>
@endif
