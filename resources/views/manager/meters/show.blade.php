@extends('layouts.app')

@section('title', 'Meter Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meters.index') }}">Meters</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $meter->serial_number }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Meter {{ $meter->serial_number }}</h1>
            <p class="mt-2 text-sm text-gray-700">Meter details and reading history</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('create', App\Models\MeterReading::class)
            <x-button href="{{ route('manager.meter-readings.create', ['meter_id' => $meter->id]) }}">
                Add Reading
            </x-button>
            @endcan
            @can('update', $meter)
            <x-button href="{{ route('manager.meters.edit', $meter) }}" variant="secondary">
                Edit Meter
            </x-button>
            @endcan
            @can('delete', $meter)
            <form action="{{ route('manager.meters.destroy', $meter) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this meter?');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    Delete
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Meter Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">Meter Information</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Serial Number</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meter->serial_number }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Type</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <span class="capitalize">{{ $meter->type->label() }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Property</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.properties.show', $meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meter->property->address }}
                        </a>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Installation Date</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meter->installation_date->format('M d, Y') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Supports Zones</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @if($meter->supports_zones)
                            <x-status-badge status="active">Yes</x-status-badge>
                        @else
                            <span class="text-gray-400">No</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Latest Reading -->
        <x-card>
            <x-slot name="title">Latest Reading</x-slot>
            
            @if($meter->readings->isNotEmpty())
                @php $latestReading = $meter->readings->first(); @endphp
                <dl class="divide-y divide-gray-100">
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Value</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            <span class="text-2xl font-semibold">{{ number_format($latestReading->value, 2) }}</span>
                        </dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Date</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $latestReading->reading_date->format('M d, Y') }}</dd>
                    </div>
                    @if($latestReading->zone)
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Zone</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $latestReading->zone }}</dd>
                    </div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-gray-500">No readings recorded yet</p>
            @endif
        </x-card>
    </div>

    <!-- Reading History Graph -->
    @if($readingHistory->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Reading History (Last 12 Readings)</x-slot>
            
            <div class="mt-4">
                <div class="relative h-64">
                    <canvas id="readingChart"></canvas>
                </div>
            </div>
        </x-card>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('readingChart');
        const data = @json($readingHistory);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Reading Value',
                    data: data.map(item => item.value),
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
    @endif

    <!-- Reading History Table -->
    <div class="mt-8">
        <x-card>
            <x-slot name="title">All Readings</x-slot>
            
            @if($meter->readings->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Date</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zone</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Entered By</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($meter->readings as $reading)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            {{ $reading->reading_date->format('M d, Y') }}
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
                            <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-gray-500">No readings recorded for this meter.</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
