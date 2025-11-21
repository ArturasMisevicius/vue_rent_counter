@extends('layouts.app')

@section('title', 'Tenant Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('admin.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('admin.tenants.index') }}">Tenants</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $tenant->name }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $tenant->name }}</h1>
            <p class="mt-2 text-sm text-gray-700">Tenant account details and history</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <form action="{{ route('admin.tenants.toggle-active', $tenant) }}" method="POST">
                @csrf
                @method('PATCH')
                @if($tenant->is_active)
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        Deactivate
                    </button>
                @else
                    <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Reactivate
                    </button>
                @endif
            </form>
            <a href="{{ route('admin.tenants.reassign-form', $tenant) }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Reassign Property
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Account Information -->
        <x-card title="Account Information">
            <dl class="divide-y divide-gray-200">
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        @if($tenant->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $tenant->email }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $tenant->created_at->format('M d, Y') }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Created By</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        {{ $tenant->parentUser->name ?? 'N/A' }}
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Current Property Assignment -->
        <x-card title="Current Property">
            @if($tenant->property)
                <dl class="divide-y divide-gray-200">
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $tenant->property->address }}</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst($tenant->property->type->value) }}</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Area</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $tenant->property->area }} m²</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500">No property assigned</p>
            @endif
        </x-card>
    </div>

    <!-- Assignment History -->
    <div class="mt-8">
        <x-card title="Assignment History">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @forelse($assignmentHistory as $index => $history)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium text-gray-900">{{ ucfirst($history->action) }}</span>
                                            @if($history->action === 'reassigned')
                                                - Moved to new property
                                            @elseif($history->action === 'assigned')
                                                - Assigned to property
                                            @elseif($history->action === 'created')
                                                - Account created
                                            @endif
                                        </p>
                                        @if($history->reason)
                                            <p class="mt-1 text-sm text-gray-500">Reason: {{ $history->reason }}</p>
                                        @endif
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="text-sm text-gray-500">No assignment history available</li>
                    @endforelse
                </ul>
            </div>
        </x-card>
    </div>

    <!-- Recent Meter Readings -->
    @if($tenant->meterReadings->isNotEmpty())
    <div class="mt-8">
        <x-card title="Recent Meter Readings">
            <div class="flow-root">
                <ul role="list" class="-my-5 divide-y divide-gray-200">
                    @foreach($tenant->meterReadings as $reading)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ ucfirst(str_replace('_', ' ', $reading->meter->type->value)) }}
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    Reading: {{ number_format($reading->value, 2) }}
                                </p>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </x-card>
    </div>
    @endif

    <!-- Recent Invoices -->
    @if($recentInvoices->isNotEmpty())
    <div class="mt-8">
        <x-card title="Recent Invoices">
            <div class="flow-root">
                <ul role="list" class="-my-5 divide-y divide-gray-200">
                    @foreach($recentInvoices as $invoice)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    Invoice #{{ $invoice->id }}
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    €{{ number_format($invoice->total_amount, 2) }}
                                </p>
                            </div>
                            <div>
                                <x-status-badge :status="$invoice->status">
                                    {{ ucfirst($invoice->status) }}
                                </x-status-badge>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection
