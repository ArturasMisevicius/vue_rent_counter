@extends('layouts.app')

@section('title', 'Consumption Report')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.reports.index') }}">Reports</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Consumption</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">Consumption Report</h1>
            <p class="mt-2 text-sm text-slate-700">Utility consumption by property and meter type</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <form method="GET" action="{{ route('manager.reports.consumption.export') }}" class="inline">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="property_id" value="{{ request('property_id') }}">
                <input type="hidden" name="building_id" value="{{ $buildingId }}">
                <input type="hidden" name="meter_type" value="{{ $meterType }}">
                <x-button type="submit" variant="secondary">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </x-button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.consumption') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-6">
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
                    name="building_id"
                    label="Building"
                    :options="$buildings->pluck('name', 'id')->toArray()"
                    :selected="$buildingId"
                    placeholder="All buildings..."
                />

                <x-form-select
                    name="property_id"
                    label="Property"
                    :options="$properties->pluck('address', 'id')->toArray()"
                    :selected="request('property_id')"
                    placeholder="All properties..."
                />

                <x-form-select
                    name="meter_type"
                    label="Meter Type"
                    :options="collect($meterTypes)->mapWithKeys(fn($type) => [$type->value => enum_label($type)])->toArray()"
                    :selected="$meterType"
                    placeholder="All types..."
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        Generate Report
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    @if($consumptionByType->isNotEmpty())
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($consumptionByType as $type => $data)
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ ucfirst($type) }}</x-slot>
            <x-slot name="value">{{ number_format($data['total'], 2) }}</x-slot>
            <x-slot name="change">{{ $data['count'] }} readings</x-slot>
        </x-stat-card>
        @endforeach
    </div>
    @endif

    @if($monthlyTrend->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Monthly Consumption Trend</x-slot>
            
            <div class="mt-4">
                <div class="space-y-3">
                    @foreach($monthlyTrend as $month => $data)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($month)->format('F Y') }}</p>
                            <p class="text-xs text-slate-600">{{ $data['count'] }} readings</p>
                        </div>
                        <p class="text-lg font-semibold text-slate-900">{{ number_format($data['total'], 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if($topProperties->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Top Consuming Properties</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="Top consuming properties">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Property</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Building</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">Total Consumption</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">Readings</th>
                        </tr>
                    </x-slot>

                    @foreach($topProperties as $item)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $item['property']) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $item['property']->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $item['property']->building?->name ?? 'N/A' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right font-semibold">
                            {{ number_format($item['total_consumption'], 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                            {{ $item['reading_count'] }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($topProperties as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">{{ $item['property']->address }}</p>
                        <p class="text-xs text-slate-600 mt-1">{{ $item['property']->building?->name ?? 'N/A' }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-slate-600">Consumption:</p>
                            <p class="text-sm font-semibold text-slate-900">{{ number_format($item['total_consumption'], 2) }}</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-slate-600">Readings:</p>
                            <p class="text-xs text-slate-600">{{ $item['reading_count'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if($readingsByProperty->isNotEmpty())
    <div class="mt-8 space-y-6">
        @foreach($readingsByProperty as $propertyAddress => $propertyReadings)
        <x-card>
            <x-slot name="title">{{ $propertyAddress }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="Consumption readings for {{ $propertyAddress }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Date</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Meter</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Type</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">Value</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Zone</th>
                        </tr>
                    </x-slot>

                    @foreach($propertyReadings as $reading)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-900 sm:pl-0">
                            {{ $reading->reading_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $reading->meter->serial_number }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            <span class="capitalize">{{ enum_label($reading->meter->type) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right">
                            {{ number_format($reading->value, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $reading->zone ?? '—' }}
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
                            <p class="text-xs font-semibold text-slate-500 capitalize">{{ enum_label($reading->meter->type) }}</p>
                        </div>
                        <p class="text-xs text-slate-600">Meter: {{ $reading->meter->serial_number }}</p>
                        <p class="text-xs text-slate-600">Value: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                        <p class="text-xs text-slate-600">Zone: {{ $reading->zone ?? '—' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
        @endforeach
    </div>
    @else
    <div class="mt-8">
        <x-card>
            <p class="text-center text-sm text-slate-500 py-8">
                No consumption data found for the selected period.
            </p>
        </x-card>
    </div>
    @endif
</div>
@endsection
