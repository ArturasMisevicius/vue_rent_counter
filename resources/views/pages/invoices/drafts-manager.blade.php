@extends('layouts.app')

@section('title', __('invoices.shared.drafts.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.drafts.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.drafts.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Invoice::class)
            <x-button href="{{ route('manager.invoices.create') }}">
                {{ __('invoices.shared.index.generate') }}
            </x-button>
            @endcan
        </div>
    </div>

    <div class="mt-8">
        @livewire('manager.invoice-filters', ['view' => 'drafts'])
    </div>
</div>
@endsection
