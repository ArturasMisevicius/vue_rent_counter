@extends('layouts.app')

@section('title', 'Correct Meter Reading')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meter-readings.index') }}">Meter Readings</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meter-readings.show', $meterReading) }}">Reading #{{ $meterReading->id }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Correct</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Correct Meter Reading</h1>
            <p class="mt-2 text-sm text-gray-700">Update reading with audit trail</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <!-- Current Reading Info -->
        <x-card class="mb-6">
            <x-slot name="title">Current Reading</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Meter</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meterReading->meter->serial_number }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Current Value</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <span class="text-xl font-semibold">{{ number_format($meterReading->value, 2) }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Reading Date</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $meterReading->reading_date->format('M d, Y') }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- Correction Form -->
        <x-card>
            <x-slot name="title">New Values</x-slot>
            
            <form action="{{ route('manager.meter-readings.update', $meterReading) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="reading_date"
                        label="Reading Date"
                        type="date"
                        :value="old('reading_date', $meterReading->reading_date->format('Y-m-d'))"
                        required
                    />

                    <x-form-input
                        name="value"
                        label="Reading Value"
                        type="number"
                        step="0.01"
                        :value="old('value', $meterReading->value)"
                        required
                        placeholder="1234.56"
                    />

                    @if($meterReading->meter->supports_zones)
                    <x-form-select
                        name="zone"
                        label="Time-of-Use Zone"
                        :options="[
                            'day' => 'Day Rate',
                            'night' => 'Night Rate'
                        ]"
                        :selected="old('zone', $meterReading->zone)"
                        placeholder="Select zone..."
                    />
                    @endif

                    <div>
                        <label for="change_reason" class="block text-sm font-medium leading-6 text-gray-900">
                            Correction Reason 
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            name="change_reason"
                            id="change_reason"
                            rows="3"
                            required
                            @class([
                                'mt-2 block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6',
                                'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500' => $errors->has('change_reason'),
                                'text-gray-900 ring-gray-300 placeholder:text-gray-400 focus:ring-indigo-600' => !$errors->has('change_reason'),
                            ])
                            placeholder="Explain why this reading is being corrected..."
                        >{{ old('change_reason') }}</textarea>
                        @error('change_reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Audit Trail</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>This correction will be recorded in the audit trail. The original value and your reason for the change will be preserved.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.meter-readings.show', $meterReading) }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Save Correction
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
