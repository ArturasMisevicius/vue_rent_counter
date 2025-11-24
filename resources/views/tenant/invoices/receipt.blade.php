@extends('layouts.tenant')

@section('title', __('invoices.tenant.receipt.title', ['id' => $invoice->id]))

@section('tenant-content')
<x-tenant.page
    :title="__('invoices.tenant.receipt.title', ['id' => $invoice->id])"
    :description="__('invoices.tenant.receipt.description', ['from' => $invoice->billing_period_start->format('Y-m-d'), 'to' => $invoice->billing_period_end->format('Y-m-d')])"
>
    <x-tenant.section-card :title="__('invoices.tenant.receipt.details')">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.invoice') }}</p>
                <p class="text-lg font-semibold text-slate-900">#{{ $invoice->id }}</p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-sm text-slate-600">{{ __('invoices.tenant.receipt.payment_status') }}</p>
                <p class="text-lg font-semibold text-slate-900">
                    {{ $invoice->isPaid() ? __('invoices.tenant.receipt.paid') : enum_label($invoice->status) }}
                </p>
            </div>
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
    </x-tenant.section-card>

    <x-tenant.section-card :title="__('invoices.tenant.receipt.line_items')">
        <div class="hidden sm:block overflow-x-auto">
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
                        <td colspan="3" class="px-3 py-4 text-sm font-semibold text-slate-900 text-right">Total</td>
                        <td class="px-3 py-4 text-sm font-semibold text-slate-900 text-right">€{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <x-tenant.stack gap="3" class="sm:hidden">
            @foreach($invoice->items as $item)
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ $item->description }}</p>
                <p class="text-xs text-slate-600">{{ __('invoices.tenant.receipt.qty_short') }} {{ number_format($item->quantity, 2) }}</p>
                <p class="text-xs text-slate-600">{{ __('invoices.tenant.receipt.unit_short') }} €{{ number_format($item->unit_price, 4) }}</p>
                <p class="text-sm font-semibold text-slate-900 mt-1">€{{ number_format($item->total_price, 2) }}</p>
            </div>
            @endforeach
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-inner">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-900">{{ __('invoices.tenant.receipt.total_sum') }}</p>
                    <p class="text-sm font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
                </div>
            </div>
        </x-tenant.stack>
    </x-tenant.section-card>
</x-tenant.page>
@endsection
