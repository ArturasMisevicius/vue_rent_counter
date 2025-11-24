@extends('layouts.app')

@section('title', __('meter_readings.manager.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('app.nav.readings') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.manager.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.manager.index.description') }}</p>
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
                <label for="group_by" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.manager.index.filters.group_by') }}</label>
                <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="none" {{ request('group_by', 'none') === 'none' ? 'selected' : '' }}>{{ __('meter_readings.manager.index.filters.none') }}</option>
                    <option value="property" {{ request('group_by') === 'property' ? 'selected' : '' }}>{{ __('meter_readings.manager.index.filters.property') }}</option>
                    <option value="meter_type" {{ request('group_by') === 'meter_type' ? 'selected' : '' }}>{{ __('meter_readings.manager.index.filters.meter_type') }}</option>
                </select>
            </div>

            <div>
                <label for="property_id" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.manager.index.filters.property_label') }}</label>
                <select name="property_id" id="property_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('meter_readings.manager.index.filters.all_properties') }}</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->address }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="meter_type" class="block text-sm font-medium text-slate-700">{{ __('meter_readings.manager.index.filters.meter_type_label') }}</label>
                <select name="meter_type" id="meter_type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('meter_readings.manager.index.filters.all_types') }}</option>
                    @foreach($meterTypeLabels as $value => $label)
                        <option value="{{ $value }}" {{ request('meter_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-button type="submit" class="w-full">
                    {{ __('meter_readings.manager.index.filters.apply') }}
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
                    <x-data-table :caption="__('meter_readings.manager.index.captions.property')">
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.type') }}</th>
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
                                <span class="capitalize">{{ $reading->meter->type->label() }}</span>
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
                                <p class="text-xs font-semibold text-slate-500 capitalize">{{ $reading->meter->type->label() }}</p>
                            </div>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.meter') }}: {{ $reading->meter->serial_number }}</p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.value') }}: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-xs text-slate-600">{{ __('meter_readings.tables.zone') }}: {{ $reading->zone ?? '—' }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ __('meter_readings.tables.entered_by') }}: {{ $reading->enteredBy->name ?? __('meter_readings.na') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @can('view', $reading)
                                <a href="{{ route('manager.meter-readings.show', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    View
                                </a>
                                @endcan
                                @can('update', $reading)
                                <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Edit
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('meter_readings.manager.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.manager.index.empty.cta') }}</a>
                    @endcan
                </p>
            @endforelse

        @elseif($groupBy === 'meter_type')
            <!-- Grouped by Meter Type -->
            @forelse($readings as $meterType => $typeReadings)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="capitalize">{{ $meterTypeLabels[$meterType] ?? $meterType }}</span>
                        <span class="ml-2 text-sm font-normal text-slate-500">({{ trans_choice('meter_readings.manager.index.count', $typeReadings->count(), ['count' => $typeReadings->count()]) }})</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <x-data-table :caption="__('meter_readings.manager.index.captions.meter_type')">
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
                            <p class="text-xs text-slate-600">Meter: {{ $reading->meter->serial_number }}</p>
                            <p class="text-xs text-slate-600">Value: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-xs text-slate-600">Zone: {{ $reading->zone ?? '—' }}</p>
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
                    {{ __('meter_readings.manager.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.manager.index.empty.cta') }}</a>
                    @endcan
                </p>
            @endforelse

        @else
            <!-- No Grouping - Standard List -->
            <div class="hidden sm:block">
            <x-data-table caption="Meter readings list">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.property') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.meter') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.labels.type') }}</th>
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
                                <span class="capitalize">{{ $reading->meter->type->label() }}</span>
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
                        {{ __('meter_readings.manager.index.empty.text') }} 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.manager.index.empty.cta') }}</a>
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
                        <p class="text-xs font-semibold text-slate-500 capitalize">{{ $reading->meter->type->label() }}</p>
                    </div>
                    <p class="text-xs text-slate-600">{{ $reading->meter->property->address }}</p>
                    <p class="text-xs text-slate-600">Meter: {{ $reading->meter->serial_number }}</p>
                    <p class="text-xs text-slate-600">Value: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                    <p class="text-xs text-slate-600">Zone: {{ $reading->zone ?? '—' }}</p>
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
                    {{ __('meter_readings.manager.index.empty.text') }}
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-700 font-semibold">{{ __('meter_readings.manager.index.empty.cta') }}</a>
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
