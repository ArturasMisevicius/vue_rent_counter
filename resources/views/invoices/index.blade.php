@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Invoices</h1>
        <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Create Invoice
        </a>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('invoices.index') }}" 
               class="border-b-2 {{ !request()->routeIs('invoices.drafts') && !request()->routeIs('invoices.finalized') && !request()->routeIs('invoices.paid') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                All
            </a>
            <a href="{{ route('invoices.drafts') }}" 
               class="border-b-2 {{ request()->routeIs('invoices.drafts') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                Drafts
            </a>
            <a href="{{ route('invoices.finalized') }}" 
               class="border-b-2 {{ request()->routeIs('invoices.finalized') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                Finalized
            </a>
            <a href="{{ route('invoices.paid') }}" 
               class="border-b-2 {{ request()->routeIs('invoices.paid') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                Paid
            </a>
        </nav>
    </div>

    {{-- Invoices List --}}
    @if($invoices->isEmpty())
        <div class="bg-white shadow-md rounded-lg p-6">
            <p class="text-gray-500 text-center">No invoices found.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($invoices as $invoice)
                <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">
                                Invoice #{{ $invoice->id }}
                            </h3>
                            <p class="text-gray-600 mt-1">
                                Period: {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}
                            </p>
                            @if($invoice->tenant)
                                <p class="text-gray-600 text-sm">
                                    Tenant: {{ $invoice->tenant->name }}
                                </p>
                                @if($invoice->tenant->property)
                                    <p class="text-gray-600 text-sm">
                                        Property: {{ $invoice->tenant->property->address }}
                                    </p>
                                @endif
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
                    
                    <div class="flex justify-end mt-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('invoices.show', $invoice) }}" 
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
