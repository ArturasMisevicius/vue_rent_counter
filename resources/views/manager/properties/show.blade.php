@extends('layouts.app')

@section('title', 'Property Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.properties.index') }}">Properties</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $property->address }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $property->address }}</h1>
            <p class="mt-2 text-sm text-gray-700">Property details and associated information</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $property)
            <x-button href="{{ route('manager.properties.edit', $property) }}" variant="secondary">
                Edit Property
            </x-button>
            @endcan
            @can('delete', $property)
            <form action="{{ route('manager.properties.destroy', $property) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this property?');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    Delete
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Property Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">Property Information</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Address</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $property->address }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Type</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <span class="capitalize">{{ $property->type->value }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Area</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ number_format($property->area_sqm, 2) }} m²</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Building</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @if($property->building)
                            <a href="{{ route('manager.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->building->address }}
                            </a>
                        @else
                            <span class="text-gray-400">Not in a building</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Current Tenant -->
        <x-card>
            <x-slot name="title">Current Tenant</x-slot>
            
            @if($property->tenants->isNotEmpty())
                @php $currentTenant = $property->tenants->first(); @endphp
                <dl class="divide-y divide-gray-100">
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Name</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $currentTenant->name }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Email</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $currentTenant->email }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-gray-900">Phone</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $currentTenant->phone ?? 'N/A' }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500">No current tenant</p>
            @endif
        </x-card>
    </div>

    <!-- Meters -->
    <div class="mt-8">
        <x-card>
            <div class="flex items-center justify-between">
                <x-slot name="title">Meters</x-slot>
                @can('create', App\Models\Meter::class)
                <x-button href="{{ route('manager.meters.create', ['property_id' => $property->id]) }}" variant="secondary" size="sm">
                    Add Meter
                </x-button>
                @endcan
            </div>
            
            @if($property->meters->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Serial Number</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Installation Date</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Latest Reading</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($property->meters as $meter)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $meter->serial_number }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <span class="capitalize">{{ str_replace('_', ' ', $meter->type->value) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $meter->installation_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            @if($meter->readings->isNotEmpty())
                                {{ number_format($meter->readings->first()->value, 2) }}
                                <span class="text-gray-400 text-xs">({{ $meter->readings->first()->reading_date->format('M d') }})</span>
                            @else
                                <span class="text-gray-400">No readings</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-gray-500">No meters installed for this property.</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
