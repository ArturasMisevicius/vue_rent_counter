@extends('layouts.app')

@section('title', 'Tariff Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.tariffs.index')">Tariffs</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $tariff->name }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Tariff Details</h1>
            <p class="mt-2 text-sm text-gray-700">View tariff configuration and version history</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-3">
            @can('update', $tariff)
            <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit Tariff
            </a>
            @endcan
            <a href="{{ route('admin.tariffs.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Tariff Information -->
        <div class="lg:col-span-2">
            <x-card title="Tariff Information">
                <dl class="divide-y divide-gray-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $tariff->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Provider</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <a href="{{ route('admin.providers.show', $tariff->provider) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $tariff->provider->name }}
                            </a>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Service Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <x-status-badge :status="$tariff->provider->service_type->value">
                                {{ enum_label($tariff->provider->service_type) }}
                            </x-status-badge>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Active From</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $tariff->active_from->format('Y-m-d') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Active Until</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $tariff->active_until ? $tariff->active_until->format('Y-m-d') : 'Present' }}
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if($tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now()))
                                <x-status-badge status="active">Active</x-status-badge>
                            @else
                                <x-status-badge status="inactive">Inactive</x-status-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- Configuration -->
            <div class="mt-6">
                <x-card title="Configuration">
                    <pre class="text-xs bg-gray-50 p-4 rounded-md overflow-x-auto">{{ json_encode($tariff->configuration, JSON_PRETTY_PRINT) }}</pre>
                </x-card>
            </div>

            <!-- Version History -->
            @if($versionHistory->isNotEmpty())
            <div class="mt-6">
                <x-card title="Version History">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active From</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active Until</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="relative px-3 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($versionHistory as $version)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $version->active_from->format('Y-m-d') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $version->active_until ? $version->active_until->format('Y-m-d') : 'Present' }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if($version->active_from <= now() && (!$version->active_until || $version->active_until >= now()))
                                            <x-status-badge status="active">Active</x-status-badge>
                                        @else
                                            <x-status-badge status="inactive">Inactive</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap px-3 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.tariffs.show', $version) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <x-card title="Quick Actions">
                <div class="space-y-3">
                    @can('update', $tariff)
                    <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="block w-full rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Edit Tariff
                    </a>
                    @endcan
                    
                    @can('delete', $tariff)
                    <form action="{{ route('admin.tariffs.destroy', $tariff) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tariff?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            Delete Tariff
                        </button>
                    </form>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
