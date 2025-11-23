@extends('layouts.app')

@section('title', 'Tariffs Management')

@section('content')
@php($tariffTypeLabels = \App\Enums\TariffType::labels())
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Tariffs</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Tariffs Management</h1>
            <p class="mt-2 text-sm text-gray-700">Configure utility pricing and time-of-use rates</p>
        </div>
        @can('create', App\Models\Tariff::class)
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('admin.tariffs.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Add Tariff
            </a>
        </div>
        @endcan
    </div>

    <!-- Tariffs List -->
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Provider</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Active Period</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($tariffs as $tariff)
                            @php
                                $isActive = $tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now());
                            @endphp
                            <tr class="{{ $isActive ? 'bg-green-50' : '' }}">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                    {{ $tariff->name }}
                                    @if($isActive)
                                        <span class="ml-2 inline-flex items-center rounded-md bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $tariff->provider->name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                        {{ $tariffTypeLabels[$tariff->configuration['type'] ?? 'flat'] ?? ucfirst(str_replace('_', ' ', $tariff->configuration['type'] ?? 'flat')) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $tariff->active_from->format('Y-m-d') }} 
                                    @if($tariff->active_until)
                                        - {{ $tariff->active_until->format('Y-m-d') }}
                                    @else
                                        - Present
                                    @endif
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    @can('update', $tariff)
                                    <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                        Edit
                                    </a>
                                    @endcan
                                    <a href="{{ route('admin.tariffs.show', $tariff) }}" class="text-indigo-600 hover:text-indigo-900">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">
                                    No tariffs found. Create your first tariff to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($tariffs->hasPages())
    <div class="mt-6">
        {{ $tariffs->links() }}
    </div>
    @endif
</div>
@endsection
