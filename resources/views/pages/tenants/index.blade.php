@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@if($role === 'admin')
    @section('title', __('tenants.headings.index'))

    @section('content')
    <x-ui.page
        class="px-4 sm:px-6 lg:px-8"
        :title="__('tenants.headings.index')"
        :description="__('tenants.headings.index_description')"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('tenants.actions.add') }}
            </a>
        </x-slot>

        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('tenants.headings.list')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">{{ __('tenants.labels.name') }}</th>
                            <th scope="col">{{ __('tenants.labels.email') }}</th>
                            <th scope="col">{{ __('tenants.labels.property') }}</th>
                            <th scope="col">{{ __('tenants.labels.status') }}</th>
                            <th scope="col">{{ __('tenants.labels.created') }}</th>
                            <th scope="col" class="text-right">
                                <span class="sr-only">{{ __('tenants.labels.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @forelse($tenants as $tenant)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $tenant->name }}</td>
                            <td>{{ $tenant->email }}</td>
                            <td>
                                @if($tenant->property)
                                    {{ $tenant->property->address }}
                                @else
                                    <span class="text-slate-400">{{ __('tenants.empty.property') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenant->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $tenant->is_active ? __('tenants.statuses.active') : __('tenants.statuses.inactive') }}
                                </span>
                            </td>
                            <td>{{ $tenant->created_at->format('M d, Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">
                                    {{ __('tenants.actions.view') }}
                                    <span class="sr-only">, {{ $tenant->name }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-sm text-slate-500">
                                {{ __('tenants.empty.list') }}
                                <a href="{{ route('admin.tenants.create') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('tenants.empty.list_cta') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse($tenants as $tenant)
                    <x-ui.list-record :title="$tenant->name" :subtitle="$tenant->email">
                        <x-slot name="aside">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenant->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $tenant->is_active ? __('tenants.statuses.active') : __('tenants.statuses.inactive') }}
                            </span>
                        </x-slot>

                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('tenants.labels.property')">
                                {{ $tenant->property->address ?? __('tenants.empty.property') }}
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('tenants.labels.created')">
                                {{ $tenant->created_at->format('M d, Y') }}
                            </x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('tenants.actions.view') }}
                            </a>
                        </x-slot>
                    </x-ui.list-record>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600 shadow-sm">
                        {{ __('tenants.empty.list') }}
                        <a href="{{ route('admin.tenants.create') }}" class="font-semibold text-indigo-700">{{ __('tenants.empty.list_cta') }}</a>
                    </div>
                @endforelse
            </div>

            @if($tenants->hasPages())
                <div class="mt-6">
                    {{ $tenants->links() }}
                </div>
            @endif
        </x-card>
    </x-ui.page>
    @endsection
@else
    @section('content')
    <x-ui.page
        class="px-4 sm:px-6 lg:px-8"
        :title="__('tenants.pages.index.title')"
        :description="__('tenants.pages.index.subtitle')"
    >
        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('tenants.pages.index.title')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">{{ __('shared.manager.fields.id') }}</th>
                            <th scope="col">{{ __('tenants.labels.name') }}</th>
                            <th scope="col">{{ __('tenants.labels.email') }}</th>
                            <th scope="col">{{ __('tenants.labels.property') }}</th>
                            <th scope="col">{{ __('shared.manager.fields.invoices') }}</th>
                            <th scope="col">{{ __('app.navigation.meter_readings') }}</th>
                            <th scope="col" class="text-right">{{ __('app.nav.actions') }}</th>
                        </tr>
                    </x-slot>

                    @forelse($tenants as $tenant)
                        <tr>
                            <td class="font-medium text-slate-500">{{ $tenant->id }}</td>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                    {{ $tenant->name }}
                                </a>
                            </td>
                            <td>{{ $tenant->email }}</td>
                            <td>
                                @if($tenant->property)
                                    <a href="{{ route('superadmin.properties.edit', $tenant->property) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                        {{ $tenant->property->address }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>{{ $tenant->invoices_count }}</td>
                            <td>{{ $tenant->meter_readings_count }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                        {{ __('common.view') }}
                                    </a>
                                    <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                        {{ __('common.edit') }}
                                    </a>
                                    <form action="{{ route('superadmin.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-sm text-slate-500">{{ __('tenants.empty.list') }}</td>
                        </tr>
                    @endforelse
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @forelse($tenants as $tenant)
                    <x-ui.list-record :title="$tenant->name" :subtitle="$tenant->email">
                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('shared.manager.fields.id')">{{ $tenant->id }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('tenants.labels.property')">
                                @if($tenant->property)
                                    <a href="{{ route('superadmin.properties.edit', $tenant->property) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ $tenant->property->address }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('shared.manager.fields.invoices')">{{ $tenant->invoices_count }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('app.navigation.meter_readings')">{{ $tenant->meter_readings_count }}</x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                                {{ __('common.view') }}
                            </a>
                            <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('common.edit') }}
                            </a>
                            <form action="{{ route('superadmin.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');" class="w-full">
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
                        {{ __('tenants.empty.list') }}
                    </div>
                @endforelse
            </div>

            @if($tenants->hasPages())
                <div class="mt-6">
                    {{ $tenants->links() }}
                </div>
            @endif
        </x-card>
    </x-ui.page>
    @endsection
@endif
