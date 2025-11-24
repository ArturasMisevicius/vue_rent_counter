@extends('layouts.app')

@section('title', 'Meter Reading Compliance Report')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.reports.index') }}">Reports</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Reading Compliance</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-slate-900 font-display">Meter Reading Compliance</h1>
            <p class="mt-2 text-sm text-slate-600">Track meter reading completion by property</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-8">
        <x-card title="Report Filters">
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
        <x-card title="Compliance Summary">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Properties with Complete Readings</p>
                    <p class="mt-2 text-4xl font-bold text-slate-900 font-display tabular-nums">
                        {{ $propertiesWithReadings->count() }} <span class="text-2xl text-slate-400">/</span> {{ $properties->count() }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Compliance Rate</p>
                    <p class="mt-2 text-4xl font-bold font-display tabular-nums {{ $complianceRate >= 80 ? 'text-emerald-600' : ($complianceRate >= 50 ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ number_format($complianceRate, 1) }}%
                    </p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-6">
                <div class="relative">
                    <div class="overflow-hidden h-3 flex rounded-full bg-slate-200">
                        <div 
                            style="width:{{ $complianceRate }}%" 
                            class="shadow-sm flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500 {{ $complianceRate >= 80 ? 'bg-gradient-to-r from-emerald-500 to-green-400' : ($complianceRate >= 50 ? 'bg-gradient-to-r from-amber-500 to-yellow-400' : 'bg-gradient-to-r from-rose-500 to-red-400') }}"
                        ></div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Property Details -->
    <div class="mt-8">
        <x-card title="Property Details">
            <div class="hidden sm:block">
                <x-data-table caption="Property meter reading compliance">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Property</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Total Meters</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Readings Submitted</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Status</th>
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
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 tabular-nums">
                            {{ $totalMeters }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 tabular-nums">
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
                                <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                    Add Readings
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            <div class="sm:hidden space-y-3">
                @foreach($properties as $property)
                @php
                    $totalMeters = $property->meters->count();
                    $metersWithReadings = $property->meters->filter(fn($meter) => $meter->readings->isNotEmpty())->count();
                    $isComplete = $totalMeters > 0 && $totalMeters === $metersWithReadings;
                @endphp
                <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                    <a href="{{ route('manager.properties.show', $property) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">
                        {{ $property->address }}
                    </a>
                    <div class="flex items-center gap-4 mt-2">
                        <p class="text-xs text-slate-600">Meters: <span class="font-semibold text-slate-900 tabular-nums">{{ $totalMeters }}</span></p>
                        <p class="text-xs text-slate-600">Readings: <span class="font-semibold text-slate-900 tabular-nums">{{ $metersWithReadings }}</span></p>
                    </div>
                    <div class="mt-2">
                        @if($isComplete)
                            <x-status-badge status="active">Complete</x-status-badge>
                        @else
                            <x-status-badge status="inactive">Incomplete</x-status-badge>
                        @endif
                    </div>
                    @if(!$isComplete)
                    <div class="mt-3">
                        <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Add Readings
                        </a>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </x-card>
    </div>
</div>
@endsection
