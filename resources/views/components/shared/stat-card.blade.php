@props([
    'label',
    'value',
    'icon' => null,
    'trend' => null,
    'realtime' => false,
])

<article
    @if ($realtime) wire:poll.30s @endif
    class="rounded-[1.75rem] border border-slate-200/80 bg-white/96 px-5 py-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur"
>
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $label }}</p>
            <p class="font-display text-3xl tracking-tight text-slate-950 sm:text-4xl">{{ $value }}</p>

            @if (filled($trend))
                <p class="text-sm leading-6 text-slate-600">{{ $trend }}</p>
            @endif
        </div>

        @if (filled($icon))
            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-ink text-white shadow-[0_16px_32px_rgba(19,38,63,0.18)]">
                <x-dynamic-component :component="$icon" class="size-6" />
            </div>
        @endif
    </div>
</article>
