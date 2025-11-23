@extends('layouts.app')

@section('content')
@php($invoiceStatusLabels = \App\Enums\InvoiceStatus::labels())
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">My Invoices</h1>

    {{-- Search and Filter Form --}}
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('tenant.invoices.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Property Filter (for multi-property tenants) --}}
                @if(count($properties) > 1)
                <div>
                    <label for="property_id" class="block text-sm font-medium text-gray-700">Property</label>
                    <select name="property_id" id="property_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Properties</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->address }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                {{-- Status Filter --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach($invoiceStatusLabels as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Date Range Filters --}}
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Apply Filters
                </button>
                @if(request()->hasAny(['property_id', 'status', 'from_date', 'to_date']))
                <a href="{{ route('tenant.invoices.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Clear Filters
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Invoices List --}}
    @if($invoices->isEmpty())
        <div class="bg-white shadow-md rounded-lg p-6">
            <p class="text-gray-500 text-center">No invoices found.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($invoices as $invoice)
                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">
                                Invoice #{{ $invoice->id }}
                            </h3>
                            <p class="text-gray-600 mt-1">
                                Period: {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}
                            </p>
                            @if($invoice->tenant && $invoice->tenant->property)
                                <p class="text-gray-600 text-sm">
                                    Property: {{ $invoice->tenant->property->address }}
                                </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                @if($invoice->status->value === 'draft') bg-yellow-100 text-yellow-800
                                @elseif($invoice->status->value === 'finalized') bg-blue-100 text-blue-800
                                @elseif($invoice->status->value === 'paid') bg-green-100 text-green-800
                                @endif">
                                {{ enum_label($invoice->status) }}
                            </span>
                            <p class="text-2xl font-bold text-gray-900 mt-2">
                                €{{ number_format($invoice->total_amount, 2) }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            {{ $invoice->items->count() }} item(s)
                        </div>
                        <a href="{{ route('tenant.invoices.show', $invoice) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            View Details
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
