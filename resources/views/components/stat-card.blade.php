@props(['label' => null, 'title' => null, 'value', 'icon' => null, 'color' => 'blue', 'href' => null])

@if($href)
<a href="{{ $href }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
@endif
    <div class="ds-stat-card @if($href) group-hover:-translate-y-0.5 group-hover:shadow-xl @endif">
        <div class="ds-stat-card__bar bg-gradient-to-r {{ match ($color) {
            'green' => 'from-green-500 to-emerald-400',
            'red' => 'from-red-500 to-rose-400',
            'yellow' => 'from-yellow-500 to-amber-400',
            default => 'from-indigo-500 to-sky-400',
        } }}"></div>
        <div class="ds-stat-card__body">
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
                    <p class="ds-stat-card__label">
                        {{ $label ?? $title }}
                    </p>
                    <p class="ds-stat-card__value">
                        {{ $value }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@if($href)
</a>
@endif
