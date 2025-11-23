@extends('layouts.app')

@section('title', 'Building Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.buildings.index') }}">Buildings</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $building->address }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $building->address }}</h1>
            <p class="mt-2 text-sm text-gray-700">Building details and gyvatukas calculations</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $building)
            <x-button href="{{ route('manager.buildings.edit', $building) }}" variant="secondary">
                Edit Building
            </x-button>
            @endcan
            @can('delete', $building)
            <form action="{{ route('manager.buildings.destroy', $building) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this building?');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    Delete
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Building Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">Building Information</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Address</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $building->address }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Total Apartments</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $building->total_apartments }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Properties Registered</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $building->properties->count() }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- Gyvatukas Calculation -->
        <x-card>
            <x-slot name="title">Gyvatukas (Circulation Fee)</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Summer Average</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @if($building->gyvatukas_summer_average)
                            <span class="font-semibold">{{ number_format($building->gyvatukas_summer_average, 2) }} kWh</span>
                        @else
                            <span class="text-gray-400">Not calculated</span>
                        @endif
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Last Calculated</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @if($building->gyvatukas_last_calculated)
                            {{ $building->gyvatukas_last_calculated->format('M d, Y') }}
                        @else
                            <span class="text-gray-400">Never</span>
                        @endif
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Status</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @if($building->gyvatukas_summer_average)
                            <x-status-badge status="active">Calculated</x-status-badge>
                        @else
                            <x-status-badge status="inactive">Pending</x-status-badge>
                        @endif
                    </dd>
                </div>
            </dl>

            @can('update', $building)
            <div class="mt-6 border-t border-gray-200 pt-6">
                <form action="{{ route('manager.buildings.calculate-gyvatukas', $building) }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="start_date"
                            label="Start Date"
                            type="date"
                            :value="old('start_date', now()->month(5)->startOfMonth()->format('Y-m-d'))"
                            required
                        />
                        
                        <x-form-input
                            name="end_date"
                            label="End Date"
                            type="date"
                            :value="old('end_date', now()->month(9)->endOfMonth()->format('Y-m-d'))"
                            required
                        />
                    </div>

                    <x-button type="submit" variant="secondary" class="w-full">
                        Calculate Summer Average
                    </x-button>
                </form>
            </div>
            @endcan
        </x-card>
    </div>

    <!-- Properties in Building -->
    <div class="mt-8">
        <x-card>
            <div class="flex items-center justify-between">
                <x-slot name="title">Properties in Building</x-slot>
                @can('create', App\Models\Property::class)
                <x-button href="{{ route('manager.properties.create', ['building_id' => $building->id]) }}" variant="secondary" size="sm">
                    Add Property
                </x-button>
                @endcan
            </div>
            
            @if($building->properties->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Address</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Area</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meters</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tenant</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($building->properties as $property)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <span class="capitalize">{{ enum_label($property->type) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ number_format($property->area_sqm, 2) }} m²
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $property->meters->count() }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            @if($property->tenants->isNotEmpty())
                                {{ $property->tenants->first()->name }}
                            @else
                                <span class="text-gray-400">Vacant</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-gray-500">No properties registered in this building.</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
