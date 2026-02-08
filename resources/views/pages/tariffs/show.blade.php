@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('admin')
@extends('layouts.app')

@section('title', __('tariffs.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('tariffs.headings.show') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tariffs.descriptions.show') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-3">
            @can('update', $tariff)
            <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('tariffs.actions.edit') }}
            </a>
            @endcan
            <a href="{{ route('admin.tariffs.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                {{ __('tariffs.actions.back') }}
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Tariff Information -->
        <div class="lg:col-span-2">
            <x-card :title="__('tariffs.headings.information')">
                <dl class="divide-y divide-slate-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tariff->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.provider') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            <a href="{{ route('admin.providers.show', $tariff->provider) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $tariff->provider->name }}
                            </a>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.service_type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            <x-status-badge :status="$tariff->provider->service_type->value">
                                {{ enum_label($tariff->provider->service_type) }}
                            </x-status-badge>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.active_from') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tariff->active_from->format('Y-m-d') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.active_until') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            {{ $tariff->active_until ? $tariff->active_until->format('Y-m-d') : __('tariffs.labels.present') }}
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.status') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            @if($tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now()))
                                <x-status-badge status="active">{{ __('tariffs.statuses.active') }}</x-status-badge>
                            @else
                                <x-status-badge status="inactive">{{ __('tariffs.statuses.inactive') }}</x-status-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- Configuration -->
            <div class="mt-6">
                <x-card :title="__('tariffs.headings.configuration')">
                    <pre class="text-xs bg-slate-50 p-4 rounded-md overflow-x-auto">{{ json_encode($tariff->configuration, JSON_PRETTY_PRINT) }}</pre>
                </x-card>
            </div>

            <!-- Version History -->
            @if($versionHistory->isNotEmpty())
            <div class="mt-6">
                <x-card :title="__('tariffs.headings.version_history')">
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200" aria-label="{{ __('tariffs.headings.version_history') }}">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('tariffs.labels.active_from') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('tariffs.labels.active_until') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('tariffs.labels.status') }}</th>
                                    <th class="relative px-3 py-3">
                                        <span class="sr-only">{{ __('tariffs.labels.actions') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($versionHistory as $version)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900">{{ $version->active_from->format('Y-m-d') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $version->active_until ? $version->active_until->format('Y-m-d') : __('tariffs.labels.present') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        @if($version->active_from <= now() && (!$version->active_until || $version->active_until >= now()))
                                            <x-status-badge status="active">{{ __('tariffs.statuses.active') }}</x-status-badge>
                                        @else
                                            <x-status-badge status="inactive">{{ __('tariffs.statuses.inactive') }}</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap px-3 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.tariffs.show', $version) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('tariffs.actions.view') }}</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($versionHistory as $version)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ $version->active_from->format('Y-m-d') }} - {{ $version->active_until?->format('Y-m-d') ?? __('tariffs.labels.present') }}</p>
                            <p class="text-xs text-slate-600">
                                {{ $version->active_from <= now() && (!$version->active_until || $version->active_until >= now()) ? __('tariffs.statuses.active') : __('tariffs.statuses.inactive') }}
                            </p>
                            <div class="mt-2">
                                <a href="{{ route('admin.tariffs.show', $version) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('tariffs.actions.view') }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <x-card :title="__('tariffs.headings.quick_actions')">
                <div class="space-y-3">
                    @can('update', $tariff)
                    <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="block w-full rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        {{ __('tariffs.actions.edit') }}
                    </a>
                    @endcan
                    
                    @can('delete', $tariff)
                    <form action="{{ route('admin.tariffs.destroy', $tariff) }}" method="POST" onsubmit="return confirm('{{ __('tariffs.confirmations.delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            {{ __('tariffs.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('tariffs.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('tariffs.headings.show') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tariffs.descriptions.show') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-3">
            @can('update', $tariff)
            <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('tariffs.actions.edit') }}
            </a>
            @endcan
            <a href="{{ route('admin.tariffs.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                {{ __('tariffs.actions.back') }}
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Tariff Information -->
        <div class="lg:col-span-2">
            <x-card :title="__('tariffs.headings.information')">
                <dl class="divide-y divide-slate-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tariff->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.provider') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            <a href="{{ route('admin.providers.show', $tariff->provider) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $tariff->provider->name }}
                            </a>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.service_type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            <x-status-badge :status="$tariff->provider->service_type->value">
                                {{ enum_label($tariff->provider->service_type) }}
                            </x-status-badge>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.active_from') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tariff->active_from->format('Y-m-d') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.active_until') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            {{ $tariff->active_until ? $tariff->active_until->format('Y-m-d') : __('tariffs.labels.present') }}
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tariffs.labels.status') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            @if($tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now()))
                                <x-status-badge status="active">{{ __('tariffs.statuses.active') }}</x-status-badge>
                            @else
                                <x-status-badge status="inactive">{{ __('tariffs.statuses.inactive') }}</x-status-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- Configuration -->
            <div class="mt-6">
                <x-card :title="__('tariffs.headings.configuration')">
                    <pre class="text-xs bg-slate-50 p-4 rounded-md overflow-x-auto">{{ json_encode($tariff->configuration, JSON_PRETTY_PRINT) }}</pre>
                </x-card>
            </div>

            <!-- Version History -->
            @if($versionHistory->isNotEmpty())
            <div class="mt-6">
                <x-card :title="__('tariffs.headings.version_history')">
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200" aria-label="{{ __('tariffs.headings.version_history') }}">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('tariffs.labels.active_from') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('tariffs.labels.active_until') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('tariffs.labels.status') }}</th>
                                    <th class="relative px-3 py-3">
                                        <span class="sr-only">{{ __('tariffs.labels.actions') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($versionHistory as $version)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900">{{ $version->active_from->format('Y-m-d') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $version->active_until ? $version->active_until->format('Y-m-d') : __('tariffs.labels.present') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        @if($version->active_from <= now() && (!$version->active_until || $version->active_until >= now()))
                                            <x-status-badge status="active">{{ __('tariffs.statuses.active') }}</x-status-badge>
                                        @else
                                            <x-status-badge status="inactive">{{ __('tariffs.statuses.inactive') }}</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap px-3 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.tariffs.show', $version) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('tariffs.actions.view') }}</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($versionHistory as $version)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ $version->active_from->format('Y-m-d') }} - {{ $version->active_until?->format('Y-m-d') ?? __('tariffs.labels.present') }}</p>
                            <p class="text-xs text-slate-600">
                                {{ $version->active_from <= now() && (!$version->active_until || $version->active_until >= now()) ? __('tariffs.statuses.active') : __('tariffs.statuses.inactive') }}
                            </p>
                            <div class="mt-2">
                                <a href="{{ route('admin.tariffs.show', $version) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('tariffs.actions.view') }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <x-card :title="__('tariffs.headings.quick_actions')">
                <div class="space-y-3">
                    @can('update', $tariff)
                    <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="block w-full rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        {{ __('tariffs.actions.edit') }}
                    </a>
                    @endcan
                    
                    @can('delete', $tariff)
                    <form action="{{ route('admin.tariffs.destroy', $tariff) }}" method="POST" onsubmit="return confirm('{{ __('tariffs.confirmations.delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            {{ __('tariffs.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
@endswitch
