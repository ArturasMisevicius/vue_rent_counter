@extends('layouts.app')

@section('title', __('invoices.manager.finalized.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.index') }}">{{ __('app.nav.invoices') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('invoices.manager.finalized.title') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.manager.finalized.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.manager.finalized.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Invoice::class)
            <x-button href="{{ route('manager.invoices.create') }}">
                {{ __('invoices.manager.index.generate') }}
            </x-button>
            @endcan
        </div>
    </div>

    <div class="mt-8">
        @livewire('manager.invoice-filters', ['view' => 'finalized'])
    </div>
</div>
@endsection
