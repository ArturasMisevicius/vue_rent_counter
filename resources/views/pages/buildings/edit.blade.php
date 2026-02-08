@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('buildings.pages.manager_form.edit_title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('buildings.pages.manager_form.edit_title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('buildings.pages.manager_form.edit_subtitle') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.buildings.update', $building) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="name"
                        label="{{ __('buildings.pages.manager_form.labels.name') }}"
                        type="text"
                        :value="old('name', $building->name)"
                        required
                        placeholder="{{ __('buildings.pages.manager_form.placeholders.name') }}"
                    />

                    <x-form-input
                        name="address"
                        label="{{ __('buildings.pages.manager_form.labels.address') }}"
                        type="text"
                        :value="old('address', $building->address)"
                        required
                        placeholder="{{ __('buildings.pages.manager_form.placeholders.address') }}"
                    />

                    <x-form-input
                        name="total_apartments"
                        label="{{ __('buildings.pages.manager_form.labels.total_apartments') }}"
                        type="number"
                        :value="old('total_apartments', $building->total_apartments)"
                        required
                        placeholder="{{ __('buildings.pages.manager_form.placeholders.total_apartments') }}"
                        min="1"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.buildings.show', $building) }}" variant="secondary">
                            {{ __('buildings.pages.manager_form.actions.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('buildings.pages.manager_form.actions.save_edit') }}
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

@section('title', __('buildings.pages.manager_form.edit_title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('buildings.pages.manager_form.edit_title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('buildings.pages.manager_form.edit_subtitle') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.buildings.update', $building) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-input
                        name="name"
                        label="{{ __('buildings.pages.manager_form.labels.name') }}"
                        type="text"
                        :value="old('name', $building->name)"
                        required
                        placeholder="{{ __('buildings.pages.manager_form.placeholders.name') }}"
                    />

                    <x-form-input
                        name="address"
                        label="{{ __('buildings.pages.manager_form.labels.address') }}"
                        type="text"
                        :value="old('address', $building->address)"
                        required
                        placeholder="{{ __('buildings.pages.manager_form.placeholders.address') }}"
                    />

                    <x-form-input
                        name="total_apartments"
                        label="{{ __('buildings.pages.manager_form.labels.total_apartments') }}"
                        type="number"
                        :value="old('total_apartments', $building->total_apartments)"
                        required
                        placeholder="{{ __('buildings.pages.manager_form.placeholders.total_apartments') }}"
                        min="1"
                    />

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.buildings.show', $building) }}" variant="secondary">
                            {{ __('buildings.pages.manager_form.actions.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('buildings.pages.manager_form.actions.save_edit') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
@endswitch
