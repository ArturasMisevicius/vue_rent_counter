@extends('layouts.app')

@section('title', __('invoices.manager.show.title', ['id' => $invoice->id]))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.invoices.index') }}">{{ __('app.nav.invoices') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('invoices.manager.show.title', ['id' => $invoice->id]) }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('invoices.manager.show.title', ['id' => $invoice->id]) }}</h1>
            <p class="mt-2 text-sm text-slate-700">
                <x-status-badge :status="$invoice->status->value">
                    {{ enum_label($invoice->status) }}
                </x-status-badge>
                @if($invoice->due_date)
                    @php($isOverdue = !$invoice->isPaid() && $invoice->due_date->isPast())
                    <span class="ml-2 text-sm {{ $isOverdue ? 'text-rose-600 font-semibold' : 'text-slate-700' }}">
                        {{ __('invoices.manager.show.due') }} {{ $invoice->due_date->format('Y-m-d') }}
                        @if($isOverdue)
                            <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ __('invoices.manager.show.overdue') }}</span>
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @if($invoice->isDraft())
                @can('update', $invoice)
                <x-button href="{{ route('manager.invoices.edit', $invoice) }}" variant="secondary">
                    {{ __('invoices.manager.show.edit') }}
                </x-button>
                <form action="{{ route('manager.invoices.finalize', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.manager.show.finalize_confirm') }}');">
                    @csrf
                    <x-button type="submit">
                        {{ __('invoices.manager.show.finalize') }}
                    </x-button>
                </form>
                @endcan
                @can('delete', $invoice)
                <form action="{{ route('manager.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.manager.show.delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">
                        {{ __('invoices.manager.show.delete') }}
                    </x-button>
                </form>
                @endcan
            @endif
            @if($invoice->isFinalized() && !$invoice->isPaid())
                @can('update', $invoice)
                <form action="{{ route('manager.invoices.mark-paid', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('invoices.manager.show.mark_paid_confirm') }}');">
                    @csrf
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input type="text" name="payment_reference" placeholder="{{ __('invoices.manager.show.payment_reference_placeholder') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64">
                        <input type="number" step="0.01" name="paid_amount" placeholder="{{ __('invoices.manager.show.paid_amount_placeholder') }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-40">
                        <x-button type="submit" variant="secondary">
                            {{ __('invoices.manager.show.mark_paid') }}
                        </x-button>
                    </div>
                </form>
                @endcan
            @endif
            {{-- PDF generation to be implemented in future task --}}
            {{-- @if($invoice->isFinalized() || $invoice->isPaid())
                <x-button href="{{ route('manager.invoices.pdf', $invoice) }}" class="inline-flex" variant="secondary">
                    {{ $invoice->isPaid() ? __('invoices.manager.show.download_receipt') : __('invoices.manager.show.download_pdf') }}
                </x-button>
            @endif --}}
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('invoices.manager.show.info.title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.info.number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">#{{ $invoice->id }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.info.billing_period') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.info.status') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <x-status-badge :status="$invoice->status->value">
                            {{ enum_label($invoice->status) }}
                        </x-status-badge>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.info.total_amount') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="text-2xl font-semibold">€{{ number_format($invoice->total_amount, 2) }}</span>
                    </dd>
                </div>
                @if($invoice->finalized_at)
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.info.finalized_at') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->finalized_at->format('M d, Y H:i') }}</dd>
                </div>
                @endif
            </dl>
        </x-card>

        <!-- Tenant Information -->
        <x-card>
            <x-slot name="title">{{ __('invoices.manager.show.tenant.title') }}</x-slot>
            
            @if($invoice->tenant)
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.tenant.name') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->name }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.tenant.email') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $invoice->tenant->email }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('invoices.manager.show.tenant.property') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        @if($invoice->tenant->property)
                            <a href="{{ route('manager.properties.show', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $invoice->tenant->property->address }}
                            </a>
                        @else
                            <span class="text-slate-400">N/A</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('invoices.manager.show.tenant.unavailable') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Line Items -->
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('invoices.manager.show.line_items.title') }}</x-slot>
            
            @if($invoice->items->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('invoices.manager.show.line_items.description') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('invoices.manager.show.line_items.unit') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.manager.show.line_items.quantity') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.manager.show.line_items.unit_price') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('invoices.manager.show.line_items.total') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="py-4 pl-4 pr-3 text-sm text-slate-900 sm:pl-0">
                            {{ $item->description }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $item->unit ?? __('invoices.manager.show.line_items.unit_na') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                            {{ number_format($item->quantity, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                            €{{ number_format($item->unit_price, 4) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right font-medium">
                            €{{ number_format($item->total_price, 2) }}
                        </td>
                    </tr>
                    @endforeach

                    <tr class="border-t-2 border-slate-300">
                        <td colspan="4" class="py-4 pl-4 pr-3 text-sm font-semibold text-slate-900 text-right sm:pl-0">
                            {{ __('invoices.manager.show.line_items.total_amount') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-slate-900 text-right">
                            €{{ number_format($invoice->total_amount, 2) }}
                        </td>
                    </tr>
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-slate-500">{{ __('invoices.manager.show.line_items.empty') }}</p>
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
                <h3 class="text-sm font-medium text-yellow-800">{{ __('invoices.manager.show.draft_alert.title') }}</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>{{ __('invoices.manager.show.draft_alert.body') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
