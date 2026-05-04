@props([
    'title',
    'description' => null,
    'icon' => null,
])

<section class="space-y-5">
    <div class="flex items-start gap-3 border-b border-slate-200/80 pb-4">
        @if (filled($icon))
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-ink text-white shadow-[0_14px_30px_rgba(19,38,63,0.14)]">
                <x-dynamic-component :component="$icon" class="size-5" />
            </span>
        @endif

        <div class="min-w-0 space-y-2">
            <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ $title }}</h2>

            @if (filled($description))
                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
            @endif
        </div>
    </div>

    <div class="space-y-5">
        {{ $slot }}
    </div>
</section>
