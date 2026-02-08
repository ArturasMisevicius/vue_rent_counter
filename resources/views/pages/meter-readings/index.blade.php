@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('meter_readings.shared.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.shared.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.shared.index.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\MeterReading::class)
            <x-button href="{{ route('manager.meter-readings.create') }}">
                {{ __('meter_readings.actions.enter_new') }}
            </x-button>
            @endcan
        </div>
    </div>

    <!-- Filters and Grouping -->
    <x-card class="mt-6">
        <form method="GET" action="{{ route('manager.meter-readings.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="group_by" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.shared.index.filters.group_by') }}</label>
                <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="none" {{ $groupBy === 'none' ? 'selected' : '' }}>{{ __('meter_readings.shared.index.filters.none') }}</option>
                    <option value="property" {{ $groupBy === 'property' ? 'selected' : '' }}>{{ __('meter_readings.shared.index.filters.property') }}</option>
                    <option value="service" {{ $groupBy === 'service' ? 'selected' : '' }}>{{ __('meter_readings.shared.filters.service') }}</option>
                </select>
            </div>

            <div>
                <label for="property_id" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.shared.index.filters.property_label') }}</label>
                <select name="property_id" id="property_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('meter_readings.shared.index.filters.all_properties') }}</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->address }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="service" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.shared.filters.service') }}</label>
                <select name="service" id="service" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('meter_readings.shared.filters.all_services') }}</option>
                    @foreach($serviceFilterOptions as $value => $label)
                        <option value="{{ $value }}" {{ $serviceFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-button type="submit" class="w-full">
                    {{ __('meter_readings.shared.index.filters.apply') }}
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card class="mt-8">
        @if($groupBy === 'property')
            <!-- Grouped by Property -->
            @forelse($readings as $propertyId => $propertyReadings)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <a href="{{ route('manager.properties.show', $propertyReadings->first()->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $propertyReadings->first()->meter->property->address }}
                        </a>
                        <span class="ml-2 text-sm font-normal text-slate-500">({{ trans_choice('meter_readings.manager.index.count', $propertyReadings->count(), ['count' => $propertyReadings->count()]) }})</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <x-data-table :caption="__('meter_readings.shared.index.captions.property')">
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.shared.filters.service') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                                </th>
                            </tr>
                        </x-slot>

                        @foreach($propertyReadings as $reading)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->serial_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->meter->getServiceDisplayName() }}
                                <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ number_format($reading->value, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->zone ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.view') }}
                                    </a>
                                    @endcan
                                    @can('update', $reading)
                                    <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.edit') }}
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </x-data-table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($propertyReadings as $reading)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $reading->meter->getServiceDisplayName() }}</p>
                            </div>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.meter') }}: {{ $reading->meter->serial_number }}</p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.value') }}: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.zone') }}: {{ $reading->zone ?? '—' }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @can('view', $reading)
                                <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.view') }}
                                </a>
                                @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.edit') }}
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('meter_readings.shared.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                </p>
            @endforelse

        @elseif($groupBy === 'service')
            <!-- Grouped by Service -->
            @forelse($readings as $serviceKey => $typeReadings)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="capitalize">{{ $typeReadings->first()?->meter?->getServiceDisplayName() ?? $serviceKey }}</span>
                        @if(!empty($typeReadings->first()?->meter?->getUnitOfMeasurement()))
                            <span class="ml-2 text-xs font-semibold text-slate-500">({{ $typeReadings->first()?->meter?->getUnitOfMeasurement() }})</span>
                        @endif
                        <span class="ml-2 text-sm font-normal text-slate-500">({{ trans_choice('meter_readings.manager.index.count', $typeReadings->count(), ['count' => $typeReadings->count()]) }})</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <x-data-table :caption="__('meter_readings.shared.filters.services_group')">
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.property') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                                </th>
                            </tr>
                        </x-slot>

                        @foreach($typeReadings as $reading)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="{{ route('manager.properties.show', $reading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->property->address }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->serial_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ number_format($reading->value, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->zone ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.view') }}
                                    </a>
                                    @endcan
                                    @can('update', $reading)
                                    <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.edit') }}
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </x-data-table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($typeReadings as $reading)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $reading->meter->property->address }}</p>
                            </div>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.meter') }} {{ $reading->meter->serial_number }}</p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.value') }} <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.zone') }} {{ $reading->zone ?? '—' }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @can('view', $reading)
                                <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.view') }}
                                </a>
                                @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.edit') }}
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('meter_readings.shared.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                </p>
            @endforelse

        @else
            <!-- No Grouping - Standard List -->
            <div class="hidden sm:block">
            <x-data-table :caption="__('meter_readings.shared.captions.list')">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.property') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.shared.filters.service') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                            <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($readings as $reading)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                        {{ $reading->reading_date->format('M d, Y') }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <a href="{{ route('manager.properties.show', $reading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $reading->meter->property->address }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $reading->meter->serial_number }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $reading->meter->getServiceDisplayName() }}
                        <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ number_format($reading->value, 2) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $reading->zone ?? '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                        <div class="flex justify-end gap-2">
                            @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('meter_readings.actions.view') }}
                            </a>
                            @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.edit') }}
                                    </a>
                                    @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('meter_readings.shared.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                    </td>
                </tr>
                @endforelse
            </x-data-table>
            </div>
            <div class="sm:hidden space-y-3">
                @forelse($readings as $reading)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                        <p class="text-xs font-semibold text-slate-500">{{ $reading->meter->getServiceDisplayName() }}</p>
                    </div>
                    <p class="text-xs text-slate-600">{{ $reading->meter->property->address }}</p>
                    <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.meter') }} {{ $reading->meter->serial_number }}</p>
                    <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.value') }} <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                    <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.zone') }} {{ $reading->zone ?? '—' }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('view', $reading)
                        <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('meter_readings.actions.view') }}
                        </a>
                        @endcan
                        @can('update', $reading)
                        <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('meter_readings.actions.edit') }}
                        </a>
                        @endcan
                    </div>
                </div>
                @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('meter_readings.shared.index.empty.text') }}
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-700 font-semibold">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                </div>
                @endforelse
            </div>

            @if($readings->hasPages())
            <div class="mt-4">
                {{ $readings->links() }}
            </div>
            @endif
        @endif
    </x-card>
