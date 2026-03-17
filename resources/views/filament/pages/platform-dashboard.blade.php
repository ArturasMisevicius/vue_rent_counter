<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ __('dashboard.platform_eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.platform_heading') }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('dashboard.platform_body') }}</p>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
