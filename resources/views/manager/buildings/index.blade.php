@extends('layouts.app')

@section('title', __('buildings.pages.manager_index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('buildings.pages.manager_index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('buildings.pages.manager_index.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @can('create', App\Models\Building::class)
            <x-button href="{{ route('manager.buildings.create') }}">
                {{ __('buildings.pages.manager_index.add') }}
            </x-button>
            @endcan
        </div>
    </div>

    <x-card class="mt-8">
        <div class="hidden sm:block">
        <x-data-table :caption="__('buildings.pages.manager_index.table_caption')">
            <x-slot name="header">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('buildings.pages.manager_index.headers.building') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_index.headers.total_apartments') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_index.headers.properties') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only">{{ __('buildings.pages.manager_index.headers.actions') }}</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($buildings as $building)
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                    <a href="{{ route('manager.buildings.show', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                        <span class="block font-semibold text-slate-900">{{ $building->display_name }}</span>
                        <span class="block text-xs font-normal text-slate-600">{{ $building->address }}</span>
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    {{ $building->total_apartments }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    {{ $building->properties_count }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        @can('view', $building)
                        <a href="{{ route('manager.buildings.show', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('buildings.pages.manager_index.mobile.view') }}
                        </a>
                        @endcan
                        @can('update', $building)
                        <a href="{{ route('manager.buildings.edit', $building) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('buildings.pages.manager_index.mobile.edit') }}
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-3 py-8 text-center text-sm text-slate-500">
                    {{ __('buildings.pages.manager_index.empty') }} 
                    @can('create', App\Models\Building::class)
                        <a href="{{ route('manager.buildings.create') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('buildings.pages.manager_index.create_now') }}</a>
                    @endcan
                </td>
            </tr>
            @endforelse
        </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($buildings as $building)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $building->display_name }}</p>
                            <p class="text-xs text-slate-600">{{ $building->address }}</p>
                            <p class="text-xs text-slate-600">{{ __('buildings.pages.manager_index.mobile.apartments') }} {{ $building->total_apartments }}</p>
                            <p class="text-xs text-slate-600">{{ __('buildings.pages.manager_index.mobile.properties') }} {{ $building->properties_count }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('view', $building)
                        <a href="{{ route('manager.buildings.show', $building) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('buildings.pages.manager_index.mobile.view') }}
                        </a>
                        @endcan
                        @can('update', $building)
                        <a href="{{ route('manager.buildings.edit', $building) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('buildings.pages.manager_index.mobile.edit') }}
                        </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('buildings.pages.manager_index.empty') }}
                    @can('create', App\Models\Building::class)
                        <a href="{{ route('manager.buildings.create') }}" class="text-indigo-700 font-semibold">{{ __('buildings.pages.manager_index.create_now') }}</a>
                    @endcan
                </div>
            @endforelse
        </div>

        @if($buildings->hasPages())
        <div class="mt-4">
            {{ $buildings->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