</div>
@endsection
@break

@case('tenant')
@extends('layouts.tenant')

@section('title', __('meter_readings.shared.title'))

@section('tenant-content')
<x-tenant.page :title="__('meter_readings.shared.title')" :description="__('meter_readings.shared.description')" x-data="consumptionHistory()">
    <x-tenant.quick-actions />

    <x-tenant.section-card :title="__('meter_readings.shared.filters.title')" :description="__('meter_readings.shared.filters.description')">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.shared.filters.service') }}</label>
                <select x-model="filters.service" @change="applyFilters()" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('meter_readings.shared.filters.all_services') }}</option>

                    @if($serviceOptions->isNotEmpty())
                        <optgroup label="{{ __('meter_readings.shared.filters.services_group') }}">
                            @foreach($serviceOptions as $service)
                                <option value="utility:{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif

                    @if($legacyTypeOptions->isNotEmpty())
                        <optgroup label="{{ __('meter_readings.shared.filters.legacy_group') }}">
                            @foreach($legacyTypeOptions as $type)
                                <option value="type:{{ $type }}">{{ $meterTypeLabels[$type] ?? $type }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.shared.filters.date_from') }}</label>
                <input x-model="filters.dateFrom" @change="applyFilters()" type="date" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.shared.filters.date_to') }}</label>
                <input x-model="filters.dateTo" @change="applyFilters()" type="date" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </x-tenant.section-card>

    <x-tenant.section-card :title="__('meter_readings.shared.submit.title')" :description="__('meter_readings.shared.submit.description')">
        @if(($properties ?? collect())->isEmpty())
            <p class="text-sm text-slate-600">{{ __('meter_readings.shared.submit.no_property') }}</p>
        @else
        <form method="POST" action="{{ route('tenant.meter-readings.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3" x-data="{ meterSupportsZones: false }" x-init="meterSupportsZones = ($el.querySelector('select[name=meter_id]')?.selectedOptions[0]?.dataset.supportsZones === 'true')">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.shared.submit.meter') }}</label>
                <select name="meter_id" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required @change="meterSupportsZones = ($event.target.selectedOptions[0]?.dataset.supportsZones === 'true')">
                    @foreach(($properties ?? collect()) as $property)
                        @foreach($property->meters as $meter)
                            <option value="{{ $meter->id }}" data-supports-zones="{{ $meter->supports_zones ? 'true' : 'false' }}" {{ old('meter_id') == $meter->id ? 'selected' : '' }}>
                                {{ $meter->serial_number }} ({{ $meter->getServiceDisplayName() }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('meter_id') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.shared.submit.reading_date') }}</label>
                <input type="date" name="reading_date" value="{{ old('reading_date') }}" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @error('reading_date') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.shared.submit.value') }}</label>
                <input type="number" step="0.01" name="value" value="{{ old('value') }}" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @error('value') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div x-show="meterSupportsZones">
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tables.zone') }}</label>
                <input type="text" name="zone" value="{{ old('zone') }}" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="day" :disabled="!meterSupportsZones" :required="meterSupportsZones">
                @error('zone') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                    {{ __('meter_readings.shared.submit.button') }}
                </button>
            </div>
        </form>
        @endif
    </x-tenant.section-card>

    <x-tenant.stack gap="6">
        <template x-for="(meterReadings, meterName) in groupedReadings" :key="meterName">
            <x-tenant.section-card>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900" x-text="meterName"></h3>
                </div>

                <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200/80 shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600 sm:pl-6">{{ __('meter_readings.shared.table.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('meter_readings.shared.table.reading') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('meter_readings.shared.table.consumption') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('meter_readings.shared.table.zone') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <template x-for="(reading, index) in meterReadings" :key="reading.id">
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6" x-text="formatDate(reading.reading_date)"></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600" x-text="reading.value"></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        <span x-show="index < meterReadings.length - 1" x-text="calculateConsumption(reading.value, meterReadings[index + 1].value)"></span>
                                        <span x-show="index === meterReadings.length - 1" class="text-slate-400">-</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        <span x-show="reading.zone" x-text="reading.zone" class="inline-flex items-center rounded-md bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-700/10"></span>
                                        <span x-show="!reading.zone" class="text-slate-400">-</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-tenant.stack gap="3" class="sm:hidden">
                    <template x-for="(reading, index) in meterReadings" :key="`mobile-${reading.id}`">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900" x-text="formatDate(reading.reading_date)"></p>
                                <p class="text-xs font-semibold text-slate-500" x-text="reading.zone || '—'"></p>
                            </div>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ __('meter_readings.shared.table.reading') }}: <span class="font-semibold" x-text="reading.value"></span>
                            </p>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ __('meter_readings.shared.table.consumption') }}:
                                <span x-show="index < meterReadings.length - 1" class="font-semibold" x-text="calculateConsumption(reading.value, meterReadings[index + 1].value)"></span>
                                <span x-show="index === meterReadings.length - 1" class="text-slate-400">—</span>
                            </p>
                        </div>
                    </template>
                </x-tenant.stack>
            </x-tenant.section-card>
        </template>

        <div x-show="Object.keys(groupedReadings).length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white/80 py-12 text-center shadow-sm">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ __('meter_readings.shared.empty.title') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('meter_readings.shared.empty.description') }}</p>
        </div>
    </x-tenant.stack>
