@extends('layouts.app')

@section('title', 'Meters')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Meters</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Meters</h1>
            <p class="mt-2 text-sm text-gray-700">Utility meters across all properties</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Meter::class)
            <x-button href="{{ route('manager.meters.create') }}">
                Add Meter
            </x-button>
            @endcan
        </div>
    </div>

    <x-card class="mt-8">
        <x-data-table>
            <x-slot name="header">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Serial Number</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Property</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Installation Date</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Latest Reading</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zones</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($meters as $meter)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                    <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $meter->serial_number }}
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span class="capitalize">{{ $meter->type->label() }}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <a href="{{ route('manager.properties.show', $meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $meter->property->address }}
                    </a>
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
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    @if($meter->supports_zones)
                        <x-status-badge status="active">Yes</x-status-badge>
                    @else
                        <span class="text-gray-400">No</span>
                    @endif
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        @can('view', $meter)
                        <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            View
                        </a>
                        @endcan
                        @can('update', $meter)
                        <a href="{{ route('manager.meters.edit', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                    No meters found. 
                    @can('create', App\Models\Meter::class)
                        <a href="{{ route('manager.meters.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>

        @if($meters->hasPages())
        <div class="mt-4">
            {{ $meters->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
