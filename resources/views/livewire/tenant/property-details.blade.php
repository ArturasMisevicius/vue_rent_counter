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

    $tenantContactLine = collect([
        $summary['tenant_email'] ?? null,
        $summary['tenant_phone'] ?? null,
    ])->filter()->implode(' · ');
@endphp

<x-tenant.page>
    <x-shared.page-header icon="heroicon-m-home-modern" :title="__('tenant.pages.property.heading')" :subtitle="__('tenant.pages.property.description')">
        @if (($summary['has_assignment'] ?? false) && filled($summary['assigned_since'] ?? null))
            <x-slot:actions>
                <x-tenant.detail-card
                    icon="heroicon-m-calendar-days"
                    :label="__('admin.properties.fields.assigned_since')"
                    :value="$summary['assigned_since']"
                    class="px-5 py-4 text-left"
                />
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
        <div class="flex flex-col gap-4 md:flex-row md:flex-wrap" data-tenant-layout-section="stats">
            <div class="min-w-0 flex-1 md:min-w-[16rem]">
                <x-shared.stat-card
                    :label="__('tenant.pages.property.tenant_information')"
                    :value="$summary['tenant_name']"
                    :trend="$tenantContactLine"
                    icon="heroicon-m-user-circle"
                />
            </div>

            <div class="min-w-0 flex-1 md:min-w-[16rem]">
                <x-shared.stat-card
                    :label="__('tenant.navigation.property')"
                    :value="$summary['property_display_name'] ?? __('dashboard.not_available')"
                    :trend="$summary['property_building_name'] ?: ($summary['property_address'] ?: __('dashboard.not_available'))"
                    icon="heroicon-m-home-modern"
                />
            </div>

            <div class="min-w-0 flex-1 md:min-w-[16rem]">
                <x-shared.stat-card
                    :label="__('tenant.pages.property.meters_heading')"
                    :value="$summary['meter_count'] ?? 0"
                    :trend="$summary['property_unit_number'] ? __('admin.properties.fields.unit_number').': '.$summary['property_unit_number'] : ($summary['property_floor_area'] ?: __('dashboard.not_available'))"
                    icon="heroicon-m-beaker"
                />
            </div>

            <div class="min-w-0 flex-1 md:min-w-[16rem]">
                <x-shared.stat-card
                    :label="__('tenant.pages.property.history_heading')"
                    :value="$summary['history_count'] ?? 0"
                    :trend="$historyScope"
                    icon="heroicon-m-document-text"
                />
            </div>
        </div>

        <x-tenant.split>
            <x-tenant.main-panel>
                <x-tenant.section-heading
                    icon="heroicon-m-home-modern"
                    :eyebrow="__('tenant.pages.property.eyebrow')"
                    :title="$summary['property_display_name']"
                    :description="$summary['property_address']"
                    class="[&>div:first-child>span]:size-12 [&>div:first-child>span>svg]:size-6 [&_h2]:text-3xl"
                >
                    @if (filled($summary['property_building_name'] ?? null))
                        <x-slot:actions>
                            <p class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                {{ __('tenant.pages.home.building_label', ['building' => $summary['property_building_name']]) }}
                            </p>
                        </x-slot:actions>
                    @endif
                </x-tenant.section-heading>

                <div class="flex flex-col gap-4 md:flex-row md:flex-wrap">
                    <x-tenant.detail-card class="min-w-0 flex-1 md:min-w-[16rem]" :label="__('admin.properties.fields.building')" :value="$summary['property_building_name'] ?: __('dashboard.not_available')" />
                    <x-tenant.detail-card class="min-w-0 flex-1 md:min-w-[16rem]" :label="__('admin.properties.fields.unit_number')" :value="$summary['property_unit_number'] ?: __('dashboard.not_available')" />
                    <x-tenant.detail-card class="min-w-0 flex-1 md:min-w-[16rem]" :label="__('admin.properties.fields.floor_area_sqm')" :value="$summary['property_floor_area'] ?: __('dashboard.not_available')" />
                    <x-tenant.detail-card class="min-w-0 flex-1 md:min-w-[16rem]" :label="__('admin.properties.fields.assigned_since')" :value="$summary['assigned_since'] ?: __('dashboard.not_available')" />
                </div>

                <div class="space-y-4">
                    <x-tenant.section-heading
                        icon="heroicon-m-beaker"
                        icon-tone="soft"
                        :eyebrow="__('tenant.pages.property.meters_heading')"
                        :title="__('tenant.pages.property.meters_heading')"
                    />

                    <div class="space-y-3">
                        @forelse ($summary['meters'] as $meter)
                            <x-tenant.card wire:key="tenant-property-meter-{{ $meter['id'] }}">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-slate-600 shadow-sm">
                                            <x-heroicon-m-bolt class="size-5" />
                                        </span>
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ $meter['display_name'] }}</p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ $meter['identifier'] }}@if (filled($meter['unit'] ?? null)) · {{ $meter['unit'] }} @endif
                                            </p>
                                        </div>
                                    </div>

                                    <p class="text-sm leading-6 text-slate-600 sm:max-w-xs sm:text-right">{{ $meter['last_reading'] }}</p>
                                </div>
                            </x-tenant.card>
                        @empty
                            <x-shared.empty-state
                                icon="heroicon-m-building-office-2"
                                :title="__('tenant.pages.property.meters_heading')"
                                :description="__('tenant.messages.no_property_meters')"
                            />
                        @endforelse
                    </div>
                </div>
            </x-tenant.main-panel>

            <x-tenant.aside-panel>
                <x-tenant.card>
                    <x-tenant.section-heading
                        icon="heroicon-m-user-circle"
                        icon-tone="white"
                        :eyebrow="__('tenant.pages.property.tenant_information')"
                        :title="$summary['tenant_name']"
                    />
                    @if (filled($summary['tenant_email'] ?? null))
                        <p class="mt-2 text-sm text-slate-600">{{ $summary['tenant_email'] }}</p>
                    @endif
                    @if (filled($summary['tenant_phone'] ?? null))
                        <p class="mt-2 text-sm text-slate-600">{{ $summary['tenant_phone'] }}</p>
                    @endif
                </x-tenant.card>

                <x-tenant.card tone="white">
                    <x-tenant.section-heading
                        icon="heroicon-m-map-pin"
                        icon-tone="soft"
                        :eyebrow="__('tenant.navigation.property')"
                        :title="$summary['property_display_name']"
                    />

                    <dl class="mt-4 space-y-4 text-sm text-slate-600">
                        <div class="space-y-1">
                            <dt class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('admin.buildings.columns.address') }}</dt>
                            <dd>{{ $summary['property_address'] ?: __('dashboard.not_available') }}</dd>
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                            <div class="min-w-0 flex-1 space-y-1 sm:min-w-[8rem]">
                                <dt class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('admin.properties.fields.unit_number') }}</dt>
                                <dd>{{ $summary['property_unit_number'] ?: __('dashboard.not_available') }}</dd>
                            </div>

                            <div class="min-w-0 flex-1 space-y-1 sm:min-w-[8rem]">
                                <dt class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('admin.properties.fields.floor_area_sqm') }}</dt>
                                <dd>{{ $summary['property_floor_area'] ?: __('dashboard.not_available') }}</dd>
                            </div>
                        </div>
                    </dl>
                </x-tenant.card>
            </x-tenant.aside-panel>
        </x-tenant.split>

        <x-tenant.main-panel>
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <x-tenant.section-heading
                    icon="heroicon-m-document-text"
                    :eyebrow="__('tenant.pages.property.history_heading')"
                    :title="__('tenant.pages.property.history_heading')"
                    :description="__('tenant.pages.property.history_description')"
                />

                <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                    <x-tenant.select-field id="propertyHistoryYear" :label="__('tenant.pages.property.history_year')" wire:model.live="selectedYear" class="min-w-0 flex-1 sm:min-w-[16rem]">
                            <option value="all">{{ __('tenant.pages.property.all_years') }}</option>
                            @foreach ($summary['available_years'] as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                    </x-tenant.select-field>

                    <x-tenant.select-field id="propertyHistoryMonth" :label="__('tenant.pages.property.history_month')" wire:model.live="selectedMonth" class="min-w-0 flex-1 sm:min-w-[16rem]">
                            <option value="all">{{ __('tenant.pages.property.all_months') }}</option>
                            @foreach ($summary['available_months'] as $month)
                                <option value="{{ $month }}">{{ \Illuminate\Support\Carbon::createFromDate(null, (int) $month, 1)->translatedFormat('F') }}</option>
                            @endforeach
                    </x-tenant.select-field>
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
        </x-tenant.main-panel>
    @endif
</x-tenant.page>
