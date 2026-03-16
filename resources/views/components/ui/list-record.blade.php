@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-lg shadow-slate-200/60 backdrop-blur-sm') }}>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-sky-400 to-teal-300"></div>

    <div class="mt-1 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h3 class="truncate text-base font-semibold text-slate-900">{{ $title }}</h3>

                @if($subtitle)
                    <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($aside)
                <div class="shrink-0">
                    {{ $aside }}
                </div>
            @endisset
        </div>

        @isset($meta)
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {{ $meta }}
            </div>
        @endisset

        @isset($actions)
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                {{ $actions }}
            </div>
        @endisset

        {{ $slot }}
    </div>
</div>
