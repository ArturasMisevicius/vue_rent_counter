@props([
    'title',
    'description' => null,
])

<div class="space-y-6">
    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-r from-indigo-600 via-sky-500 to-indigo-500 text-white shadow-2xl shadow-indigo-500/20">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute -left-12 -top-12 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
        </div>
        <div class="relative px-5 py-6 sm:px-8 sm:py-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-indigo-100/90">{{ __('dashboard.shared.workspace_label') }}</p>
                    <h1 class="text-2xl sm:text-3xl font-semibold leading-tight text-white">{{ $title }}</h1>
                    @if($description)
                        <p class="max-w-3xl text-sm sm:text-base text-indigo-50/90">{{ $description }}</p>
                    @endif

                    @isset($meta)
                        <div class="mt-3 flex flex-wrap gap-2 text-[12px] font-semibold text-indigo-50/80">
                            {{ $meta }}
                        </div>
                    @endisset
                </div>

                @isset($actions)
                    <div class="flex flex-wrap gap-3">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        </div>
    </div>

    <div class="space-y-6">
        {{ $slot }}
    </div>
</div>
