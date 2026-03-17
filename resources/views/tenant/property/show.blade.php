<x-layouts.tenant
    :title="__('tenant.pages.property.title').' · '.config('app.name', 'Tenanto')"
    :heading="__('tenant.pages.property.heading')"
    :breadcrumbs="[
        ['label' => __('tenant.navigation.home'), 'url' => route('tenant.home')],
        ['label' => __('tenant.pages.property.heading')],
    ]"
>
    <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.pages.property.eyebrow') }}</p>
            <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ $summary['property_name'] }}</h2>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ $summary['property_address'] }}</p>
            <p class="text-sm text-slate-500">{{ __('tenant.pages.property.assigned_since', ['date' => $summary['assigned_since']]) }}</p>
        </div>

        <div class="mt-8 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.property.meters_heading') }}</h3>
                <a
                    href="{{ route('tenant.readings.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    {{ __('tenant.actions.submit_new_reading') }}
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($summary['meters'] as $meter)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $meter['name'] }}</p>
                                <p class="text-sm text-slate-500">{{ $meter['identifier'] }} · {{ $meter['unit'] }}</p>
                            </div>
                            @if ($meter['has_reading'])
                                <p class="text-sm text-slate-600">{{ $meter['last_reading'] }}</p>
                            @else
                                <p class="text-sm text-slate-600">{{ __('tenant.pages.property.last_reading_prefix') }} <a href="{{ route('tenant.readings.create') }}" class="font-semibold text-brand-ink underline decoration-brand-mint/60 underline-offset-4">{{ __('tenant.pages.property.none_recorded_yet') }}</a></p>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state
                        :heading="__('tenant.pages.property.meters_heading')"
                        :description="__('tenant.messages.no_property_meters')"
                    />
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.tenant>
