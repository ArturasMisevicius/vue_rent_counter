@extends('layouts.tenant')

@section('title', __('tenant.property.title'))

@section('tenant-content')
<x-tenant.page :title="__('tenant.property.title')" :description="__('tenant.property.description')">
    @if(!$property)
        <x-tenant.alert type="warning" :title="__('tenant.property.no_property_title')">
            {{ __('tenant.property.no_property_body') }}
        </x-tenant.alert>
    @else
        <x-tenant.section-card :title="__('tenant.property.info_title')">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.property.labels.address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.property.labels.type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($property->type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.property.labels.area') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->area_sqm }} m²</dd>
                </div>
                @if($property->building)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.property.labels.building') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->building->display_name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.property.labels.building_address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $property->building->address }}</dd>
                </div>
                @endif
            </dl>
        </x-tenant.section-card>

        <x-tenant.section-card :title="__('tenant.property.meters_title')" :description="__('tenant.property.meters_description')">
            @if($property->meters && $property->meters->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($property->meters as $meter)
                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meters.labels.type') }}</p>
                                    <p class="text-base font-semibold text-slate-900">{{ enum_label($meter->type) }}</p>
                                </div>
                                <x-status-badge status="active">{{ __('tenant.property.meter_status') }}</x-status-badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-700">
                                <span class="font-semibold text-slate-800">{{ __('tenant.property.labels.serial') }}</span> {{ $meter->serial_number }}
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('tenant.property.view_details') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('tenant.property.no_meters') }}</p>
            @endif
        </x-tenant.section-card>
    @endif
</x-tenant.page>
@endsection
