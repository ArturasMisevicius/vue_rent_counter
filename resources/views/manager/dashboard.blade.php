@extends('layouts.app')

@section('title', 'Manager Dashboard')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :active="true">Dashboard</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Manager Dashboard</h1>
            <p class="mt-2 text-sm text-gray-700">Property management overview and pending tasks</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @can('viewAny', App\Models\Property::class)
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </x-slot>
            <x-slot name="label">Total Properties</x-slot>
            <x-slot name="value">{{ $stats['total_properties'] }}</x-slot>
        </x-stat-card>
        @endcan

        @can('create', App\Models\MeterReading::class)
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </x-slot>
            <x-slot name="label">Meters Pending Reading</x-slot>
            <x-slot name="value">
                <span class="text-orange-600">{{ $stats['meters_pending_reading'] }}</span>
            </x-slot>
        </x-stat-card>
        @endcan

        @can('create', App\Models\Invoice::class)
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">Draft Invoices</x-slot>
            <x-slot name="value">{{ $stats['draft_invoices'] }}</x-slot>
        </x-stat-card>
        @endcan
    </div>

    <!-- Pending Meter Readings Section -->
    @if($propertiesNeedingReadings->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Properties Requiring Meter Readings</x-slot>
            
            <div class="mt-4 space-y-4">
                @foreach($propertiesNeedingReadings as $property)
                <div class="border-l-4 border-orange-400 bg-orange-50 p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-orange-800">
                                {{ $property->address }}
                            </h3>
                            <div class="mt-2 text-sm text-orange-700">
                                <p>{{ $property->meters->count() }} meter(s) need reading for this month</p>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="inline-flex items-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500">
                                    Enter Readings
                                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>
    </div>
    @endif

    <!-- Draft Invoices Summary -->
    @if($draftInvoices->isNotEmpty())
    <div class="mt-8">
        <x-card>
            <x-slot name="title">Draft Invoices</x-slot>
            
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Property</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Period</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </x-slot>
                    
                    @foreach($draftInvoices as $invoice)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                            {{ $invoice->tenant->property->address ?? 'N/A' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            €{{ number_format($invoice->total_amount, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <x-status-badge status="draft">Draft</x-status-badge>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                View<span class="sr-only">, invoice {{ $invoice->id }}</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
                
                <div class="mt-4">
                    <a href="{{ route('manager.invoices.drafts') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        View all draft invoices →
                    </a>
                </div>
            </div>
        </x-card>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @can('create', App\Models\MeterReading::class)
            <a href="{{ route('manager.meter-readings.create') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Enter Meter Readings</h3>
                        <p class="text-sm text-gray-500">Record utility consumption</p>
                    </div>
                </div>
            </a>
            @endcan

            @can('create', App\Models\Invoice::class)
            <a href="{{ route('manager.invoices.create') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Generate Invoice</h3>
                        <p class="text-sm text-gray-500">Create new bill</p>
                    </div>
                </div>
            </a>
            @endcan

            @can('viewAny', App\Models\Property::class)
            <a href="{{ route('manager.properties.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">View Properties</h3>
                        <p class="text-sm text-gray-500">Manage property portfolio</p>
                    </div>
                </div>
            </a>
            @endcan

            @can('viewAny', App\Models\Building::class)
            <a href="{{ route('manager.buildings.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">View Buildings</h3>
                        <p class="text-sm text-gray-500">Manage multi-unit buildings</p>
                    </div>
                </div>
            </a>
            @endcan

            @can('viewAny', App\Models\Meter::class)
            <a href="{{ route('manager.meters.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">View Meters</h3>
                        <p class="text-sm text-gray-500">Manage utility meters</p>
                    </div>
                </div>
            </a>
            @endcan

            <a href="{{ route('manager.reports.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">View Reports</h3>
                        <p class="text-sm text-gray-500">Analytics and insights</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
