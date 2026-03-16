@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@section('content')
<x-ui.page
    class="px-4 sm:px-6 lg:px-8"
    :title="__('subscriptions.pages.index.title')"
    :description="__('subscriptions.pages.index.description')"
>
    <x-ui.section-card>
        <form method="GET" action="{{ route('superadmin.subscriptions.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('subscriptions.pages.index.filters.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('subscriptions.pages.index.filters.search_placeholder') }}" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('subscriptions.pages.index.filters.status') }}</label>
                <select name="status" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('subscriptions.pages.index.filters.plan_type') }}</label>
                <select name="plan_type" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($planOptions as $plan)
                        <option value="{{ $plan->value }}" @selected(request('plan_type') === $plan->value)>
                            {{ $plan->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('subscriptions.pages.index.filters.expiring_soon') }}</label>
                <select name="expiring_soon" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1" @selected(request('expiring_soon') === '1')>
                        {{ __('subscriptions.pages.index.filters.within_days', ['count' => 14]) }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-end xl:col-span-5 xl:justify-end">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                    {{ __('common.filter') }}
                </button>

                @if(request()->filled('search') || request()->filled('status') || request()->filled('plan_type') || request()->filled('expiring_soon'))
                    <a href="{{ route('superadmin.subscriptions.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                        {{ __('common.clear') }}
                    </a>
                @endif
            </div>
        </form>
    </x-ui.section-card>

    <x-card>
        <div class="hidden md:block">
            <x-data-table :caption="__('subscriptions.pages.index.title')">
                <x-slot name="header">
                    <tr>
                        <th scope="col">{{ __('subscriptions.labels.organization') }}</th>
                        <th scope="col">{{ __('subscriptions.labels.plan_type') }}</th>
                        <th scope="col">{{ __('subscriptions.labels.status') }}</th>
                        <th scope="col">{{ __('subscriptions.pages.index.table.limits') }}</th>
                        <th scope="col">{{ __('subscriptions.labels.expires_at') }}</th>
                        <th scope="col" class="text-right">{{ __('app.nav.actions') }}</th>
                    </tr>
                </x-slot>

                @forelse($subscriptions as $subscription)
                    <tr>
                        <td>
                            <div class="font-medium text-slate-900">{{ $subscription->user->organization_name }}</div>
                            <div class="text-sm text-slate-500">{{ $subscription->user->email }}</div>
                        </td>
                        <td class="font-medium text-slate-900">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</td>
                        <td><x-status-badge :status="$subscription->status" /></td>
                        <td class="text-sm text-slate-600">
                            <div>{{ trans_choice('subscriptions.pages.index.table.properties', $subscription->max_properties, ['count' => $subscription->max_properties]) }}</div>
                            <div>{{ trans_choice('subscriptions.pages.index.table.tenants', $subscription->max_tenants, ['count' => $subscription->max_tenants]) }}</div>
                        </td>
                        <td>
                            <div class="text-sm font-medium text-slate-900">{{ $subscription->expires_at->format('M d, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $subscription->expires_at->diffForHumans() }}</div>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">
                                {{ __('subscriptions.pages.index.table.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-sm text-slate-500">
                            {{ __('shared.dashboard.overview.subscriptions.empty') }}
                        </td>
                    </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse($subscriptions as $subscription)
                <x-ui.list-record
                    :title="$subscription->user->organization_name"
                    :subtitle="$subscription->user->email"
                >
                    <x-slot name="aside">
                        <x-status-badge :status="$subscription->status" />
                    </x-slot>

                    <x-slot name="meta">
                        <x-ui.list-meta :label="__('subscriptions.labels.plan_type')">
                            {{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}
                        </x-ui.list-meta>

                        <x-ui.list-meta :label="__('subscriptions.pages.index.table.limits')">
                            <div>{{ trans_choice('subscriptions.pages.index.table.properties', $subscription->max_properties, ['count' => $subscription->max_properties]) }}</div>
                            <div>{{ trans_choice('subscriptions.pages.index.table.tenants', $subscription->max_tenants, ['count' => $subscription->max_tenants]) }}</div>
                        </x-ui.list-meta>

                        <x-ui.list-meta :label="__('subscriptions.labels.expires_at')">
                            <div class="font-medium text-slate-900">{{ $subscription->expires_at->format('M d, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $subscription->expires_at->diffForHumans() }}</div>
                        </x-ui.list-meta>
                    </x-slot>

                    <x-slot name="actions">
                        <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('subscriptions.pages.index.table.manage') }}
                        </a>
                    </x-slot>
                </x-ui.list-record>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('shared.dashboard.overview.subscriptions.empty') }}
                </div>
            @endforelse
        </div>

        @if($subscriptions->hasPages())
            <div class="mt-6">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </x-card>
</x-ui.page>
@endsection
