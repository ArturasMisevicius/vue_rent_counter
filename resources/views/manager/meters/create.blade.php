@extends('layouts.app')

@section('title', 'Create Meter')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meters.index') }}">Meters</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Create</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Create Meter</h1>
            <p class="mt-2 text-sm text-gray-700">Register a new utility meter</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.meters.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input
                        name="serial_number"
                        label="Serial Number"
                        type="text"
                        :value="old('serial_number')"
                        required
                        placeholder="ABC123456"
                    />

                    <x-form-select
                        name="type"
                        label="Meter Type"
                        :options="[
                            'electricity' => 'Electricity',
                            'water_cold' => 'Cold Water',
                            'water_hot' => 'Hot Water',
                            'heating' => 'Heating'
                        ]"
                        :selected="old('type')"
                        required
                    />

                    <x-form-select
                        name="property_id"
                        label="Property"
                        :options="$properties->pluck('address', 'id')->toArray()"
                        :selected="old('property_id', request('property_id'))"
                        required
                    />

                    <x-form-input
                        name="installation_date"
                        label="Installation Date"
                        type="date"
                        :value="old('installation_date', now()->format('Y-m-d'))"
                        required
                    />

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            name="supports_zones"
                            id="supports_zones"
                            value="1"
                            {{ old('supports_zones') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                        >
                        <label for="supports_zones" class="ml-2 block text-sm text-gray-900">
                            Supports time-of-use zones (day/night rates)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.meters.index') }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Create Meter
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
