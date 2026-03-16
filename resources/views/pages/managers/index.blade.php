@extends('layouts.app')

@section('content')
<x-ui.page
    class="px-4 sm:px-6 lg:px-8"
    :title="__('shared.pages.index.title')"
    :description="__('shared.pages.index.subtitle')"
>
    <x-card>
        <div class="hidden md:block">
            <x-data-table :caption="__('shared.pages.index.title')">
                <x-slot name="header">
                    <tr>
                        <th scope="col">{{ __('shared.fields.id') }}</th>
                        <th scope="col">{{ __('shared.fields.name') }}</th>
                        <th scope="col">{{ __('shared.fields.email') }}</th>
                        <th scope="col">{{ __('shared.fields.properties') }}</th>
                        <th scope="col">{{ __('shared.fields.buildings') }}</th>
                        <th scope="col">{{ __('shared.fields.invoices') }}</th>
                        <th scope="col" class="text-right">{{ __('shared.fields.actions') }}</th>
                    </tr>
                </x-slot>

                @forelse($managers as $manager)
                    <tr>
                        <td class="font-medium text-slate-500">{{ $manager->id }}</td>
                        <td class="font-medium text-slate-900">
                            <a href="{{ route('superadmin.managers.show', $manager) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                {{ $manager->name }}
                            </a>
                        </td>
                        <td>{{ $manager->email }}</td>
                        <td>{{ $manager->properties_count }}</td>
                        <td>{{ $manager->buildings_count }}</td>
                        <td>{{ $manager->invoices_count }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.managers.show', $manager) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                    {{ __('common.view') }}
                                </a>
                                <a href="{{ route('superadmin.compat.users.edit', $manager) }}" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                    {{ __('common.edit') }}
                                </a>
                                <form action="{{ route('superadmin.compat.users.destroy', $manager) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
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
                        <td colspan="7" class="text-center text-sm text-slate-500">{{ __('shared.empty') }}</td>
                    </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse($managers as $manager)
                <x-ui.list-record :title="$manager->name" :subtitle="$manager->email">
                    <x-slot name="meta">
                        <x-ui.list-meta :label="__('shared.fields.id')">{{ $manager->id }}</x-ui.list-meta>
                        <x-ui.list-meta :label="__('shared.fields.properties')">{{ $manager->properties_count }}</x-ui.list-meta>
                        <x-ui.list-meta :label="__('shared.fields.buildings')">{{ $manager->buildings_count }}</x-ui.list-meta>
                        <x-ui.list-meta :label="__('shared.fields.invoices')">{{ $manager->invoices_count }}</x-ui.list-meta>
                    </x-slot>

                    <x-slot name="actions">
                        <a href="{{ route('superadmin.managers.show', $manager) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                            {{ __('common.view') }}
                        </a>
                        <a href="{{ route('superadmin.compat.users.edit', $manager) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('common.edit') }}
                        </a>
                        <form action="{{ route('superadmin.compat.users.destroy', $manager) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');" class="w-full">
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
                    {{ __('shared.empty') }}
                </div>
            @endforelse
        </div>

        @if($managers->hasPages())
            <div class="mt-6">
                {{ $managers->links() }}
            </div>
        @endif
    </x-card>
</x-ui.page>
@endsection
