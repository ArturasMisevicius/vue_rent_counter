<x-layouts.tenant
    :title="__('tenant.pages.readings.title').' · '.config('app.name', 'Tenanto')"
    :breadcrumbs="$breadcrumbs"
    :heading="__('tenant.pages.readings.heading')"
>
    <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.navigation.readings') }}</p>
            <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('tenant.pages.readings.heading') }}</h2>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.pages.readings.description') }}</p>
        </div>

        @if ($currentProperty)
            <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Assigned Property</p>
                <p class="mt-2 font-semibold text-slate-950">{{ $currentProperty->name }}</p>
            </div>
        @endif

        <div class="mt-8 space-y-3">
            @forelse ($meters as $meter)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $meter->name }}</p>
                            <p class="text-sm text-slate-500">{{ $meter->identifier }} · {{ $meter->unit }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            Available
                        </span>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No meters are currently available for submission.</p>
            @endforelse
        </div>
    </section>
</x-layouts.tenant>
