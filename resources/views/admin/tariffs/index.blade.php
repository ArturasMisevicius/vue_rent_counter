@extends('layouts.app')

@section('title', __('tariffs.headings.index'))

@section('content')
@php
    $tariffTypeLabels = \App\Enums\TariffType::labels();
@endphp
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('tariffs.headings.index') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tariffs.descriptions.index') }}</p>
        </div>
        @can('create', App\Models\Tariff::class)
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('filament.admin.resources.tariffs.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('tariffs.actions.add') }}
            </a>
        </div>
        @endcan
    </div>

    <!-- Tariffs List -->
    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table :caption="__('tariffs.headings.list')">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">{{ __('tariffs.labels.name') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tariffs.labels.provider') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tariffs.labels.type') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('tariffs.labels.active_period') }}</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ __('tariffs.labels.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($tariffs as $tariff)
                @php
                    $isActive = $tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now());
                @endphp
                <tr class="{{ $isActive ? 'bg-green-50' : '' }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ $tariff->name }}
                        @if($isActive)
                            <span class="ml-2 inline-flex items-center rounded-md bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                {{ __('tariffs.statuses.active') }}
                            </span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $tariff->provider->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                            {{ $tariffTypeLabels[$tariff->configuration['type'] ?? 'flat'] ?? ucfirst(str_replace('_', ' ', $tariff->configuration['type'] ?? 'flat')) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $tariff->active_from->format('Y-m-d') }} 
                        @if($tariff->active_until)
                            - {{ $tariff->active_until->format('Y-m-d') }}
                        @else
                            - {{ __('tariffs.labels.present') }}
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('update', $tariff)
                        <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('tariffs.actions.edit') }}
                        </a>
                        @endcan
                        <a href="{{ route('admin.tariffs.show', $tariff) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('tariffs.actions.view') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('tariffs.empty.list') }}
                    </td>
                </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($tariffs as $tariff)
            @php
                $isActive = $tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now());
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $tariff->name }}</p>
                        <p class="text-xs text-slate-600">{{ $tariff->provider->name }}</p>
                        <p class="text-xs text-slate-600 mt-1">{{ $tariffTypeLabels[$tariff->configuration['type'] ?? 'flat'] ?? ucfirst(str_replace('_', ' ', $tariff->configuration['type'] ?? 'flat')) }}</p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p>{{ $tariff->active_from->format('Y-m-d') }} - {{ $tariff->active_until?->format('Y-m-d') ?? __('tariffs.labels.present') }}</p>
                        <p class="mt-1">{{ $isActive ? __('tariffs.statuses.active') : __('tariffs.statuses.inactive') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @can('update', $tariff)
                    <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('tariffs.actions.edit') }}
                    </a>
                    @endcan
                    <a href="{{ route('admin.tariffs.show', $tariff) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('tariffs.actions.view') }}
                    </a>
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                {{ __('tariffs.empty.list') }}
            </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    @if($tariffs->hasPages())
    <div class="mt-6">
        {{ $tariffs->links() }}
    </div>
    @endif
</div>
@endsection
