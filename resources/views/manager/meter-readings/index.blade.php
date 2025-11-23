@extends('layouts.app')

@section('title', 'Meter Readings')

@section('content')
@php($meterTypeLabels = \App\Enums\MeterType::labels())
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Meter Readings</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Meter Readings</h1>
            <p class="mt-2 text-sm text-gray-700">Recorded utility consumption across all properties</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\MeterReading::class)
            <x-button href="{{ route('manager.meter-readings.create') }}">
                Add Reading
            </x-button>
            @endcan
        </div>
    </div>

    <!-- Filters and Grouping -->
    <x-card class="mt-6">
        <form method="GET" action="{{ route('manager.meter-readings.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="group_by" class="block text-sm font-medium text-gray-700">Group By</label>
                <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="none" {{ request('group_by', 'none') === 'none' ? 'selected' : '' }}>No Grouping</option>
                    <option value="property" {{ request('group_by') === 'property' ? 'selected' : '' }}>By Property</option>
                    <option value="meter_type" {{ request('group_by') === 'meter_type' ? 'selected' : '' }}>By Meter Type</option>
                </select>
            </div>

            <div>
                <label for="property_id" class="block text-sm font-medium text-gray-700">Property</label>
                <select name="property_id" id="property_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Properties</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->address }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="meter_type" class="block text-sm font-medium text-gray-700">Meter Type</label>
                <select name="meter_type" id="meter_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Types</option>
                    @foreach($meterTypeLabels as $value => $label)
                        <option value="{{ $value }}" {{ request('meter_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-button type="submit" class="w-full">
                    Apply Filters
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card class="mt-8">
        @if($groupBy === 'property')
            <!-- Grouped by Property -->
            @forelse($readings as $propertyId => $propertyReadings)
                @php
                    $property = $propertyReadings->first()->meter->property;
                @endphp
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $property->address }}
                        </a>
                        <span class="ml-2 text-sm font-normal text-gray-500">({{ $propertyReadings->count() }} readings)</span>
                    </h3>
                    
                    <x-data-table>
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Date</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meter</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zone</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Entered By</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </x-slot>

                        @foreach($propertyReadings as $reading)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->serial_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <span class="capitalize">{{ $reading->meter->type->label() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ number_format($reading->value, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $reading->zone ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $reading->enteredBy->name ?? 'N/A' }}
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        View
                                    </a>
                                    @endcan
                                    @can('update', $reading)
                                    <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </x-data-table>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-gray-500">
                    No meter readings found. 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                    @endcan
                </p>
            @endforelse

        @elseif($groupBy === 'meter_type')
            <!-- Grouped by Meter Type -->
            @forelse($readings as $meterType => $typeReadings)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="capitalize">{{ $meterTypeLabels[$meterType] ?? $meterType }}</span>
                        <span class="ml-2 text-sm font-normal text-gray-500">({{ $typeReadings->count() }} readings)</span>
                    </h3>
                    
                    <x-data-table>
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Date</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Property</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meter</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zone</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Entered By</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </x-slot>

                        @foreach($typeReadings as $reading)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <a href="{{ route('manager.properties.show', $reading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->property->address }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ $reading->meter->serial_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ number_format($reading->value, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $reading->zone ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $reading->enteredBy->name ?? 'N/A' }}
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    @can('view', $reading)
                                    <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        View
                                    </a>
                                    @endcan
                                    @can('update', $reading)
                                    <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </x-data-table>
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-gray-500">
                    No meter readings found. 
                    @can('create', App\Models\MeterReading::class)
                        <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                    @endcan
                </p>
            @endforelse

        @else
            <!-- No Grouping - Standard List -->
            <x-data-table>
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Date</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Property</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meter</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zone</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Entered By</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($readings as $reading)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                        {{ $reading->reading_date->format('M d, Y') }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <a href="{{ route('manager.properties.show', $reading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $reading->meter->property->address }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <a href="{{ route('manager.meters.show', $reading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $reading->meter->serial_number }}
                        </a>
                    </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <span class="capitalize">{{ $reading->meter->type->label() }}</span>
                            </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ number_format($reading->value, 2) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $reading->zone ?? '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $reading->enteredBy->name ?? 'N/A' }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                        <div class="flex justify-end gap-2">
                            @can('view', $reading)
                            <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </a>
                            @endcan
                            @can('update', $reading)
                            <a href="{{ route('manager.meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                Edit
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-3 py-8 text-center text-sm text-gray-500">
                        No meter readings found. 
                        @can('create', App\Models\MeterReading::class)
                            <a href="{{ route('manager.meter-readings.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </x-data-table>

            @if($readings->hasPages())
            <div class="mt-4">
                {{ $readings->links() }}
            </div>
            @endif
        @endif
    </x-card>
</div>
@endsection
