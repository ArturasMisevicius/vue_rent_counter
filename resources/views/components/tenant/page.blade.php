@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-200/70 backdrop-blur-sm']) }}>
    <div class="pointer-events-none absolute inset-0 opacity-60">
        <div class="absolute -left-10 -top-16 h-52 w-52 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="absolute -right-12 top-12 h-40 w-40 rounded-full bg-sky-400/10 blur-3xl"></div>
    </div>
    <div class="relative p-6 sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">{{ __('dashboard.tenant.space_label') }}</p>
                <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ $title }}</h1>
                @if($description)
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ $description }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="flex flex-col items-start gap-3 sm:items-end">
                    {{ $actions }}
                </div>
            @endif
        </div>

        <x-tenant.stack class="mt-6">
            {{ $slot }}
        </x-tenant.stack>
    </div>
</section>
