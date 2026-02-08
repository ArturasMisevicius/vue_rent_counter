@extends('layouts.app')

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
