@extends('layouts.app')

@section('title', 'Properties')

@section('content')
@php($propertyTypeLabels = \App\Enums\PropertyType::labels())
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Properties</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Properties</h1>
            <p class="mt-2 text-sm text-gray-700">A list of all properties in your portfolio.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Property::class)
            <x-button href="{{ route('manager.properties.create') }}">
                Add Property
            </x-button>
            @endcan
        </div>
    </div>

    {{-- Search and Filter Form --}}
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('manager.properties.index') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       placeholder="Search by address..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div class="sm:w-48">
                <label for="property_type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="property_type" id="property_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Types</option>
                    @foreach($propertyTypeLabels as $value => $label)
                        <option value="{{ $value }}" {{ request('property_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-48">
                <label for="building_id" class="block text-sm font-medium text-gray-700">Building</label>
                <select name="building_id" id="building_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" {{ request('building_id') == $building->id ? 'selected' : '' }}>
                            {{ $building->address }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'property_type', 'building_id']))
                <a href="{{ route('manager.properties.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    <x-card class="mt-8">
        <x-data-table>
            <x-slot name="header">
                <tr>
                    <x-sortable-header column="address" label="Address" class="py-3.5 pl-4 pr-3 sm:pl-0" />
                    <x-sortable-header column="property_type" label="Type" />
                    <x-sortable-header column="area_sqm" label="Area" />
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Building</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meters</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tenants</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($properties as $property)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                    <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $property->address }}
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span class="capitalize">{{ $property->type->label() }}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ number_format($property->area_sqm, 2) }} m²
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    @if($property->building)
                        <a href="{{ route('manager.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $property->building->address }}
                        </a>
                    @else
                        <span class="text-gray-400">N/A</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ $property->meters_count }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ $property->tenants->count() }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        @can('view', $property)
                        <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                            View
                        </a>
                        @endcan
                        @can('update', $property)
                        <a href="{{ route('manager.properties.edit', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                    No properties found. 
                    @can('create', App\Models\Property::class)
                        <a href="{{ route('manager.properties.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>

        @if($properties->hasPages())
        <div class="mt-4">
            {{ $properties->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
