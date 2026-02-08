@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('invoices.shared.show.title', ['id' => $invoice->id]))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.show.title', ['id' => $invoice->id]) }}</h1>
            <p class="mt-2 text-sm text-slate-700">
                <x-status-badge :status="$invoice->status->value">
                    {{ enum_label($invoice->status) }}
                </x-status-badge>
                @if($invoice->due_date)
                    <span class="ml-2 text-sm {{ (!$invoice->isPaid() && $invoice->due_date->isPast()) ? 'text-rose-600 font-semibold' : 'text-slate-700' }}">
                        {{ __('invoices.shared.show.due') }} {{ $invoice->due_date->format('Y-m-d') }}
                        @if(!$invoice->isPaid() && $invoice->due_date->isPast())
                            <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ __('invoices.shared.show.overdue') }}</span>
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @if($invoice->isDraft())
                @can('update', $invoice)
                <x-button href="{{ route('manager.invoices.edit', $invoice) }}" variant="secondary">
                    {{ __('invoices.shared.show.edit') }}
                </x-button>
                <form action="{{ route('manager.invoices.finalize', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.shared.show.finalize_confirm') }}');">
                    @csrf
                    <x-button type="submit">
                        {{ __('invoices.shared.show.finalize') }}
                    </x-button>
                </form>
                @endcan
                @can('delete', $invoice)
                <form action="{{ route('manager.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.shared.show.delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">
                        {{ __('invoices.shared.show.delete') }}
                    </x-button>
                </form>
                @endcan
            @endif
            @if($invoice->isFinalized() && !$invoice->isPaid())
                @can('update', $invoice)
                <form action="{{ route('manager.invoices.mark-paid', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.shared.show.mark_paid_confirm') }}');">
                    @csrf
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-4 sm:items-center">
                        <input type="text" name="payment_reference" placeholder="{{ __('invoices.shared.show.payment_reference_placeholder') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="number" step="0.01" name="paid_amount" placeholder="{{ __('invoices.shared.show.paid_amount_placeholder') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\\TH:i') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('invoices.shared.show.paid_at_placeholder') }}">
                        <x-button type="submit" variant="secondary">
                            {{ __('invoices.shared.show.mark_paid') }}
                        </x-button>
                    </div>
                </form>
                @endcan
            @endif
            {{-- PDF generation to be implemented in future task --}}
            {{-- @if($invoice->isFinalized() || $invoice->isPaid())
                <x-button href="{{ route('manager.invoices.pdf', $invoice) }}" class="inline-flex" variant="secondary">
                    {{ $invoice->isPaid() ? __('invoices.shared.show.download_receipt') : __('invoices.shared.show.download_pdf') }}
                </x-button>
            @endif --}}
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('invoices.shared.show.info.title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">#{{ $invoice->id }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.billing_period') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.status') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <x-status-badge :status="$invoice->status->value">
                            {{ enum_label($invoice->status) }}
                        </x-status-badge>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.total_amount') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">€{{ number_format($invoice->total_amount, 2) }}</span>
                    </dd>
                </div>
                @if($invoice->finalized_at)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.finalized_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->finalized_at->format('M d, Y H:i') }}</dd>
                </div>
                @endif
                @if($invoice->isPaid() || $invoice->paid_at || $invoice->payment_reference || $invoice->paid_amount)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.paid_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->paid_at ? $invoice->paid_at->format('M d, Y H:i') : '—' }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.payment_reference') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->payment_reference ?? '—' }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.paid_amount') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->paid_amount ? '€' . number_format($invoice->paid_amount, 2) : '—' }}
                    </dd>
                </div>
                @endif
            </dl>
        </x-card>

        <!-- Tenant Information -->
        <x-card>
            <x-slot name="title">{{ __('invoices.shared.show.shared.title') }}</x-slot>
            
            @if($invoice->tenant)
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.shared.name') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->name }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.shared.email') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->email }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.shared.property') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        @if($invoice->tenant->property)
                            <a href="{{ route('manager.properties.show', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $invoice->tenant->property->address }}
                            </a>
                        @else
                            <span class="text-slate-400">{{ __('app.common.na') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('invoices.shared.show.shared.unavailable') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Invoice Summary with Line Items and Consumption History -->
    <div class="mt-8">
        <x-invoice-summary 
            :invoice="$invoice" 
            :consumption-history="$consumptionHistory ?? collect()" 
        />
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
                <h3 class="text-sm font-medium text-yellow-800">{{ __('invoices.shared.show.draft_alert.title') }}</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>{{ __('invoices.shared.show.draft_alert.body') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
@break

@case('tenant')
@extends('layouts.tenant')

@section('tenant-content')
<x-tenant.page
    :title="__('invoices.shared.show.title', ['id' => $invoice->id])"
    :description="__('invoices.shared.show.description', ['from' => $invoice->billing_period_start->format('Y-m-d'), 'to' => $invoice->billing_period_end->format('Y-m-d')])"
>
    <x-slot name="actions">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tenant.invoices.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                ← {{ __('invoices.shared.show.back') }}
            </a>
            @if($invoice->isFinalized() || $invoice->isPaid())
                <a href="{{ route('tenant.invoices.pdf', $invoice) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                    {{ $invoice->isPaid() ? __('invoices.shared.show.download_receipt') : __('invoices.shared.show.download_pdf') }}
                </a>
            @endif
        </div>
    </x-slot>

    <x-invoice-summary 
        :invoice="$invoice" 
        :consumption-history="$consumptionHistory ?? collect()" 
    />

    @if($invoice->due_date && !$invoice->isPaid())
        <x-tenant.alert :type="$invoice->due_date->isPast() ? 'error' : 'info'" :title="$invoice->due_date->isPast() ? __('invoices.shared.show.payment_overdue') : __('invoices.shared.show.payment_due')" class="mt-4">
            <p class="text-sm">
                {{ __('invoices.shared.show.due_date') }} <span class="font-semibold">{{ $invoice->due_date->format('Y-m-d') }}</span>
            </p>
            @if($invoice->payment_reference)
                <p class="text-sm">{{ __('invoices.shared.show.payment_reference') }} <span class="font-semibold">{{ $invoice->payment_reference }}</span></p>
            @endif
            @if($invoice->paid_amount)
                <p class="text-sm">{{ __('invoices.shared.show.paid_amount') }} <span class="font-semibold">€{{ number_format($invoice->paid_amount, 2) }}</span></p>
            @endif
            @if($invoice->due_date->isPast())
                <p class="text-sm">{{ __('invoices.shared.show.overdue_notice') }}</p>
            @endif
        </x-tenant.alert>
    @endif
</x-tenant.page>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('invoices.shared.show.title', ['id' => $invoice->id]))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.shared.show.title', ['id' => $invoice->id]) }}</h1>
            <p class="mt-2 text-sm text-slate-700">
                <x-status-badge :status="$invoice->status->value">
                    {{ enum_label($invoice->status) }}
                </x-status-badge>
                @if($invoice->due_date)
                    <span class="ml-2 text-sm {{ (!$invoice->isPaid() && $invoice->due_date->isPast()) ? 'text-rose-600 font-semibold' : 'text-slate-700' }}">
                        {{ __('invoices.shared.show.due') }} {{ $invoice->due_date->format('Y-m-d') }}
                        @if(!$invoice->isPaid() && $invoice->due_date->isPast())
                            <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ __('invoices.shared.show.overdue') }}</span>
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @if($invoice->isDraft())
                @can('update', $invoice)
                <x-button href="{{ route('manager.invoices.edit', $invoice) }}" variant="secondary">
                    {{ __('invoices.shared.show.edit') }}
                </x-button>
                <form action="{{ route('manager.invoices.finalize', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.shared.show.finalize_confirm') }}');">
                    @csrf
                    <x-button type="submit">
                        {{ __('invoices.shared.show.finalize') }}
                    </x-button>
                </form>
                @endcan
                @can('delete', $invoice)
                <form action="{{ route('manager.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.shared.show.delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">
                        {{ __('invoices.shared.show.delete') }}
                    </x-button>
                </form>
                @endcan
            @endif
            @if($invoice->isFinalized() && !$invoice->isPaid())
                @can('update', $invoice)
                <form action="{{ route('manager.invoices.mark-paid', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.shared.show.mark_paid_confirm') }}');">
                    @csrf
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-4 sm:items-center">
                        <input type="text" name="payment_reference" placeholder="{{ __('invoices.shared.show.payment_reference_placeholder') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="number" step="0.01" name="paid_amount" placeholder="{{ __('invoices.shared.show.paid_amount_placeholder') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\\TH:i') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('invoices.shared.show.paid_at_placeholder') }}">
                        <x-button type="submit" variant="secondary">
                            {{ __('invoices.shared.show.mark_paid') }}
                        </x-button>
                    </div>
                </form>
                @endcan
            @endif
            {{-- PDF generation to be implemented in future task --}}
            {{-- @if($invoice->isFinalized() || $invoice->isPaid())
                <x-button href="{{ route('manager.invoices.pdf', $invoice) }}" class="inline-flex" variant="secondary">
                    {{ $invoice->isPaid() ? __('invoices.shared.show.download_receipt') : __('invoices.shared.show.download_pdf') }}
                </x-button>
            @endif --}}
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('invoices.shared.show.info.title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">#{{ $invoice->id }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.billing_period') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.status') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <x-status-badge :status="$invoice->status->value">
                            {{ enum_label($invoice->status) }}
                        </x-status-badge>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.total_amount') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">€{{ number_format($invoice->total_amount, 2) }}</span>
                    </dd>
                </div>
                @if($invoice->finalized_at)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.finalized_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->finalized_at->format('M d, Y H:i') }}</dd>
                </div>
                @endif
                @if($invoice->isPaid() || $invoice->paid_at || $invoice->payment_reference || $invoice->paid_amount)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.paid_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->paid_at ? $invoice->paid_at->format('M d, Y H:i') : '—' }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.payment_reference') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->payment_reference ?? '—' }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.info.paid_amount') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->paid_amount ? '€' . number_format($invoice->paid_amount, 2) : '—' }}
                    </dd>
                </div>
                @endif
            </dl>
        </x-card>

        <!-- Tenant Information -->
        <x-card>
            <x-slot name="title">{{ __('invoices.shared.show.shared.title') }}</x-slot>
            
            @if($invoice->tenant)
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.shared.name') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->name }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.shared.email') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->email }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.shared.show.shared.property') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        @if($invoice->tenant->property)
                            <a href="{{ route('manager.properties.show', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $invoice->tenant->property->address }}
                            </a>
                        @else
                            <span class="text-slate-400">{{ __('app.common.na') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('invoices.shared.show.shared.unavailable') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Invoice Summary with Line Items and Consumption History -->
    <div class="mt-8">
        <x-invoice-summary 
            :invoice="$invoice" 
            :consumption-history="$consumptionHistory ?? collect()" 
        />
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
                <h3 class="text-sm font-medium text-yellow-800">{{ __('invoices.shared.show.draft_alert.title') }}</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>{{ __('invoices.shared.show.draft_alert.body') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
@endswitch
