@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('manager')
@section('title', __('meter_readings.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.headings.show') }} #{{ $meterReading->id }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.shared.show.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $meterReading)
            <x-button href="{{ route('manager.meter-readings.edit', $meterReading) }}" variant="secondary">
                {{ __('meter_readings.actions.correct') }}
            </x-button>
            @endcan
            @can('delete', $meterReading)
            <form action="{{ route('manager.meter-readings.destroy', $meterReading) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('meter_readings.actions.delete') }}?');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('meter_readings.actions.delete') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Reading Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('meter_readings.headings.show') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.reading_date') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->reading_date->format('M d, Y') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.value') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">{{ number_format($meterReading->value, 2) }}</span>
                    </dd>
                </div>
                @if($meterReading->zone)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.zone') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->zone }}</dd>
                </div>
                @endif
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.entered_by') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->enteredBy->name ?? __('meter_readings.na') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.created_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- Meter Information -->
        <x-card>
            <x-slot name="title">{{ __('meters.labels.meter') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.serial_number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.meters.show', $meterReading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meterReading->meter->serial_number }}
                        </a>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.type') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $meterReading->meter->getServiceDisplayName() }}
                        <span class="text-xs text-slate-400">({{ $meterReading->meter->getUnitOfMeasurement() }})</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.labels.property') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.properties.show', $meterReading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meterReading->meter->property->address }}
                        </a>
                    </dd>
                </div>
            </dl>
        </x-card>
    </div>

    <!-- Audit Trail -->
    @if($meterReading->auditTrail->isNotEmpty())
    <div class="mt-8">
            <x-card>
            <x-slot name="title">{{ __('meter_readings.labels.history') }}</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.old_value') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.new_value') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.reason') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.changed_by') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($meterReading->auditTrail as $audit)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            {{ $audit->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($audit->old_value, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($audit->new_value, 2) }}
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500">
                            {{ $audit->change_reason }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $audit->changedByUser->name ?? __('app.common.na') }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection
@break

@case('tenant')
<!DOCTYPE html>
<html>
<head>
    <title>Meter Reading</title>
</head>
<body>
    <h1>Meter Reading</h1>
    <p>Date: {{ $meterReading->reading_date }}</p>
    <p>Value: {{ $meterReading->value }}</p>
</body>
</html>
@break

@default
@section('title', __('meter_readings.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.headings.show') }} #{{ $meterReading->id }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.shared.show.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $meterReading)
            <x-button href="{{ route('manager.meter-readings.edit', $meterReading) }}" variant="secondary">
                {{ __('meter_readings.actions.correct') }}
            </x-button>
            @endcan
            @can('delete', $meterReading)
            <form action="{{ route('manager.meter-readings.destroy', $meterReading) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('meter_readings.actions.delete') }}?');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('meter_readings.actions.delete') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Reading Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('meter_readings.headings.show') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.reading_date') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->reading_date->format('M d, Y') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.value') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">{{ number_format($meterReading->value, 2) }}</span>
                    </dd>
                </div>
                @if($meterReading->zone)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.zone') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->zone }}</dd>
                </div>
                @endif
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.entered_by') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->enteredBy->name ?? __('meter_readings.na') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meter_readings.labels.created_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $meterReading->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- Meter Information -->
        <x-card>
            <x-slot name="title">{{ __('meters.labels.meter') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.serial_number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.meters.show', $meterReading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meterReading->meter->serial_number }}
                        </a>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('meters.labels.type') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $meterReading->meter->getServiceDisplayName() }}
                        <span class="text-xs text-slate-400">({{ $meterReading->meter->getUnitOfMeasurement() }})</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.labels.property') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.properties.show', $meterReading->meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meterReading->meter->property->address }}
                        </a>
                    </dd>
                </div>
            </dl>
        </x-card>
    </div>

    <!-- Audit Trail -->
    @if($meterReading->auditTrail->isNotEmpty())
    <div class="mt-8">
            <x-card>
            <x-slot name="title">{{ __('meter_readings.labels.history') }}</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meter_readings.tables.date') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.old_value') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.new_value') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.reason') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meter_readings.labels.changed_by') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($meterReading->auditTrail as $audit)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            {{ $audit->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($audit->old_value, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($audit->new_value, 2) }}
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500">
                            {{ $audit->change_reason }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $audit->changedByUser->name ?? __('app.common.na') }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection
@endswitch
