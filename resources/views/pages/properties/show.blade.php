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
            <h1 class="text-2xl font-bold text-slate-900">{{ $property->address }}</h1>
            <p class="text-slate-600">{{ $property->building?->name ?? $property->building?->address }}</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('superadmin.compat.properties.edit', $property) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('superadmin.compat.properties.destroy', $property) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700">{{ __('common.delete') }}</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="{{ __('shared.properties.fields.tenants') }}" value="{{ $tenants->count() }}" />
        <x-stat-card label="{{ __('shared.properties.fields.meters') }}" value="{{ $meters->count() }}" />
        <x-stat-card label="{{ __('billing.invoices.title') ?? 'Invoices' }}" value="{{ $invoices->count() }}" />
        <x-stat-card label="{{ __('shared.properties.fields.area') }}" value="{{ $property->area_sqm ?? '—' }}" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-card>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('shared.properties.singular') }} Details</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm text-slate-700">
                <dt class="font-semibold">{{ __('shared.properties.fields.address') }}</dt>
                <dd>{{ $property->address }}</dd>
                <dt class="font-semibold">{{ __('shared.properties.fields.building') }}</dt>
                <dd>
                    @if($property->building)
                        <a href="{{ route('superadmin.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                            {{ $property->building->name ?? $property->building->address }}
                        </a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="font-semibold">{{ __('shared.properties.fields.type') }}</dt>
                <dd>{{ $property->type?->label() }}</dd>
                <dt class="font-semibold">{{ __('shared.properties.fields.area') }}</dt>
                <dd>{{ $property->area_sqm ?? '—' }}</dd>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('shared.buildings.fields.tenants') }}</h2>
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
                                    <a href="{{ route('superadmin.compat.tenants.edit', $tenant) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.tenants.destroy', $tenant) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
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
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('shared.properties.fields.meters') }}</h2>
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
                                    <a href="{{ route('superadmin.compat.meters.edit', $meter) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.meters.edit', $meter) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.meters.destroy', $meter) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
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
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.shared') ?? 'Tenant' }}</th>
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
                                    <a href="{{ route('superadmin.compat.invoices.edit', $invoice) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.invoices.edit', $invoice) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.invoices.destroy', $invoice) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection
@break

@case('admin')
@section('title', 'Property')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-slate-900">{{ $property->name }}</h1>
        <p class="mt-2 text-sm text-slate-700">{{ $property->address }}</p>
    </div>
@endsection
@break

@case('manager')
@section('title', __('properties.pages.manager_show.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $property->address }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('properties.pages.manager_show.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $property)
            <x-button href="{{ route('manager.properties.edit', $property) }}" variant="secondary">
                {{ __('properties.pages.manager_show.edit_property') }}
            </x-button>
            @endcan
            @can('delete', $property)
            <form action="{{ route('manager.properties.destroy', $property) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('properties.pages.manager_show.delete_confirm') }}');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('properties.pages.manager_show.delete_property') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Property Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card>
            <x-slot name="title">{{ __('properties.pages.manager_show.info_title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.address') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $property->address }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.type') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        <span class="capitalize">{{ enum_label($property->type) }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.area') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ number_format($property->area_sqm, 2) }} m²</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.labels.building') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">
                        @if($property->building)
                            <a href="{{ route('manager.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->building->address }}
                            </a>
                        @else
                            <span class="text-slate-400">{{ __('properties.pages.manager_show.building_missing') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Current Tenant -->
        <x-card>
            <x-slot name="title">{{ __('properties.pages.manager_show.current_tenant_title') }}</x-slot>
            
            @if($property->tenants->isNotEmpty())
                <dl class="divide-y divide-slate-100">
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.tenant_labels.name') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $property->tenants->first()?->name }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.tenant_labels.email') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $property->tenants->first()?->email }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('properties.pages.manager_show.tenant_labels.phone') }}</dt>
                        <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $property->tenants->first()?->phone ?? __('properties.pages.manager_show.tenant_na') }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('properties.pages.manager_show.no_tenant') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Meters -->
    <div class="mt-8">
        <x-card>
            <div class="flex items-center justify-between">
                <x-slot name="title">{{ __('properties.pages.manager_show.meters_title') }}</x-slot>
                @can('create', App\Models\Meter::class)
                <x-button href="{{ route('manager.meters.create', ['property_id' => $property->id]) }}" variant="secondary" size="sm">
                    {{ __('properties.pages.manager_show.add_meter') }}
                </x-button>
                @endcan
            </div>
            
            @if($property->meters->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('properties.pages.manager_show.meters_headers.serial') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('properties.pages.manager_show.meters_headers.type') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('properties.pages.manager_show.meters_headers.installation') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('properties.pages.manager_show.meters_headers.latest') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('properties.pages.manager_show.meters_headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($property->meters as $meter)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $meter->serial_number }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $meter->getServiceDisplayName() }}
                            <span class="text-xs text-slate-400">({{ $meter->getUnitOfMeasurement() }})</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $meter->installation_date->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            @if($meter->readings->isNotEmpty())
                                {{ number_format($meter->readings->first()->value, 2) }}
                                <span class="text-slate-400 text-xs">({{ $meter->readings->first()->reading_date->format('M d') }})</span>
                            @else
                                <span class="text-slate-400">{{ __('properties.pages.manager_show.latest_none') }}</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('properties.pages.manager_show.view') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-slate-500">{{ __('properties.pages.manager_show.no_meters_installed') }}</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
@break

@default
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $property->address }}</h1>
            <p class="text-slate-600">{{ $property->building?->name ?? $property->building?->address }}</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('superadmin.compat.properties.edit', $property) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('superadmin.compat.properties.destroy', $property) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700">{{ __('common.delete') }}</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="{{ __('shared.properties.fields.tenants') }}" value="{{ $tenants->count() }}" />
        <x-stat-card label="{{ __('shared.properties.fields.meters') }}" value="{{ $meters->count() }}" />
        <x-stat-card label="{{ __('billing.invoices.title') ?? 'Invoices' }}" value="{{ $invoices->count() }}" />
        <x-stat-card label="{{ __('shared.properties.fields.area') }}" value="{{ $property->area_sqm ?? '—' }}" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-card>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('shared.properties.singular') }} Details</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm text-slate-700">
                <dt class="font-semibold">{{ __('shared.properties.fields.address') }}</dt>
                <dd>{{ $property->address }}</dd>
                <dt class="font-semibold">{{ __('shared.properties.fields.building') }}</dt>
                <dd>
                    @if($property->building)
                        <a href="{{ route('superadmin.buildings.show', $property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                            {{ $property->building->name ?? $property->building->address }}
                        </a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="font-semibold">{{ __('shared.properties.fields.type') }}</dt>
                <dd>{{ $property->type?->label() }}</dd>
                <dt class="font-semibold">{{ __('shared.properties.fields.area') }}</dt>
                <dd>{{ $property->area_sqm ?? '—' }}</dd>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ __('shared.buildings.fields.tenants') }}</h2>
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
                                    <a href="{{ route('superadmin.compat.tenants.edit', $tenant) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.tenants.destroy', $tenant) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
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
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('shared.properties.fields.meters') }}</h2>
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
                                    <a href="{{ route('superadmin.compat.meters.edit', $meter) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.meters.edit', $meter) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.meters.destroy', $meter) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
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
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('invoices.fields.shared') ?? 'Tenant' }}</th>
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
                                    <a href="{{ route('superadmin.compat.invoices.edit', $invoice) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.invoices.edit', $invoice) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.invoices.destroy', $invoice) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection
@endswitch
