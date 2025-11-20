@extends('layouts.app')

@section('title', 'Consumption Report')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.reports.index') }}">Reports</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Consumption</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Consumption Report</h1>
            <p class="mt-2 text-sm text-gray-700">Utility consumption by property</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.consumption') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <x-form-input
                    name="start_date"
                    label="Start Date"
                    type="date"
                    :value="request('start_date', $startDate)"
                />

                <x-form-input
                    name="end_date"
                    label="End Date"
                    type="date"
                    :value="request('end_date', $endDate)"
                />

                <x-form-select
                    name="property_id"
                    label="Property"
                    :options="$properties->pluck('address', 'id')->toArray()"
                    :selected="request('property_id')"
                    placeholder="All properties..."
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        Generate Report
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Report Results -->
    @if($readings->isNotEmpty())
    <div class="mt-8 space-y-6">
        @foreach($readings as $propertyAddress => $propertyReadings)
        <x-card>
            <x-slot name="title">{{ $propertyAddress }}</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Date</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meter</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Value</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zone</th>
                        </tr>
                    </x-slot>

                    @foreach($propertyReadings as $reading)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-0">
                            {{ $reading->reading_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $reading->meter->serial_number }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <span class="capitalize">{{ str_replace('_', ' ', $reading->meter->type->value) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                            {{ number_format($reading->value, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $reading->zone ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
        </x-card>
        @endforeach
    </div>
    @else
    <div class="mt-8">
        <x-card>
            <p class="text-center text-sm text-gray-500 py-8">
                No consumption data found for the selected period.
            </p>
        </x-card>
    </div>
    @endif
</div>
@endsection
