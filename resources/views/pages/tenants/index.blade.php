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
            <h1 class="text-2xl font-bold text-slate-900">{{ __('tenants.pages.index.title') ?? 'Tenants' }}</h1>
            <p class="text-slate-600">{{ __('tenants.pages.index.subtitle') ?? 'All tenants across all organizations' }}</p>
        </div>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.name') ?? 'Name' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.email') ?? 'Email' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.property') ?? 'Property' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.invoices') ?? 'Invoices' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.readings') ?? 'Readings' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($tenant->property)
                                <a href="{{ route('superadmin.compat.properties.edit', $tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $tenant->property->address }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->invoices_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->meter_readings_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">
                                    {{ __('common.view') }}
                                </a>
                                <a href="{{ route('superadmin.compat.tenants.edit', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') }}
                                </a>
                                <form action="{{ route('superadmin.compat.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
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
                        <td colspan="7" class="px-6 py-4 text-center text-slate-500">
                            {{ __('tenants.empty') ?? 'No tenants found' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
@break

@case('admin')
@extends('layouts.app')

@section('title', __('tenants.headings.index'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('tenants.headings.index') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tenants.headings.index_description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('admin.tenants.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('tenants.actions.add') }}
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

    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table :caption="__('tenants.headings.list')">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">{{ __('tenants.labels.name') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tenants.labels.email') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tenants.labels.property') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tenants.labels.status') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tenants.labels.created') }}</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ __('tenants.labels.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($tenants as $tenant)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ $tenant->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $tenant->email }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($tenant->property)
                            {{ $tenant->property->address }}
                        @else
                            <span class="text-slate-400">{{ __('tenants.empty.property') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($tenant->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ __('tenants.statuses.active') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ __('tenants.statuses.inactive') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $tenant->created_at->format('M d, Y') }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('tenants.actions.view') }}<span class="sr-only">, {{ $tenant->name }}</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('tenants.empty.list') }} <a href="{{ route('admin.tenants.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('tenants.empty.list_cta') }}</a>
                    </td>
                </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($tenants as $tenant)
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $tenant->name }}</p>
                        <p class="text-xs text-slate-600">{{ $tenant->email }}</p>
                        <p class="text-xs text-slate-600 mt-1">
                            {{ $tenant->property->address ?? __('tenants.empty.property') }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p>{{ __('tenants.labels.status') }}: {{ $tenant->is_active ? __('tenants.statuses.active') : __('tenants.statuses.inactive') }}</p>
                        <p>{{ __('tenants.labels.created') }}: {{ $tenant->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('tenants.actions.view') }}
                    </a>
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                {{ __('tenants.empty.list') }}
                <a href="{{ route('admin.tenants.create') }}" class="text-indigo-700 font-semibold">{{ __('tenants.empty.list_cta') }}</a>
            </div>
            @endforelse
        </div>
    </div>

    @if($tenants->hasPages())
    <div class="mt-4">
        {{ $tenants->links() }}
    </div>
    @endif
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('tenants.pages.index.title') ?? 'Tenants' }}</h1>
            <p class="text-slate-600">{{ __('tenants.pages.index.subtitle') ?? 'All tenants across all organizations' }}</p>
        </div>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.name') ?? 'Name' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.email') ?? 'Email' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.property') ?? 'Property' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.invoices') ?? 'Invoices' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.readings') ?? 'Readings' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($tenant->property)
                                <a href="{{ route('superadmin.compat.properties.edit', $tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $tenant->property->address }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->invoices_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->meter_readings_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">
                                    {{ __('common.view') }}
                                </a>
                                <a href="{{ route('superadmin.compat.tenants.edit', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') }}
                                </a>
                                <form action="{{ route('superadmin.compat.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
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
                        <td colspan="7" class="px-6 py-4 text-center text-slate-500">
                            {{ __('tenants.empty') ?? 'No tenants found' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
@endswitch
