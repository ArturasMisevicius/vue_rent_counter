@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $manager->name }}</h1>
            <p class="text-slate-600">{{ $manager->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('filament.admin.resources.users.edit', $manager) }}" class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('filament.admin.resources.users.destroy', $manager) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 text-sm font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                    {{ __('common.delete') }}
                </button>
            </form>
        </div>
    </div>

    <div class="space-y-6 mb-8">
        <x-card>
            <h2 class="text-lg font-semibold mb-3">{{ __('manager.sections.details') ?? 'Details' }}</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">ID</dt>
                    <dd class="text-slate-900">{{ $manager->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('manager.fields.properties') ?? 'Properties' }}</dt>
                    <dd class="text-slate-900">{{ $manager->properties->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('manager.fields.buildings') ?? 'Buildings' }}</dt>
                    <dd class="text-slate-900">{{ $manager->buildings->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('manager.fields.invoices') ?? 'Invoices' }}</dt>
                    <dd class="text-slate-900">{{ $manager->invoices->count() }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-lg font-semibold mb-3">{{ __('manager.sections.properties') ?? 'Properties' }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Address</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Building</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Tenants</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 uppercase">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($manager->properties as $property)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2 text-sm text-slate-900">
                                <a href="{{ route('filament.admin.resources.properties.edit', $property) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $property->address }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-500">
                                @if($property->building)
                                    <a href="{{ route('filament.admin.resources.buildings.edit', $property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $property->building->display_name ?? $property->building->address }}
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-500">{{ $property->tenants_count ?? $property->tenants()->count() }}</td>
                            <td class="px-4 py-2 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('filament.admin.resources.properties.edit', $property) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
                                    <form action="{{ route('filament.admin.resources.properties.destroy', $property) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">{{ __('common.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-center text-slate-500">{{ __('manager.empty_properties') ?? 'No properties found' }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">{{ __('manager.sections.invoices') ?? 'Invoices' }}</h2>
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
                    @forelse($manager->invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('filament.admin.resources.invoices.view', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">
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
                                <a href="{{ route('filament.admin.resources.invoices.view', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">{{ __('common.view') }}</a>
                                <a href="{{ route('filament.admin.resources.invoices.edit', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ __('common.edit') }}</a>
                                <form action="{{ route('filament.admin.resources.invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
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
</div>
@endsection
