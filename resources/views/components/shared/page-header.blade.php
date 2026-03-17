@props([
    'title',
    'subtitle' => null,
])

<header class="mb-8 rounded-[2rem] border border-white/60 bg-white/92 px-6 py-6 shadow-[0_24px_70px_rgba(15,23,42,0.14)] backdrop-blur sm:px-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-3">
            <h1 class="font-display text-4xl tracking-tight text-slate-950 sm:text-5xl">{{ $title }}</h1>

            @if (filled($subtitle))
                <p class="max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap gap-3 lg:justify-end">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
