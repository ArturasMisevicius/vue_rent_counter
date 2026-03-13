@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('superadmin')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $tenant->name }}</h1>
            <p class="text-slate-600">{{ $tenant->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.compat.tenants.edit', $tenant) }}" class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('superadmin.compat.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 text-sm font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                    {{ __('common.delete') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-lg font-semibold mb-3">{{ __('tenants.sections.details') ?? 'Details' }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">ID</dt>
                    <dd class="text-slate-900">{{ $tenant->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.email') ?? 'Email' }}</dt>
                    <dd class="text-slate-900">{{ $tenant->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.phone') ?? 'Phone' }}</dt>
                    <dd class="text-slate-900">{{ $tenant->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.property') ?? 'Property' }}</dt>
                    <dd class="text-slate-900">
                        @if($tenant->property)
                            <a href="{{ route('superadmin.compat.properties.edit', $tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->property->address }}
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.building') ?? 'Building' }}</dt>
                    <dd class="text-slate-900">
                        @if($tenant->property?->building)
                            <a href="{{ route('superadmin.compat.buildings.edit', $tenant->property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->property->building->display_name ?? $tenant->property->building->address }}
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-lg font-semibold mb-3">{{ __('tenants.sections.stats') ?? 'Stats' }}</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs uppercase text-slate-500">{{ __('tenants.fields.invoices') ?? 'Invoices' }}</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $tenant->invoices->count() }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs uppercase text-slate-500">{{ __('tenants.fields.readings') ?? 'Readings' }}</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $tenant->meterReadings->count() }}</p>
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">{{ __('tenants.sections.invoices') ?? 'Invoices' }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.period') ?? 'Period' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.status') ?? 'Status' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.total') ?? 'Total' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenant->invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('superadmin.compat.invoices.view', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">
                                #{{ $invoice->invoice_number ?? $invoice->id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <x-status-badge :status="$invoice->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.compat.invoices.view', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">{{ __('common.view') }}</a>
                                <a href="{{ route('superadmin.compat.invoices.edit', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
                                <form action="{{ route('superadmin.compat.invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">{{ __('invoices.empty') ?? 'No invoices found' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">{{ __('meter_readings.sections.recent') ?? 'Recent Meter Readings' }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meters.fields.meter') ?? 'Meter' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.fields.value') ?? 'Value' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.fields.date') ?? 'Date' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenant->meterReadings as $reading)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $reading->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $reading->meter_id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ number_format((float) $reading->value, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $reading->reading_date->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-slate-500">{{ __('meter_readings.empty') ?? 'No readings found' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
@break

@case('admin')
@section('title', __('tenants.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $tenant->name }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tenants.headings.show') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <form action="{{ route('admin.tenants.toggle-active', $tenant) }}" method="POST">
                @csrf
                @method('PATCH')
                @if($tenant->is_active)
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        {{ __('tenants.actions.deactivate') }}
                    </button>
                @else
                    <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        {{ __('tenants.actions.reactivate') }}
                    </button>
                @endif
            </form>
            <a href="{{ route('admin.tenants.reassign-form', $tenant) }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('tenants.actions.reassign') }}
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Account Information -->
        <x-card title="{{ __('tenants.headings.account') }}">
            <dl class="divide-y divide-slate-200">
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.status') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        @if($tenant->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ __('tenants.statuses.active') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ __('tenants.statuses.inactive') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.email') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->email }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.created') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->created_at->format('M d, Y') }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.created_by') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        {{ $tenant->parentUser->name ?? __('providers.statuses.not_available') }}
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Current Property Assignment -->
        <x-card title="{{ __('tenants.headings.current_property') }}">
            @if($tenant->property)
                <dl class="divide-y divide-slate-200">
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.address') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->property->address }}</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ enum_label($tenant->property->type) }}</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.area') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->property->area }} m²</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('tenants.empty.property') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Assignment History -->
    <div class="mt-8">
        <x-card title="{{ __('tenants.headings.assignment_history') }}">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @forelse($assignmentHistory as $index => $history)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
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
                                        <p class="text-sm text-slate-500">
                                            <span class="font-medium text-slate-900">{{ enum_label($history->action, \App\Enums\UserAssignmentAction::class) }}</span>
                                            @if($history->action === \App\Enums\UserAssignmentAction::REASSIGNED->value)
                                                - {{ __('tenants.actions.reassign') }}
                                            @elseif($history->action === \App\Enums\UserAssignmentAction::ASSIGNED->value)
                                                - {{ __('tenants.actions.reassign') }}
                                            @elseif($history->action === \App\Enums\UserAssignmentAction::CREATED->value)
                                                - {{ __('tenants.headings.show') }}
                                            @endif
                                        </p>
                                        @if($history->reason)
                                            <p class="mt-1 text-sm text-slate-500">{{ __('tenants.labels.reason') ?? 'Reason' }}: {{ $history->reason }}</p>
                                        @endif
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-slate-500">
                                        {{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="text-sm text-slate-500">{{ __('tenants.empty.assignment_history') }}</li>
                    @endforelse
                </ul>
            </div>
        </x-card>
    </div>

    <!-- Recent Meter Readings -->
    @if($tenant->meterReadings->isNotEmpty())
    <div class="mt-8">
        <x-card title="{{ __('tenants.headings.recent_readings') }}">
            <div class="flow-root">
                <ul role="list" class="-my-5 divide-y divide-slate-200">
                    @foreach($tenant->meterReadings as $reading)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">
                                    {{ $reading->meter->getServiceDisplayName() }}
                                    <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                                </p>
                                <p class="text-sm text-slate-500 truncate">
                                    {{ __('tenants.labels.reading') }}: {{ number_format($reading->value, 2) }}
                                </p>
                            </div>
                            <div class="text-sm text-slate-500">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </div>
                        </div>
                    </li>
                    @endforeach
                    @if($tenant->meterReadings->isEmpty())
                    <li class="py-4 text-sm text-slate-500">{{ __('tenants.empty.recent_readings') }}</li>
                    @endif
                </ul>
            </div>
        </x-card>
    </div>
    @endif

    <!-- Recent Invoices -->
    @if($recentInvoices->isNotEmpty())
    <div class="mt-8">
        <x-card title="{{ __('tenants.headings.recent_invoices') }}">
            <div class="flow-root">
                <ul role="list" class="-my-5 divide-y divide-slate-200">
                    @foreach($recentInvoices as $invoice)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">
                                    {{ __('tenants.labels.invoice', ['id' => $invoice->id]) }}
                                </p>
                                <p class="text-sm text-slate-500 truncate">
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
                    @if($recentInvoices->isEmpty())
                    <li class="py-4 text-sm text-slate-500">{{ __('tenants.empty.recent_invoices') }}</li>
                    @endif
                </ul>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection
@break

@default
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $tenant->name }}</h1>
            <p class="text-slate-600">{{ $tenant->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.compat.tenants.edit', $tenant) }}" class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('superadmin.compat.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 text-sm font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                    {{ __('common.delete') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-lg font-semibold mb-3">{{ __('tenants.sections.details') ?? 'Details' }}</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">ID</dt>
                    <dd class="text-slate-900">{{ $tenant->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.email') ?? 'Email' }}</dt>
                    <dd class="text-slate-900">{{ $tenant->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.phone') ?? 'Phone' }}</dt>
                    <dd class="text-slate-900">{{ $tenant->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.property') ?? 'Property' }}</dt>
                    <dd class="text-slate-900">
                        @if($tenant->property)
                            <a href="{{ route('superadmin.compat.properties.edit', $tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->property->address }}
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('tenants.fields.building') ?? 'Building' }}</dt>
                    <dd class="text-slate-900">
                        @if($tenant->property?->building)
                            <a href="{{ route('superadmin.compat.buildings.edit', $tenant->property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->property->building->display_name ?? $tenant->property->building->address }}
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-lg font-semibold mb-3">{{ __('tenants.sections.stats') ?? 'Stats' }}</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs uppercase text-slate-500">{{ __('tenants.fields.invoices') ?? 'Invoices' }}</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $tenant->invoices->count() }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs uppercase text-slate-500">{{ __('tenants.fields.readings') ?? 'Readings' }}</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $tenant->meterReadings->count() }}</p>
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">{{ __('tenants.sections.invoices') ?? 'Invoices' }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.period') ?? 'Period' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.status') ?? 'Status' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.total') ?? 'Total' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenant->invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('superadmin.compat.invoices.view', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">
                                #{{ $invoice->invoice_number ?? $invoice->id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <x-status-badge :status="$invoice->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.compat.invoices.view', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">{{ __('common.view') }}</a>
                                <a href="{{ route('superadmin.compat.invoices.edit', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
                                <form action="{{ route('superadmin.compat.invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">{{ __('invoices.empty') ?? 'No invoices found' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">{{ __('meter_readings.sections.recent') ?? 'Recent Meter Readings' }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meters.fields.meter') ?? 'Meter' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.fields.value') ?? 'Value' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('meter_readings.fields.date') ?? 'Date' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenant->meterReadings as $reading)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $reading->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $reading->meter_id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ number_format((float) $reading->value, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $reading->reading_date->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-slate-500">{{ __('meter_readings.empty') ?? 'No readings found' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
@endswitch
