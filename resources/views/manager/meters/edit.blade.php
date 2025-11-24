@extends('layouts.app')

@section('title', __('meters.actions.edit_meter'))

@section('content')
@php($meterTypeOptions = \App\Enums\MeterType::labels())
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meters.index') }}">{{ __('app.nav.meters') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.meters.show', $meter) }}">{{ $meter->serial_number }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('meters.actions.edit') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meters.actions.edit_meter') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meters.headings.show_description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.meters.update', $meter) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="serial_number"
                        :label="__('meters.labels.serial_number')"
                        type="text"
                        :value="old('serial_number', $meter->serial_number)"
                        required
                        placeholder="ABC123456"
                    />

                    <x-form-select
                        name="type"
                        :label="__('meters.labels.type')"
                        :options="$meterTypeOptions"
                        :selected="old('type', $meter->type->value)"
                        required
                    />

                    <x-form-select
                        name="property_id"
                        :label="__('meters.labels.property')"
                        :options="$properties->pluck('address', 'id')->toArray()"
                        :selected="old('property_id', $meter->property_id)"
                        required
                    />

                    <x-form-input
                        name="installation_date"
                        :label="__('meters.labels.installation_date')"
                        type="date"
                        :value="old('installation_date', $meter->installation_date->format('Y-m-d'))"
                        required
                    />

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            name="supports_zones"
                            id="supports_zones"
                            value="1"
                            {{ old('supports_zones', $meter->supports_zones) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                        >
                        <label for="supports_zones" class="ml-2 block text-sm text-slate-900">
                            {{ __('meters.manager.index.headers.zones') }} ({{ __('meters.manager.index.zones.yes') }}/{{ __('meters.manager.index.zones.no') }})
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.meters.show', $meter) }}" variant="secondary">
                            {{ __('invoices.manager.edit.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('meters.actions.edit_meter') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
