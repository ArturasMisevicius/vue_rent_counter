@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.index') }}">Invoices</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Invoice #{{ $invoice->id }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Invoice #{{ $invoice->id }}</h1>
            <p class="mt-2 text-sm text-gray-700">
                <x-status-badge :status="$invoice->status->value">
                    {{ ucfirst($invoice->status->value) }}
                </x-status-badge>
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @if($invoice->isDraft())
                @can('update', $invoice)
                <x-button href="{{ route('manager.invoices.edit', $invoice) }}" variant="secondary">
                    Edit Invoice
                </x-button>
                <form action="{{ route('manager.invoices.finalize', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to finalize this invoice? It will become immutable.');">
                    @csrf
                    <x-button type="submit">
                        Finalize Invoice
                    </x-button>
                </form>
                @endcan
                @can('delete', $invoice)
                <form action="{{ route('manager.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this invoice?');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">
                        Delete
                    </x-button>
                </form>
                @endcan
            @endif
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">Invoice Information</x-slot>
            
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Invoice Number</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">#{{ $invoice->id }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Billing Period</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Status</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-status-badge :status="$invoice->status->value">
                            {{ ucfirst($invoice->status->value) }}
                        </x-status-badge>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Total Amount</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">€{{ number_format($invoice->total_amount, 2) }}</span>
                    </dd>
                </div>
                @if($invoice->finalized_at)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Finalized At</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $invoice->finalized_at->format('M d, Y H:i') }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        <!-- Tenant Information -->
        <x-card>
            <x-slot name="title">Tenant Information</x-slot>
            
            @if($invoice->tenant)
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Name</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->name }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Email</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->email }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">Property</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @if($invoice->tenant->property)
                            <a href="{{ route('manager.properties.show', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $invoice->tenant->property->address }}
                            </a>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @else
                <p class="text-sm text-gray-500">Tenant information not available</p>
            @endif
        </x-card>
    </div>

    <!-- Line Items -->
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Line Items</x-slot>
            
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

    @if($invoice->isDraft())
    <div class="mt-6 rounded-md bg-yellow-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Draft Invoice</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>This invoice is in draft status. You can edit the line items before finalizing. Once finalized, the invoice becomes immutable and all pricing data is snapshotted.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
