@extends('layouts.app')

@section('title', __('invoices.tenant.receipt.title', ['id' => $invoice->id]))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-indigo-600 hover:text-indigo-800">
            ← {{ __('invoices.tenant.show.back') }}
        </a>
    </div>

    <x-card>
        <h1 class="text-2xl font-semibold text-slate-900 mb-4">{{ __('invoices.tenant.receipt.title', ['id' => $invoice->id]) }}</h1>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.period') }}</p>
                <p class="text-sm text-slate-900">
                    {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}
                </p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.total') }}</p>
                <p class="text-2xl font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.paid_at') }}</p>
                <p class="text-sm text-slate-900">{{ $invoice->paid_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.payment_reference') }}</p>
                <p class="text-sm text-slate-900">{{ $invoice->payment_reference ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.paid_amount') }}</p>
                <p class="text-sm text-slate-900">
                    {{ $invoice->paid_amount ? '€' . number_format($invoice->paid_amount, 2) : '—' }}
                </p>
            </div>
            @if($invoice->due_date)
            <div>
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.due_date') }}</p>
                <p class="text-sm text-slate-900">{{ $invoice->due_date->format('Y-m-d') }}</p>
            </div>
            @endif
            @if($invoice->tenant && $invoice->tenant->property)
            <div class="sm:col-span-2">
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.property') }}</p>
                <p class="text-sm text-slate-900">{{ $invoice->tenant->property->address }}</p>
            </div>
            @endif
        </div>
    </x-card>

    <x-card class="mt-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('invoices.tenant.receipt.line_items') }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">{{ __('invoices.tenant.receipt.description') }}</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">{{ __('invoices.tenant.receipt.qty') }}</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">{{ __('invoices.tenant.receipt.unit_price') }}</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">{{ __('invoices.tenant.receipt.sum') }}</th>
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
                        <td colspan="3" class="px-3 py-4 text-sm font-semibold text-slate-900 text-right">{{ __('invoices.tenant.receipt.total_sum') }}</td>
                        <td class="px-3 py-4 text-sm font-semibold text-slate-900 text-right">€{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
