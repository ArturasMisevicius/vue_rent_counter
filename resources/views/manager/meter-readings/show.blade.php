@extends('layouts.app')

@section('title', 'Meter Reading Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meter-readings.index') }}">Meter Readings</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Reading #{{ $meterReading->id }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Meter Reading #{{ $meterReading->id }}</h1>
            <p class="mt-2 text-sm text-gray-700">Reading details and audit trail</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $meterReading)
            <x-button href="{{ route('manager.meter-readings.edit', $meterReading) }}" variant="secondary">
                Correct Reading
            </x-button>
            @endcan
            @can('delete', $meterReading)
            <form action="{{ route('manager.meter-readings.destroy', $meterReading) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this reading?');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    Delete
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Reading Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">Reading Information</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Reading Date</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meterReading->reading_date->format('M d, Y') }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Value</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">{{ number_format($meterReading->value, 2) }}</span>
                    </dd>
                </div>
                @if($meterReading->zone)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Zone</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meterReading->zone }}</dd>
                </div>
                @endif
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Entered By</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meterReading->enteredBy->name ?? 'N/A' }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Created At</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meterReading->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- Meter Information -->
        <x-card>
            <x-slot name="title">Meter Information</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Serial Number</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('manager.meters.show', $meterReading->meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $meterReading->meter->serial_number }}
                        </a>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Type</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <span class="capitalize">{{ enum_label($meterReading->meter->type) }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Property</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
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
            <x-slot name="title">Correction History</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Date</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Old Value</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">New Value</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reason</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Changed By</th>
                        </tr>
                    </x-slot>

                    @foreach($meterReading->auditTrail as $audit)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            {{ $audit->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ number_format($audit->old_value, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ number_format($audit->new_value, 2) }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">
                            {{ $audit->change_reason }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $audit->changedByUser->name ?? 'N/A' }}
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
