@extends('layouts.app')

@section('title', 'Revenue Report')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('manager.reports.index') }}">Reports</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Revenue</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Revenue Report</h1>
            <p class="mt-2 text-sm text-gray-700">Billing revenue by period</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-6">
        <x-card>
            <form method="GET" action="{{ route('manager.reports.revenue') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-form-input
                    name="start_date"
                    label="Start Date"
                    type="date"
                    :value="request('start_date', $startDate)"
                />

                <x-form-input
                    name="end_date"
                    label="End Date"
                    type="date"
                    :value="request('end_date', $endDate)"
                />

                <div class="flex items-end">
                    <x-button type="submit" class="w-full">
                        Generate Report
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Summary Stats -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">Total Revenue</x-slot>
            <x-slot name="value">€{{ number_format($totalRevenue, 2) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">Paid</x-slot>
            <x-slot name="value">€{{ number_format($paidRevenue, 2) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">Finalized</x-slot>
            <x-slot name="value">€{{ number_format($finalizedRevenue, 2) }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </x-slot>
            <x-slot name="label">Draft</x-slot>
            <x-slot name="value">€{{ number_format($draftRevenue, 2) }}</x-slot>
        </x-stat-card>
    </div>

    <!-- Invoice List -->
    @if($invoices->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Invoices</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Invoice #</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Property</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Period</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Amount</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                        </tr>
                    </x-slot>

                    @foreach($invoices as $invoice)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                #{{ $invoice->id }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            @if($invoice->tenant && $invoice->tenant->property)
                                {{ $invoice->tenant->property->address }}
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                            €{{ number_format($invoice->total_amount, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <x-status-badge :status="$invoice->status->value">
                                {{ enum_label($invoice->status) }}
                            </x-status-badge>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
        </x-card>
    </div>
    @else
    <div class="mt-8">
        <x-card>
            <p class="text-center text-sm text-gray-500 py-8">
                No invoices found for the selected period.
            </p>
        </x-card>
    </div>
    @endif
</div>
@endsection
