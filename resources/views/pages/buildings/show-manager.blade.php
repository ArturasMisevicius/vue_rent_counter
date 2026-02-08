@extends('layouts.app')

@section('title', __('buildings.pages.manager_show.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $building->address }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('buildings.pages.manager_show.description') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-2">
            @can('update', $building)
            <x-button href="{{ route('manager.buildings.edit', $building) }}" variant="secondary">
                {{ __('buildings.pages.manager_show.edit_building') }}
            </x-button>
            @endcan
            @can('delete', $building)
            <form action="{{ route('manager.buildings.destroy', $building) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('buildings.pages.manager_show.delete_confirm') }}');">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">
                    {{ __('buildings.pages.manager_show.delete_building') }}
                </x-button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Building Details -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-1">
        <x-card>
            <x-slot name="title">{{ __('buildings.pages.manager_show.info_title') }}</x-slot>
            
            <dl class="divide-y divide-slate-100">
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.name') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->display_name }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.address') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->address }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.total_apartments') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->total_apartments }}</dd>
                </div>
                <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-slate-900">{{ __('buildings.pages.manager_show.labels.properties_registered') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-700 sm:col-span-2 sm:mt-0">{{ $building->properties->count() }}</dd>
                </div>
            </dl>
        </x-card>
    </div>

    <!-- Properties in Building -->
    <div class="mt-8">
        <x-card>
            <div class="flex items-center justify-between">
                <x-slot name="title">{{ __('buildings.pages.manager_show.properties_title') }}</x-slot>
                @can('create', App\Models\Property::class)
                <x-button href="{{ route('manager.properties.create', ['building_id' => $building->id]) }}" variant="secondary" size="sm">
                    {{ __('buildings.pages.manager_show.add_property') }}
                </x-button>
                @endcan
            </div>
            
            @if($building->properties->isNotEmpty())
            <div class="mt-4">
                <x-data-table>
                    <x-slot name="header">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">{{ __('buildings.pages.manager_show.properties_headers.address') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.type') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.area') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.meters') }}</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('buildings.pages.manager_show.properties_headers.shared') }}</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">{{ __('buildings.pages.manager_show.properties_headers.actions') }}</span>
                            </th>
                        </tr>
                    </x-slot>

                    @foreach($building->properties as $property)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            <span class="capitalize">{{ enum_label($property->type) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ number_format($property->area_sqm, 2) }} m²
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            {{ $property->meters->count() }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                            @if($property->tenants->isNotEmpty())
                                {{ $property->tenants->first()->name }}
                            @else
                                <span class="text-slate-400">{{ __('buildings.pages.manager_show.vacant') }}</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                            <a href="{{ route('manager.properties.show', $property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('buildings.pages.manager_show.view') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            @else
                <p class="mt-4 text-sm text-slate-500">{{ __('buildings.pages.manager_show.empty_properties') }}</p>
            @endif
        </x-card>
    </div>
</div>
@endsection
