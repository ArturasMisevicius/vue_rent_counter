@php
    $role = auth()->user()?->role?->value;
@endphp

@extends(auth()->user()?->role?->value === 'tenant' ? 'layouts.tenant' : 'layouts.app')

@switch($role)
@case('tenant')
@section('title', __('shared.property.title'))

@section('tenant-content')
<x-tenant.page :title="__('shared.property.title')" :description="__('shared.property.description')">
    @if(!$property)
        <x-tenant.alert type="warning" :title="__('shared.property.no_property_title')">
            {{ __('shared.property.no_property_body') }}
        </x-tenant.alert>
    @else
        <x-tenant.section-card :title="__('shared.property.info_title')">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($property->type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.area') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->area_sqm }} m²</dd>
                </div>
                @if($property->building)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.building') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->building->display_name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.building_address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->building->address }}</dd>
                </div>
                @endif
            </dl>
        </x-tenant.section-card>

        <x-tenant.section-card :title="__('shared.property.meters_title')" :description="__('shared.property.meters_description')">
            @if($property->meters && $property->meters->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($property->meters as $meter)
                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.meters.labels.type') }}</p>
                                    <p class="text-base font-semibold text-slate-900">{{ $meter->getServiceDisplayName() }}</p>
                                </div>
                                <x-status-badge status="active">{{ __('shared.property.meter_status') }}</x-status-badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-700">
                                <span class="font-semibold text-slate-800">{{ __('shared.property.labels.serial') }}</span> {{ $meter->serial_number }}
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('shared.property.view_details') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('shared.property.no_meters') }}</p>
            @endif
        </x-tenant.section-card>

        <x-tenant.section-card :title="__('shared.property.services_title')" :description="__('shared.property.services_description')" class="mt-6">
            @if($property->serviceConfigurations && $property->serviceConfigurations->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($property->serviceConfigurations as $configuration)
                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-900">{{ $configuration->utilityService?->name ?? __('app.common.na') }}</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $configuration->pricing_model?->label() ?? $configuration->pricing_model?->value }}
                                        @if($configuration->utilityService?->unit_of_measurement)
                                            • {{ $configuration->utilityService->unit_of_measurement }}
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-700">
                                    {{ $configuration->requiresConsumptionData() ? __('shared.property.service_dynamic') : __('shared.property.service_fixed') }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.property.service_meters') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $configuration->meters?->count() ?? 0 }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.property.service_input') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $configuration->requiresConsumptionData() ? __('shared.property.service_input_meter') : __('shared.property.service_input_none') }}
                                    </p>
                                </div>
                            </div>

                            @if($configuration->requiresConsumptionData())
                                <div class="mt-4">
                                    <a href="{{ route('tenant.meter-readings.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        {{ __('shared.property.submit_reading') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('shared.property.no_services') }}</p>
            @endif
        </x-tenant.section-card>
    @endif
</x-tenant.page>
@endsection
@break

@default
@section('title', __('shared.property.title'))

@section('tenant-content')
<x-tenant.page :title="__('shared.property.title')" :description="__('shared.property.description')">
    @if(!$property)
        <x-tenant.alert type="warning" :title="__('shared.property.no_property_title')">
            {{ __('shared.property.no_property_body') }}
        </x-tenant.alert>
    @else
        <x-tenant.section-card :title="__('shared.property.info_title')">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($property->type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.area') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->area_sqm }} m²</dd>
                </div>
                @if($property->building)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.building') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->building->display_name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.building_address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->building->address }}</dd>
                </div>
                @endif
            </dl>
        </x-tenant.section-card>

        <x-tenant.section-card :title="__('shared.property.meters_title')" :description="__('shared.property.meters_description')">
            @if($property->meters && $property->meters->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($property->meters as $meter)
                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.meters.labels.type') }}</p>
                                    <p class="text-base font-semibold text-slate-900">{{ $meter->getServiceDisplayName() }}</p>
                                </div>
                                <x-status-badge status="active">{{ __('shared.property.meter_status') }}</x-status-badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-700">
                                <span class="font-semibold text-slate-800">{{ __('shared.property.labels.serial') }}</span> {{ $meter->serial_number }}
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('shared.property.view_details') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('shared.property.no_meters') }}</p>
            @endif
        </x-tenant.section-card>

        <x-tenant.section-card :title="__('shared.property.services_title')" :description="__('shared.property.services_description')" class="mt-6">
            @if($property->serviceConfigurations && $property->serviceConfigurations->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($property->serviceConfigurations as $configuration)
                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-900">{{ $configuration->utilityService?->name ?? __('app.common.na') }}</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $configuration->pricing_model?->label() ?? $configuration->pricing_model?->value }}
                                        @if($configuration->utilityService?->unit_of_measurement)
                                            • {{ $configuration->utilityService->unit_of_measurement }}
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-700">
                                    {{ $configuration->requiresConsumptionData() ? __('shared.property.service_dynamic') : __('shared.property.service_fixed') }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.property.service_meters') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $configuration->meters?->count() ?? 0 }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.property.service_input') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $configuration->requiresConsumptionData() ? __('shared.property.service_input_meter') : __('shared.property.service_input_none') }}
                                    </p>
                                </div>
                            </div>

                            @if($configuration->requiresConsumptionData())
                                <div class="mt-4">
                                    <a href="{{ route('tenant.meter-readings.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        {{ __('shared.property.submit_reading') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('shared.property.no_services') }}</p>
            @endif
        </x-tenant.section-card>
    @endif
</x-tenant.page>
@endsection
@endswitch
