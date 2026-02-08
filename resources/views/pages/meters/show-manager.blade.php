@extends('layouts.app')

@section('title', __('meters.headings.show', ['serial' => $meter->serial_number]))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meters.headings.show', ['serial' => $meter->serial_number]) }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meters.headings.show_description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('create', App\Models\MeterReading::class)
            <x-button href="{{ route('manager.meter-readings.create', ['meter_id' => $meter->id]) }}">
                {{ __('meter_readings.actions.enter_new') }}
            </x-button>
            @endcan
            @can('update', $meter)
            <x-button href="{{ route('manager.meters.edit', $meter) }}" variant="secondary">
                {{ __('meters.actions.edit_meter') }}
            </x-button>
            @endcan
            @can('delete', $meter)
            <form action="{{ route('manager.meters.destroy', $meter) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('meters.confirmations.delete') }}');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('meters.actions.delete') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Meter Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('meters.headings.information') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.serial_number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meter->serial_number }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.type') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $meter->getServiceDisplayName() }}
                        <span class="text-slate-400 text-xs">({{ $meter->getUnitOfMeasurement() }})</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.property') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.properties.show', $meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meter->property->address }}
                        </a>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.installation_date') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meter->installation_date->format('M d, Y') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.shared.index.headers.zones') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        @if($meter->supports_zones)
                            <x-status-badge status="active">{{ __('meters.shared.index.zones.yes') }}</x-status-badge>
                        @else
                            <span class="text-slate-400">{{ __('meters.shared.index.zones.no') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Latest Reading -->
        <x-card>
            <x-slot name="title">{{ __('meter_readings.headings.show') }}</x-slot>
            
            @if($meter->readings->isNotEmpty())
                <dl class="divide-y divide-slate-100">
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.value') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                            <span class="text-2xl font-semibold">{{ number_format($meter->readings->first()->getEffectiveValue(), 2) }}</span>
                        </dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.reading_date') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meter->readings->first()->reading_date->format('M d, Y') }}</dd>
                    </div>
                    @if($meter->readings->first()->zone)
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.zone') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meter->readings->first()->zone }}</dd>
                    </div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('meter_readings.empty.readings') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Reading History Graph -->
    @if($readingHistory->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('meter_readings.headings.index') }}</x-slot>
            
            <div class="mt-4">
                <div class="relative h-64">
                    <canvas id="readingChart"></canvas>
                </div>
            </div>
        </x-card>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('readingChart');
            const data = @json($readingHistory);
            const ChartLib = window.Chart;

            if (!ctx || !ChartLib) return;

            new ChartLib(ctx, {
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
        });
    </script>
    @endif

    <!-- Reading History Table -->
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('meter_readings.headings.index') }}</x-slot>
            
            @if($meter->readings->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.value') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.zone') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.tables.entered_by') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('meter_readings.tables.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($meter->readings as $reading)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            {{ $reading->reading_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($reading->getEffectiveValue(), 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $reading->zone ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.meter-readings.show', $reading) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('meter_readings.actions.view') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-slate-500">{{ __('meter_readings.empty.readings') }}</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
