@extends('layouts.app')

@section('title', 'Create Building')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.buildings.index') }}">Buildings</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Create</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">Create Building</h1>
            <p class="mt-2 text-sm text-slate-700">Add a new multi-unit building</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.buildings.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input
                        name="name"
                        label="Building Name"
                        type="text"
                        :value="old('name')"
                        required
                        placeholder="Gedimino 15"
                    />

                    <x-form-input
                        name="address"
                        label="Address"
                        type="text"
                        :value="old('address')"
                        required
                        placeholder="123 Main Street, Vilnius"
                    />

                    <x-form-input
                        name="total_apartments"
                        label="Total Apartments"
                        type="number"
                        :value="old('total_apartments')"
                        required
                        placeholder="10"
                        min="1"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.buildings.index') }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Create Building
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
