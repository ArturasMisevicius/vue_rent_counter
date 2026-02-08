@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

<div {{ $attributes->class('space-y-6') }}>
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-200/60 backdrop-blur-sm">
        <div class="pointer-events-none absolute inset-0 opacity-60">
            <div class="absolute -left-14 -top-20 h-56 w-56 rounded-full bg-indigo-500/10 blur-3xl"></div>
            <div class="absolute right-0 top-0 h-44 w-44 rounded-full bg-sky-400/10 blur-3xl"></div>
        </div>
        <div class="relative px-5 py-6 sm:px-8 sm:py-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    @if($eyebrow)
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-500">{{ $eyebrow }}</p>
                    @endif
                    <h1 class="mt-1 text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $title }}</h1>
                    @if($description)
                        <p class="mt-2 max-w-3xl text-sm text-slate-600 sm:text-base">{{ $description }}</p>
                    @endif
                </div>
                @isset($actions)
                    <div class="flex flex-wrap gap-3">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        </div>
    </section>

    <div class="space-y-6">
        {{ $slot }}
    </div>
</div>
