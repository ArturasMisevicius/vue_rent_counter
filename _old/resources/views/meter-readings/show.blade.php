@extends('layouts.app')

@section('title', __('meter_readings.headings.show'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('meter-readings.index') }}" class="text-blue-600 hover:text-blue-800">
            ← {{ __('meter_readings.actions.back') }}
        </a>
        <div class="flex gap-2">
            <a href="{{ route('meter-readings.edit', $meterReading) }}" class="px-3 py-2 text-sm font-semibold text-indigo-700 border border-indigo-200 rounded-md hover:bg-indigo-50">
                {{ __('meter_readings.actions.edit') }}
            </a>
        </div>
    </div>

    <x-card title="{{ __('meter_readings.headings.show') }}">
        <dl class="divide-y divide-slate-200">
            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-slate-500">{{ __('meter_readings.labels.reading_date') }}</dt>
                <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $meterReading->reading_date->format('Y-m-d') }}</dd>
            </div>
            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-slate-500">{{ __('meter_readings.labels.meter_serial') }}</dt>
                <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $meterReading->meter->serial_number ?? __('meter_readings.na') }}</dd>
            </div>
            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-slate-500">{{ __('meter_readings.labels.value') }}</dt>
                <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ number_format($meterReading->value, 2) }}</dd>
            </div>
            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-slate-500">{{ __('meter_readings.labels.zone') }}</dt>
                <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $meterReading->zone ?? '—' }}</dd>
            </div>
            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-slate-500">{{ __('meter_readings.labels.entered_by') }}</dt>
                <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $meterReading->enteredBy->name ?? __('meter_readings.na') }}</dd>
            </div>
        </dl>
    </x-card>
</div>
@endsection
