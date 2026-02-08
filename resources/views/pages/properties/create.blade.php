@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('properties.pages.manager_form.create_title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('properties.pages.manager_form.create_title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('properties.pages.manager_form.create_subtitle') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.properties.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input
                        name="address"
                        label="{{ __('properties.pages.manager_form.labels.address') }}"
                        type="text"
                        :value="old('address')"
                        required
                        placeholder="{{ __('properties.pages.manager_form.placeholders.address') }}"
                    />

                    <x-form-select
                        name="type"
                        label="{{ __('properties.pages.manager_form.labels.type') }}"
                        :options="$propertyTypeOptions"
                        :selected="old('type')"
                        required
                    />

                    <x-form-input
                        name="area_sqm"
                        label="{{ __('properties.pages.manager_form.labels.area') }}"
                        type="number"
                        step="0.01"
                        :value="old('area_sqm')"
                        required
                        placeholder="{{ __('properties.pages.manager_form.placeholders.area') }}"
                    />

                    <x-form-select
                        name="building_id"
                        label="{{ __('properties.pages.manager_form.labels.building') }}"
                        :options="$buildings->pluck('address', 'id')->toArray()"
                        :selected="old('building_id')"
                        placeholder="{{ __('properties.pages.manager_form.placeholders.building') }}"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.properties.index') }}" variant="secondary">
                            {{ __('properties.pages.manager_form.actions.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('properties.pages.manager_form.actions.save_create') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('properties.pages.manager_form.create_title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('properties.pages.manager_form.create_title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('properties.pages.manager_form.create_subtitle') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.properties.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input
                        name="address"
                        label="{{ __('properties.pages.manager_form.labels.address') }}"
                        type="text"
                        :value="old('address')"
                        required
                        placeholder="{{ __('properties.pages.manager_form.placeholders.address') }}"
                    />

                    <x-form-select
                        name="type"
                        label="{{ __('properties.pages.manager_form.labels.type') }}"
                        :options="$propertyTypeOptions"
                        :selected="old('type')"
                        required
                    />

                    <x-form-input
                        name="area_sqm"
                        label="{{ __('properties.pages.manager_form.labels.area') }}"
                        type="number"
                        step="0.01"
                        :value="old('area_sqm')"
                        required
                        placeholder="{{ __('properties.pages.manager_form.placeholders.area') }}"
                    />

                    <x-form-select
                        name="building_id"
                        label="{{ __('properties.pages.manager_form.labels.building') }}"
                        :options="$buildings->pluck('address', 'id')->toArray()"
                        :selected="old('building_id')"
                        placeholder="{{ __('properties.pages.manager_form.placeholders.building') }}"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.properties.index') }}" variant="secondary">
                            {{ __('properties.pages.manager_form.actions.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('properties.pages.manager_form.actions.save_create') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
@endswitch
