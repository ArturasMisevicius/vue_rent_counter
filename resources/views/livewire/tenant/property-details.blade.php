<div class="space-y-6">
    @if (! ($summary['has_assignment'] ?? false))
        <x-shared.empty-state
            icon="heroicon-m-home-modern"
            :title="__('tenant.pages.home.unassigned_title')"
            :description="__('tenant.pages.home.unassigned_description')"
        />
    @else
        <section class="rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-8">
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-600">
                {{ __('tenant.pages.property.assigned_since', ['date' => $summary['assigned_since']]) }}
            </div>

            <div class="mt-8 space-y-4">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.eyebrow') }}</p>
                    <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ $summary['property_name'] }}</h2>
                    @if ($summary['property_address'])
                        <p class="text-sm text-slate-500">{{ $summary['property_address'] }}</p>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse ($summary['meters'] as $meter)
                        <article wire:key="tenant-property-meter-{{ $meter['id'] }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $meter['name'] }}</p>
                                    <p class="text-sm text-slate-500">{{ $meter['identifier'] }} · {{ $meter['unit'] }}</p>
                                </div>

                                <p class="text-sm text-slate-600">{{ $meter['last_reading'] }}</p>
                            </div>
                        </article>
                    @empty
                        <x-shared.empty-state
                            icon="heroicon-m-building-office-2"
                            :title="__('tenant.pages.property.meters_heading')"
                            :description="__('tenant.messages.no_property_meters')"
                        />
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</div>
