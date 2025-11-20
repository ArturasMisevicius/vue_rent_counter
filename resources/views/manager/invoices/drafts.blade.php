@extends('layouts.app')

@section('title', 'Draft Invoices')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.index') }}">Invoices</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Drafts</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Draft Invoices</h1>
            <p class="mt-2 text-sm text-gray-700">Invoices that can still be edited before finalization</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Invoice::class)
            <x-button href="{{ route('manager.invoices.create') }}">
                Generate Invoice
            </x-button>
            @endcan
        </div>
    </div>

    <x-card class="mt-8">
        <x-data-table>
            <x-slot name="header">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Invoice #</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Property</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Billing Period</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Created</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($invoices as $invoice)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                    <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                        #{{ $invoice->id }}
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    @if($invoice->tenant && $invoice->tenant->property)
                        <a href="{{ route('manager.properties.show', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $invoice->tenant->property->address }}
                        </a>
                    @else
                        <span class="text-gray-400">N/A</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    €{{ number_format($invoice->total_amount, 2) }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ $invoice->created_at->format('M d, Y') }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        @can('view', $invoice)
                        <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                            View
                        </a>
                        @endcan
                        @can('update', $invoice)
                        <a href="{{ route('manager.invoices.edit', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                    No draft invoices found. 
                    @can('create', App\Models\Invoice::class)
                        <a href="{{ route('manager.invoices.create') }}" class="text-indigo-600 hover:text-indigo-900">Generate one now</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>

        @if($invoices->hasPages())
        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
