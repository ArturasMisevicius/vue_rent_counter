@extends('layouts.app')

@section('title', __('properties.pages.manager_form.edit_title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('properties.pages.manager_form.edit_title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('properties.pages.manager_form.edit_subtitle') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.properties.update', $property) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="address"
                        label="{{ __('properties.pages.manager_form.labels.address') }}"
                        type="text"
                        :value="old('address', $property->address)"
                        required
                        placeholder="{{ __('properties.pages.manager_form.placeholders.address') }}"
                    />

                    <x-form-select
                        name="type"
                        label="{{ __('properties.pages.manager_form.labels.type') }}"
                        :options="$propertyTypeOptions"
                        :selected="old('type', $property->type->value)"
                        required
                    />

                    <x-form-input
                        name="area_sqm"
                        label="{{ __('properties.pages.manager_form.labels.area') }}"
                        type="number"
                        step="0.01"
                        :value="old('area_sqm', $property->area_sqm)"
                        required
                        placeholder="{{ __('properties.pages.manager_form.placeholders.area') }}"
                    />

                    <x-form-select
                        name="building_id"
                        label="{{ __('properties.pages.manager_form.labels.building') }}"
                        :options="$buildings->pluck('address', 'id')->toArray()"
                        :selected="old('building_id', $property->building_id)"
                        placeholder="{{ __('properties.pages.manager_form.placeholders.building') }}"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.properties.show', $property) }}" variant="secondary">
                            {{ __('properties.pages.manager_form.actions.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('properties.pages.manager_form.actions.save_edit') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
