@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('admin')
@extends('layouts.app')

@section('title', __('tenants.pages.admin_form.title'))

@section('content')
<x-backoffice.page
    class="px-4 sm:px-6 lg:px-8"
    :title="__('tenants.pages.admin_form.title')"
    :description="__('tenants.pages.admin_form.subtitle')"
>
    <div class="max-w-3xl">
        <x-validation-errors />
    </div>

    <div class="max-w-3xl">
        <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-6">
            @csrf

            <x-card>
                <div class="space-y-6">
                    <x-form-input
                        name="name"
                        :label="__('tenants.pages.admin_form.labels.name')"
                        required
                    />

                    <div class="space-y-1">
                        <x-form-input
                            name="email"
                            type="email"
                            :label="__('tenants.pages.admin_form.labels.email')"
                            required
                        />
                        <p class="mt-1 text-sm text-slate-500">{{ __('tenants.pages.admin_form.notes.credentials_sent') }}</p>
                    </div>

                    <x-form-input
                        name="password"
                        type="password"
                        :label="__('tenants.pages.admin_form.labels.password')"
                        required
                        autocomplete="new-password"
                    />

                    <x-form-input
                        name="password_confirmation"
                        type="password"
                        :label="__('tenants.pages.admin_form.labels.password_confirmation')"
                        required
                        autocomplete="new-password"
                    />

                    <div class="space-y-1">
                        <x-form-select
                            name="property_id"
                            :label="__('tenants.pages.admin_form.labels.property')"
                            :options="$properties->mapWithKeys(fn ($property) => [(string) $property->id => $property->address])->all()"
                            :placeholder="__('tenants.pages.admin_form.placeholders.property')"
                            required
                        />
                        @if($properties->isEmpty())
                            <p class="mt-1 text-sm text-red-600">{{ __('tenants.pages.admin_form.notes.no_properties') }}</p>
                        @endif
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <x-button
                    :href="route('admin.tenants.index')"
                    variant="secondary"
                >
                    {{ __('tenants.pages.admin_form.actions.cancel') }}
                </x-button>
                <x-button type="submit">
                    {{ __('tenants.pages.admin_form.actions.submit') }}
                </x-button>
            </div>
        </form>
    </div>
</x-backoffice.page>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('tenants.pages.admin_form.title'))

@section('content')
<x-backoffice.page
    class="px-4 sm:px-6 lg:px-8"
    :title="__('tenants.pages.admin_form.title')"
    :description="__('tenants.pages.admin_form.subtitle')"
>
    <div class="max-w-3xl">
        <x-validation-errors />
    </div>

    <div class="max-w-3xl">
        <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-6">
            @csrf

            <x-card>
                <div class="space-y-6">
                    <x-form-input
                        name="name"
                        :label="__('tenants.pages.admin_form.labels.name')"
                        required
                    />

                    <div class="space-y-1">
                        <x-form-input
                            name="email"
                            type="email"
                            :label="__('tenants.pages.admin_form.labels.email')"
                            required
                        />
                        <p class="mt-1 text-sm text-slate-500">{{ __('tenants.pages.admin_form.notes.credentials_sent') }}</p>
                    </div>

                    <x-form-input
                        name="password"
                        type="password"
                        :label="__('tenants.pages.admin_form.labels.password')"
                        required
                        autocomplete="new-password"
                    />

                    <x-form-input
                        name="password_confirmation"
                        type="password"
                        :label="__('tenants.pages.admin_form.labels.password_confirmation')"
                        required
                        autocomplete="new-password"
                    />

                    <div class="space-y-1">
                        <x-form-select
                            name="property_id"
                            :label="__('tenants.pages.admin_form.labels.property')"
                            :options="$properties->mapWithKeys(fn ($property) => [(string) $property->id => $property->address])->all()"
                            :placeholder="__('tenants.pages.admin_form.placeholders.property')"
                            required
                        />
                        @if($properties->isEmpty())
                            <p class="mt-1 text-sm text-red-600">{{ __('tenants.pages.admin_form.notes.no_properties') }}</p>
                        @endif
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <x-button
                    :href="route('admin.tenants.index')"
                    variant="secondary"
                >
                    {{ __('tenants.pages.admin_form.actions.cancel') }}
                </x-button>
                <x-button type="submit">
                    {{ __('tenants.pages.admin_form.actions.submit') }}
                </x-button>
            </div>
        </form>
    </div>
</x-backoffice.page>
@endsection
@endswitch
