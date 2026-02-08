@extends('layouts.app')

@section('title', __('meters.actions.add'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meters.actions.add') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meters.headings.show_description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.meters.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <input type="hidden" name="type" value="{{ \App\Enums\MeterType::CUSTOM->value }}">
                    <x-form-input
                        name="serial_number"
                        :label="__('meters.labels.serial_number')"
                        type="text"
                        :value="old('serial_number')"
                        required
                        placeholder="ABC123456"
                    />

                    <x-form-select
                        name="service_configuration_id"
                        label="Service"
                        :options="$serviceConfigurationOptions"
                        :selected="old('service_configuration_id')"
                        placeholder="—"
                    />

                    <x-form-select
                        name="property_id"
                        :label="__('meters.labels.property')"
                        :options="$properties->pluck('address', 'id')->toArray()"
                        :selected="old('property_id', request('property_id'))"
                        required
                    />

                    <x-form-input
                        name="installation_date"
                        :label="__('meters.labels.installation_date')"
                        type="date"
                        :value="old('installation_date', now()->format('Y-m-d'))"
                        required
                    />

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            name="supports_zones"
                            id="supports_zones"
                            value="1"
                            {{ old('supports_zones') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                        >
                        <label for="supports_zones" class="ml-2 block text-sm text-slate-900">
                            {{ __('meters.shared.index.headers.zones') }} ({{ __('meters.shared.index.zones.yes') }}/{{ __('meters.shared.index.zones.no') }})
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.meters.index') }}" variant="secondary">
                            {{ __('invoices.shared.create.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('meters.actions.add') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
