@props([
    'title',
    'eyebrow' => null,
    'icon' => null,
    'subtitle' => null,
])

<header class="mb-8 rounded-[2rem] border border-white/60 bg-white/92 px-6 py-6 shadow-[0_24px_70px_rgba(15,23,42,0.14)] backdrop-blur sm:px-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
            @if (filled($icon))
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-ink text-white shadow-[0_18px_38px_rgba(19,38,63,0.18)]">
                    <x-dynamic-component :component="$icon" class="size-7" />
                </span>
            @endif

            <div class="min-w-0 space-y-3">
                @if (filled($eyebrow))
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ $eyebrow }}</p>
                @endif

                <h1 class="font-display text-4xl tracking-tight text-slate-950 sm:text-5xl">{{ $title }}</h1>

                @if (filled($subtitle))
                    <p class="max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap gap-3 lg:justify-end">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
