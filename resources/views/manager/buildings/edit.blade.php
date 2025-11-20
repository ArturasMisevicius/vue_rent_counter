@extends('layouts.app')

@section('title', 'Edit Building')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.buildings.index') }}">Buildings</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.buildings.show', $building) }}">{{ $building->address }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Edit</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Building</h1>
            <p class="mt-2 text-sm text-gray-700">Update building information</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.buildings.update', $building) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="address"
                        label="Address"
                        type="text"
                        :value="old('address', $building->address)"
                        required
                        placeholder="123 Main Street, Vilnius"
                    />

                    <x-form-input
                        name="total_apartments"
                        label="Total Apartments"
                        type="number"
                        :value="old('total_apartments', $building->total_apartments)"
                        required
                        placeholder="10"
                        min="1"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.buildings.show', $building) }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Update Building
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
