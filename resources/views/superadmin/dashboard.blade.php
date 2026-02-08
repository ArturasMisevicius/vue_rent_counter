@extends('layouts.app')

@section('title', __('superadmin.dashboard.title'))

@section('content')
<x-backoffice.page
    class="container mx-auto px-4 py-8"
    wire:poll.60s
    :title="__('superadmin.dashboard.title')"
    :description="__('superadmin.dashboard.subtitle')"
    :eyebrow="__('superadmin.dashboard.badges.platform')"
>

    {{-- Subscription Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_subscriptions')" 
            :value="$totalSubscriptions" 
            icon="📊"
            href="{{ route('superadmin.subscriptions.index') }}"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.active_subscriptions')" 
            :value="$activeSubscriptions" 
            icon="✅"
            color="green"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::ACTIVE->value]) }}"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.expired_subscriptions')" 
            :value="$expiredSubscriptions" 
            icon="⏰"
            color="red"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::EXPIRED->value]) }}"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.suspended_subscriptions')" 
            :value="$suspendedSubscriptions" 
            icon="⏸️"
            color="yellow"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::SUSPENDED->value]) }}"
        />
    </div>

    {{-- Quick Actions --}}
    <x-card class="mb-8">
        <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.quick_actions.title') }}</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-backoffice.quick-action
                :href="route('superadmin.organizations.create')"
                :title="__('superadmin.dashboard.quick_actions.create_organization')"
                :description="__('superadmin.dashboard.quick_actions.create_organization_desc')"
            >
                <x-slot:icon>➕</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.subscriptions.index')"
                :title="__('superadmin.dashboard.quick_actions.create_subscription')"
                :description="__('superadmin.dashboard.quick_actions.create_subscription_desc')"
            >
                <x-slot:icon>🧾</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                href="#recent-activity"
                :title="__('superadmin.dashboard.quick_actions.view_all_activity')"
                :description="__('superadmin.dashboard.quick_actions.view_all_activity_desc')"
            >
                <x-slot:icon>🕒</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.organizations.index')"
                :title="__('superadmin.dashboard.quick_actions.manage_organizations')"
                :description="__('superadmin.dashboard.quick_actions.manage_organizations_desc')"
            >
                <x-slot:icon>🏢</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.subscriptions.index')"
                :title="__('superadmin.dashboard.quick_actions.manage_subscriptions')"
                :description="__('superadmin.dashboard.quick_actions.manage_subscriptions_desc')"
            >
                <x-slot:icon>📊</x-slot:icon>
            </x-backoffice.quick-action>
        </div>
    </x-card>

    {{-- System Health --}}
    <x-card class="mb-8" id="system-health">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">{{ __('superadmin.dashboard.system_health.title') }}</h2>
                <p class="text-slate-500 text-sm">{{ __('superadmin.dashboard.system_health.description') }}</p>
            </div>
            <form method="POST" action="{{ route('superadmin.dashboard.health-check') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded hover:bg-slate-800 text-sm font-semibold">
                    {{ __('superadmin.dashboard.system_health.actions.run_check') }}
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($systemHealthMetrics as $metric)
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-slate-900">{{ ucfirst($metric->metric_type) }}</div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-white border border-slate-200 text-slate-700">
                            {{ ucfirst($metric->status) }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-2">{{ $metric->checked_at?->diffForHumans() }}</div>
                </div>
            @empty
                <p class="text-slate-500">{{ __('superadmin.dashboard.system_health.empty') }}</p>
            @endforelse
        </div>
    </x-card>

    {{-- Organization Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <a href="{{ route('superadmin.organizations.index') }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <x-card class="h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organizations.title') }}</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('superadmin.dashboard.organizations.total') }}</span>
                        <span class="text-2xl font-bold">{{ $totalOrganizations }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('superadmin.dashboard.organizations.active') }}</span>
                        <span class="text-2xl font-bold text-green-600">{{ $activeOrganizations }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('superadmin.dashboard.organizations.inactive') }}</span>
                        <span class="text-2xl font-bold text-red-600">{{ $totalOrganizations - $activeOrganizations }}</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span>{{ __('superadmin.dashboard.organizations.view_all') }}</span>
                    <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </x-card>
        </a>

        <a href="{{ route('superadmin.subscriptions.index') }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <x-card class="h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.subscription_plans.title') }}</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('superadmin.dashboard.subscription_plans.basic') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['basic'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('superadmin.dashboard.subscription_plans.professional') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['professional'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('superadmin.dashboard.subscription_plans.enterprise') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['enterprise'] ?? 0 }}</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span>{{ __('superadmin.dashboard.subscription_plans.view_all') }}</span>
                    <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </x-card>
        </a>
    </div>

    {{-- System-wide Usage Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_properties')" 
            :value="$totalProperties" 
            icon="🏢"
            href="{{ route('superadmin.properties.index') }}"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_buildings')" 
            :value="$totalBuildings" 
            icon="🏗️"
            href="{{ route('superadmin.buildings.index') }}"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_tenants')" 
            :value="$totalTenants" 
            icon="👥"
            href="{{ route('superadmin.managers.index') }}"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_invoices')" 
            :value="$totalInvoices" 
            icon="📄"
            href="{{ route('invoices.index') }}"
        />
    </div>

    {{-- Expiring Subscriptions Alert --}}
    @if($expiringSubscriptions->count() > 0)
    <x-card class="mb-8 border-yellow-300 bg-yellow-50">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <span class="text-3xl">⚠️</span>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-lg font-semibold text-yellow-800">{{ __('superadmin.dashboard.expiring_subscriptions.title') }}</h3>
                <p class="text-yellow-700 mt-1">{{ __('superadmin.dashboard.expiring_subscriptions.alert', ['count' => $expiringSubscriptions->count()]) }}</p>
                <div class="mt-4 space-y-2">
                    @foreach($expiringSubscriptions as $subscription)
                    <div class="flex justify-between items-center bg-white p-3 rounded">
                        <div>
                            <span class="font-medium">{{ $subscription->user->organization_name }}</span>
                            <span class="text-sm text-slate-600 ml-2">({{ $subscription->user->email }})</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.expiring_subscriptions.expires') }}</span>
                            <span class="font-medium text-yellow-700">{{ $subscription->expires_at->format('M d, Y') }}</span>
                            <span class="text-sm text-slate-600 ml-2">
                                ({{ trans_choice('superadmin.dashboard.expiring_subscriptions.days', now()->startOfDay()->diffInDays($subscription->expires_at->startOfDay()), ['count' => now()->startOfDay()->diffInDays($subscription->expires_at->startOfDay())]) }})
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-card>
    @endif

    {{-- Top Organizations & Activity --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organizations.top_by_properties') }}</h2>
            <div class="space-y-3">
                @forelse($topOrganizations as $org)
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium">{{ $org->name }}</div>
                        <div class="text-sm text-slate-600">{{ $org->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ $org->properties_count }}</div>
                        <div class="text-xs text-slate-600">{{ __('superadmin.dashboard.organizations.properties_count') }}</div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-center py-4">{{ __('superadmin.dashboard.organizations.no_organizations') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card id="recent-activity">
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.recent_activity.title') }}</h2>
            <div class="space-y-3">
                @forelse($recentActivity as $activity)
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium">{{ $activity->organization?->name ?? __('superadmin.dashboard.recent_activity.system') }}</div>
                        <div class="text-sm text-slate-600">{{ $activity->action }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-600">{{ __('superadmin.dashboard.recent_activity.occurred') }}</div>
                        <div class="text-sm font-medium">{{ $activity->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-center py-4">{{ __('superadmin.dashboard.recent_activity.no_activity') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Overview tables mapped to widget logic --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card id="subscriptions-overview">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('superadmin.dashboard.overview.subscriptions.title') }}</h2>
                    <p class="text-slate-500 text-sm">{{ __('superadmin.dashboard.overview.subscriptions.description') }}</p>
                </div>
                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('superadmin.dashboard.overview.subscriptions.open') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.subscriptions.headers.organization') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.subscriptions.headers.plan') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.subscriptions.headers.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.subscriptions.headers.expires') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.subscriptions.headers.manage') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($subscriptionList as $subscription)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $subscription->user->organization_name }}</div>
                                    <div class="text-sm text-slate-500">{{ $subscription->user->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$subscription->status" /></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div>{{ $subscription->expires_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-slate-500">{{ $subscription->expires_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.subscriptions.headers.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500">{{ __('superadmin.dashboard.overview.subscriptions.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card id="organizations-overview">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('superadmin.dashboard.overview.organizations.title') }}</h2>
                    <p class="text-slate-500 text-sm">{{ __('superadmin.dashboard.overview.organizations.description') }}</p>
                </div>
                <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('superadmin.dashboard.overview.organizations.open') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.organizations.headers.organization') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.organizations.headers.subscription') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.organizations.headers.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.organizations.headers.created') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('superadmin.dashboard.overview.organizations.headers.manage') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($organizationList as $organization)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $organization->name }}</div>
                                    <div class="text-sm text-slate-500">{{ $organization->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if($organization->plan)
                                        {{ enum_label($organization->plan, \App\Enums\SubscriptionPlan::class) }}
                                    @else
                                        <span class="text-slate-400">{{ __('superadmin.dashboard.overview.organizations.no_subscription') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($organization->is_active)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('superadmin.dashboard.overview.organizations.status_active') }}</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('superadmin.dashboard.overview.organizations.status_inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $organization->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a href="{{ route('superadmin.organizations.show', $organization) }}" class="text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.organizations.headers.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500">{{ __('superadmin.dashboard.overview.organizations.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Resource drill-downs for each widget --}}
    <x-card id="resources-overview" class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">{{ __('superadmin.dashboard.overview.resources.title') }}</h2>
                <p class="text-slate-500 text-sm">{{ __('superadmin.dashboard.overview.resources.description') }}</p>
            </div>
            <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('superadmin.dashboard.overview.resources.manage_orgs') }}</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div id="resource-properties" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('superadmin.dashboard.overview.resources.properties.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.resources.properties.open_owners') }}</a>
                </div>
                @forelse($latestProperties as $property)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $property->address }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.properties.building') }}: {{ $property->building?->display_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.properties.organization') }}: {{ ($organizationLookup[$property->tenant_id] ?? null)?->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$property->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$property->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.organizations.headers.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('superadmin.dashboard.overview.resources.properties.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-buildings" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('superadmin.dashboard.overview.resources.buildings.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.resources.buildings.open_owners') }}</a>
                </div>
                @forelse($latestBuildings as $building)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $building->display_name }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.buildings.address') }}: {{ $building->address }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.buildings.organization') }}: {{ ($organizationLookup[$building->tenant_id] ?? null)?->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$building->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$building->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.organizations.headers.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('superadmin.dashboard.overview.resources.buildings.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-tenants" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('superadmin.dashboard.overview.resources.tenants.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.resources.tenants.open_owners') }}</a>
                </div>
                @forelse($latestTenants as $tenant)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $tenant->name }}</div>
                            <div class="text-xs text-slate-500">{{ $tenant->email }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.tenants.property') }}: {{ $tenant->property?->address ?? __('superadmin.dashboard.overview.resources.tenants.not_assigned') }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.tenants.organization') }}: {{ ($organizationLookup[$tenant->tenant_id] ?? null)?->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tenant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $tenant->is_active ? __('superadmin.dashboard.overview.resources.tenants.status_active') : __('superadmin.dashboard.overview.resources.tenants.status_inactive') }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('superadmin.dashboard.overview.resources.tenants.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-invoices" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('superadmin.dashboard.overview.resources.invoices.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.resources.invoices.open_owners') }}</a>
                </div>
                @forelse($latestInvoices as $invoice)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $invoice->tenant?->name ?? __('tenants.labels.name') }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.invoices.amount') }}: {{ number_format($invoice->total_amount, 2) }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.invoices.status') }}: {{ enum_label($invoice->status, \App\Enums\InvoiceStatus::class) }}</div>
                            <div class="text-xs text-slate-500">{{ __('superadmin.dashboard.overview.resources.invoices.organization') }}: {{ ($organizationLookup[$invoice->tenant_id] ?? null)?->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$invoice->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$invoice->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('superadmin.dashboard.overview.resources.invoices.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('superadmin.dashboard.overview.resources.invoices.empty') }}</p>
                @endforelse
            </div>
        </div>
    </x-card>

    {{-- Analytics --}}
    <x-card class="mb-8" id="analytics">
        <h2 class="text-xl font-semibold mb-2">{{ __('superadmin.dashboard.analytics.title') }}</h2>
        <p class="text-slate-500">{{ __('superadmin.dashboard.analytics.empty') }}</p>
    </x-card>

</x-backoffice.page>
@endsection
