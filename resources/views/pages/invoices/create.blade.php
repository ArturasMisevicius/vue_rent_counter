@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('invoices.shared.create.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.create.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.create.description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.invoices.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-select
                        name="tenant_renter_id"
                        :label="__('invoices.shared.create.shared')"
                        :options="$tenants->mapWithKeys(function($tenant) {
                            return [$tenant->id => $tenant->name . ' - ' . ($tenant->property->address ?? __('invoices.shared.create.tenant_option_no_property'))];
                        })->toArray()"
                        :selected="old('tenant_renter_id')"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="billing_period_start"
                            :label="__('invoices.shared.create.period_start')"
                            type="date"
                            :value="old('billing_period_start', now()->startOfMonth()->format('Y-m-d'))"
                            required
                        />

                        <x-form-input
                            name="billing_period_end"
                            :label="__('invoices.shared.create.period_end')"
                            type="date"
                            :value="old('billing_period_end', now()->endOfMonth()->format('Y-m-d'))"
                            required
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
                                    {{ __('invoices.shared.create.info') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.invoices.index') }}" variant="secondary">
                            {{ __('invoices.shared.create.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('invoices.shared.create.submit') }}
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

@section('title', __('invoices.shared.create.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.create.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.create.description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.invoices.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-select
                        name="tenant_renter_id"
                        :label="__('invoices.shared.create.shared')"
                        :options="$tenants->mapWithKeys(function($tenant) {
                            return [$tenant->id => $tenant->name . ' - ' . ($tenant->property->address ?? __('invoices.shared.create.tenant_option_no_property'))];
                        })->toArray()"
                        :selected="old('tenant_renter_id')"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="billing_period_start"
                            :label="__('invoices.shared.create.period_start')"
                            type="date"
                            :value="old('billing_period_start', now()->startOfMonth()->format('Y-m-d'))"
                            required
                        />

                        <x-form-input
                            name="billing_period_end"
                            :label="__('invoices.shared.create.period_end')"
                            type="date"
                            :value="old('billing_period_end', now()->endOfMonth()->format('Y-m-d'))"
                            required
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
                                    {{ __('invoices.shared.create.info') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.invoices.index') }}" variant="secondary">
                            {{ __('invoices.shared.create.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('invoices.shared.create.submit') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
@endswitch
