@extends('layouts.app')

@section('title', 'Edit Tariff')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.tariffs.index')">Tariffs</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.tariffs.show', $tariff)">{{ $tariff->name }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Edit</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Tariff</h1>
            <p class="mt-2 text-sm text-gray-700">Update tariff configuration or create a new version</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('admin.tariffs.update', $tariff) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input 
                        name="name" 
                        label="Tariff Name" 
                        :value="old('name', $tariff->name)" 
                        required 
                    />

                    <x-form-select 
                        name="provider_id" 
                        label="Provider" 
                        :options="$providers->pluck('name', 'id')" 
                        :selected="old('provider_id', $tariff->provider_id)" 
                        required 
                    />

                    <div>
                        <label for="configuration" class="block text-sm font-medium text-gray-700">Configuration</label>
                        <div class="mt-2 rounded-md bg-gray-50 p-4 text-xs text-gray-700 space-y-2">
                            <p class="font-semibold">Required Fields:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><code>type</code>: "flat" or "time_of_use"</li>
                                <li><code>currency</code>: "EUR"</li>
                                <li>For flat: <code>rate</code> (numeric)</li>
                                <li>For time_of_use: <code>zones</code> array with id, start, end, rate</li>
                            </ul>
                        </div>
                        
                        <textarea 
                            id="configuration" 
                            name="configuration" 
                            rows="12" 
                            @class([
                                'mt-2 block w-full rounded-md shadow-sm focus:ring-indigo-500 sm:text-sm font-mono',
                                'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('configuration'),
                                'border-gray-300 focus:border-indigo-500' => !$errors->has('configuration'),
                            ])
                            required
                        >{{ old('configuration', json_encode($tariff->configuration, JSON_PRETTY_PRINT)) }}</textarea>
                        
                        @error('configuration')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('configuration.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input 
                            name="active_from" 
                            label="Active From" 
                            type="date" 
                            :value="old('active_from', $tariff->active_from->format('Y-m-d'))" 
                            required 
                        />

                        <x-form-input 
                            name="active_until" 
                            label="Active Until (Optional)" 
                            type="date" 
                            :value="old('active_until', $tariff->active_until?->format('Y-m-d'))" 
                        />
                    </div>

                    <div class="rounded-md bg-blue-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-blue-800">Versioning</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Check the box below to create a new version instead of updating the existing tariff. This preserves historical pricing data.</p>
                                </div>
                                <div class="mt-4">
                                    <div class="flex items-center">
                                        <input 
                                            id="create_new_version" 
                                            name="create_new_version" 
                                            type="checkbox" 
                                            value="1"
                                            {{ old('create_new_version') ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                        >
                                        <label for="create_new_version" class="ml-2 text-sm font-medium text-blue-700">
                                            Create new version (preserves historical data)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-3">
                    <a href="{{ route('admin.tariffs.show', $tariff) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Update Tariff
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
