@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@if($role === 'manager')
    @section('title', __('properties.shared.index.title'))

    @section('content')
    <x-ui.page
        class="px-4 sm:px-6 lg:px-8"
        :title="__('properties.shared.index.title')"
        :description="__('properties.shared.index.description')"
    >
        @can('create', App\Models\Property::class)
            <x-slot name="actions">
                <x-ui.button href="{{ route('manager.properties.create') }}">
                    {{ __('properties.actions.add') }}
                </x-ui.button>
            </x-slot>
        @endcan

        <x-ui.section-card>
            <form method="GET" action="{{ route('manager.properties.index') }}" class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
                <div>
                    <label for="search" class="mb-2 block text-sm font-medium text-slate-700">{{ __('properties.shared.index.filters.search') }}</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('properties.shared.index.filters.search_placeholder') }}"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                </div>

                <div>
                    <label for="property_type" class="mb-2 block text-sm font-medium text-slate-700">{{ __('properties.shared.index.filters.type') }}</label>
                    <select name="property_type" id="property_type" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">{{ __('properties.shared.index.filters.all_types') }}</option>
                        @foreach($propertyTypeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('property_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="building_id" class="mb-2 block text-sm font-medium text-slate-700">{{ __('properties.shared.index.filters.building') }}</label>
                    <select name="building_id" id="building_id" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">{{ __('properties.shared.index.filters.all_buildings') }}</option>
                        @foreach($buildings as $building)
                            <option value="{{ $building->id }}" @selected(request('building_id') == $building->id)>{{ $building->address }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        {{ __('properties.shared.index.filters.filter') }}
                    </button>

                    @if(request()->hasAny(['search', 'property_type', 'building_id']))
                        <a href="{{ route('manager.properties.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                            {{ __('properties.shared.index.filters.clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.section-card>

        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('properties.shared.index.caption')">
                    <x-slot name="header">
                        <tr>
                            <x-sortable-header column="address" :label="__('properties.shared.index.headers.address')" />
                            <x-sortable-header column="property_type" :label="__('properties.shared.index.headers.type')" />
                            <x-sortable-header column="area_sqm" :label="__('properties.shared.index.headers.area')" />
                            <th scope="col">{{ __('properties.shared.index.headers.building') }}</th>
                            <th scope="col">{{ __('properties.shared.index.headers.meters') }}</th>
                            <th scope="col">{{ __('properties.shared.index.headers.tenants') }}</th>
                            <th scope="col" class="text-right">
                                <span class="sr-only">{{ __('properties.shared.index.headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @forelse($properties as $property)
                        <tr>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                    {{ $property->address }}
                                </a>
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ $property->type->label() }}
                                </span>
                            </td>
                            <td>{{ number_format($property->area_sqm, 2) }} m²</td>
                            <td>
                                @if($property->building)
                                    <a href="{{ route('manager.buildings.show', $property->building) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                        {{ $property->building->address }}
                                    </a>
                                @else
                                    <span class="text-slate-400">{{ __('meter_readings.na') }}</span>
                                @endif
                            </td>
                            <td class="font-medium text-slate-900">{{ $property->meters_count }}</td>
                            <td class="font-medium text-slate-900">{{ $property->tenants->count() }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-3">
                                    @can('view', $property)
                                        <a href="{{ route('manager.properties.show', $property) }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">
                                            {{ __('properties.actions.view') }}
                                        </a>
                                    @endcan
                                    @can('update', $property)
                                        <a href="{{ route('manager.properties.edit', $property) }}" class="text-sm font-semibold text-slate-700 transition hover:text-slate-900">
                                            {{ __('properties.actions.edit') }}
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-sm text-slate-500">
                                {{ __('properties.shared.index.empty.text') }}
                                @can('create', App\Models\Property::class)
                                    <a href="{{ route('manager.properties.create') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('properties.shared.index.empty.cta') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse($properties as $property)
                    <x-ui.list-record :title="$property->address" :subtitle="$property->type->label()">
                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('properties.shared.index.headers.area')">
                                {{ number_format($property->area_sqm, 2) }} m²
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('properties.shared.index.headers.building')">
                                {{ $property->building?->address ?? __('app.common.na') }}
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('properties.shared.index.headers.meters')">
                                {{ $property->meters_count }}
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('properties.shared.index.headers.tenants')">
                                {{ $property->tenants->count() }}
                            </x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            @can('view', $property)
                                <a href="{{ route('manager.properties.show', $property) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('properties.actions.view') }}
                                </a>
                            @endcan
                            @can('update', $property)
                                <a href="{{ route('manager.properties.edit', $property) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('properties.actions.edit') }}
                                </a>
                            @endcan
                        </x-slot>
                    </x-ui.list-record>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600 shadow-sm">
                        {{ __('properties.shared.index.empty.text') }}
                        @can('create', App\Models\Property::class)
                            <a href="{{ route('manager.properties.create') }}" class="font-semibold text-indigo-700">{{ __('properties.shared.index.empty.cta') }}</a>
                        @endcan
                    </div>
                @endforelse
            </div>

            @if($properties->hasPages())
                <div class="mt-6">
                    {{ $properties->links() }}
                </div>
            @endif
        </x-card>
    </x-ui.page>
    @endsection
@else
    @section('content')
    <x-ui.page
        class="px-4 sm:px-6 lg:px-8"
        :title="__('shared.properties.title')"
        :description="__('properties.pages.superadmin_index.description')"
    >
        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('shared.properties.title')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">{{ __('shared.manager.fields.id') }}</th>
                            <th scope="col">{{ __('shared.properties.fields.address') }}</th>
                            <th scope="col">{{ __('shared.properties.fields.type') }}</th>
                            <th scope="col">{{ __('shared.properties.fields.building') }}</th>
                            <th scope="col">{{ __('shared.properties.fields.tenants') }}</th>
                            <th scope="col">{{ __('shared.properties.fields.meters') }}</th>
                            <th scope="col" class="text-right">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </x-slot>

                    @forelse($properties as $property)
                        <tr>
                            <td class="font-medium text-slate-500">{{ $property->id }}</td>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('superadmin.properties.show', $property) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                    {{ $property->address }}
                                </a>
                            </td>
                            <td>{{ $property->type?->label() }}</td>
                            <td>
                                @if($property->building)
                                    <a href="{{ route('superadmin.buildings.show', $property->building) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                        {{ $property->building->name ?? $property->building->address }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>{{ $property->tenants_count }}</td>
                            <td>{{ $property->meters_count }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('superadmin.properties.show', $property) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.compat.properties.edit', $property) }}" class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.compat.properties.destroy', $property) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse($properties as $property)
                    <x-ui.list-record :title="$property->address" :subtitle="$property->type?->label()">
                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('shared.manager.fields.id')">{{ $property->id }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('shared.properties.fields.building')">
                                @if($property->building)
                                    <a href="{{ route('superadmin.buildings.show', $property->building) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ $property->building->name ?? $property->building->address }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('shared.properties.fields.tenants')">{{ $property->tenants_count }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('shared.properties.fields.meters')">{{ $property->meters_count }}</x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            <a href="{{ route('superadmin.properties.show', $property) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                                {{ __('common.view') }}
                            </a>
                            <a href="{{ route('superadmin.compat.properties.edit', $property) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('common.edit') }}
                            </a>
                            <form action="{{ route('superadmin.compat.properties.destroy', $property) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2">
                                    {{ __('common.delete') }}
                                </button>
                            </form>
                        </x-slot>
                    </x-ui.list-record>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600 shadow-sm">
                        {{ __('shared.empty') }}
                    </div>
                @endforelse
            </div>

            @if($properties->hasPages())
                <div class="mt-6">
                    {{ $properties->links() }}
                </div>
            @endif
        </x-card>
    </x-ui.page>
    @endsection
@endif
