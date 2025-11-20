@extends('layouts.app')

@section('title', 'Edit Provider')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.providers.index')">Providers</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.providers.show', $provider)">{{ $provider->name }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Edit</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Provider</h1>
            <p class="mt-2 text-sm text-gray-700">Update provider information</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('admin.providers.update', $provider) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input 
                        name="name" 
                        label="Provider Name" 
                        :value="old('name', $provider->name)" 
                        required 
                    />

                    <x-form-select 
                        name="service_type" 
                        label="Service Type" 
                        :options="['electricity' => 'Electricity', 'water' => 'Water', 'heating' => 'Heating']" 
                        :selected="old('service_type', $provider->service_type->value)" 
                        required 
                    />

                    <div>
                        <label for="contact_info" class="block text-sm font-medium text-gray-700">Contact Information</label>
                        <textarea 
                            id="contact_info" 
                            name="contact_info" 
                            rows="3" 
                            @class([
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 sm:text-sm',
                                'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('contact_info'),
                                'border-gray-300 focus:border-indigo-500' => !$errors->has('contact_info'),
                            ])
                        >{{ old('contact_info', is_array($provider->contact_info) ? ($provider->contact_info['notes'] ?? '') : $provider->contact_info) }}</textarea>
                        @error('contact_info')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-3">
                    <a href="{{ route('admin.providers.show', $provider) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Update Provider
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
