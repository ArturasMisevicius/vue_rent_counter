@extends('layouts.app')

@section('title', 'Edit Property')

@section('content')
@php($propertyTypeOptions = \App\Enums\PropertyType::labels())
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.properties.index') }}">Properties</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.properties.show', $property) }}">{{ $property->address }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Edit</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Property</h1>
            <p class="mt-2 text-sm text-gray-700">Update property information</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.properties.update', $property) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="address"
                        label="Address"
                        type="text"
                        :value="old('address', $property->address)"
                        required
                        placeholder="123 Main Street, Vilnius"
                    />

                    <x-form-select
                        name="type"
                        label="Property Type"
                        :options="$propertyTypeOptions"
                        :selected="old('type', $property->type->value)"
                        required
                    />

                    <x-form-input
                        name="area_sqm"
                        label="Area (m²)"
                        type="number"
                        step="0.01"
                        :value="old('area_sqm', $property->area_sqm)"
                        required
                        placeholder="50.00"
                    />

                    <x-form-select
                        name="building_id"
                        label="Building (Optional)"
                        :options="$buildings->pluck('address', 'id')->toArray()"
                        :selected="old('building_id', $property->building_id)"
                        placeholder="Select a building..."
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.properties.show', $property) }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Update Property
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
