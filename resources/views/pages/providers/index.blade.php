@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('admin')
@section('title', __('providers.headings.index'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('providers.headings.index') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('providers.descriptions.index') }}</p>
        </div>
        @can('create', App\Models\Provider::class)
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('admin.providers.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('providers.actions.add') }}
            </a>
        </div>
        @endcan
    </div>

    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table caption="{{ __('providers.headings.index') }}">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">{{ __('providers.tables.name') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('providers.tables.service_type') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('providers.tables.tariffs') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('providers.tables.contact_info') }}</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ __('providers.tables.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($providers as $provider)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ $provider->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <x-status-badge :status="$provider->service_type->value">
                            {{ enum_label($provider->service_type) }}
                        </x-status-badge>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ trans_choice('providers.counts.tariffs', $provider->tariffs_count, ['count' => $provider->tariffs_count]) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($provider->contact_info)
                            {{ is_array($provider->contact_info) ? Str::limit(json_encode($provider->contact_info), 30) : Str::limit($provider->contact_info, 30) }}
                        @else
                            {{ __('providers.statuses.not_available') }}
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.providers.show', $provider) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('providers.actions.view') }}
                        </a>
                        @can('update', $provider)
                        <a href="{{ route('admin.providers.edit', $provider) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('providers.actions.edit') }}
                        </a>
                        @endcan
                        @can('delete', $provider)
                        <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('providers.confirmations.delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                {{ __('providers.actions.delete') }}
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('providers.empty.providers') }}
                    </td>
                </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($providers as $provider)
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $provider->name }}</p>
                        <p class="text-xs text-slate-600">{{ enum_label($provider->service_type) }}</p>
                        <p class="text-xs text-slate-600 mt-1">
                            {{ trans_choice('providers.counts.tariffs', $provider->tariffs_count, ['count' => $provider->tariffs_count]) }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p>{{ __('providers.tables.contact_info') }}:</p>
                        <p class="font-semibold text-slate-900">{{ $provider->contact_info ? (is_array($provider->contact_info) ? Str::limit(json_encode($provider->contact_info), 30) : Str::limit($provider->contact_info, 30)) : __('providers.statuses.not_available') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('admin.providers.show', $provider) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('providers.actions.view') }}
                    </a>
                    @can('update', $provider)
                    <a href="{{ route('admin.providers.edit', $provider) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('providers.actions.edit') }}
                    </a>
                    @endcan
                    @can('delete', $provider)
                    <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" class="inline w-full" onsubmit="return confirm('{{ __('providers.confirmations.delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm transition hover:border-rose-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                            {{ __('providers.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                {{ __('providers.empty.providers') }}
            </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $providers->links() }}
    </div>
</div>
@endsection
@break

@default
@section('title', __('providers.headings.index'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('providers.headings.index') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('providers.descriptions.index') }}</p>
        </div>
        @can('create', App\Models\Provider::class)
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('admin.providers.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('providers.actions.add') }}
            </a>
        </div>
        @endcan
    </div>

    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table caption="{{ __('providers.headings.index') }}">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">{{ __('providers.tables.name') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('providers.tables.service_type') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('providers.tables.tariffs') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('providers.tables.contact_info') }}</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ __('providers.tables.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($providers as $provider)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ $provider->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <x-status-badge :status="$provider->service_type->value">
                            {{ enum_label($provider->service_type) }}
                        </x-status-badge>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ trans_choice('providers.counts.tariffs', $provider->tariffs_count, ['count' => $provider->tariffs_count]) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($provider->contact_info)
                            {{ is_array($provider->contact_info) ? Str::limit(json_encode($provider->contact_info), 30) : Str::limit($provider->contact_info, 30) }}
                        @else
                            {{ __('providers.statuses.not_available') }}
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.providers.show', $provider) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('providers.actions.view') }}
                        </a>
                        @can('update', $provider)
                        <a href="{{ route('admin.providers.edit', $provider) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('providers.actions.edit') }}
                        </a>
                        @endcan
                        @can('delete', $provider)
                        <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('providers.confirmations.delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                {{ __('providers.actions.delete') }}
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('providers.empty.providers') }}
                    </td>
                </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($providers as $provider)
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $provider->name }}</p>
                        <p class="text-xs text-slate-600">{{ enum_label($provider->service_type) }}</p>
                        <p class="text-xs text-slate-600 mt-1">
                            {{ trans_choice('providers.counts.tariffs', $provider->tariffs_count, ['count' => $provider->tariffs_count]) }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p>{{ __('providers.tables.contact_info') }}:</p>
                        <p class="font-semibold text-slate-900">{{ $provider->contact_info ? (is_array($provider->contact_info) ? Str::limit(json_encode($provider->contact_info), 30) : Str::limit($provider->contact_info, 30)) : __('providers.statuses.not_available') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('admin.providers.show', $provider) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('providers.actions.view') }}
                    </a>
                    @can('update', $provider)
                    <a href="{{ route('admin.providers.edit', $provider) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('providers.actions.edit') }}
                    </a>
                    @endcan
                    @can('delete', $provider)
                    <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" class="inline w-full" onsubmit="return confirm('{{ __('providers.confirmations.delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm transition hover:border-rose-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                            {{ __('providers.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                {{ __('providers.empty.providers') }}
            </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $providers->links() }}
    </div>
</div>
@endsection
@endswitch
