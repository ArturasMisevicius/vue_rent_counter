@extends('layouts.app')

@section('title', 'Create Tariff')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.tariffs.index')">Tariffs</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Create</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Create Tariff</h1>
            <p class="mt-2 text-sm text-gray-700">Add a new tariff configuration</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('admin.tariffs.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input 
                        name="name" 
                        label="Tariff Name" 
                        :value="old('name')" 
                        required 
                    />

                    <x-form-select 
                        name="provider_id" 
                        label="Provider" 
                        :options="$providers->pluck('name', 'id')" 
                        :selected="old('provider_id', request('provider_id'))" 
                        required 
                    />

                    <div>
                        <label for="configuration" class="block text-sm font-medium text-gray-700">Configuration</label>
                        <div class="mt-2 rounded-md bg-gray-50 p-4 text-xs text-gray-700 space-y-2">
                            <p class="font-semibold">Flat Rate Example:</p>
                            <pre class="bg-white p-2 rounded border border-gray-200">{{ json_encode(['type' => 'flat', 'currency' => 'EUR', 'rate' => 0.15], JSON_PRETTY_PRINT) }}</pre>
                            
                            <p class="font-semibold mt-3">Time of Use Example:</p>
                            <pre class="bg-white p-2 rounded border border-gray-200">{{ json_encode([
    'type' => 'time_of_use',
    'currency' => 'EUR',
    'zones' => [
        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.09]
    ],
    'weekend_logic' => 'apply_night_rate'
], JSON_PRETTY_PRINT) }}</pre>
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
                            placeholder="Enter tariff configuration as JSON object"
                        >{{ old('configuration', json_encode(['type' => 'flat', 'currency' => 'EUR', 'rate' => 0.15], JSON_PRETTY_PRINT)) }}</textarea>
                        
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
                            :value="old('active_from', now()->format('Y-m-d'))" 
                            required 
                        />

                        <x-form-input 
                            name="active_until" 
                            label="Active Until (Optional)" 
                            type="date" 
                            :value="old('active_until')" 
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-3">
                    <a href="{{ route('admin.tariffs.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Tariff
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
