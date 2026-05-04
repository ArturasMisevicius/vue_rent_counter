@props([
    'title',
    'description' => null,
    'eyebrow' => null,
    'icon' => null,
    'iconTone' => 'ink',
])

<div {{ $attributes->class('flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between') }}>
    <div class="flex min-w-0 items-start gap-3">
        @if (filled($icon))
            <span
                @class([
                    'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                    'bg-brand-ink text-white shadow-[0_14px_30px_rgba(19,38,63,0.14)]' => $iconTone === 'ink',
                    'bg-white text-slate-600 shadow-sm' => $iconTone === 'white',
                    'bg-slate-100 text-slate-700' => $iconTone === 'soft',
                    'bg-emerald-50 text-emerald-700' => $iconTone === 'success',
                    'bg-white text-amber-700 shadow-sm' => $iconTone === 'warning',
                ])
            >
                <x-dynamic-component :component="$icon" class="size-5" />
            </span>
        @endif

        <div class="min-w-0 space-y-2">
            @if (filled($eyebrow))
                <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ $eyebrow }}</p>
            @endif

            <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ $title }}</h2>

            @if (filled($description))
                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
            {{ $actions }}
        </div>
    @endisset
</div>
