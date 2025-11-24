@extends('layouts.app')

@section('title', __('meters.manager.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('app.nav.meters') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meters.manager.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meters.manager.index.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Meter::class)
            <x-button href="{{ route('manager.meters.create') }}">
                {{ __('meters.actions.add') }}
            </x-button>
            @endcan
        </div>
    </div>

    <x-card class="mt-8">
        <div class="hidden sm:block">
        <x-data-table :caption="__('meters.manager.index.caption')">
            <x-slot name="header">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('meters.manager.index.headers.serial_number') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.manager.index.headers.type') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.manager.index.headers.property') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.manager.index.headers.installation_date') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.manager.index.headers.latest_reading') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('meters.manager.index.headers.zones') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">{{ __('meters.manager.index.headers.actions') }}</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($meters as $meter)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                    <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $meter->serial_number }}
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <span class="capitalize">{{ $meter->type->label() }}</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <a href="{{ route('manager.properties.show', $meter->property) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $meter->property->address }}
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    {{ $meter->installation_date->format('M d, Y') }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    @if($meter->readings->isNotEmpty())
                        {{ number_format($meter->readings->first()->value, 2) }}
                        <span class="text-slate-400 text-xs">({{ $meter->readings->first()->reading_date->format('M d') }})</span>
                    @else
                        <span class="text-slate-400">No readings</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    @if($meter->supports_zones)
                        <x-status-badge status="active">{{ __('meters.manager.index.zones.yes') }}</x-status-badge>
                    @else
                        <span class="text-slate-400">{{ __('meters.manager.index.zones.no') }}</span>
                    @endif
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        @can('view', $meter)
                        <a href="{{ route('manager.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('meters.actions.view') }}
                        </a>
                        @endcan
                        @can('update', $meter)
                        <a href="{{ route('manager.meters.edit', $meter) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('meters.actions.edit') }}
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('meters.manager.index.empty.text') }} 
                    @can('create', App\Models\Meter::class)
                        <a href="{{ route('manager.meters.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('meters.manager.index.empty.cta') }}</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($meters as $meter)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $meter->serial_number }}</p>
                            <p class="text-xs text-slate-600 capitalize">{{ $meter->type->label() }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ $meter->property->address }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-600">
                            <p>{{ __('meters.manager.index.headers.installation_date') }}: {{ $meter->installation_date->format('M d, Y') }}</p>
                            <p class="mt-1">{{ __('meters.manager.index.headers.zones') }}: {{ $meter->supports_zones ? __('meters.manager.index.zones.yes') : __('meters.manager.index.zones.no') }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">
                        {{ __('meters.manager.index.headers.latest_reading') }}:
                        @if($meter->readings->isNotEmpty())
                            <span class="font-semibold text-slate-900">{{ number_format($meter->readings->first()->value, 2) }}</span>
                            <span class="text-slate-400">({{ $meter->readings->first()->reading_date->format('M d') }})</span>
                        @else
                            <span class="text-slate-400">{{ __('meter_readings.empty.readings') }}</span>
                        @endif
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('view', $meter)
                        <a href="{{ route('manager.meters.show', $meter) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('meters.actions.view') }}
                        </a>
                        @endcan
                        @can('update', $meter)
                        <a href="{{ route('manager.meters.edit', $meter) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('meters.actions.edit') }}
                        </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('meters.manager.index.empty.text') }}
                    @can('create', App\Models\Meter::class)
                        <a href="{{ route('manager.meters.create') }}" class="text-indigo-700 font-semibold">{{ __('meters.manager.index.empty.cta') }}</a>
                    @endcan
                </div>
            @endforelse
        </div>

        @if($meters->hasPages())
        <div class="mt-4">
            {{ $meters->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
