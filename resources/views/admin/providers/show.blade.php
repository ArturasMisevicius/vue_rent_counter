@extends('layouts.app')

@section('title', __('providers.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.providers.index')">{{ __('providers.labels.providers') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $provider->name }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('providers.headings.show') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('providers.descriptions.show') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-3">
            @can('update', $provider)
            <a href="{{ route('admin.providers.edit', $provider) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('providers.actions.edit') }}
            </a>
            @endcan
            <a href="{{ route('admin.providers.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                {{ __('providers.actions.back') }}
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Provider Information -->
        <div class="lg:col-span-2">
            <x-card title="{{ __('providers.headings.information') }}">
                <dl class="divide-y divide-slate-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('providers.tables.name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $provider->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('providers.tables.service_type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            <x-status-badge :status="$provider->service_type->value">
                                {{ enum_label($provider->service_type) }}
                            </x-status-badge>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('providers.tables.contact_info') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            @if($provider->contact_info)
                                @if(is_array($provider->contact_info))
                                    <pre class="text-xs bg-slate-50 p-2 rounded">{{ json_encode($provider->contact_info, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    {{ $provider->contact_info }}
                                @endif
                            @else
                                {{ __('providers.statuses.not_available') }}
                            @endif
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('providers.labels.created') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $provider->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Associated Tariffs -->
            <div class="mt-6">
                <x-card title="{{ __('providers.headings.associated_tariffs') }}">
                    @if($provider->tariffs->isNotEmpty())
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200" aria-label="{{ __('providers.headings.associated_tariffs') }}">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('providers.tables.name') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('providers.tables.active_from') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('providers.tables.active_until') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('providers.tables.status') }}</th>
                                    <th class="relative px-3 py-3">
                                        <span class="sr-only">{{ __('providers.tables.actions') }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($provider->tariffs as $tariff)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-900">{{ $tariff->name }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $tariff->active_from->format('Y-m-d') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ $tariff->active_until ? $tariff->active_until->format('Y-m-d') : __('providers.statuses.present') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        @if($tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now()))
                                            <x-status-badge status="active">{{ __('providers.statuses.active') }}</x-status-badge>
                                        @else
                                            <x-status-badge status="inactive">{{ __('providers.statuses.inactive') }}</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap px-3 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.tariffs.show', $tariff) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('providers.actions.view') }}</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="sm:hidden space-y-3">
                        @foreach($provider->tariffs as $tariff)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ $tariff->name }}</p>
                            <p class="text-xs text-slate-600">{{ $tariff->active_from->format('Y-m-d') }} - {{ $tariff->active_until?->format('Y-m-d') ?? __('providers.statuses.present') }}</p>
                            <p class="text-xs text-slate-600">{{ $tariff->active_from <= now() && (!$tariff->active_until || $tariff->active_until >= now()) ? __('providers.statuses.active') : __('providers.statuses.inactive') }}</p>
                            <div class="mt-2">
                                <a href="{{ route('admin.tariffs.show', $tariff) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('providers.actions.view') }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-slate-500">{{ __('providers.empty.tariffs') }}</p>
                    @endif
                </x-card>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <x-card title="{{ __('providers.headings.quick_actions') }}">
                <div class="space-y-3">
                    @can('update', $provider)
                    <a href="{{ route('admin.providers.edit', $provider) }}" class="block w-full rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        {{ __('providers.actions.edit') }}
                    </a>
                    @endcan
                    
                    @can('create', App\Models\Tariff::class)
                    <a href="{{ route('admin.tariffs.create', ['provider_id' => $provider->id]) }}" class="block w-full rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('providers.actions.add_tariff') }}
                    </a>
                    @endcan
                    
                    @can('delete', $provider)
                    <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" onsubmit="return confirm('{{ __('providers.confirmations.delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            {{ __('providers.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
