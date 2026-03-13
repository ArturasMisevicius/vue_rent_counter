@extends('layouts.app')

@section('title', __('invoices.public_index.title'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-800">{{ __('invoices.public_index.title') }}</h1>
        <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            {{ __('invoices.public_index.create') }}
        </a>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('invoices.index') }}" 
               class="border-b-2 {{ !request()->routeIs('invoices.drafts') && !request()->routeIs('invoices.finalized') && !request()->routeIs('invoices.paid') ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} py-4 px-1 text-sm font-medium">
                {{ __('invoices.public_index.tabs.all') }}
            </a>
            <a href="{{ route('invoices.drafts') }}" 
               class="border-b-2 {{ request()->routeIs('invoices.drafts') ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} py-4 px-1 text-sm font-medium">
                {{ __('invoices.public_index.tabs.drafts') }}
            </a>
            <a href="{{ route('invoices.finalized') }}" 
               class="border-b-2 {{ request()->routeIs('invoices.finalized') ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} py-4 px-1 text-sm font-medium">
                {{ __('invoices.public_index.tabs.finalized') }}
            </a>
            <a href="{{ route('invoices.paid') }}" 
               class="border-b-2 {{ request()->routeIs('invoices.paid') ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} py-4 px-1 text-sm font-medium">
                {{ __('invoices.public_index.tabs.paid') }}
            </a>
        </nav>
    </div>

    {{-- Invoices List --}}
    @if($invoices->isEmpty())
        <div class="bg-white shadow-md rounded-lg p-6">
            <p class="text-slate-500 text-center">{{ __('invoices.public_index.empty') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($invoices as $invoice)
                <div class="bg-white shadow-md rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-800">
                                {{ __('invoices.public_index.invoice_number', ['id' => $invoice->id]) }}
                            </h3>
                            <p class="text-slate-600 mt-1">
                                {{ __('invoices.public_index.period', [
                                    'from' => $invoice->billing_period_start->format('Y-m-d'),
                                    'to' => $invoice->billing_period_end->format('Y-m-d')
                                ]) }}
                            </p>
                            @if($invoice->tenant)
                                <p class="text-slate-600 text-sm">
                                    {{ __('invoices.public_index.shared', ['name' => $invoice->tenant->name]) }}
                                </p>
                                @if($invoice->tenant->property)
                                    <p class="text-slate-600 text-sm">
                                        {{ __('invoices.public_index.property', ['address' => $invoice->tenant->property->address]) }}
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
                            <p class="text-2xl font-bold text-slate-900 mt-2">
                                €{{ number_format($invoice->total_amount, 2) }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-4 pt-4 border-t border-slate-200">
                        <a href="{{ route('invoices.show', $invoice) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            {{ __('invoices.public_index.view') }}
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
