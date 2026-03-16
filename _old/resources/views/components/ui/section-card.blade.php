@props([
    'title' => null,
    'description' => null,
    'badge' => null,
])

<div {{ $attributes->class('ds-card relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-lg shadow-slate-200/70 backdrop-blur-sm') }}>
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-sky-400 to-teal-300"></div>
    <div class="px-4 py-5 sm:p-6">
        @if($title || $description || $badge)
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    @if($title)
                        <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                    @endif

                    @if($description)
                        <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
                    @endif
                </div>

                @if($badge)
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700">{{ $badge }}</span>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
