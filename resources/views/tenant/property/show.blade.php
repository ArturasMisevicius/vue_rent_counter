<x-layouts.app
    :title="__('tenant.pages.property.title').' · '.config('app.name', 'Tenanto')"
    :heading="$summary['property_name'] ?: __('tenant.pages.property.heading')"
    :subtitle="$summary['property_address']"
    :show-tenant-navigation="true"
    :breadcrumbs="[
        ['label' => __('tenant.navigation.home'), 'url' => route('tenant.home')],
        ['label' => __('tenant.pages.property.heading')],
    ]"
>
    <x-slot:actions>
        <a
            href="{{ route('tenant.readings.create') }}"
            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
        >
            {{ __('tenant.actions.submit_new_reading') }}
        </a>
    </x-slot:actions>

    <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-600">
            {{ __('tenant.pages.property.assigned_since', ['date' => $summary['assigned_since']]) }}
        </div>

        <div class="mt-8 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.property.meters_heading') }}</h3>
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
                    <x-shared.empty-state
                        icon="heroicon-m-building-office-2"
                        :title="__('tenant.pages.property.meters_heading')"
                        :description="__('tenant.messages.no_property_meters')"
                    >
                        <a
                            href="{{ route('tenant.readings.create') }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
                        >
                            {{ __('tenant.actions.submit_new_reading') }}
                        </a>
                    </x-shared.empty-state>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>
