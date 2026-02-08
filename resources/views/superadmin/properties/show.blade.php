@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $property->address }}</h1>
            <p class="text-slate-600">{{ $property->building?->name ?? $property->building?->address }}</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('filament.admin.resources.properties.edit', $property) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('filament.admin.resources.properties.destroy', $property) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700">{{ __('common.delete') }}</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="{{ __('superadmin.properties.fields.tenants') }}" value="{{ $tenants->count() }}" />
        <x-stat-card label="{{ __('superadmin.properties.fields.meters') }}" value="{{ $meters->count() }}" />
        <x-stat-card label="{{ __('billing.invoices.title') ?? 'Invoices' }}" value="{{ $invoices->count() }}" />
        <x-stat-card label="{{ __('superadmin.properties.fields.area') }}" value="{{ $property->area_sqm ?? '—' }}" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-card>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('superadmin.properties.singular') }} Details</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm text-slate-700">
                <dt class="font-semibold">{{ __('superadmin.properties.fields.address') }}</dt>
                <dd>{{ $property->address }}</dd>
                <dt class="font-semibold">{{ __('superadmin.properties.fields.building') }}</dt>
                <dd>
                    @if($property->building)
                        <a href="{{ route('superadmin.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                            {{ $property->building->name ?? $property->building->address }}
                        </a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="font-semibold">{{ __('superadmin.properties.fields.type') }}</dt>
                <dd>{{ $property->type?->label() }}</dd>
                <dt class="font-semibold">{{ __('superadmin.properties.fields.area') }}</dt>
                <dd>{{ $property->area_sqm ?? '—' }}</dd>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('superadmin.buildings.fields.tenants') }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.name') ?? 'Name' }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.email') ?? 'Email' }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($tenants as $tenant)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $tenant->id }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $tenant->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $tenant->email }}</td>
                                <td class="px-4 py-3 text-sm text-right space-x-2">
                                    <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('filament.admin.resources.tenants.edit', $tenant) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('filament.admin.resources.tenants.destroy', $tenant) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-center text-sm text-slate-500">{{ __('superadmin.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('superadmin.properties.fields.meters') }}</h2>
                    <p class="text-sm text-slate-500">Meters assigned to this property</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.type') ?? 'Type' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.serial_number') ?? 'Serial' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.reading_date') ?? 'Last Reading' }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($meters as $meter)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $meter->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $meter->getServiceDisplayName() }}
                                    <span class="text-xs text-slate-400">({{ $meter->getUnitOfMeasurement() }})</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $meter->serial_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ optional($meter->readings->first())->reading_date?->toDateString() ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                    <a href="{{ route('filament.admin.resources.meters.edit', $meter) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('filament.admin.resources.meters.edit', $meter) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('filament.admin.resources.meters.destroy', $meter) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('superadmin.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('billing.invoices.title') ?? 'Invoices' }}</h2>
                    <p class="text-sm text-slate-500">Invoices for tenants in this property</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.tenant') ?? 'Tenant' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.total') ?? 'Total' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.status') ?? 'Status' }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $invoice->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $invoice->tenant?->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $invoice->total_amount }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $invoice->status->label() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                    <a href="{{ route('filament.admin.resources.invoices.edit', $invoice) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('filament.admin.resources.invoices.edit', $invoice) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('filament.admin.resources.invoices.destroy', $invoice) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('superadmin.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection
