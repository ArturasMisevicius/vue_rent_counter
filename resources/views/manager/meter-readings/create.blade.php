@extends('layouts.app')

@section('title', __('meter_readings.headings.create'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meter-readings.index') }}">{{ __('meter_readings.headings.index') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('meter_readings.headings.create') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.headings.create') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.manager.show.description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.meter-readings.store') }}" method="POST" x-data="{ 
                selectedMeter: {{ request('meter_id') ?? 'null' }},
                meters: {{ $meters->toJson() }},
                get currentMeter() {
                    return this.meters.find(m => m.id == this.selectedMeter);
                },
                get supportsZones() {
                    return this.currentMeter?.supports_zones || false;
                }
            }">
                @csrf

                <div class="space-y-6">
                    <!-- Property Selection (for grouping) -->
                    <x-form-select
                        name="property_filter"
                        label="{{ __('properties.labels.property') }}"
                        :options="$properties->pluck('address', 'id')->toArray()"
                        placeholder="{{ __('properties.filters.all_properties') ?? __('properties.labels.properties') }}"
                        x-on:change="selectedMeter = null"
                    />

                    <!-- Meter Selection -->
                    <div>
                        <label for="meter_id" class="block text-sm font-medium leading-6 text-slate-900">
                            {{ __('meters.labels.meter') }} 
                            <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="meter_id"
                            id="meter_id"
                            x-model="selectedMeter"
                            required
                            @class([
                                'mt-2 block w-full rounded-md border-0 py-1.5 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6',
                                'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500' => $errors->has('meter_id'),
                                'text-slate-900 ring-slate-300 focus:ring-indigo-600' => !$errors->has('meter_id'),
                            ])
                        >
                            <option value="">{{ __('meter_readings.manager.create.select_meter') }}</option>
                            @foreach($meters as $meter)
                            <option value="{{ $meter->id }}" {{ old('meter_id', request('meter_id')) == $meter->id ? 'selected' : '' }}>
                                {{ $meter->serial_number }} - {{ $meter->property->address }} ({{ enum_label($meter->type) }})
                            </option>
                            @endforeach
                        </select>
                        @error('meter_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-form-input
                        name="reading_date"
                        label="{{ __('meter_readings.labels.reading_date') }}"
                        type="date"
                        :value="old('reading_date', now()->format('Y-m-d'))"
                        required
                    />

                    <x-form-input
                        name="value"
                        label="{{ __('meter_readings.labels.value') }}"
                        type="number"
                        step="0.01"
                        :value="old('value')"
                        required
                        placeholder="1234.56"
                    />

                    <!-- Zone field (only for meters that support zones) -->
                    <div x-show="supportsZones" x-cloak>
                        <x-form-select
                            name="zone"
                            label="{{ __('meter_readings.labels.zone') }}"
                            :options="[
                                'day' => __('meter_readings.manager.create.zone_options.day'),
                                'night' => __('meter_readings.manager.create.zone_options.night')
                            ]"
                            :selected="old('zone')"
                            placeholder="{{ __('meter_readings.labels.zone') }}"
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
                                <p class="text-sm text-blue-700">
                                    {{ __('meter_readings.validation.value.min') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.meter-readings.index') }}" variant="secondary">
                            {{ __('meter_readings.actions.back') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('meter_readings.headings.create') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
