@php
    $selectedYear = (string) ($summary['selected_year'] ?? 'all');
    $selectedMonth = (string) ($summary['selected_month'] ?? 'all');
    $selectedMonthLabel = $selectedMonth === 'all'
        ? __('tenant.pages.property.all_months')
        : \Illuminate\Support\Carbon::createFromDate(null, (int) $selectedMonth, 1)->translatedFormat('F');
    $historyScope = $selectedYear === 'all'
        ? __('tenant.pages.property.all_years')
        : $selectedYear;

    if ($selectedMonth !== 'all') {
        $historyScope .= ' • '.$selectedMonthLabel;
    }
@endphp

<div class="space-y-6">
    <x-shared.page-header :title="__('tenant.pages.property.heading')" :subtitle="__('tenant.pages.property.description')">
        @if (($summary['has_assignment'] ?? false) && filled($summary['assigned_since'] ?? null))
            <x-slot:actions>
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-4 text-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.assigned_since') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ $summary['assigned_since'] }}</p>
                </div>
            </x-slot:actions>
        @endif
    </x-shared.page-header>

    @if (! ($summary['has_assignment'] ?? false))
        <x-shared.empty-state
            icon="heroicon-m-home-modern"
            :title="__('tenant.pages.home.unassigned_title')"
            :description="__('tenant.pages.home.unassigned_description')"
        />
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-shared.stat-card
                :label="__('tenant.pages.property.tenant_information')"
                :value="$summary['tenant_name']"
                :trend="$summary['tenant_email']"
            />

            <x-shared.stat-card
                :label="__('tenant.navigation.property')"
                :value="$summary['property_name'] ?? __('dashboard.not_available')"
                :trend="$summary['property_building_name'] ?: ($summary['property_address'] ?: __('dashboard.not_available'))"
                icon="heroicon-m-home-modern"
            />

            <x-shared.stat-card
                :label="__('tenant.pages.property.meters_heading')"
                :value="$summary['meter_count'] ?? 0"
                :trend="$summary['property_unit_number'] ? __('admin.properties.fields.unit_number').': '.$summary['property_unit_number'] : ($summary['property_floor_area'] ?: __('dashboard.not_available'))"
                icon="heroicon-m-beaker"
            />

            <x-shared.stat-card
                :label="__('tenant.pages.property.history_heading')"
                :value="$summary['history_count'] ?? 0"
                :trend="$historyScope"
                icon="heroicon-m-document-text"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-8">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.eyebrow') }}</p>
                    <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ $summary['property_name'] }}</h2>

                    @if (filled($summary['property_building_name'] ?? null))
                        <p class="text-sm font-medium text-slate-700">{{ __('tenant.pages.home.building_label', ['building' => $summary['property_building_name']]) }}</p>
                    @endif

                    @if (filled($summary['property_address'] ?? null))
                        <p class="text-sm leading-6 text-slate-500">{{ $summary['property_address'] }}</p>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.building') }}</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $summary['property_building_name'] ?: __('dashboard.not_available') }}</p>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.unit_number') }}</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $summary['property_unit_number'] ?: __('dashboard.not_available') }}</p>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.floor_area_sqm') }}</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $summary['property_floor_area'] ?: __('dashboard.not_available') }}</p>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.assigned_since') }}</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $summary['assigned_since'] ?: __('dashboard.not_available') }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.meters_heading') }}</p>
                        <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.property.meters_heading') }}</h3>
                    </div>

                    <div class="space-y-3">
                        @forelse ($summary['meters'] as $meter)
                            <article wire:key="tenant-property-meter-{{ $meter['id'] }}" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $meter['name'] }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $meter['identifier'] }}@if (filled($meter['unit'] ?? null)) · {{ $meter['unit'] }} @endif
                                        </p>
                                    </div>

                                    <p class="text-sm leading-6 text-slate-600 sm:max-w-xs sm:text-right">{{ $meter['last_reading'] }}</p>
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

            <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-8">
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.tenant_information') }}</p>
                    <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $summary['tenant_name'] }}</p>
                    @if (filled($summary['tenant_email'] ?? null))
                        <p class="mt-2 text-sm text-slate-600">{{ $summary['tenant_email'] }}</p>
                    @endif
                </div>

                <div class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.navigation.property') }}</p>
                        <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ $summary['property_name'] }}</h3>
                    </div>

                    <dl class="mt-4 space-y-4 text-sm text-slate-600">
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.buildings.columns.address') }}</dt>
                            <dd>{{ $summary['property_address'] ?: __('dashboard.not_available') }}</dd>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.unit_number') }}</dt>
                                <dd>{{ $summary['property_unit_number'] ?: __('dashboard.not_available') }}</dd>
                            </div>

                            <div class="space-y-1">
                                <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.properties.fields.floor_area_sqm') }}</dt>
                                <dd>{{ $summary['property_floor_area'] ?: __('dashboard.not_available') }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>

        <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.history_heading') }}</p>
                    <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.property.history_heading') }}</h2>
                    <p class="text-sm leading-6 text-slate-600">{{ __('tenant.pages.property.history_description') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-4 py-4">
                        <label for="propertyHistoryYear" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.property.history_year') }}</label>
                        <select
                            id="propertyHistoryYear"
                            wire:model.live="selectedYear"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                        >
                            <option value="all">{{ __('tenant.pages.property.all_years') }}</option>
                            @foreach ($summary['available_years'] as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-4 py-4">
                        <label for="propertyHistoryMonth" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.property.history_month') }}</label>
                        <select
                            id="propertyHistoryMonth"
                            wire:model.live="selectedMonth"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                        >
                            <option value="all">{{ __('tenant.pages.property.all_months') }}</option>
                            @foreach ($summary['available_months'] as $month)
                                <option value="{{ $month }}">{{ \Illuminate\Support\Carbon::createFromDate(null, (int) $month, 1)->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if (($summary['history_count'] ?? 0) > 0)
                <x-shared.data-table-wrapper>
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('tenant.pages.property.history_month') }}</th>
                                <th class="px-4 py-3">{{ __('tenant.pages.property.meter_label') }}</th>
                                <th class="px-4 py-3">{{ __('tenant.pages.readings.reading_value') }}</th>
                                <th class="px-4 py-3">{{ __('tenant.pages.readings.reading_date') }}</th>
                                <th class="px-4 py-3">{{ __('tenant.pages.property.submitted_via') }}</th>
                                <th class="px-4 py-3">{{ __('tenant.pages.property.submitted_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($summary['history_entries'] as $entry)
                                <tr wire:key="tenant-property-history-{{ $entry['id'] }}">
                                    <td class="px-4 py-4 text-slate-600">{{ $entry['month_label'] }}</td>
                                    <td class="px-4 py-4">
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ $entry['meter_name'] }}</p>
                                            <p class="text-sm text-slate-500">{{ $entry['meter_identifier'] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 font-semibold text-slate-950">{{ $entry['reading_value'] }} {{ $entry['unit'] }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $entry['reading_date'] }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $entry['submitted_via'] }}</td>
                                    <td class="px-4 py-4 text-slate-600">
                                        <div>
                                            <p>{{ $entry['submitted_at'] }}</p>
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">{{ $entry['status_label'] }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-shared.data-table-wrapper>
            @else
                <x-shared.empty-state
                    icon="heroicon-m-document-text"
                    :title="__('tenant.pages.property.history_heading')"
                    :description="__('tenant.pages.property.history_empty')"
                />
            @endif
        </section>
    @endif
</div>
