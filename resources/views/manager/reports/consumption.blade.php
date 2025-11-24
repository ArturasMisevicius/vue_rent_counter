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
            <h1 class="text-3xl font-bold text-slate-900 font-display">Consumption Report</h1>
            <p class="mt-2 text-sm text-slate-600">Utility consumption by property and meter type</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-8">
        <x-card title="Report Filters">
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
        <x-card :title="$propertyAddress">
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
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            {{ $reading->reading_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                            {{ $reading->meter->serial_number }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                            <span class="capitalize">{{ enum_label($reading->meter->type) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-slate-900 text-right tabular-nums">
                            {{ number_format($reading->value, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                            {{ $reading->zone ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            <div class="sm:hidden space-y-3">
                @foreach($propertyReadings as $reading)
                <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('M d, Y') }}</p>
                        <p class="text-xs font-medium text-slate-500 capitalize">{{ enum_label($reading->meter->type) }}</p>
                    </div>
                    <p class="text-xs text-slate-600 mt-1">Meter: {{ $reading->meter->serial_number }}</p>
                    <p class="text-xs text-slate-600">Value: <span class="font-semibold text-slate-900 tabular-nums">{{ number_format($reading->value, 2) }}</span></p>
                    <p class="text-xs text-slate-600">Zone: {{ $reading->zone ?? '—' }}</p>
                </div>
                @endforeach
            </div>
        </x-card>
        @endforeach
    </div>
    @else
    <div class="mt-8">
        <x-card>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                </svg>
                <p class="mt-4 text-sm font-medium text-slate-900">No consumption data found</p>
                <p class="mt-1 text-sm text-slate-500">Try adjusting your filters or date range</p>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection
