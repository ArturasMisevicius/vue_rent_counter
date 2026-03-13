@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('manager')
@section('title', __('reports.shared.revenue.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('reports.shared.revenue.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('reports.shared.revenue.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <form method="GET" action="{{ route('manager.reports.revenue.export') }}" class="inline">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="building_id" value="{{ $buildingId }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <x-button type="submit" variant="secondary">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('reports.shared.revenue.export') }}
                </x-button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.revenue') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                <x-form-input
                    name="start_date"
                    label="{{ __('reports.common.start_date') }}"
                    type="date"
                    :value="request('start_date', $startDate)"
                />

                <x-form-input
                    name="end_date"
                    label="{{ __('reports.common.end_date') }}"
                    type="date"
                    :value="request('end_date', $endDate)"
                />

                <x-form-select
                    name="building_id"
                    label="{{ __('reports.common.building') }}"
                    :options="$buildings->pluck('name', 'id')->toArray()"
                    :selected="$buildingId"
                    placeholder="{{ __('reports.common.all_buildings') }}"
                />

                <x-form-select
                    name="status"
                    label="{{ __('reports.common.status') }}"
                    :options="['draft' => __('reports.shared.revenue.filters.status_options.draft'), 'finalized' => __('reports.shared.revenue.filters.status_options.finalized'), 'paid' => __('reports.shared.revenue.filters.status_options.paid')]"
                    :selected="$status"
                    placeholder="{{ __('reports.common.all_statuses') }}"
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        {{ __('reports.shared.revenue.filters.submit') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.total') }}</x-slot>
            <x-slot name="value">€{{ number_format($totalRevenue, 2) }}</x-slot>
            <x-slot name="change">{{ trans_choice('reports.common.invoices_count', $invoices->count(), ['count' => $invoices->count()]) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.paid') }}</x-slot>
            <x-slot name="value">€{{ number_format($paidRevenue, 2) }}</x-slot>
            <x-slot name="change">{{ __('reports.shared.revenue.stats.payment_rate', ['rate' => number_format($paymentRate, 1)]) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.finalized') }}</x-slot>
            <x-slot name="value">€{{ number_format($finalizedRevenue, 2) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 {{ $overdueAmount > 0 ? 'text-red-600' : 'text-yellow-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.overdue') }}</x-slot>
            <x-slot name="value">€{{ number_format($overdueAmount, 2) }}</x-slot>
            <x-slot name="change">{{ trans_choice('reports.common.invoices_count', $overdueInvoices->count(), ['count' => $overdueInvoices->count()]) }}</x-slot>
        </x-stat-card>
    </div>

    @if($revenueByMonth->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.revenue.monthly.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="space-y-3">
                    @foreach($revenueByMonth as $month => $data)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</p>
                                <p class="text-xs text-slate-600">{{ trans_choice('reports.common.invoices_count', $data['count'], ['count' => $data['count']]) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-slate-900">€{{ number_format($data['total'], 2) }}</p>
                                <p class="text-xs text-emerald-600">{{ __('reports.shared.revenue.monthly.paid', ['amount' => number_format($data['paid'], 2)]) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if($revenueByBuilding->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.revenue.by_building.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="{{ __('reports.shared.revenue.by_building.caption') }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('buildings.labels.building') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.by_building.headers.revenue') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.by_building.headers.invoices') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($revenueByBuilding as $building => $data)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            {{ $building }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right font-semibold">
                            €{{ number_format($data['total'], 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                            {{ $data['count'] }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($revenueByBuilding as $building => $data)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">{{ $building }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-slate-600">{{ __('reports.shared.revenue.by_building.mobile.revenue') }}</p>
                            <p class="text-sm font-semibold text-slate-900">€{{ number_format($data['total'], 2) }}</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-slate-600">{{ __('reports.shared.revenue.by_building.mobile.invoices') }}</p>
                            <p class="text-xs text-slate-600">{{ $data['count'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if($invoices->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.revenue.invoices.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="{{ __('reports.shared.revenue.invoices.caption') }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('reports.shared.revenue.invoices.headers.number') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.property') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.period') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.amount') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.status') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.due') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($invoices as $invoice)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                #{{ $invoice->id }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $invoice->tenant?->property?->address ?? __('reports.common.na') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right">
                            €{{ number_format($invoice->total_amount, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <x-status-badge :status="$invoice->status->value">
                                {{ enum_label($invoice->status) }}
                            </x-status-badge>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            @if($invoice->due_date)
                                <span class="{{ ($invoiceOverdueMap[$invoice->id] ?? false) ? 'text-rose-600 font-semibold' : '' }}">
                                    {{ $invoice->due_date->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($invoices as $invoice)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">#{{ $invoice->id }}</p>
                                <p class="text-xs text-slate-600">
                                    {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-slate-600 mt-1">
                                    {{ $invoice->tenant?->property?->address ?? __('reports.common.na') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <x-status-badge :status="$invoice->status->value" />
                                <p class="mt-1 text-sm font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @else
    <div class="mt-8">
        <x-card>
            <p class="text-center text-sm text-slate-500 py-8">
                {{ __('reports.shared.revenue.invoices.empty') }}
            </p>
        </x-card>
    </div>
    @endif
</div>
@endsection
@break

@default
@section('title', __('reports.shared.revenue.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('reports.shared.revenue.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('reports.shared.revenue.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <form method="GET" action="{{ route('manager.reports.revenue.export') }}" class="inline">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="building_id" value="{{ $buildingId }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <x-button type="submit" variant="secondary">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('reports.shared.revenue.export') }}
                </x-button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.revenue') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                <x-form-input
                    name="start_date"
                    label="{{ __('reports.common.start_date') }}"
                    type="date"
                    :value="request('start_date', $startDate)"
                />

                <x-form-input
                    name="end_date"
                    label="{{ __('reports.common.end_date') }}"
                    type="date"
                    :value="request('end_date', $endDate)"
                />

                <x-form-select
                    name="building_id"
                    label="{{ __('reports.common.building') }}"
                    :options="$buildings->pluck('name', 'id')->toArray()"
                    :selected="$buildingId"
                    placeholder="{{ __('reports.common.all_buildings') }}"
                />

                <x-form-select
                    name="status"
                    label="{{ __('reports.common.status') }}"
                    :options="['draft' => __('reports.shared.revenue.filters.status_options.draft'), 'finalized' => __('reports.shared.revenue.filters.status_options.finalized'), 'paid' => __('reports.shared.revenue.filters.status_options.paid')]"
                    :selected="$status"
                    placeholder="{{ __('reports.common.all_statuses') }}"
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        {{ __('reports.shared.revenue.filters.submit') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.total') }}</x-slot>
            <x-slot name="value">€{{ number_format($totalRevenue, 2) }}</x-slot>
            <x-slot name="change">{{ trans_choice('reports.common.invoices_count', $invoices->count(), ['count' => $invoices->count()]) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.paid') }}</x-slot>
            <x-slot name="value">€{{ number_format($paidRevenue, 2) }}</x-slot>
            <x-slot name="change">{{ __('reports.shared.revenue.stats.payment_rate', ['rate' => number_format($paymentRate, 1)]) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.finalized') }}</x-slot>
            <x-slot name="value">€{{ number_format($finalizedRevenue, 2) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 {{ $overdueAmount > 0 ? 'text-red-600' : 'text-yellow-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.revenue.stats.overdue') }}</x-slot>
            <x-slot name="value">€{{ number_format($overdueAmount, 2) }}</x-slot>
            <x-slot name="change">{{ trans_choice('reports.common.invoices_count', $overdueInvoices->count(), ['count' => $overdueInvoices->count()]) }}</x-slot>
        </x-stat-card>
    </div>

    @if($revenueByMonth->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.revenue.monthly.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="space-y-3">
                    @foreach($revenueByMonth as $month => $data)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</p>
                                <p class="text-xs text-slate-600">{{ trans_choice('reports.common.invoices_count', $data['count'], ['count' => $data['count']]) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-slate-900">€{{ number_format($data['total'], 2) }}</p>
                                <p class="text-xs text-emerald-600">{{ __('reports.shared.revenue.monthly.paid', ['amount' => number_format($data['paid'], 2)]) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if($revenueByBuilding->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.revenue.by_building.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="{{ __('reports.shared.revenue.by_building.caption') }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('buildings.labels.building') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.by_building.headers.revenue') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.by_building.headers.invoices') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($revenueByBuilding as $building => $data)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            {{ $building }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right font-semibold">
                            €{{ number_format($data['total'], 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 text-right">
                            {{ $data['count'] }}
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($revenueByBuilding as $building => $data)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">{{ $building }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-slate-600">{{ __('reports.shared.revenue.by_building.mobile.revenue') }}</p>
                            <p class="text-sm font-semibold text-slate-900">€{{ number_format($data['total'], 2) }}</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-slate-600">{{ __('reports.shared.revenue.by_building.mobile.invoices') }}</p>
                            <p class="text-xs text-slate-600">{{ $data['count'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @endif

    @if($invoices->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.revenue.invoices.title') }}</x-slot>
            
            <div class="mt-4">
                <div class="hidden sm:block">
                <x-data-table caption="{{ __('reports.shared.revenue.invoices.caption') }}">
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('reports.shared.revenue.invoices.headers.number') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.property') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.period') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.amount') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.status') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('reports.shared.revenue.invoices.headers.due') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($invoices as $invoice)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                #{{ $invoice->id }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $invoice->tenant?->property?->address ?? __('reports.common.na') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900 text-right">
                            €{{ number_format($invoice->total_amount, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <x-status-badge :status="$invoice->status->value">
                                {{ enum_label($invoice->status) }}
                            </x-status-badge>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            @if($invoice->due_date)
                                <span class="{{ ($invoiceOverdueMap[$invoice->id] ?? false) ? 'text-rose-600 font-semibold' : '' }}">
                                    {{ $invoice->due_date->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                </div>
                <div class="sm:hidden space-y-3">
                    @foreach($invoices as $invoice)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">#{{ $invoice->id }}</p>
                                <p class="text-xs text-slate-600">
                                    {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-slate-600 mt-1">
                                    {{ $invoice->tenant?->property?->address ?? __('reports.common.na') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <x-status-badge :status="$invoice->status->value" />
                                <p class="mt-1 text-sm font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>
    @else
    <div class="mt-8">
        <x-card>
            <p class="text-center text-sm text-slate-500 py-8">
                {{ __('reports.shared.revenue.invoices.empty') }}
            </p>
        </x-card>
    </div>
    @endif
</div>
@endsection
@endswitch
