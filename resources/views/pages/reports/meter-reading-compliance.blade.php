@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('reports.shared.compliance.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('reports.shared.compliance.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('reports.shared.compliance.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <form method="GET" action="{{ route('manager.reports.compliance.export') }}" class="inline">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="building_id" value="{{ $buildingId }}">
                <x-button type="submit" variant="secondary">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('reports.shared.compliance.export') }}
                </x-button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.meter-reading-compliance') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-form-input
                    name="month"
                    label="{{ __('reports.shared.compliance.filters.month') }}"
                    type="month"
                    :value="request('month', $month)"
                />

                <x-form-select
                    name="building_id"
                    label="{{ __('reports.shared.compliance.filters.building') }}"
                    :options="$buildings->pluck('name', 'id')->toArray()"
                    :selected="$buildingId"
                    placeholder="{{ __('reports.shared.compliance.filters.placeholders.building') }}"
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        {{ __('reports.shared.compliance.filters.submit') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.summary.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-sm text-emerald-700">{{ __('reports.shared.compliance.summary.complete.label') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $propertiesWithReadings->count() }}</p>
                        <p class="text-xs text-emerald-600">{{ __('reports.shared.compliance.summary.complete.description') }}</p>
                    </div>
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
                        <p class="text-sm text-yellow-700">{{ __('reports.shared.compliance.summary.partial.label') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-yellow-900">{{ $propertiesWithPartialReadings->count() }}</p>
                        <p class="text-xs text-yellow-600">{{ __('reports.shared.compliance.summary.partial.description') }}</p>
                    </div>
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-sm text-red-700">{{ __('reports.shared.compliance.summary.none.label') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-red-900">{{ $propertiesWithNoReadings->count() }}</p>
                        <p class="text-xs text-red-600">{{ __('reports.shared.compliance.summary.none.description') }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">{{ __('reports.shared.compliance.summary.overall') }}</p>
                            <p class="mt-1 text-3xl font-semibold {{ $complianceRate >= 80 ? 'text-green-600' : ($complianceRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($complianceRate, 1) }}%
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-500">{{ __('reports.shared.compliance.summary.properties') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-slate-900">
                                {{ $propertiesWithReadings->count() }} / {{ $properties->count() }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="relative pt-1">
                            <div class="overflow-hidden h-4 text-xs flex rounded bg-slate-200">
                                <div 
                                    style="width:{{ $complianceRate }}%" 
                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $complianceRate >= 80 ? 'bg-green-500' : ($complianceRate >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    @if($complianceByBuilding->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.by_building.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="space-y-3">
                    @foreach($complianceByBuilding as $building => $data)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $building }}</p>
                                <p class="text-xs text-slate-600">{{ __('reports.shared.compliance.by_building.properties', ['complete' => $data['complete'], 'total' => $data['total']]) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold {{ $data['rate'] >= 80 ? 'text-green-600' : ($data['rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($data['rate'], 1) }}%
                                </p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="overflow-hidden h-2 text-xs flex rounded bg-slate-200">
                                <div 
                                    style="width:{{ $data['rate'] }}%" 
                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $data['rate'] >= 80 ? 'bg-green-500' : ($data['rate'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                ></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if(!empty($complianceByMeterType))
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.by_meter_type.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($complianceByMeterType as $type => $data)
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900 capitalize">{{ $type }}</p>
                        <p class="mt-1 text-2xl font-semibold {{ $data['rate'] >= 80 ? 'text-green-600' : ($data['rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($data['rate'], 1) }}%
                        </p>
                        <p class="text-xs text-slate-600">{{ __('reports.shared.compliance.by_meter_type.meters', ['complete' => $data['complete'], 'total' => $data['total']]) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.details.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="{{ __('reports.shared.compliance.details.caption') }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('reports.shared.compliance.details.headers.property') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.building') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.total_meters') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.readings_submitted') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.status') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('reports.shared.compliance.details.headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($properties as $property)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $property->building?->name ?? __('reports.common.na') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $propertyCompliance[$property->id]['total_meters'] ?? 0 }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $propertyCompliance[$property->id]['meters_with_readings'] ?? 0 }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @if($propertyCompliance[$property->id]['is_complete'] ?? false)
                                <x-status-badge status="active">{{ __('reports.shared.compliance.summary.complete.label') }}</x-status-badge>
                            @elseif($propertyCompliance[$property->id]['is_partial'] ?? false)
                                <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.partial.label') }}</x-status-badge>
                            @else
                                <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.none.label') }}</x-status-badge>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            @if(!($propertyCompliance[$property->id]['is_complete'] ?? false))
                                <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ __('reports.shared.compliance.details.add_readings') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($properties as $property)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $property->address }}</p>
                                <p class="text-xs text-slate-600 mt-1">{{ $property->building?->name ?? __('reports.common.na') }}</p>
                                <p class="text-xs text-slate-600 mt-1">{{ __('reports.shared.compliance.details.mobile.meters') }} {{ $propertyCompliance[$property->id]['total_meters'] ?? 0 }} | {{ __('reports.shared.compliance.details.mobile.readings') }} {{ $propertyCompliance[$property->id]['meters_with_readings'] ?? 0 }}</p>
                            </div>
                            <div>
                                @if($propertyCompliance[$property->id]['is_complete'] ?? false)
                                    <x-status-badge status="active">{{ __('reports.shared.compliance.summary.complete.label') }}</x-status-badge>
                                @elseif($propertyCompliance[$property->id]['is_partial'] ?? false)
                                    <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.partial.label') }}</x-status-badge>
                                @else
                                    <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.none.label') }}</x-status-badge>
                                @endif
                            </div>
                        </div>
                        @if(!($propertyCompliance[$property->id]['is_complete'] ?? false))
                        <div class="mt-3">
                            <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('reports.shared.compliance.details.add_readings') }}
                            </a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('reports.shared.compliance.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('reports.shared.compliance.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('reports.shared.compliance.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <form method="GET" action="{{ route('manager.reports.compliance.export') }}" class="inline">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="building_id" value="{{ $buildingId }}">
                <x-button type="submit" variant="secondary">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('reports.shared.compliance.export') }}
                </x-button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.meter-reading-compliance') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-form-input
                    name="month"
                    label="{{ __('reports.shared.compliance.filters.month') }}"
                    type="month"
                    :value="request('month', $month)"
                />

                <x-form-select
                    name="building_id"
                    label="{{ __('reports.shared.compliance.filters.building') }}"
                    :options="$buildings->pluck('name', 'id')->toArray()"
                    :selected="$buildingId"
                    placeholder="{{ __('reports.shared.compliance.filters.placeholders.building') }}"
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        {{ __('reports.shared.compliance.filters.submit') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.summary.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-sm text-emerald-700">{{ __('reports.shared.compliance.summary.complete.label') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $propertiesWithReadings->count() }}</p>
                        <p class="text-xs text-emerald-600">{{ __('reports.shared.compliance.summary.complete.description') }}</p>
                    </div>
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
                        <p class="text-sm text-yellow-700">{{ __('reports.shared.compliance.summary.partial.label') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-yellow-900">{{ $propertiesWithPartialReadings->count() }}</p>
                        <p class="text-xs text-yellow-600">{{ __('reports.shared.compliance.summary.partial.description') }}</p>
                    </div>
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-sm text-red-700">{{ __('reports.shared.compliance.summary.none.label') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-red-900">{{ $propertiesWithNoReadings->count() }}</p>
                        <p class="text-xs text-red-600">{{ __('reports.shared.compliance.summary.none.description') }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">{{ __('reports.shared.compliance.summary.overall') }}</p>
                            <p class="mt-1 text-3xl font-semibold {{ $complianceRate >= 80 ? 'text-green-600' : ($complianceRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($complianceRate, 1) }}%
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-500">{{ __('reports.shared.compliance.summary.properties') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-slate-900">
                                {{ $propertiesWithReadings->count() }} / {{ $properties->count() }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="relative pt-1">
                            <div class="overflow-hidden h-4 text-xs flex rounded bg-slate-200">
                                <div 
                                    style="width:{{ $complianceRate }}%" 
                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $complianceRate >= 80 ? 'bg-green-500' : ($complianceRate >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    @if($complianceByBuilding->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.by_building.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="space-y-3">
                    @foreach($complianceByBuilding as $building => $data)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $building }}</p>
                                <p class="text-xs text-slate-600">{{ __('reports.shared.compliance.by_building.properties', ['complete' => $data['complete'], 'total' => $data['total']]) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold {{ $data['rate'] >= 80 ? 'text-green-600' : ($data['rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($data['rate'], 1) }}%
                                </p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="overflow-hidden h-2 text-xs flex rounded bg-slate-200">
                                <div 
                                    style="width:{{ $data['rate'] }}%" 
                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $data['rate'] >= 80 ? 'bg-green-500' : ($data['rate'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                ></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if(!empty($complianceByMeterType))
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.by_meter_type.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($complianceByMeterType as $type => $data)
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900 capitalize">{{ $type }}</p>
                        <p class="mt-1 text-2xl font-semibold {{ $data['rate'] >= 80 ? 'text-green-600' : ($data['rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($data['rate'], 1) }}%
                        </p>
                        <p class="text-xs text-slate-600">{{ __('reports.shared.compliance.by_meter_type.meters', ['complete' => $data['complete'], 'total' => $data['total']]) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.compliance.details.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="{{ __('reports.shared.compliance.details.caption') }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('reports.shared.compliance.details.headers.property') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.building') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.total_meters') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.readings_submitted') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.compliance.details.headers.status') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('reports.shared.compliance.details.headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($properties as $property)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $property->building?->name ?? __('reports.common.na') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $propertyCompliance[$property->id]['total_meters'] ?? 0 }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $propertyCompliance[$property->id]['meters_with_readings'] ?? 0 }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @if($propertyCompliance[$property->id]['is_complete'] ?? false)
                                <x-status-badge status="active">{{ __('reports.shared.compliance.summary.complete.label') }}</x-status-badge>
                            @elseif($propertyCompliance[$property->id]['is_partial'] ?? false)
                                <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.partial.label') }}</x-status-badge>
                            @else
                                <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.none.label') }}</x-status-badge>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            @if(!($propertyCompliance[$property->id]['is_complete'] ?? false))
                                <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ __('reports.shared.compliance.details.add_readings') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($properties as $property)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $property->address }}</p>
                                <p class="text-xs text-slate-600 mt-1">{{ $property->building?->name ?? __('reports.common.na') }}</p>
                                <p class="text-xs text-slate-600 mt-1">{{ __('reports.shared.compliance.details.mobile.meters') }} {{ $propertyCompliance[$property->id]['total_meters'] ?? 0 }} | {{ __('reports.shared.compliance.details.mobile.readings') }} {{ $propertyCompliance[$property->id]['meters_with_readings'] ?? 0 }}</p>
                            </div>
                            <div>
                                @if($propertyCompliance[$property->id]['is_complete'] ?? false)
                                    <x-status-badge status="active">{{ __('reports.shared.compliance.summary.complete.label') }}</x-status-badge>
                                @elseif($propertyCompliance[$property->id]['is_partial'] ?? false)
                                    <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.partial.label') }}</x-status-badge>
                                @else
                                    <x-status-badge status="inactive">{{ __('reports.shared.compliance.summary.none.label') }}</x-status-badge>
                                @endif
                            </div>
                        </div>
                        @if(!($propertyCompliance[$property->id]['is_complete'] ?? false))
                        <div class="mt-3">
                            <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('reports.shared.compliance.details.add_readings') }}
                            </a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
@endswitch
