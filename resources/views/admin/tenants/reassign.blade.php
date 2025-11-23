@extends('layouts.app')

@section('title', 'Reassign Tenant')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('admin.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('admin.tenants.index') }}">Tenants</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('admin.tenants.show', $tenant) }}">{{ $tenant->name }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Reassign</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Reassign Tenant to Different Property</h1>
            <p class="mt-2 text-sm text-gray-700">Move {{ $tenant->name }} to a different property in your portfolio</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc space-y-1 pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-8">
        <form action="{{ route('admin.tenants.reassign', $tenant) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <x-card>
                <div class="space-y-6">
                    <!-- Current Property -->
                    <div class="rounded-md bg-blue-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-blue-800">Current Property</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    @if($tenant->property)
                                        <p class="font-medium">{{ $tenant->property->address }}</p>
                                        <p class="mt-1">{{ enum_label($tenant->property->type) }} - {{ $tenant->property->area }} m²</p>
                                    @else
                                        <p>No property currently assigned</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Property Selection -->
                    <div>
                        <label for="property_id" class="block text-sm font-medium leading-6 text-gray-900">New Property</label>
                        <div class="mt-2">
                            <select name="property_id" id="property_id" required
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="">Select a property</option>
                                @foreach($properties as $property)
                                <option value="{{ $property->id }}">
                                        {{ $property->address }} ({{ enum_label($property->type) }} - {{ $property->area }} m²)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($properties->isEmpty())
                            <p class="mt-1 text-sm text-red-600">No other properties available for reassignment.</p>
                        @else
                            <p class="mt-1 text-sm text-gray-500">Select the property to reassign this tenant to</p>
                        @endif
                    </div>

                    <!-- Warning Message -->
                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Important Information</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc space-y-1 pl-5">
                                        <li>All historical meter readings and invoices will be preserved</li>
                                        <li>The tenant will be notified via email about the reassignment</li>
                                        <li>This action will be logged in the audit trail</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" {{ $properties->isEmpty() ? 'disabled' : '' }}>
                    Reassign Tenant
                </button>
            </div>
        </form>
    </div>

    <!-- Reassignment History -->
    @if($tenant->property)
    <div class="mt-8">
        <x-card title="Reassignment History">
            <p class="text-sm text-gray-500">Previous property assignments will be displayed here after reassignment</p>
        </x-card>
    </div>
    @endif
</div>
@endsection
