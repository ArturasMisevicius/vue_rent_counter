@extends('layouts.app')

@section('title', 'Create Property')

@section('content')
@php($propertyTypeOptions = \App\Enums\PropertyType::labels())
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.properties.index') }}">Properties</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Create</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">Create Property</h1>
            <p class="mt-2 text-sm text-slate-700">Add a new property to your portfolio</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.properties.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input
                        name="address"
                        label="Address"
                        type="text"
                        :value="old('address')"
                        required
                        placeholder="123 Main Street, Vilnius"
                    />

                    <x-form-select
                        name="type"
                        label="Property Type"
                        :options="$propertyTypeOptions"
                        :selected="old('type')"
                        required
                    />

                    <x-form-input
                        name="area_sqm"
                        label="Area (m²)"
                        type="number"
                        step="0.01"
                        :value="old('area_sqm')"
                        required
                        placeholder="50.00"
                    />

                    <x-form-select
                        name="building_id"
                        label="Building (Optional)"
                        :options="$buildings->pluck('address', 'id')->toArray()"
                        :selected="old('building_id')"
                        placeholder="Select a building..."
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.properties.index') }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Create Property
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
