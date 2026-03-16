@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@if($role === 'manager')
    @section('title', __('buildings.pages.manager_index.title'))

    @section('content')
    <x-ui.page
        class="px-4 sm:px-6 lg:px-8"
        :title="__('buildings.pages.manager_index.title')"
        :description="__('buildings.pages.manager_index.description')"
    >
        @can('create', App\Models\Building::class)
            <x-slot name="actions">
                <x-ui.button href="{{ route('manager.buildings.create') }}">
                    {{ __('buildings.pages.manager_index.add') }}
                </x-ui.button>
            </x-slot>
        @endcan

        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('buildings.pages.manager_index.table_caption')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">{{ __('buildings.pages.manager_index.headers.building') }}</th>
                            <th scope="col">{{ __('buildings.pages.manager_index.headers.total_apartments') }}</th>
                            <th scope="col">{{ __('buildings.pages.manager_index.headers.properties') }}</th>
                            <th scope="col" class="text-right">
                                <span class="sr-only">{{ __('buildings.pages.manager_index.headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @forelse($buildings as $building)
                        <tr>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('manager.buildings.show', $building) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                    <span class="block font-semibold text-slate-900">{{ $building->display_name }}</span>
                                    <span class="block text-xs font-normal text-slate-600">{{ $building->address }}</span>
                                </a>
                            </td>
                            <td>{{ $building->total_apartments }}</td>
                            <td>{{ $building->properties_count }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-3">
                                    @can('view', $building)
                                        <a href="{{ route('manager.buildings.show', $building) }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">
                                            {{ __('buildings.pages.manager_index.mobile.view') }}
                                        </a>
                                    @endcan
                                    @can('update', $building)
                                        <a href="{{ route('manager.buildings.edit', $building) }}" class="text-sm font-semibold text-slate-700 transition hover:text-slate-900">
                                            {{ __('buildings.pages.manager_index.mobile.edit') }}
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-sm text-slate-500">
                                {{ __('buildings.pages.manager_index.empty') }}
                                @can('create', App\Models\Building::class)
                                    <a href="{{ route('manager.buildings.create') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('buildings.pages.manager_index.create_now') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse($buildings as $building)
                    <x-ui.list-record :title="$building->display_name" :subtitle="$building->address">
                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('buildings.pages.manager_index.mobile.apartments')">{{ $building->total_apartments }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('buildings.pages.manager_index.mobile.properties')">{{ $building->properties_count }}</x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            @can('view', $building)
                                <a href="{{ route('manager.buildings.show', $building) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('buildings.pages.manager_index.mobile.view') }}
                                </a>
                            @endcan
                            @can('update', $building)
                                <a href="{{ route('manager.buildings.edit', $building) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('buildings.pages.manager_index.mobile.edit') }}
                                </a>
                            @endcan
                        </x-slot>
                    </x-ui.list-record>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600 shadow-sm">
                        {{ __('buildings.pages.manager_index.empty') }}
                        @can('create', App\Models\Building::class)
                            <a href="{{ route('manager.buildings.create') }}" class="font-semibold text-indigo-700">{{ __('buildings.pages.manager_index.create_now') }}</a>
                        @endcan
                    </div>
                @endforelse
            </div>

            @if($buildings->hasPages())
                <div class="mt-6">
                    {{ $buildings->links() }}
                </div>
            @endif
        </x-card>
    </x-ui.page>
    @endsection
@else
    @section('content')
    <x-ui.page
        class="px-4 sm:px-6 lg:px-8"
        :title="__('shared.buildings.title')"
        :description="__('buildings.pages.superadmin_index.description')"
    >
        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('shared.buildings.title')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">{{ __('shared.manager.fields.id') }}</th>
                            <th scope="col">{{ __('shared.buildings.fields.name') }}</th>
                            <th scope="col">{{ __('shared.buildings.fields.address') }}</th>
                            <th scope="col">{{ __('shared.buildings.fields.total_apartments') }}</th>
                            <th scope="col">{{ __('shared.buildings.fields.properties') }}</th>
                            <th scope="col" class="text-right">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </x-slot>

                    @forelse($buildings as $building)
                        <tr>
                            <td class="font-medium text-slate-500">{{ $building->id }}</td>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('superadmin.buildings.show', $building) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                    {{ $building->name ?? __('shared.buildings.singular') . ' #' . $building->id }}
                                </a>
                            </td>
                            <td>{{ $building->address }}</td>
                            <td>{{ $building->total_apartments ?? '—' }}</td>
                            <td>{{ $building->properties_count }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('superadmin.buildings.show', $building) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">{{ __('common.view') }}</a>
                                    <a href="{{ route('superadmin.buildings.edit', $building) }}" class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">{{ __('common.edit') }}</a>
                                    <form action="{{ route('superadmin.buildings.destroy', $building) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">{{ __('common.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse($buildings as $building)
                    <x-ui.list-record :title="$building->name ?? __('shared.buildings.singular') . ' #' . $building->id" :subtitle="$building->address">
                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('shared.manager.fields.id')">{{ $building->id }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('shared.buildings.fields.total_apartments')">{{ $building->total_apartments ?? '—' }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('shared.buildings.fields.properties')">{{ $building->properties_count }}</x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            <a href="{{ route('superadmin.buildings.show', $building) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                                {{ __('common.view') }}
                            </a>
                            <a href="{{ route('superadmin.buildings.edit', $building) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('common.edit') }}
                            </a>
                            <form action="{{ route('superadmin.buildings.destroy', $building) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');" class="w-full">
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

            @if($buildings->hasPages())
                <div class="mt-6">
                    {{ $buildings->links() }}
                </div>
            @endif
        </x-card>
    </x-ui.page>
    @endsection
@endif
