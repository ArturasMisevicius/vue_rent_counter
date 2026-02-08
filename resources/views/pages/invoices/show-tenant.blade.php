@extends('layouts.tenant')

@section('tenant-content')
<x-tenant.page
    :title="__('invoices.tenant.show.title', ['id' => $invoice->id])"
    :description="__('invoices.tenant.show.description', ['from' => $invoice->billing_period_start->format('Y-m-d'), 'to' => $invoice->billing_period_end->format('Y-m-d')])"
>
    <x-slot name="actions">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tenant.invoices.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                ← {{ __('invoices.tenant.show.back') }}
            </a>
            @if($invoice->isFinalized() || $invoice->isPaid())
                <a href="{{ route('tenant.invoices.pdf', $invoice) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                    {{ $invoice->isPaid() ? __('invoices.tenant.show.download_receipt') : __('invoices.tenant.show.download_pdf') }}
                </a>
            @endif
        </div>
    </x-slot>

    <x-invoice-summary 
        :invoice="$invoice" 
        :consumption-history="$consumptionHistory ?? collect()" 
    />

    @if($invoice->due_date && !$invoice->isPaid())
        <x-tenant.alert :type="$invoice->due_date->isPast() ? 'error' : 'info'" :title="$invoice->due_date->isPast() ? __('invoices.tenant.show.payment_overdue') : __('invoices.tenant.show.payment_due')" class="mt-4">
            <p class="text-sm">
                {{ __('invoices.tenant.show.due_date') }} <span class="font-semibold">{{ $invoice->due_date->format('Y-m-d') }}</span>
            </p>
            @if($invoice->payment_reference)
                <p class="text-sm">{{ __('invoices.tenant.show.payment_reference') }} <span class="font-semibold">{{ $invoice->payment_reference }}</span></p>
            @endif
            @if($invoice->paid_amount)
                <p class="text-sm">{{ __('invoices.tenant.show.paid_amount') }} <span class="font-semibold">€{{ number_format($invoice->paid_amount, 2) }}</span></p>
            @endif
            @if($invoice->due_date->isPast())
                <p class="text-sm">{{ __('invoices.tenant.show.overdue_notice') }}</p>
            @endif
        </x-tenant.alert>
    @endif
</x-tenant.page>
@endsection
