@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('superadmin')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('subscriptions.pages.index.title') }}</h1>
        <p class="text-slate-600 mt-2">{{ __('subscriptions.pages.index.description') }}</p>
    </div>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('superadmin.subscriptions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('subscriptions.pages.index.filters.search_placeholder') }}" class="w-full px-3 py-2 border border-slate-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.status') }}</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.plan_type') }}</label>
                <select name="plan_type" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($planOptions as $plan)
                        <option value="{{ $plan->value }}" {{ request('plan_type') === $plan->value ? 'selected' : '' }}>
                            {{ $plan->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.expiring_soon') }}</label>
                <select name="expiring_soon" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1" {{ request('expiring_soon') === '1' ? 'selected' : '' }}>{{ __('subscriptions.pages.index.filters.within_days', ['count' => 14]) }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                    {{ __('common.filter') }}
                </button>
            </div>
        </form>
    </x-card>

    {{-- Subscriptions Table --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.organization') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.plan_type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.pages.index.table.limits') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.expires_at') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-slate-900">{{ $subscription->user->organization_name }}</div>
                            <div class="text-sm text-slate-500">{{ $subscription->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-status-badge :status="$subscription->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <div>{{ trans_choice('subscriptions.pages.index.table.properties', $subscription->max_properties, ['count' => $subscription->max_properties]) }}</div>
                            <div>{{ trans_choice('subscriptions.pages.index.table.tenants', $subscription->max_tenants, ['count' => $subscription->max_tenants]) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900">{{ $subscription->expires_at->format('M d, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $subscription->expires_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-900">{{ __('subscriptions.pages.index.table.manage') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500">
                            {{ __('shared.dashboard.overview.subscriptions.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
        <div class="mt-4">
            {{ $subscriptions->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
@break

@default
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('subscriptions.pages.index.title') }}</h1>
        <p class="text-slate-600 mt-2">{{ __('subscriptions.pages.index.description') }}</p>
    </div>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('superadmin.subscriptions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('subscriptions.pages.index.filters.search_placeholder') }}" class="w-full px-3 py-2 border border-slate-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.status') }}</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.plan_type') }}</label>
                <select name="plan_type" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($planOptions as $plan)
                        <option value="{{ $plan->value }}" {{ request('plan_type') === $plan->value ? 'selected' : '' }}>
                            {{ $plan->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('subscriptions.pages.index.filters.expiring_soon') }}</label>
                <select name="expiring_soon" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1" {{ request('expiring_soon') === '1' ? 'selected' : '' }}>{{ __('subscriptions.pages.index.filters.within_days', ['count' => 14]) }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                    {{ __('common.filter') }}
                </button>
            </div>
        </form>
    </x-card>

    {{-- Subscriptions Table --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.organization') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.plan_type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.pages.index.table.limits') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('subscriptions.labels.expires_at') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-slate-900">{{ $subscription->user->organization_name }}</div>
                            <div class="text-sm text-slate-500">{{ $subscription->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-status-badge :status="$subscription->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <div>{{ trans_choice('subscriptions.pages.index.table.properties', $subscription->max_properties, ['count' => $subscription->max_properties]) }}</div>
                            <div>{{ trans_choice('subscriptions.pages.index.table.tenants', $subscription->max_tenants, ['count' => $subscription->max_tenants]) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900">{{ $subscription->expires_at->format('M d, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $subscription->expires_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-900">{{ __('subscriptions.pages.index.table.manage') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500">
                            {{ __('shared.dashboard.overview.subscriptions.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
        <div class="mt-4">
            {{ $subscriptions->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
@endswitch
