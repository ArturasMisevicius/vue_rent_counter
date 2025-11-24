@extends('layouts.app')

@section('title', 'Receipt for Invoice #' . $invoice->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-indigo-600 hover:text-indigo-800">
            ← Back
        </a>
    </div>

    <x-card>
        <h1 class="text-2xl font-semibold text-slate-900 mb-4">Receipt for Invoice #{{ $invoice->id }}</h1>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-slate-600">Period</p>
                <p class="text-sm text-slate-900">
                    {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}
                </p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-sm text-slate-600">Total</p>
                <p class="text-2xl font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600">Paid At</p>
                <p class="text-sm text-slate-900">{{ $invoice->paid_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600">Payment Reference</p>
                <p class="text-sm text-slate-900">{{ $invoice->payment_reference ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600">Paid Amount</p>
                <p class="text-sm text-slate-900">
                    {{ $invoice->paid_amount ? '€' . number_format($invoice->paid_amount, 2) : '—' }}
                </p>
            </div>
            @if($invoice->due_date)
            <div>
                <p class="text-sm text-slate-600">Due Date</p>
                <p class="text-sm text-slate-900">{{ $invoice->due_date->format('Y-m-d') }}</p>
            </div>
            @endif
            @if($invoice->tenant && $invoice->tenant->property)
            <div class="sm:col-span-2">
                <p class="text-sm text-slate-600">Property</p>
                <p class="text-sm text-slate-900">{{ $invoice->tenant->property->address }}</p>
            </div>
            @endif
        </div>
    </x-card>

    <x-card class="mt-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-3">Line Items</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Description</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Qty</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Unit Price</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="px-3 py-4 text-sm text-slate-900">{{ $item->description }}</td>
                        <td class="px-3 py-4 text-sm text-slate-600 text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-3 py-4 text-sm text-slate-600 text-right">€{{ number_format($item->unit_price, 4) }}</td>
                        <td class="px-3 py-4 text-sm text-slate-900 text-right font-semibold">€{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="bg-slate-50">
                        <td colspan="3" class="px-3 py-4 text-sm font-semibold text-slate-900 text-right">Total</td>
                        <td class="px-3 py-4 text-sm font-semibold text-slate-900 text-right">€{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
