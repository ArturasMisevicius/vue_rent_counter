@extends('layouts.app')

@section('title', __('invoices.manager.create.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.index') }}">{{ __('app.nav.invoices') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('invoices.manager.create.breadcrumb') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.manager.create.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.manager.create.description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.invoices.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-select
                        name="tenant_renter_id"
                        :label="__('invoices.manager.create.tenant')"
                        :options="$tenants->mapWithKeys(function($tenant) {
                            return [$tenant->id => $tenant->name . ' - ' . ($tenant->property->address ?? __('invoices.manager.create.tenant_option_no_property'))];
                        })->toArray()"
                        :selected="old('tenant_renter_id')"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="billing_period_start"
                            :label="__('invoices.manager.create.period_start')"
                            type="date"
                            :value="old('billing_period_start', now()->startOfMonth()->format('Y-m-d'))"
                            required
                        />

                        <x-form-input
                            name="billing_period_end"
                            :label="__('invoices.manager.create.period_end')"
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
                                    {{ __('invoices.manager.create.info') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.invoices.index') }}" variant="secondary">
                            {{ __('invoices.manager.create.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('invoices.manager.create.submit') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
