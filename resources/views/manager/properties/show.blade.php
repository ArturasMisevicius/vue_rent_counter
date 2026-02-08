@extends('layouts.app')

@section('title', __('properties.pages.manager_show.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $property->address }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('properties.pages.manager_show.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $property)
            <x-button href="{{ route('manager.properties.edit', $property) }}" variant="secondary">
                {{ __('properties.pages.manager_show.edit_property') }}
            </x-button>
            @endcan
            @can('delete', $property)
            <form action="{{ route('manager.properties.destroy', $property) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('properties.pages.manager_show.delete_confirm') }}');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('properties.pages.manager_show.delete_property') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Property Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('properties.pages.manager_show.info_title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.address') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $property->address }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.type') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="capitalize">{{ enum_label($property->type) }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.area') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ number_format($property->area_sqm, 2) }} m²</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.building') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        @if($property->building)
                            <a href="{{ route('manager.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->building->address }}
                            </a>
                        @else
                            <span class="text-slate-400">{{ __('properties.pages.manager_show.building_missing') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Current Tenant -->
        <x-card>
            <x-slot name="title">{{ __('properties.pages.manager_show.current_tenant_title') }}</x-slot>
            
            @if($property->tenants->isNotEmpty())
                @php $currentTenant = $property->tenants->first(); @endphp
                <dl class="divide-y divide-slate-100">
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.tenant_labels.name') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $currentTenant->name }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.tenant_labels.email') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $currentTenant->email }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.tenant_labels.phone') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $currentTenant->phone ?? __('properties.pages.manager_show.tenant_na') }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('properties.pages.manager_show.no_tenant') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Meters -->
    <div class="mt-8">
        <x-card>
            <div class="flex items-center justify-between">
                <x-slot name="title">{{ __('properties.pages.manager_show.meters_title') }}</x-slot>
                @can('create', App\Models\Meter::class)
                <x-button href="{{ route('manager.meters.create', ['property_id' => $property->id]) }}" variant="secondary" size="sm">
                    {{ __('properties.pages.manager_show.add_meter') }}
                </x-button>
                @endcan
            </div>
            
            @if($property->meters->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('properties.pages.manager_show.meters_headers.serial') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('properties.pages.manager_show.meters_headers.type') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('properties.pages.manager_show.meters_headers.installation') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('properties.pages.manager_show.meters_headers.latest') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('properties.pages.manager_show.meters_headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($property->meters as $meter)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $meter->serial_number }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $meter->getServiceDisplayName() }}
                            <span class="text-xs text-slate-400">({{ $meter->getUnitOfMeasurement() }})</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $meter->installation_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            @if($meter->readings->isNotEmpty())
                                {{ number_format($meter->readings->first()->value, 2) }}
                                <span class="text-slate-400 text-xs">({{ $meter->readings->first()->reading_date->format('M d') }})</span>
                            @else
                                <span class="text-slate-400">{{ __('properties.pages.manager_show.latest_none') }}</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('properties.pages.manager_show.view') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-slate-500">{{ __('properties.pages.manager_show.no_meters_installed') }}</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