</x-tenant.page>

@push('scripts')
<script>
function consumptionHistory() {
    return {
        readings: @json($readings instanceof \Illuminate\Pagination\AbstractPaginator ? $readings->items() : $readings),
        meterTypeLabels: @json($meterTypeLabels),
        filters: {
            service: '{{ request('service') }}',
            dateFrom: '{{ request('date_from') }}',
            dateTo: '{{ request('date_to') }}'
        },
        init() {
            // Default to last 12 months if no date filter was provided
            if (!this.filters.dateFrom) {
                const d = new Date();
                d.setFullYear(d.getFullYear() - 1);
                this.filters.dateFrom = d.toISOString().slice(0, 10);
            }
        },
        
        get groupedReadings() {
            let filtered = this.readings;
            
            // Apply filters
            if (this.filters.service) {
                const [kind, value] = this.filters.service.split(':', 2);

                if (kind === 'utility') {
                    const serviceId = parseInt(value, 10);
                    filtered = filtered.filter(r => this.getUtilityServiceId(r.meter) === serviceId);
                }

                if (kind === 'type') {
                    filtered = filtered.filter(r => r.meter.type === value && this.getUtilityServiceId(r.meter) === null);
                }
            }
            
            if (this.filters.dateFrom) {
                filtered = filtered.filter(r => r.reading_date >= this.filters.dateFrom);
            }
            
            if (this.filters.dateTo) {
                filtered = filtered.filter(r => r.reading_date <= this.filters.dateTo);
            }
            
            // Group by meter
            const grouped = {};
            filtered.forEach(reading => {
                const key = this.formatMeterLabel(reading.meter);
                if (!grouped[key]) {
                    grouped[key] = [];
                }
                grouped[key].push(reading);
            });
            
            // Sort readings within each group by date descending
            Object.keys(grouped).forEach(key => {
                grouped[key].sort((a, b) => new Date(b.reading_date) - new Date(a.reading_date));
            });
            
            return grouped;
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('lt-LT', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },
        
        formatMeterType(type) {
            return this.meterTypeLabels[type] || type;
        },

        getUtilityServiceId(meter) {
            return meter?.service_configuration?.utility_service?.id ?? null;
        },

        getUtilityServiceName(meter) {
            return meter?.service_configuration?.utility_service?.name ?? null;
        },

        formatMeterLabel(meter) {
            const serviceName = this.getUtilityServiceName(meter);

            if (serviceName) {
                return `${meter.serial_number} (${serviceName})`;
            }

            return `${meter.serial_number} (${this.formatMeterType(meter.type)})`;
        },
        
        calculateConsumption(current, previous) {
            const consumption = current - previous;
            return consumption >= 0 ? consumption.toFixed(2) : '0.00';
        },
        
        applyFilters() {
            // Filters are reactive, so this just triggers recalculation
        }
    }
}
</script>
@endpush
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('meter_readings.shared.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.shared.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.shared.index.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\MeterReading::class)
            <x-button href="{{ route('manager.meter-readings.create') }}">
                {{ __('meter_readings.actions.enter_new') }}
            </x-button>
            @endcan
        </div>
    </div>

    <!-- Filters and Grouping -->
    <x-card class="mt-6">
        <form method="GET" action="{{ route('manager.meter-readings.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="group_by" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.shared.index.filters.group_by') }}</label>
                <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="none" {{ $groupBy === 'none' ? 'selected' : '' }}>{{ __('meter_readings.shared.index.filters.none') }}</option>
                    <option value="property" {{ $groupBy === 'property' ? 'selected' : '' }}>{{ __('meter_readings.shared.index.filters.property') }}</option>
                    <option value="service" {{ $groupBy === 'service' ? 'selected' : '' }}>{{ __('meter_readings.shared.filters.service') }}</option>
                </select>
            </div>

            <div>
                <label for="property_id" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.shared.index.filters.property_label') }}</label>
                <select name="property_id" id="property_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('meter_readings.shared.index.filters.all_properties') }}</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->address }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="service" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.shared.filters.service') }}</label>
                <select name="service" id="service" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('meter_readings.shared.filters.all_services') }}</option>
                    @foreach($serviceFilterOptions as $value => $label)
                        <option value="{{ $value }}" {{ $serviceFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-button type="submit" class="w-full">
                    {{ __('meter_readings.shared.index.filters.apply') }}
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card class="mt-8">
        @if($groupBy === 'property')
            <!-- Grouped by Property -->
            @forelse($readings as $propertyId => $propertyReadings)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <a href="{{ route('manager.properties.show', $propertyReadings->first()->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $propertyReadings->first()->meter->property->address }}
                        </a>
                        <span class="ml-2 text-sm font-normal text-slate-500">({{ trans_choice('meter_readings.manager.index.count', $propertyReadings->count(), ['count' => $propertyReadings->count()]) }})</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <x-data-table :caption="__('meter_readings.shared.index.captions.property')">
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.shared.filters.service') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                                </th>
                            </tr>
                        </x-slot>

                        @foreach($propertyReadings as $reading)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->serial_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->meter->getServiceDisplayName() }}
                                <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ number_format($reading->value, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->zone ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.view') }}
                                    </a>
                                    @endcan
                                    @can('update', $reading)
                                    <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.edit') }}
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </x-data-table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($propertyReadings as $reading)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $reading->meter->getServiceDisplayName() }}</p>
                            </div>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.meter') }}: {{ $reading->meter->serial_number }}</p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.value') }}: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.zone') }}: {{ $reading->zone ?? '—' }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @can('view', $reading)
                                <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.view') }}
                                </a>
                                @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.edit') }}
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('meter_readings.shared.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                </p>
            @endforelse

        @elseif($groupBy === 'service')
            <!-- Grouped by Service -->
            @forelse($readings as $serviceKey => $typeReadings)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="capitalize">{{ $typeReadings->first()?->meter?->getServiceDisplayName() ?? $serviceKey }}</span>
                        @if(!empty($typeReadings->first()?->meter?->getUnitOfMeasurement()))
                            <span class="ml-2 text-xs font-semibold text-slate-500">({{ $typeReadings->first()?->meter?->getUnitOfMeasurement() }})</span>
                        @endif
                        <span class="ml-2 text-sm font-normal text-slate-500">({{ trans_choice('meter_readings.manager.index.count', $typeReadings->count(), ['count' => $typeReadings->count()]) }})</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <x-data-table :caption="__('meter_readings.shared.filters.services_group')">
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.property') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                                </th>
                            </tr>
                        </x-slot>

                        @foreach($typeReadings as $reading)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="{{ route('manager.properties.show', $reading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->property->address }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->serial_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ number_format($reading->value, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->zone ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.view') }}
                                    </a>
                                    @endcan
                                    @can('update', $reading)
                                    <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.edit') }}
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </x-data-table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($typeReadings as $reading)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $reading->meter->property->address }}</p>
                            </div>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.meter') }} {{ $reading->meter->serial_number }}</p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.value') }} <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.zone') }} {{ $reading->zone ?? '—' }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @can('view', $reading)
                                <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.view') }}
                                </a>
                                @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('meter_readings.actions.edit') }}
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('meter_readings.shared.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                </p>
            @endforelse

        @else
            <!-- No Grouping - Standard List -->
            <div class="hidden sm:block">
            <x-data-table :caption="__('meter_readings.shared.captions.list')">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.property') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.shared.filters.service') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                            <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($readings as $reading)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                        {{ $reading->reading_date->format('M d, Y') }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <a href="{{ route('manager.properties.show', $reading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $reading->meter->property->address }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $reading->meter->serial_number }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $reading->meter->getServiceDisplayName() }}
                        <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ number_format($reading->value, 2) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $reading->zone ?? '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                        <div class="flex justify-end gap-2">
                            @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('meter_readings.actions.view') }}
                            </a>
                            @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ __('meter_readings.actions.edit') }}
                                    </a>
                                    @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('meter_readings.shared.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                    </td>
                </tr>
                @endforelse
            </x-data-table>
            </div>
            <div class="sm:hidden space-y-3">
                @forelse($readings as $reading)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                        <p class="text-xs font-semibold text-slate-500">{{ $reading->meter->getServiceDisplayName() }}</p>
                    </div>
                    <p class="text-xs text-slate-600">{{ $reading->meter->property->address }}</p>
                    <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.meter') }} {{ $reading->meter->serial_number }}</p>
                    <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.value') }} <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                    <p class="text-xs text-slate-600">{{ __('meter_readings.shared.mobile.zone') }} {{ $reading->zone ?? '—' }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('view', $reading)
                        <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('meter_readings.actions.view') }}
                        </a>
                        @endcan
                        @can('update', $reading)
                        <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('meter_readings.actions.edit') }}
                        </a>
                        @endcan
                    </div>
                </div>
                @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('meter_readings.shared.index.empty.text') }}
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-700 font-semibold">{{ __('meter_readings.shared.index.empty.cta') }}</a>
                    @endcan
                </div>
                @endforelse
            </div>

            @if($readings->hasPages())
            <div class="mt-4">
                {{ $readings->links() }}
            </div>
            @endif
        @endif
    </x-card>
</div>
@endsection
@endswitch
