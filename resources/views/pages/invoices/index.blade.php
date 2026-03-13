@php
    $role = auth()->user()?->role?->value;
@endphp

@extends(auth()->user()?->role?->value === 'tenant' ? 'layouts.tenant' : 'layouts.app')

@switch($role)
@case('manager')
@section('title', __('invoices.shared.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.index.description') }}</p>
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
        @livewire('manager.invoice-filters', ['view' => 'all'])
    </div>
</div>
@endsection
@break

@case('tenant')
@section('tenant-content')
<x-tenant.page :title="__('invoices.shared.title')" :description="__('invoices.shared.description')">
    <x-tenant.quick-actions />

    <x-tenant.section-card :title="__('invoices.shared.filters.title')" :description="__('invoices.shared.filters.description')">
        <form method="GET" action="{{ route('tenant.invoices.index') }}">
            <x-tenant.stack gap="4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    @if(count($properties) > 1)
                    <div>
                        <label for="property_id" class="block text-sm font-semibold text-slate-800">{{ __('invoices.shared.filters.property') }}</label>
                        <select name="property_id" id="property_id" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('invoices.shared.filters.all_properties') }}</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                                    {{ $property->address }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-800">{{ __('invoices.shared.filters.status') }}</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('invoices.shared.filters.all_statuses') }}</option>
                            @foreach($invoiceStatusLabels as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="from_date" class="block text-sm font-semibold text-slate-800">{{ __('invoices.shared.filters.from_date') }}</label>
                        <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="to_date" class="block text-sm font-semibold text-slate-800">{{ __('invoices.shared.filters.to_date') }}</label>
                        <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('invoices.shared.filters.apply') }}
                    </button>
                    @if(request()->hasAny(['property_id', 'status', 'from_date', 'to_date']))
                    <a href="{{ route('tenant.invoices.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('invoices.shared.filters.clear') }}
                    </a>
                    @endif
                </div>
            </x-tenant.stack>
        </form>
    </x-tenant.section-card>

    @if($invoices->isEmpty())
        <x-tenant.section-card :title="__('invoices.shared.empty.title')">
            <p class="text-sm text-slate-600">{{ __('invoices.shared.empty.description') }}</p>
        </x-tenant.section-card>
    @else
        <x-tenant.stack gap="4">
            @foreach($invoices as $invoice)
                <x-tenant.section-card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <x-tenant.stack gap="1">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('invoices.shared.list.invoice_label') }}</p>
                            <h3 class="text-xl font-semibold text-slate-900">#{{ $invoice->id }}</h3>
                            <p class="text-sm text-slate-600">
                                {{ __('invoices.shared.list.period', ['from' => $invoice->billing_period_start->format('Y-m-d'), 'to' => $invoice->billing_period_end->format('Y-m-d')]) }}
                            </p>
                            @if($invoice->tenant && $invoice->tenant->property)
                                <p class="text-sm text-slate-600">
                                    {{ __('invoices.shared.list.property', ['address' => $invoice->tenant->property->address]) }}
                                </p>
                            @endif
                        </x-tenant.stack>
                        <x-tenant.stack gap="2" class="text-left sm:text-right">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyles[$invoice->status->value] ?? 'bg-slate-100 text-slate-800' }}">
                                {{ enum_label($invoice->status) }}
                            </span>
                            <p class="text-3xl font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
                            @if($invoice->due_date)
                                <p class="text-sm {{ (!$invoice->isPaid() && $invoice->due_date->isPast()) ? 'text-rose-600' : 'text-slate-600' }}">
                                    {{ __('invoices.shared.list.due') }} {{ $invoice->due_date->format('Y-m-d') }}
                                    @if(!$invoice->isPaid() && $invoice->due_date->isPast())
                                        <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ __('invoices.shared.list.overdue') }}</span>
                                    @endif
                                </p>
                            @endif
                        </x-tenant.stack>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-slate-600">
                            {{ trans_choice('invoices.tenant.list.items', $invoice->items->count(), ['count' => $invoice->items->count()]) }}
                        </div>
                        <a href="{{ route('tenant.invoices.show', $invoice) }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                            {{ __('invoices.shared.list.view_details') }}
                        </a>
                    </div>
                </x-tenant.section-card>
            @endforeach
        </x-tenant.stack>

        <div>
            {{ $invoices->links() }}
        </div>
    @endif
</x-tenant.page>
@endsection
@break

@default
@section('title', __('invoices.shared.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.index.description') }}</p>
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
        @livewire('manager.invoice-filters', ['view' => 'all'])
    </div>
</div>
@endsection
@endswitch
