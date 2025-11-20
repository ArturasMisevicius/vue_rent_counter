@extends('layouts.app')

@section('title', 'Meter Reading Compliance Report')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.reports.index') }}">Reports</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Reading Compliance</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Meter Reading Compliance</h1>
            <p class="mt-2 text-sm text-gray-700">Track meter reading completion by property</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.meter-reading-compliance') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-input
                    name="month"
                    label="Month"
                    type="month"
                    :value="request('month', $month)"
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        Generate Report
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Compliance Summary -->
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Compliance Summary</x-slot>
            
            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Properties with Complete Readings</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ $propertiesWithReadings->count() }} / {{ $properties->count() }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Compliance Rate</p>
                        <p class="mt-1 text-3xl font-semibold {{ $complianceRate >= 80 ? 'text-green-600' : ($complianceRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($complianceRate, 1) }}%
                        </p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-6">
                    <div class="relative pt-1">
                        <div class="overflow-hidden h-4 text-xs flex rounded bg-gray-200">
                            <div 
                                style="width:{{ $complianceRate }}%" 
                                class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $complianceRate >= 80 ? 'bg-green-500' : ($complianceRate >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Property Details -->
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Property Details</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Property</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Meters</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Readings Submitted</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($properties as $property)
                    @php
                        $totalMeters = $property->meters->count();
                        $metersWithReadings = $property->meters->filter(fn($meter) => $meter->readings->isNotEmpty())->count();
                        $isComplete = $totalMeters > 0 && $totalMeters === $metersWithReadings;
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $totalMeters }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $metersWithReadings }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            @if($isComplete)
                                <x-status-badge status="active">Complete</x-status-badge>
                            @else
                                <x-status-badge status="inactive">Incomplete</x-status-badge>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            @if(!$isComplete)
                                <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                    Add Readings
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
        </x-card>
    </div>
</div>
@endsection
