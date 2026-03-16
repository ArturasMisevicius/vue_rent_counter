@extends('layouts.app')

@section('content')
<x-ui.page class="px-4 sm:px-6 lg:px-8" :title="__('tariffs.index.title')">
    @can('create', App\Models\Tariff::class)
        <x-slot name="actions">
            <a href="{{ route('admin.tariffs.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('tariffs.index.create_button') }}
            </a>
        </x-slot>
    @endcan

    @if($tariffs->count() > 0)
        <x-card>
            <div class="hidden md:block">
                <x-data-table :caption="__('tariffs.index.title')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">
                                <a href="{{ route('admin.tariffs.index', ['sort' => 'name', 'direction' => request('sort') === 'name' && request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 transition hover:text-slate-700">
                                    {{ __('tariffs.index.table.name') }}
                                    @if(request('sort') === 'name')
                                        <span aria-hidden="true">{{ request('direction') === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">{{ __('tariffs.index.table.provider') }}</th>
                            <th scope="col">{{ __('tariffs.index.table.type') }}</th>
                            <th scope="col">
                                <a href="{{ route('admin.tariffs.index', ['sort' => 'active_from', 'direction' => request('sort') === 'active_from' && request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 transition hover:text-slate-700">
                                    {{ __('tariffs.index.table.active_period') }}
                                    @if(request('sort') === 'active_from')
                                        <span aria-hidden="true">{{ request('direction') === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="text-right">{{ __('tariffs.index.table.actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($tariffs as $tariff)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $tariff->name }}</td>
                            <td>{{ $tariff->provider->name }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tariff->configuration['type'] === 'flat' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700' }}">
                                    {{ __('tariffs.types.' . $tariff->configuration['type']) }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-600">
                                {{ $tariff->active_from->format('Y-m-d') }}
                                @if($tariff->active_until)
                                    → {{ $tariff->active_until->format('Y-m-d') }}
                                @else
                                    → {{ __('tariffs.index.table.ongoing') }}
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.tariffs.show', $tariff) }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">
                                        {{ __('tariffs.index.table.view') }}
                                    </a>
                                    @can('update', $tariff)
                                        <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="text-sm font-semibold text-slate-700 transition hover:text-slate-900">
                                            {{ __('tariffs.index.table.edit') }}
                                        </a>
                                    @endcan
                                    @can('delete', $tariff)
                                        <form action="{{ route('admin.tariffs.destroy', $tariff) }}" method="POST" onsubmit="return confirm('{{ __('tariffs.index.table.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-700 transition hover:text-rose-900">
                                                {{ __('tariffs.index.table.delete') }}
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @foreach($tariffs as $tariff)
                    <x-ui.list-record :title="$tariff->name" :subtitle="$tariff->provider->name">
                        <x-slot name="aside">
                            @if($tariff->is_currently_active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    {{ __('tariffs.index.table.ongoing') }}
                                </span>
                            @endif
                        </x-slot>

                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('tariffs.index.table.type')">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $tariff->configuration['type'] === 'flat' ? 'bg-sky-100 text-sky-700' : 'bg-violet-100 text-violet-700' }}">
                                    {{ __('tariffs.types.' . $tariff->configuration['type']) }}
                                </span>
                            </x-ui.list-meta>

                            <x-ui.list-meta :label="__('tariffs.index.table.active_period')">
                                {{ $tariff->active_from->format('Y-m-d') }}
                                @if($tariff->active_until)
                                    → {{ $tariff->active_until->format('Y-m-d') }}
                                @else
                                    → {{ __('tariffs.index.table.ongoing') }}
                                @endif
                            </x-ui.list-meta>
                        </x-slot>

                        <x-slot name="actions">
                            <a href="{{ route('admin.tariffs.show', $tariff) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                                {{ __('tariffs.index.table.view') }}
                            </a>
                            @can('update', $tariff)
                                <a href="{{ route('admin.tariffs.edit', $tariff) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ __('tariffs.index.table.edit') }}
                                </a>
                            @endcan
                            @can('delete', $tariff)
                                <form action="{{ route('admin.tariffs.destroy', $tariff) }}" method="POST" onsubmit="return confirm('{{ __('tariffs.index.table.delete_confirm') }}')" class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2">
                                        {{ __('tariffs.index.table.delete') }}
                                    </button>
                                </form>
                            @endcan
                        </x-slot>
                    </x-ui.list-record>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $tariffs->links() }}
            </div>
        </x-card>
    @else
        <x-ui.section-card class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('tariffs.index.empty.title') }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ __('tariffs.index.empty.description') }}</p>

            @can('create', App\Models\Tariff::class)
                <div class="mt-6">
                    <a href="{{ route('admin.tariffs.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('tariffs.index.empty.create_button') }}
                    </a>
                </div>
            @endcan
        </x-ui.section-card>
    @endif
</x-ui.page>
@endsection
