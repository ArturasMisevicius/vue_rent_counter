@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.index') }}">Invoices</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.show', $invoice) }}">Invoice #{{ $invoice->id }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Edit</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Invoice #{{ $invoice->id }}</h1>
            <p class="mt-2 text-sm text-gray-700">Modify draft invoice details before finalization</p>
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
                        label="Tenant"
                        :options="$tenants->mapWithKeys(function($tenant) {
                            return [$tenant->id => $tenant->name . ' - ' . ($tenant->property->address ?? 'No property')];
                        })->toArray()"
                        :selected="old('tenant_renter_id', $invoice->tenant_renter_id)"
                        required
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input
                            name="billing_period_start"
                            label="Billing Period Start"
                            type="date"
                            :value="old('billing_period_start', $invoice->billing_period_start->format('Y-m-d'))"
                            required
                        />

                        <x-form-input
                            name="billing_period_end"
                            label="Billing Period End"
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
                                    Changing the billing period will regenerate the invoice items based on meter readings and tariffs for the new period. This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-4">
                        <x-button href="{{ route('manager.invoices.show', $invoice) }}" variant="secondary">
                            Cancel
                        </x-button>
                        <x-button type="submit">
                            Update Invoice
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>

        <!-- Current Line Items -->
        <div class="mt-8">
            <x-card>
                <x-slot name="title">Current Line Items</x-slot>
                
                @if($invoice->items->isNotEmpty())
                <div class="mt-4">
                    <x-data-table>
                        <x-slot name="header">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Description</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Unit</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Quantity</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Unit Price</th>
                                <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Total</th>
                            </tr>
                        </x-slot>

                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-0">
                                {{ $item->description }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $item->unit ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-right">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-right">
                                €{{ number_format($item->unit_price, 4) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right font-medium">
                                €{{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                        @endforeach

                        <tr class="border-t-2 border-gray-300">
                            <td colspan="4" class="py-4 pl-4 pr-3 text-sm font-semibold text-gray-900 text-right sm:pl-0">
                                Total Amount:
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900 text-right">
                                €{{ number_format($invoice->total_amount, 2) }}
                            </td>
                        </tr>
                    </x-data-table>
                </div>
                @else
                    <p class="mt-4 text-sm text-gray-500">No line items for this invoice.</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
@endsection
