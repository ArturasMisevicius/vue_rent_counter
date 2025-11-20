@extends('layouts.app')

@section('title', 'Buildings')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Buildings</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Buildings</h1>
            <p class="mt-2 text-sm text-gray-700">Multi-unit buildings with gyvatukas calculations</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Building::class)
            <x-button href="{{ route('manager.buildings.create') }}">
                Add Building
            </x-button>
            @endcan
        </div>
    </div>

    <x-card class="mt-8">
        <x-data-table>
            <x-slot name="header">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Address</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Apartments</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Properties</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Gyvatukas Average</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Calculated</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($buildings as $building)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                    <a href="{{ route('manager.buildings.show', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $building->address }}
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ $building->total_apartments }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ $building->properties_count }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    @if($building->gyvatukas_summer_average)
                        {{ number_format($building->gyvatukas_summer_average, 2) }} kWh
                    @else
                        <span class="text-gray-400">Not calculated</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    @if($building->gyvatukas_last_calculated)
                        {{ $building->gyvatukas_last_calculated->format('M d, Y') }}
                    @else
                        <span class="text-gray-400">Never</span>
                    @endif
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        @can('view', $building)
                        <a href="{{ route('manager.buildings.show', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                            View
                        </a>
                        @endcan
                        @can('update', $building)
                        <a href="{{ route('manager.buildings.edit', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                    No buildings found. 
                    @can('create', App\Models\Building::class)
                        <a href="{{ route('manager.buildings.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>

        @if($buildings->hasPages())
        <div class="mt-4">
            {{ $buildings->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
