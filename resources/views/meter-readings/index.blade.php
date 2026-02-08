@extends('layouts.app')

@section('title', __('meter_readings.headings.index'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('meter_readings.headings.index') }}</h1>
        <a href="{{ route('meter-readings.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            {{ __('meter_readings.actions.enter_new') }}
        </a>
    </div>
    
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.tables.date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.tables.meter') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.tables.value') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.tables.zone') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.tables.entered_by') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.tables.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($readings as $reading)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                        {{ $reading->reading_date->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                        {{ $reading->meter->serial_number ?? __('meter_readings.na') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                        {{ number_format($reading->value, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                        {{ $reading->zone ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                        {{ $reading->enteredBy->name ?? __('meter_readings.na') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('meter-readings.show', $reading) }}" class="text-blue-600 hover:text-blue-900 mr-3">{{ __('meter_readings.actions.view') }}</a>
                        <a href="{{ route('meter-readings.edit', $reading) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meter_readings.actions.edit') }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">
                        {{ __('meter_readings.empty.readings') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $readings->links() }}
    </div>
</div>
@endsection
