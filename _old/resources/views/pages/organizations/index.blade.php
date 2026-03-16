@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@section('content')
<x-ui.page class="px-4 sm:px-6 lg:px-8" :title="__('organizations.pages.index.title')">
    <x-slot name="actions">
        <a href="{{ route('superadmin.organizations.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ __('organizations.pages.index.actions.create') }}
        </a>
    </x-slot>

    <x-card>
        <div class="hidden md:block">
            <x-data-table :caption="__('organizations.pages.index.title')">
                <x-slot name="header">
                    <tr>
                        <th scope="col">{{ __('organizations.labels.name') }}</th>
                        <th scope="col">{{ __('organizations.labels.email') }}</th>
                        <th scope="col">{{ __('organizations.labels.plan') }}</th>
                        <th scope="col">{{ __('organizations.labels.is_active') }}</th>
                        <th scope="col" class="text-right">{{ __('app.nav.actions') }}</th>
                    </tr>
                </x-slot>

                @forelse($organizations as $organization)
                    <tr>
                        <td class="font-medium text-slate-900">
                            <a href="{{ route('superadmin.organizations.show', $organization) }}" class="text-indigo-600 transition hover:text-indigo-800">
                                {{ $organization->name }}
                            </a>
                        </td>
                        <td>{{ $organization->email }}</td>
                        <td>{{ enum_label($organization->plan, \App\Enums\SubscriptionPlan::class) }}</td>
                        <td>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $organization->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $organization->is_active ? __('common.status.active') : __('common.status.inactive') }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('superadmin.organizations.show', $organization) }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">
                                {{ __('app.cta.view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-sm text-slate-500">{{ __('organizations.pages.index.empty') }}</td>
                    </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse($organizations as $organization)
                <x-ui.list-record :title="$organization->name" :subtitle="$organization->email">
                    <x-slot name="aside">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $organization->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $organization->is_active ? __('common.status.active') : __('common.status.inactive') }}
                        </span>
                    </x-slot>

                    <x-slot name="meta">
                        <x-ui.list-meta :label="__('organizations.labels.plan')">
                            {{ enum_label($organization->plan, \App\Enums\SubscriptionPlan::class) }}
                        </x-ui.list-meta>
                    </x-slot>

                    <x-slot name="actions">
                        <a href="{{ route('superadmin.organizations.show', $organization) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('app.cta.view') }}
                        </a>
                    </x-slot>
                </x-ui.list-record>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('organizations.pages.index.empty') }}
                </div>
            @endforelse
        </div>

        @if($organizations->hasPages())
            <div class="mt-6">
                {{ $organizations->links() }}
            </div>
        @endif
    </x-card>
</x-ui.page>
@endsection
