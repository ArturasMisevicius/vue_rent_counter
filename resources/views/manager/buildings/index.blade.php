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
            <h1 class="text-2xl font-semibold text-slate-900">Buildings</h1>
            <p class="mt-2 text-sm text-slate-700">Multi-unit buildings with gyvatukas calculations</p>
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
        <div class="hidden sm:block">
        <x-data-table caption="Buildings list">
            <x-slot name="header">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Building</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Total Apartments</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Properties</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Gyvatukas Average</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Last Calculated</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($buildings as $building)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                    <a href="{{ route('manager.buildings.show', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                        <span class="block font-semibold text-slate-900">{{ $building->display_name }}</span>
                        <span class="block text-xs font-normal text-slate-600">{{ $building->address }}</span>
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    {{ $building->total_apartments }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    {{ $building->properties_count }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    @if($building->gyvatukas_summer_average)
                        {{ number_format($building->gyvatukas_summer_average, 2) }} kWh
                    @else
                        <span class="text-slate-400">Not calculated</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    @if($building->gyvatukas_last_calculated)
                        {{ $building->gyvatukas_last_calculated->format('M d, Y') }}
                    @else
                        <span class="text-slate-400">Never</span>
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
                <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">
                    No buildings found. 
                    @can('create', App\Models\Building::class)
                        <a href="{{ route('manager.buildings.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($buildings as $building)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $building->display_name }}</p>
                            <p class="text-xs text-slate-600">{{ $building->address }}</p>
                            <p class="text-xs text-slate-600">Apartments: {{ $building->total_apartments }}</p>
                            <p class="text-xs text-slate-600">Properties: {{ $building->properties_count }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-600">
                            <p>Gyvatukas: 
                                @if($building->gyvatukas_summer_average)
                                    <span class="font-semibold text-slate-900">{{ number_format($building->gyvatukas_summer_average, 2) }} kWh</span>
                                @else
                                    <span class="text-slate-400">Not calculated</span>
                                @endif
                            </p>
                            <p class="mt-1">Last: {{ $building->gyvatukas_last_calculated?->format('M d, Y') ?? 'Never' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('view', $building)
                        <a href="{{ route('manager.buildings.show', $building) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            View
                        </a>
                        @endcan
                        @can('update', $building)
                        <a href="{{ route('manager.buildings.edit', $building) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Edit
                        </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    No buildings found.
                    @can('create', App\Models\Building::class)
                        <a href="{{ route('manager.buildings.create') }}" class="text-indigo-700 font-semibold">Create one now</a>
                    @endcan
                </div>
            @endforelse
        </div>

        @if($buildings->hasPages())
        <div class="mt-4">
            {{ $buildings->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
