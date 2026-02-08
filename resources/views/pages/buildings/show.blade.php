@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('superadmin')
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $building->name ?? __('shared.buildings.singular') . ' #' . $building->id }}</h1>
            <p class="text-slate-600">{{ $building->address }}</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('superadmin.compat.buildings.edit', $building) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('superadmin.compat.buildings.destroy', $building) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700">{{ __('common.delete') }}</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="{{ __('shared.buildings.fields.properties') }}" value="{{ $properties->count() }}" />
        <x-stat-card label="{{ __('shared.buildings.fields.meters') }}" value="{{ $meters->count() }}" />
        <x-stat-card label="{{ __('shared.buildings.fields.tenants') }}" value="{{ $tenants->count() }}" />
        <x-stat-card label="{{ __('billing.invoices.title') ?? 'Invoices' }}" value="{{ $invoices->count() }}" />
    </div>

    <div class="space-y-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('shared.buildings.fields.properties') }}</h2>
                    <p class="text-sm text-slate-500">All properties linked to this building</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.address') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.tenants') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.meters') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($properties as $property)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    <a href="{{ route('superadmin.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $property->address }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->type?->label() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->tenants_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->meters_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                    <a href="{{ route('superadmin.properties.show', $property) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.properties.edit', $property) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.properties.destroy', $property) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('shared.buildings.fields.meters') }}</h2>
                    <p class="text-sm text-slate-500">Meters across all properties in this building</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.type') ?? 'Type' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.serial_number') ?? 'Serial' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.address') }}</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $meter->property?->address }}</td>
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
    </div>
</div>
@endsection
@break

@case('manager')
@extends('layouts.app')

@section('title', __('buildings.pages.manager_show.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $building->address }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('buildings.pages.manager_show.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $building)
            <x-button href="{{ route('manager.buildings.edit', $building) }}" variant="secondary">
                {{ __('buildings.pages.manager_show.edit_building') }}
            </x-button>
            @endcan
            @can('delete', $building)
            <form action="{{ route('manager.buildings.destroy', $building) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('buildings.pages.manager_show.delete_confirm') }}');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('buildings.pages.manager_show.delete_building') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Building Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-1">
        <x-card>
            <x-slot name="title">{{ __('buildings.pages.manager_show.info_title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.name') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->display_name }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.address') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->address }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.total_apartments') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->total_apartments }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.properties_registered') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->properties->count() }}</dd>
                </div>
            </dl>
        </x-card>
    </div>

    <!-- Properties in Building -->
    <div class="mt-8">
        <x-card>
            <div class="flex items-center justify-between">
                <x-slot name="title">{{ __('buildings.pages.manager_show.properties_title') }}</x-slot>
                @can('create', App\Models\Property::class)
                <x-button href="{{ route('manager.properties.create', ['building_id' => $building->id]) }}" variant="secondary" size="sm">
                    {{ __('buildings.pages.manager_show.add_property') }}
                </x-button>
                @endcan
            </div>
            
            @if($building->properties->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('buildings.pages.manager_show.properties_headers.address') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.type') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.area') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.meters') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.shared') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('buildings.pages.manager_show.properties_headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($building->properties as $property)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            <span class="capitalize">{{ enum_label($property->type) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($property->area_sqm, 2) }} m²
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $property->meters->count() }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            @if($property->tenants->isNotEmpty())
                                {{ $property->tenants->first()->name }}
                            @else
                                <span class="text-slate-400">{{ __('buildings.pages.manager_show.vacant') }}</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('buildings.pages.manager_show.view') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-slate-500">{{ __('buildings.pages.manager_show.empty_properties') }}</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $building->name ?? __('shared.buildings.singular') . ' #' . $building->id }}</h1>
            <p class="text-slate-600">{{ $building->address }}</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('superadmin.compat.buildings.edit', $building) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700">{{ __('common.edit') }}</a>
            <form action="{{ route('superadmin.compat.buildings.destroy', $building) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700">{{ __('common.delete') }}</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="{{ __('shared.buildings.fields.properties') }}" value="{{ $properties->count() }}" />
        <x-stat-card label="{{ __('shared.buildings.fields.meters') }}" value="{{ $meters->count() }}" />
        <x-stat-card label="{{ __('shared.buildings.fields.tenants') }}" value="{{ $tenants->count() }}" />
        <x-stat-card label="{{ __('billing.invoices.title') ?? 'Invoices' }}" value="{{ $invoices->count() }}" />
    </div>

    <div class="space-y-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('shared.buildings.fields.properties') }}</h2>
                    <p class="text-sm text-slate-500">All properties linked to this building</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.address') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.tenants') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.meters') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($properties as $property)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    <a href="{{ route('superadmin.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $property->address }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->type?->label() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->tenants_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->meters_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                    <a href="{{ route('superadmin.properties.show', $property) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.properties.edit', $property) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.properties.destroy', $property) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('{{ __('common.confirm_delete') }}')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('shared.buildings.fields.meters') }}</h2>
                    <p class="text-sm text-slate-500">Meters across all properties in this building</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.type') ?? 'Type' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('meters.labels.serial_number') ?? 'Serial' }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('shared.properties.fields.address') }}</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $meter->property?->address }}</td>
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
    </div>
</div>
@endsection
@endswitch
