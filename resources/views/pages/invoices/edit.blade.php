@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('invoices.shared.edit.title', ['id' => $invoice->id]))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.edit.title', ['id' => $invoice->id]) }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.edit.description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.invoices.update', $invoice) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-select
                        name="tenant_renter_id"
                        :label="__('invoices.shared.create.shared')"
                        :options="$tenants->mapWithKeys(function($tenant) {
                            return [$tenant->id => $tenant->name . ' - ' . ($tenant->property->address ?? __('invoices.shared.create.tenant_option_no_property'))];
                        })->toArray()"
                        :selected="old('tenant_renter_id', $invoice->tenant_renter_id)"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="billing_period_start"
                            :label="__('invoices.shared.create.period_start')"
                            type="date"
                            :value="old('billing_period_start', $invoice->billing_period_start->format('Y-m-d'))"
                            required
                        />

                        <x-form-input
                            name="billing_period_end"
                            :label="__('invoices.shared.create.period_end')"
                            type="date"
                            :value="old('billing_period_end', $invoice->billing_period_end->format('Y-m-d'))"
                            required
                        />
                    </div>

                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-yellow-700">
                                    {{ __('invoices.shared.edit.warning') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.invoices.show', $invoice) }}" variant="secondary">
                            {{ __('invoices.shared.edit.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('invoices.shared.edit.submit') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>

        <!-- Current Line Items -->
        <div class="mt-8">
            <x-card>
                <x-slot name="title">{{ __('invoices.shared.edit.current_items') }}</x-slot>
                
                @if($invoice->items->isNotEmpty())
                <div class="mt-4">
                    <x-data-table>
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('invoices.shared.show.line_items.description') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.unit') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.quantity') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.unit_price') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.total') }}</th>
                            </tr>
                        </x-slot>

                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="py-4 pl-4 pr-3 text-sm text-slate-900 sm:pl-0">
                                {{ $item->description }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $item->unit ?? __('invoices.shared.show.line_items.unit_na') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                                €{{ number_format($item->unit_price, 4) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right font-medium">
                                €{{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                        @endforeach

                        <tr class="border-t-2 border-slate-300">
                            <td colspan="4" class="py-4 pl-4 pr-3 text-sm font-semibold text-slate-900 text-right sm:pl-0">
                                {{ __('invoices.shared.show.line_items.total_amount') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-slate-900 text-right">
                                €{{ number_format($invoice->total_amount, 2) }}
                            </td>
                        </tr>
                    </x-data-table>
                </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">{{ __('invoices.shared.show.line_items.empty') }}</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('invoices.shared.edit.title', ['id' => $invoice->id]))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.edit.title', ['id' => $invoice->id]) }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('invoices.shared.edit.description') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('manager.invoices.update', $invoice) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <x-form-select
                        name="tenant_renter_id"
                        :label="__('invoices.shared.create.shared')"
                        :options="$tenants->mapWithKeys(function($tenant) {
                            return [$tenant->id => $tenant->name . ' - ' . ($tenant->property->address ?? __('invoices.shared.create.tenant_option_no_property'))];
                        })->toArray()"
                        :selected="old('tenant_renter_id', $invoice->tenant_renter_id)"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="billing_period_start"
                            :label="__('invoices.shared.create.period_start')"
                            type="date"
                            :value="old('billing_period_start', $invoice->billing_period_start->format('Y-m-d'))"
                            required
                        />

                        <x-form-input
                            name="billing_period_end"
                            :label="__('invoices.shared.create.period_end')"
                            type="date"
                            :value="old('billing_period_end', $invoice->billing_period_end->format('Y-m-d'))"
                            required
                        />
                    </div>

                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm text-yellow-700">
                                    {{ __('invoices.shared.edit.warning') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.invoices.show', $invoice) }}" variant="secondary">
                            {{ __('invoices.shared.edit.cancel') }}
                        </x-button>
                        <x-button type="submit">
                            {{ __('invoices.shared.edit.submit') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>

        <!-- Current Line Items -->
        <div class="mt-8">
            <x-card>
                <x-slot name="title">{{ __('invoices.shared.edit.current_items') }}</x-slot>
                
                @if($invoice->items->isNotEmpty())
                <div class="mt-4">
                    <x-data-table>
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('invoices.shared.show.line_items.description') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.unit') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.quantity') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.unit_price') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.shared.show.line_items.total') }}</th>
                            </tr>
                        </x-slot>

                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="py-4 pl-4 pr-3 text-sm text-slate-900 sm:pl-0">
                                {{ $item->description }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                {{ $item->unit ?? __('invoices.shared.show.line_items.unit_na') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                                €{{ number_format($item->unit_price, 4) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right font-medium">
                                €{{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                        @endforeach

                        <tr class="border-t-2 border-slate-300">
                            <td colspan="4" class="py-4 pl-4 pr-3 text-sm font-semibold text-slate-900 text-right sm:pl-0">
                                {{ __('invoices.shared.show.line_items.total_amount') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-slate-900 text-right">
                                €{{ number_format($invoice->total_amount, 2) }}
                            </td>
                        </tr>
                    </x-data-table>
                </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">{{ __('invoices.shared.show.line_items.empty') }}</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
@endsection
@endswitch
