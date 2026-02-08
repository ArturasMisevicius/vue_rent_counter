@php
    $role = auth()->user()?->role?->value;
@endphp

@extends($role === 'tenant' ? 'layouts.tenant' : 'layouts.app')

@switch($role)
@case('superadmin')

@section('title', __('shared.dashboard.title'))

@section('content')
<x-backoffice.page
    class="container mx-auto px-4 py-8"
    wire:poll.60s
    :title="__('shared.dashboard.title')"
    :description="__('shared.dashboard.subtitle')"
    :eyebrow="__('shared.dashboard.badges.platform')"
>

    {{-- Subscription Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_subscriptions')" 
            :value="$totalSubscriptions" 
            icon="📊"
            href="{{ route('superadmin.subscriptions.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.active_subscriptions')" 
            :value="$activeSubscriptions" 
            icon="✅"
            color="green"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::ACTIVE->value]) }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.expired_subscriptions')" 
            :value="$expiredSubscriptions" 
            icon="⏰"
            color="red"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::EXPIRED->value]) }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.suspended_subscriptions')" 
            :value="$suspendedSubscriptions" 
            icon="⏸️"
            color="yellow"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::SUSPENDED->value]) }}"
        />
    </div>

    {{-- Quick Actions --}}
    <x-card class="mb-8">
        <h2 class="text-xl font-semibold">{{ __('shared.dashboard.quick_actions.title') }}</h2>
        <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.quick_actions.description') }}</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-backoffice.quick-action
                :href="route('superadmin.organizations.create')"
                :title="__('shared.dashboard.quick_actions.create_organization')"
                :description="__('shared.dashboard.quick_actions.create_organization_desc')"
            >
                <x-slot:icon>➕</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.subscriptions.index')"
                :title="__('shared.dashboard.quick_actions.create_subscription')"
                :description="__('shared.dashboard.quick_actions.create_subscription_desc')"
            >
                <x-slot:icon>🧾</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                href="#recent-activity"
                :title="__('shared.dashboard.quick_actions.view_all_activity')"
                :description="__('shared.dashboard.quick_actions.view_all_activity_desc')"
            >
                <x-slot:icon>🕒</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.organizations.index')"
                :title="__('shared.dashboard.quick_actions.manage_organizations')"
                :description="__('shared.dashboard.quick_actions.manage_organizations_desc')"
            >
                <x-slot:icon>🏢</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.subscriptions.index')"
                :title="__('shared.dashboard.quick_actions.manage_subscriptions')"
                :description="__('shared.dashboard.quick_actions.manage_subscriptions_desc')"
            >
                <x-slot:icon>📊</x-slot:icon>
            </x-backoffice.quick-action>
        </div>
    </x-card>

    {{-- System Health --}}
    <x-card class="mb-8" id="system-health">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.system_health.title') }}</h2>
                <p class="text-slate-500 text-sm">{{ __('shared.dashboard.system_health.description') }}</p>
            </div>
            <form method="POST" action="{{ route('superadmin.dashboard.health-check') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded hover:bg-slate-800 text-sm font-semibold">
                    {{ __('shared.dashboard.system_health.actions.run_check') }}
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
                <p class="text-slate-500">{{ __('shared.dashboard.system_health.empty') }}</p>
            @endforelse
        </div>
    </x-card>

    {{-- Organization Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <a href="{{ route('superadmin.organizations.index') }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <x-card class="h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.organizations.title') }}</h2>
                <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.organizations.description') }}</p>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.organizations.total') }}</span>
                        <span class="text-2xl font-bold">{{ $totalOrganizations }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.organizations.active') }}</span>
                        <span class="text-2xl font-bold text-green-600">{{ $activeOrganizations }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.organizations.inactive') }}</span>
                        <span class="text-2xl font-bold text-red-600">{{ $totalOrganizations - $activeOrganizations }}</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span>{{ __('shared.dashboard.organizations.view_all') }}</span>
                    <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </x-card>
        </a>

        <a href="{{ route('superadmin.subscriptions.index') }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <x-card class="h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.subscription_plans.title') }}</h2>
                <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.subscription_plans.description') }}</p>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.subscription_plans.basic') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['basic'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.subscription_plans.professional') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['professional'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.subscription_plans.enterprise') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['enterprise'] ?? 0 }}</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span>{{ __('shared.dashboard.subscription_plans.view_all') }}</span>
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
            :title="__('shared.dashboard.stats.total_properties')" 
            :value="$totalProperties" 
            icon="🏢"
            href="{{ route('superadmin.properties.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_buildings')" 
            :value="$totalBuildings" 
            icon="🏗️"
            href="{{ route('superadmin.buildings.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_tenants')" 
            :value="$totalTenants" 
            icon="👥"
            href="{{ route('superadmin.managers.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_invoices')" 
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
                <h3 class="text-lg font-semibold text-yellow-800">{{ __('shared.dashboard.expiring_subscriptions.title') }}</h3>
                <p class="text-yellow-700 mt-1">{{ __('shared.dashboard.expiring_subscriptions.alert', ['count' => $expiringSubscriptions->count()]) }}</p>
                <div class="mt-4 space-y-2">
                    @foreach($expiringSubscriptions as $subscription)
                    <div class="flex justify-between items-center bg-white p-3 rounded">
                        <div>
                            <span class="font-medium">{{ $subscription->user->organization_name }}</span>
                            <span class="text-sm text-slate-600 ml-2">({{ $subscription->user->email }})</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-slate-600">{{ __('shared.dashboard.expiring_subscriptions.expires') }}</span>
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
            <h2 class="text-xl font-semibold">{{ __('shared.dashboard.organizations.top_by_properties') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.organizations.top_by_properties_description') }}</p>
            <div class="space-y-3">
                @forelse($topOrganizations as $org)
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium">{{ $org->name }}</div>
                        <div class="text-sm text-slate-600">{{ $org->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ $org->properties_count }}</div>
                        <div class="text-xs text-slate-600">{{ __('shared.dashboard.organizations.properties_count') }}</div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-center py-4">{{ __('shared.dashboard.organizations.no_organizations') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card id="recent-activity">
            <h2 class="text-xl font-semibold">{{ __('shared.dashboard.recent_activity.title') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.recent_activity.description') }}</p>
            <div class="space-y-3">
                @forelse($recentActivity as $activity)
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium">{{ $activity->organization?->name ?? __('shared.dashboard.recent_activity.system') }}</div>
                        <div class="text-sm text-slate-600">{{ $activity->action }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-600">{{ __('shared.dashboard.recent_activity.occurred') }}</div>
                        <div class="text-sm font-medium">{{ $activity->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-center py-4">{{ __('shared.dashboard.recent_activity.no_activity') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Overview tables mapped to widget logic --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card id="subscriptions-overview">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('shared.dashboard.overview.subscriptions.title') }}</h2>
                    <p class="text-slate-500 text-sm">{{ __('shared.dashboard.overview.subscriptions.description') }}</p>
                </div>
                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('shared.dashboard.overview.subscriptions.open') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.organization') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.plan') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.expires') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.manage') }}</th>
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
                                    <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.subscriptions.headers.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500">{{ __('shared.dashboard.overview.subscriptions.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card id="organizations-overview">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('shared.dashboard.overview.organizations.title') }}</h2>
                    <p class="text-slate-500 text-sm">{{ __('shared.dashboard.overview.organizations.description') }}</p>
                </div>
                <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('shared.dashboard.overview.organizations.open') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.organization') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.subscription') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.created') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</th>
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
                                        <span class="text-slate-400">{{ __('shared.dashboard.overview.organizations.no_subscription') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($organization->is_active)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('shared.dashboard.overview.organizations.status_active') }}</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('shared.dashboard.overview.organizations.status_inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $organization->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a href="{{ route('superadmin.organizations.show', $organization) }}" class="text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500">{{ __('shared.dashboard.overview.organizations.empty') }}</td>
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
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.overview.resources.title') }}</h2>
                <p class="text-slate-500 text-sm">{{ __('shared.dashboard.overview.resources.description') }}</p>
            </div>
            <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('shared.dashboard.overview.resources.manage_orgs') }}</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div id="resource-properties" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.properties.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.properties.open_owners') }}</a>
                </div>
                @forelse($latestProperties as $property)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $property->address }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.properties.building') }}: {{ $property->building?->display_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.properties.organization') }}: {{ ($organizationLookup[$property->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$property->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$property->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.properties.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-buildings" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.buildings.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.buildings.open_owners') }}</a>
                </div>
                @forelse($latestBuildings as $building)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $building->display_name }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.buildings.address') }}: {{ $building->address }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.buildings.organization') }}: {{ ($organizationLookup[$building->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$building->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$building->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.buildings.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-tenants" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.tenants.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.tenants.open_owners') }}</a>
                </div>
                @forelse($latestTenants as $tenant)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $tenant->name }}</div>
                            <div class="text-xs text-slate-500">{{ $tenant->email }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.tenants.property') }}: {{ $tenant->property?->address ?? __('shared.dashboard.overview.resources.tenants.not_assigned') }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.tenants.organization') }}: {{ ($organizationLookup[$tenant->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tenant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $tenant->is_active ? __('shared.dashboard.overview.resources.tenants.status_active') : __('shared.dashboard.overview.resources.tenants.status_inactive') }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.tenants.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-invoices" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.invoices.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.invoices.open_owners') }}</a>
                </div>
                @forelse($latestInvoices as $invoice)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $invoice->tenant?->name ?? __('tenants.labels.name') }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.amount') }}: {{ number_format($invoice->total_amount, 2) }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.status') }}: {{ enum_label($invoice->status, \App\Enums\InvoiceStatus::class) }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.organization') }}: {{ ($organizationLookup[$invoice->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$invoice->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$invoice->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.invoices.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.empty') }}</p>
                @endforelse
            </div>
        </div>
    </x-card>

    {{-- Analytics --}}
    <x-card class="mb-8" id="analytics">
        <h2 class="text-xl font-semibold mb-2">{{ __('shared.dashboard.analytics.title') }}</h2>
        <p class="text-sm text-slate-500 mb-2">{{ __('shared.dashboard.analytics.description') }}</p>
        <p class="text-slate-500">{{ __('shared.dashboard.analytics.empty') }}</p>
    </x-card>

</x-backoffice.page>
@endsection
@break

@case('admin')

@section('title', __('dashboard.shared.title'))

@section('content')
<x-backoffice.page
    class="px-4 sm:px-6 lg:px-8"
    :title="auth()->user()->role->value === 'admin'
        ? __('dashboard.shared.org_dashboard', ['name' => auth()->user()->organization_name ?? '—'])
        : __('dashboard.shared.title')"
    :description="auth()->user()->role->value === 'admin'
        ? __('dashboard.shared.portfolio_subtitle')
        : __('dashboard.shared.system_subtitle')"
    :eyebrow="auth()->user()->role->value === 'admin'
        ? __('dashboard.shared.badges.shared')
        : __('dashboard.shared.badges.platform')"
>

    @if(auth()->user()->role->value === 'admin')
        <!-- Subscription Status Banner -->
        @if($subscriptionStatus === 'no_subscription')
            <div class="mt-6 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800">{{ __('dashboard.shared.banner.no_subscription_title') }}</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ __('dashboard.shared.banner.no_subscription_body') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($subscriptionStatus === 'expired' && isset($subscription))
            <div class="mt-6 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800">{{ __('dashboard.shared.banner.expired_title') }}</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ __('dashboard.shared.banner.expired_body', ['date' => $subscription->expires_at->format('M d, Y')]) }}</p>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('admin.profile.show') }}" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                {{ __('dashboard.shared.banner.renew') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($subscriptionStatus === 'expiring_soon' && isset($subscription))
            <div class="mt-6 rounded-md bg-yellow-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-yellow-800">{{ __('dashboard.shared.banner.expiring_title') }}</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>{{ __('dashboard.shared.banner.expiring_body', [
                                'days' => trans_choice('dashboard.admin.banner.days', $daysUntilExpiry, ['count' => $daysUntilExpiry]),
                                'date' => $subscription->expires_at->format('M d, Y'),
                            ]) }}</p>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('admin.profile.show') }}" class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                                {{ __('dashboard.shared.banner.renew_now') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($subscription))
            <!-- Subscription Limits Card -->
            <div class="mt-6">
                <x-card title="{{ __('dashboard.shared.subscription_card.title') }}">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ __('dashboard.shared.subscription_card.plan_type') }}</p>
                                <p class="text-sm text-slate-500">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-slate-900">{{ __('dashboard.shared.subscription_card.expires') }}</p>
                                <p class="text-sm text-slate-500">{{ $subscription->expires_at->format('M d, Y') }}</p>
                            </div>
                        </div>

                    @if(isset($usageStats))
                        <!-- Properties Usage -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-slate-700">{{ __('dashboard.shared.subscription_card.properties') }}</span>
                                <span class="text-sm text-slate-500">{{ $usageStats['properties_used'] }} / {{ $usageStats['properties_max'] }}</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usageStats['properties_percentage'], 100) }}%"></div>
                            </div>
                            @if($usageStats['properties_percentage'] >= 90)
                                <p class="mt-1 text-xs text-yellow-600">{{ __('dashboard.shared.subscription_card.approaching_limit') }}</p>
                            @endif
                        </div>

                        <!-- Tenants Usage -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-slate-700">{{ __('dashboard.shared.subscription_card.tenants') }}</span>
                                <span class="text-sm text-slate-500">{{ $usageStats['tenants_used'] }} / {{ $usageStats['tenants_max'] }}</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usageStats['tenants_percentage'], 100) }}%"></div>
                            </div>
                            @if($usageStats['tenants_percentage'] >= 90)
                                <p class="mt-1 text-xs text-yellow-600">{{ __('dashboard.shared.subscription_card.approaching_limit') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
        @endif
    @endif

    <!-- Primary Stats Grid -->
    @if(auth()->user()->role->value === 'admin')
        <!-- Admin Portfolio Stats -->
        <x-backoffice.stats-section class="mt-8">
            <x-stat-card label="{{ __('dashboard.shared.stats.total_properties') }}" :value="$stats['total_properties']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="{{ __('dashboard.shared.stats.active_tenants') }}" :value="$stats['active_tenants']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="{{ __('dashboard.shared.stats.active_meters') }}" :value="$stats['active_meters']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="{{ __('dashboard.shared.stats.unpaid_invoices') }}" :value="$stats['unpaid_invoices']" :href="route('invoices.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </x-backoffice.stats-section>

        <!-- Pending Tasks -->
        @if(isset($pendingTasks))
        <x-backoffice.stats-section class="mt-6" :title="__('settings.maintenance.title')" :columns="3">
                <x-stat-card label="{{ __('meter_readings.actions.enter_new') }}" :value="$pendingTasks['pending_meter_readings']" :href="route('admin.tenants.index')" />
                <x-stat-card label="{{ __('invoices.actions.finalize') }}" :value="$pendingTasks['draft_invoices']" :href="route('invoices.drafts')" />
                <x-stat-card label="{{ __('app.nav.tenants') }}" :value="$pendingTasks['inactive_tenants']" :href="route('admin.tenants.index')" />
        </x-backoffice.stats-section>
        @endif
    @else
        <!-- System-wide Stats for Superadmin/Manager -->
        <x-backoffice.stats-section class="mt-8">
            <x-stat-card label="{{ __('dashboard.shared.stats.total_users') }}" :value="$stats['total_users']" :href="route('admin.users.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="{{ __('dashboard.shared.stats.total_properties') }}" :value="$stats['total_properties']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.active_meters')" :value="$stats['active_meters']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card label="{{ __('dashboard.shared.stats.total_meter_readings') }}" :value="$stats['total_meter_readings']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </x-backoffice.stats-section>
    @endif

    @if(auth()->user()->role->value === 'admin')
        <!-- Admin Secondary Stats -->
        <x-backoffice.stats-section class="mt-6">
            <x-stat-card :label="__('dashboard.shared.stats.total_buildings')" :value="$stats['total_buildings']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.total_tenants')" :value="$stats['total_tenants']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.draft_invoices')" :value="$stats['draft_invoices']" :href="route('invoices.drafts')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.readings_last_7_days')" :value="$stats['recent_readings_count']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </x-backoffice.stats-section>
    @else
        <!-- System-wide Secondary Stats -->
        <x-backoffice.stats-section class="mt-6">
            <x-stat-card :label="__('dashboard.shared.stats.total_buildings')" :value="$stats['total_buildings']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.utility_providers')" :value="$stats['total_providers']" :href="route('admin.providers.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.active_tariffs')" :value="$stats['active_tariffs']" :href="route('admin.tariffs.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('dashboard.shared.stats.readings_last_7_days')" :value="$stats['recent_readings_count']" :href="route('admin.tenants.index')">
                <x-slot:icon>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </x-backoffice.stats-section>

        <!-- User Role Breakdown -->
        <x-backoffice.stats-section class="mt-8" :title="__('dashboard.shared.breakdown.users_title')" :columns="3">
            <x-stat-card :label="__('dashboard.shared.breakdown.administrators')" :value="$stats['admin_count']" :href="route('admin.users.index', ['role' => 'admin'])" />
            <x-stat-card :label="__('dashboard.shared.breakdown.managers')" :value="$stats['manager_count']" :href="route('admin.users.index', ['role' => 'manager'])" />
            <x-stat-card :label="__('dashboard.shared.breakdown.tenants')" :value="$stats['tenant_count']" :href="route('admin.tenants.index')" />
        </x-backoffice.stats-section>

        <!-- Invoice Status Breakdown -->
        <x-backoffice.stats-section class="mt-8" :title="__('dashboard.shared.breakdown.invoice_title')" :columns="3">
            <x-stat-card :label="__('dashboard.shared.breakdown.draft_invoices')" :value="$stats['draft_invoices']" :href="route('invoices.drafts')" />
            <x-stat-card :label="__('dashboard.shared.breakdown.finalized_invoices')" :value="$stats['finalized_invoices']" :href="route('invoices.finalized')" />
            <x-stat-card :label="__('dashboard.shared.breakdown.paid_invoices')" :value="$stats['paid_invoices']" :href="route('invoices.paid')" />
        </x-backoffice.stats-section>
    @endif

    <!-- Quick Actions -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-slate-900 mb-4">{{ __('dashboard.shared.quick_actions.title') }}</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @if(auth()->user()->role->value === 'admin')
                <x-backoffice.quick-action
                    :href="route('admin.tenants.index')"
                    :title="__('dashboard.shared.quick_actions.manage_tenants_title')"
                    :description="__('dashboard.shared.quick_actions.manage_tenants_desc')"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </x-slot:icon>
                </x-backoffice.quick-action>

                <x-backoffice.quick-action
                    :href="route('admin.profile.show')"
                    :title="__('dashboard.shared.quick_actions.organization_profile_title')"
                    :description="__('dashboard.shared.quick_actions.organization_profile_desc')"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </x-slot:icon>
                </x-backoffice.quick-action>

                <x-backoffice.quick-action
                    :href="route('admin.tenants.create')"
                    :title="__('dashboard.shared.quick_actions.create_tenant_title')"
                    :description="__('dashboard.shared.quick_actions.create_tenant_desc')"
                    variant="dashed"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </x-slot:icon>
                </x-backoffice.quick-action>
            @else
                @can('viewAny', App\Models\User::class)
                    <x-backoffice.quick-action
                        :href="route('admin.users.index')"
                        :title="__('dashboard.shared.quick_actions.manage_users_title')"
                        :description="__('dashboard.shared.quick_actions.manage_users_desc')"
                    >
                        <x-slot:icon>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </x-slot:icon>
                    </x-backoffice.quick-action>
                @endcan

                @can('viewAny', App\Models\Provider::class)
                    <x-backoffice.quick-action
                        :href="route('admin.providers.index')"
                        :title="__('dashboard.shared.quick_actions.manage_providers_title')"
                        :description="__('dashboard.shared.quick_actions.manage_providers_desc')"
                    >
                        <x-slot:icon>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                            </svg>
                        </x-slot:icon>
                    </x-backoffice.quick-action>
                @endcan

                @can('viewAny', App\Models\Tariff::class)
                    <x-backoffice.quick-action
                        :href="route('admin.tariffs.index')"
                        :title="__('dashboard.shared.quick_actions.manage_tariffs_title')"
                        :description="__('dashboard.shared.quick_actions.manage_tariffs_desc')"
                    >
                        <x-slot:icon>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </x-slot:icon>
                    </x-backoffice.quick-action>
                @endcan

                <x-backoffice.quick-action
                    :href="route('admin.audit.index')"
                    :title="__('dashboard.shared.quick_actions.view_audit_log_title')"
                    :description="__('dashboard.shared.quick_actions.view_audit_log_desc')"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </x-slot:icon>
                </x-backoffice.quick-action>

                <x-backoffice.quick-action
                    :href="route('admin.settings.index')"
                    :title="__('dashboard.shared.quick.settings')"
                    :description="__('dashboard.shared.quick.settings_desc')"
                >
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </x-slot:icon>
                </x-backoffice.quick-action>

                @can('create', App\Models\User::class)
                    <x-backoffice.quick-action
                        :href="route('admin.users.create')"
                        :title="__('dashboard.shared.quick.create_user')"
                        :description="__('dashboard.shared.quick.create_user_desc')"
                        variant="dashed"
                    >
                        <x-slot:icon>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </x-slot:icon>
                    </x-backoffice.quick-action>
                @endcan
            @endif
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-slate-900 mb-4">
            {{ __('dashboard.shared.activity.recent_portfolio') }}
        </h2>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @if(auth()->user()->role->value === 'admin')
                <!-- Recent Tenants -->
                <x-card title="{{ __('dashboard.shared.activity.recent_tenants') }}">
                    <div class="flow-root">
                        <ul role="list" class="-my-5 divide-y divide-slate-200">
                            @forelse($recentActivity['recent_tenants'] as $tenant)
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ $tenant->name }}
                                        </p>
                                        <p class="text-sm text-slate-500 truncate">
                                            {{ $tenant->property->address ?? __('tenants.empty.property') }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ $tenant->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div>
                                        @if($tenant->is_active)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ __('tenants.statuses.active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ __('tenants.statuses.inactive') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="py-4 text-sm text-slate-500">{{ __('tenants.empty.assignment_history') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </x-card>
            @else
                <!-- Recent Users -->
                <x-card title="{{ __('dashboard.shared.activity.recent_users') }}">
                    <div class="flow-root">
                        <ul role="list" class="-my-5 divide-y divide-slate-200">
                            @forelse($recentActivity['recent_users'] as $user)
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ $user->name }}
                                        </p>
                                        <p class="text-sm text-slate-500 truncate">
                                            {{ $user->email }}
                                        </p>
                                    </div>
                                    <div>
                                        <x-status-badge :status="$user->role->value">
                                            {{ ucfirst($user->role->value) }}
                                        </x-status-badge>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="py-4 text-sm text-slate-500">{{ __('dashboard.shared.activity.no_users') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </x-card>
            @endif

            <!-- Recent Invoices -->
            <x-card title="{{ __('dashboard.shared.activity.recent_invoices') }}">
                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-slate-200">
                        @forelse($recentActivity['recent_invoices'] as $invoice)
                        <li class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">
                                        {{ __('invoices.labels.number', ['id' => $invoice->id]) }}
                                    </p>
                                    <p class="text-sm text-slate-500 truncate">
                                        @if(auth()->user()->role->value === 'admin')
                                            {{ $invoice->property->address ?? __('providers.statuses.not_available') }} - €{{ number_format($invoice->total_amount, 2) }}
                                        @else
                                            {{ $invoice->tenant->name ?? __('providers.statuses.not_available') }} - €{{ number_format($invoice->total_amount, 2) }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                    </p>
                                </div>
                                <div>
                                    <x-status-badge :status="$invoice->status->value">
                                        {{ enum_label($invoice->status) }}
                                    </x-status-badge>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="py-4 text-sm text-slate-500">{{ __('notifications.invoice.none') }}</li>
                        @endforelse
                    </ul>
                </div>
            </x-card>

            <!-- Recent Meter Readings -->
            <x-card title="{{ __('meter_readings.headings.index') }}">
                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-slate-200">
                        @forelse($recentActivity['recent_readings'] as $reading)
                        <li class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">
                                        {{ $reading->meter->getServiceDisplayName() }}
                                        <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                                    </p>
                                    <p class="text-sm text-slate-500 truncate">
                                        {{ $reading->meter->property->address ?? __('app.common.na') }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ $reading->reading_date->format('M d, Y') }} - {{ number_format($reading->value, 2) }}
                                    </p>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="py-4 text-sm text-slate-500">{{ __('meter_readings.recent_empty') }}</li>
                        @endforelse
                    </ul>
                </div>
            </x-card>
        </div>
    </div>
</x-backoffice.page>
@endsection
@break

@case('manager')

@section('title', __('dashboard.shared.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-manager.page :title="__('dashboard.shared.title')" :description="__('dashboard.shared.description')">
        <x-slot:meta>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 shadow-sm shadow-indigo-500/10">
                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                {{ __('dashboard.shared.pending_section') }}: {{ $stats['meters_pending_reading'] }} · {{ __('dashboard.shared.stats.draft_invoices') }}: {{ $stats['draft_invoices'] }}
            </span>
        </x-slot:meta>

        <x-slot:actions>
            @can('create', App\Models\MeterReading::class)
                <x-button href="{{ route('manager.meter-readings.create') }}" class="bg-white/90 text-indigo-700 shadow-lg shadow-indigo-500/10 hover:bg-white">
                    {{ __('meter_readings.actions.enter_new') }}
                </x-button>
            @endcan
            @can('create', App\Models\Invoice::class)
                <x-button href="{{ route('manager.invoices.create') }}" class="bg-slate-900 text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800">
                    {{ __('invoices.shared.index.generate') }}
                </x-button>
            @endcan
            @can('create', App\Models\Property::class)
                <x-button href="{{ route('manager.properties.create') }}" variant="secondary">
                    {{ __('properties.actions.add') }}
                </x-button>
            @endcan
        </x-slot:actions>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-manager.stat-card :label="__('dashboard.shared.stats.total_properties')" :value="$stats['total_properties']" tone="indigo" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M3 9.75 12 3l9 6.75M4.5 10.5V21h5.25v-4.5A1.5 1.5 0 0 1 11.25 15h1.5A1.5 1.5 0 0 1 14.25 16.5V21H19.5V10.5' />
</svg>
SVG"/>
            <x-manager.stat-card :label="__('dashboard.shared.stats.active_meters')" :value="$stats['active_meters']" tone="slate" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 3v6m0 0 3-3m-3 3-3-3m6 6v6m0 0 3-3m-3 3-3-3M6 5.25h-.75A1.5 1.5 0 0 0 3.75 6.75v10.5a1.5 1.5 0 0 0 1.5 1.5H6M18 5.25h.75a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H18' />
</svg>
SVG"/>
            <x-manager.stat-card :label="__('dashboard.shared.stats.meters_pending')" :value="$stats['meters_pending_reading']" tone="amber" :hint="__('dashboard.shared.hints.operations')" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z' />
</svg>
SVG"/>
            <x-manager.stat-card :label="__('dashboard.shared.stats.draft_invoices')" :value="$stats['draft_invoices']" tone="indigo" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M9 8.25h6m-6 3h3.75M7.5 21h9A2.25 2.25 0 0 0 18.75 18.75V5.25A2.25 2.25 0 0 0 16.5 3h-9A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21z' />
</svg>
SVG"/>
            <x-manager.stat-card :label="__('dashboard.shared.stats.overdue_invoices')" :value="$stats['overdue_invoices']" tone="amber" :hint="__('dashboard.shared.hints.drafts')" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' />
</svg>
SVG"/>
            <x-manager.stat-card :label="__('dashboard.shared.stats.active_tenants')" :value="$stats['active_tenants']" tone="emerald" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.75 19.5a4.5 4.5 0 0 1 10.5 0' />
</svg>
SVG"/>
        </div>

        <x-manager.section-card :title="__('dashboard.shared.sections.operations')" :description="__('dashboard.shared.hints.operations')" class="mt-4">
            @if($propertiesNeedingReadings->isNotEmpty())
                <div class="space-y-3">
                    @foreach($propertiesNeedingReadings as $property)
                        <div class="flex flex-col gap-3 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3 shadow-inner shadow-amber-100/60 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-amber-900">{{ $property->address }}</p>
                                <p class="text-xs text-amber-800">
                                    {{ trans_choice('dashboard.manager.pending_meter_line', $property->meters->count(), ['count' => $property->meters->count()]) }}
                                    @if($property->building)
                                        · {{ $property->building->name ?? $property->building->address }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-100">{{ __('app.nav.meters') }}: {{ $property->meters->count() }}</span>
                                <a href="{{ route('manager.meter-readings.create', ['property_id' => $property->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-500">
                                    {{ __('meter_readings.actions.enter_new') }}
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('dashboard.shared.empty.operations') }}</p>
            @endif
        </x-manager.section-card>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-manager.section-card :title="__('dashboard.shared.sections.drafts')" :description="__('dashboard.shared.hints.drafts')">
                @if($draftInvoices->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($draftInvoices as $invoice)
                            <div class="py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $invoice->tenant->property->address ?? __('app.common.na') }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-600">
                                            {{ __('invoices.labels.amount') }}: €{{ number_format($invoice->total_amount, 2) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <x-status-badge status="draft">{{ enum_label($invoice->status) }}</x-status-badge>
                                        <div class="mt-2 flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                                {{ __('invoices.actions.view') }}
                                            </a>
                                            <a href="{{ route('manager.invoices.edit', $invoice) }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                                                {{ __('invoices.actions.edit') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('manager.invoices.drafts') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                            {{ __('dashboard.shared.sections.drafts') }}
                        </a>
                    </div>
                @else
                    <p class="text-sm text-slate-600">{{ __('dashboard.shared.empty.drafts') }}</p>
                @endif
            </x-manager.section-card>

            <x-manager.section-card :title="__('dashboard.shared.sections.recent')" :description="__('dashboard.shared.hints.recent')">
                @if($stats['recent_invoices']->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($stats['recent_invoices'] as $invoice)
                            <div class="rounded-xl border border-slate-100 px-4 py-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">#{{ $invoice->id }} · {{ $invoice->tenant->property->address ?? __('app.common.na') }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-600">€{{ number_format($invoice->total_amount, 2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <x-status-badge :status="$invoice->status->value">{{ enum_label($invoice->status) }}</x-status-badge>
                                        <a href="{{ route('manager.invoices.show', $invoice) }}" class="mt-2 block text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            {{ __('invoices.actions.view') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-600">{{ __('dashboard.shared.empty.recent') }}</p>
                @endif
            </x-manager.section-card>
        </div>

        <x-manager.section-card :title="__('dashboard.shared.sections.shortcuts')" :description="__('dashboard.shared.hints.shortcuts')">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @can('create', App\Models\MeterReading::class)
                    <a href="{{ route('manager.meter-readings.create') }}" class="group relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white/80 p-3 text-indigo-700 shadow-sm ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25m-4.5-13.5h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('meter_readings.actions.enter_new') }}</p>
                                <p class="text-xs text-slate-600">{{ __('dashboard.shared.quick_actions.enter_reading_desc') }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('create', App\Models\Invoice::class)
                    <a href="{{ route('manager.invoices.create') }}" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 via-white to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white/80 p-3 text-slate-900 shadow-sm ring-1 ring-slate-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('invoices.shared.index.generate') }}</p>
                                <p class="text-xs text-slate-600">{{ __('dashboard.shared.quick_actions.generate_invoice_desc') }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('viewAny', App\Models\Property::class)
                    <a href="{{ route('manager.properties.index') }}" class="group relative overflow-hidden rounded-2xl border border-indigo-50 bg-gradient-to-br from-white via-white to-indigo-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-indigo-50 p-3 text-indigo-700 ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5m-15-3h13.5m-12-3h10.5M4.5 10.5 12 3l7.5 7.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('app.nav.properties') }}</p>
                                <p class="text-xs text-slate-600">{{ __('dashboard.shared.quick_actions.view_buildings_desc') }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('viewAny', App\Models\Building::class)
                    <a href="{{ route('manager.buildings.index') }}" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-white via-white to-slate-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white p-3 text-slate-900 ring-1 ring-slate-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 3.75h15m-13.5 0V21m12-17.25V21M7.5 7.5h3m-3 3h3m-3 3h3m3-6h3m-3 3h3m-3 3h3M9 21v-3a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 18v3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('dashboard.shared.quick_actions.view_buildings') }}</p>
                                <p class="text-xs text-slate-600">{{ __('dashboard.shared.quick_actions.view_buildings_desc') }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('viewAny', App\Models\Meter::class)
                    <a href="{{ route('manager.meters.index') }}" class="group relative overflow-hidden rounded-2xl border border-indigo-50 bg-gradient-to-br from-indigo-50 via-white to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white/80 p-3 text-indigo-700 shadow-sm ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94a1.125 1.125 0 0 1 1.11-.94h2.593a1.125 1.125 0 0 1 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869z' />
                                    <path stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z' />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('dashboard.shared.quick_actions.view_meters') }}</p>
                                <p class="text-xs text-slate-600">{{ __('dashboard.shared.quick_actions.view_meters_desc') }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                <a href="{{ route('manager.reports.index') }}" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-white via-slate-50 to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-white p-3 text-slate-900 ring-1 ring-slate-100">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5 9 13.5l4 4.5 6.75-9M3.75 5.25h16.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ __('dashboard.shared.quick_actions.view_reports') }}</p>
                            <p class="text-xs text-slate-600">{{ __('dashboard.shared.quick_actions.view_reports_desc') }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </x-manager.section-card>
    </x-manager.page>
</div>
@endsection
@break

@case('tenant')

@section('title', __('dashboard.shared.title'))

@section('tenant-content')
<x-tenant.page :title="__('dashboard.shared.title')" :description="__('dashboard.shared.description')">
    @if(!$stats['property'])
        <x-tenant.alert type="warning" :title="__('dashboard.shared.alerts.no_property_title')">
            {{ __('dashboard.shared.alerts.no_property_body') }}
        </x-tenant.alert>
    @else
        <x-tenant.quick-actions />

        <x-tenant.section-card :title="__('dashboard.shared.property.title')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('dashboard.shared.property.address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $stats['property']->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('dashboard.shared.property.type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($stats['property']->type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('dashboard.shared.property.area') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $stats['property']->area_sqm }} m²</dd>
                </div>
                @if($stats['property']->building)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('dashboard.shared.property.building') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $stats['property']->building->display_name }}</dd>
                </div>
                @endif
            </dl>
        </x-tenant.section-card>

        @if($stats['unpaid_balance'] > 0)
        <x-tenant.alert type="error" :title="__('dashboard.shared.balance.title')">
            <p class="text-sm">
                <span class="font-semibold">{{ __('dashboard.shared.balance.outstanding') }}</span> €{{ number_format($stats['unpaid_balance'], 2) }}
            </p>
            <p class="mt-1 text-sm">
                {{ trans_choice('dashboard.tenant.balance.notice', $stats['unpaid_invoices'], ['count' => $stats['unpaid_invoices']]) }}
            </p>
            <x-slot name="action">
                <a href="{{ route('tenant.invoices.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-transparent bg-rose-500 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                    {{ __('dashboard.shared.balance.cta') }}
                </a>
            </x-slot>
        </x-tenant.alert>
        @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-tenant.stat-card :label="__('dashboard.shared.stats.total_invoices')" :value="$stats['total_invoices']" />
            <x-tenant.stat-card :label="__('dashboard.shared.stats.unpaid_invoices')" :value="$stats['unpaid_invoices']" value-color="text-orange-600" />
            <x-tenant.stat-card :label="__('dashboard.shared.stats.active_meters')" :value="$stats['property']->meters->count()" />
        </div>

        @if($stats['latest_readings']->isNotEmpty())
        <x-tenant.section-card :title="__('dashboard.shared.readings.title')">
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('dashboard.shared.readings.meter_type') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('dashboard.shared.readings.serial') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('dashboard.shared.readings.reading') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('dashboard.shared.readings.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($stats['latest_readings'] as $reading)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                {{ $reading->meter->getServiceDisplayName() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ $reading->meter->serial_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                {{ number_format($reading->value, 2) }} {{ $reading->meter->getUnitOfMeasurement() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ $reading->reading_date->format('Y-m-d') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-tenant.stack gap="3" class="sm:hidden">
                @foreach($stats['latest_readings'] as $reading)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ $reading->meter->getServiceDisplayName() }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ $reading->reading_date->format('Y-m-d') }}</p>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ __('dashboard.shared.readings.serial_short') }} {{ $reading->meter->serial_number }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ number_format($reading->value, 2) }} {{ $reading->meter->getUnitOfMeasurement() }}
                        </p>
                    </div>
                @endforeach
            </x-tenant.stack>
        </x-tenant.section-card>
        @endif

        <x-tenant.section-card :title="__('dashboard.shared.consumption.title')" :description="__('dashboard.shared.consumption.description')">
            @if(empty($stats['consumption_trends']) || $stats['consumption_trends']->every(fn($t) => !$t['previous']))
                <p class="text-sm text-slate-600">{{ __('dashboard.shared.consumption.need_more') }}</p>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($stats['consumption_trends'] as $trend)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">{{ $trend['meter']->getServiceDisplayName() }}</p>
                                <p class="text-xs text-slate-500">{{ $trend['meter']->serial_number }}</p>
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <p class="text-2xl font-semibold text-slate-900">
                                    {{ $trend['latest'] ? number_format($trend['latest']->value, 2) : '—' }}
                                </p>
                                <p class="text-xs text-slate-600">{{ __('dashboard.shared.consumption.current') }}</p>
                            </div>
                            @if($trend['previous'])
                                <p class="text-sm text-slate-600">
                                    {{ __('dashboard.shared.consumption.previous', ['value' => number_format($trend['previous']->value, 2), 'date' => $trend['previous']->reading_date->format('Y-m-d')]) }}
                                </p>
                                <p class="mt-1 text-sm {{ $trend['delta'] !== null && $trend['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $trend['delta'] !== null && $trend['delta'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($trend['delta'] ?? 0), 2) }}
                                    @if(!is_null($trend['percent']))
                                        ({{ number_format($trend['percent'], 1) }}%)
                                    @endif
                                    {{ __('dashboard.shared.consumption.since_last') }}
                                </p>
                            @else
                                <p class="text-sm text-slate-500">{{ __('dashboard.shared.consumption.missing_previous') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-tenant.section-card>
    @endif
</x-tenant.page>
@endsection
@break

@default

@section('title', __('shared.dashboard.title'))

@section('content')
<x-backoffice.page
    class="container mx-auto px-4 py-8"
    wire:poll.60s
    :title="__('shared.dashboard.title')"
    :description="__('shared.dashboard.subtitle')"
    :eyebrow="__('shared.dashboard.badges.platform')"
>

    {{-- Subscription Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_subscriptions')" 
            :value="$totalSubscriptions" 
            icon="📊"
            href="{{ route('superadmin.subscriptions.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.active_subscriptions')" 
            :value="$activeSubscriptions" 
            icon="✅"
            color="green"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::ACTIVE->value]) }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.expired_subscriptions')" 
            :value="$expiredSubscriptions" 
            icon="⏰"
            color="red"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::EXPIRED->value]) }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.suspended_subscriptions')" 
            :value="$suspendedSubscriptions" 
            icon="⏸️"
            color="yellow"
            href="{{ route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::SUSPENDED->value]) }}"
        />
    </div>

    {{-- Quick Actions --}}
    <x-card class="mb-8">
        <h2 class="text-xl font-semibold">{{ __('shared.dashboard.quick_actions.title') }}</h2>
        <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.quick_actions.description') }}</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-backoffice.quick-action
                :href="route('superadmin.organizations.create')"
                :title="__('shared.dashboard.quick_actions.create_organization')"
                :description="__('shared.dashboard.quick_actions.create_organization_desc')"
            >
                <x-slot:icon>➕</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.subscriptions.index')"
                :title="__('shared.dashboard.quick_actions.create_subscription')"
                :description="__('shared.dashboard.quick_actions.create_subscription_desc')"
            >
                <x-slot:icon>🧾</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                href="#recent-activity"
                :title="__('shared.dashboard.quick_actions.view_all_activity')"
                :description="__('shared.dashboard.quick_actions.view_all_activity_desc')"
            >
                <x-slot:icon>🕒</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.organizations.index')"
                :title="__('shared.dashboard.quick_actions.manage_organizations')"
                :description="__('shared.dashboard.quick_actions.manage_organizations_desc')"
            >
                <x-slot:icon>🏢</x-slot:icon>
            </x-backoffice.quick-action>
            <x-backoffice.quick-action
                :href="route('superadmin.subscriptions.index')"
                :title="__('shared.dashboard.quick_actions.manage_subscriptions')"
                :description="__('shared.dashboard.quick_actions.manage_subscriptions_desc')"
            >
                <x-slot:icon>📊</x-slot:icon>
            </x-backoffice.quick-action>
        </div>
    </x-card>

    {{-- System Health --}}
    <x-card class="mb-8" id="system-health">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.system_health.title') }}</h2>
                <p class="text-slate-500 text-sm">{{ __('shared.dashboard.system_health.description') }}</p>
            </div>
            <form method="POST" action="{{ route('superadmin.dashboard.health-check') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded hover:bg-slate-800 text-sm font-semibold">
                    {{ __('shared.dashboard.system_health.actions.run_check') }}
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
                <p class="text-slate-500">{{ __('shared.dashboard.system_health.empty') }}</p>
            @endforelse
        </div>
    </x-card>

    {{-- Organization Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <a href="{{ route('superadmin.organizations.index') }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <x-card class="h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.organizations.title') }}</h2>
                <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.organizations.description') }}</p>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.organizations.total') }}</span>
                        <span class="text-2xl font-bold">{{ $totalOrganizations }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.organizations.active') }}</span>
                        <span class="text-2xl font-bold text-green-600">{{ $activeOrganizations }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.organizations.inactive') }}</span>
                        <span class="text-2xl font-bold text-red-600">{{ $totalOrganizations - $activeOrganizations }}</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span>{{ __('shared.dashboard.organizations.view_all') }}</span>
                    <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </x-card>
        </a>

        <a href="{{ route('superadmin.subscriptions.index') }}" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <x-card class="h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200">
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.subscription_plans.title') }}</h2>
                <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.subscription_plans.description') }}</p>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.subscription_plans.basic') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['basic'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.subscription_plans.professional') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['professional'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">{{ __('shared.dashboard.subscription_plans.enterprise') }}</span>
                        <span class="text-2xl font-bold">{{ $subscriptionsByPlan['enterprise'] ?? 0 }}</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span>{{ __('shared.dashboard.subscription_plans.view_all') }}</span>
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
            :title="__('shared.dashboard.stats.total_properties')" 
            :value="$totalProperties" 
            icon="🏢"
            href="{{ route('superadmin.properties.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_buildings')" 
            :value="$totalBuildings" 
            icon="🏗️"
            href="{{ route('superadmin.buildings.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_tenants')" 
            :value="$totalTenants" 
            icon="👥"
            href="{{ route('superadmin.managers.index') }}"
        />
        <x-stat-card 
            :title="__('shared.dashboard.stats.total_invoices')" 
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
                <h3 class="text-lg font-semibold text-yellow-800">{{ __('shared.dashboard.expiring_subscriptions.title') }}</h3>
                <p class="text-yellow-700 mt-1">{{ __('shared.dashboard.expiring_subscriptions.alert', ['count' => $expiringSubscriptions->count()]) }}</p>
                <div class="mt-4 space-y-2">
                    @foreach($expiringSubscriptions as $subscription)
                    <div class="flex justify-between items-center bg-white p-3 rounded">
                        <div>
                            <span class="font-medium">{{ $subscription->user->organization_name }}</span>
                            <span class="text-sm text-slate-600 ml-2">({{ $subscription->user->email }})</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-slate-600">{{ __('shared.dashboard.expiring_subscriptions.expires') }}</span>
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
            <h2 class="text-xl font-semibold">{{ __('shared.dashboard.organizations.top_by_properties') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.organizations.top_by_properties_description') }}</p>
            <div class="space-y-3">
                @forelse($topOrganizations as $org)
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium">{{ $org->name }}</div>
                        <div class="text-sm text-slate-600">{{ $org->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ $org->properties_count }}</div>
                        <div class="text-xs text-slate-600">{{ __('shared.dashboard.organizations.properties_count') }}</div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-center py-4">{{ __('shared.dashboard.organizations.no_organizations') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card id="recent-activity">
            <h2 class="text-xl font-semibold">{{ __('shared.dashboard.recent_activity.title') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('shared.dashboard.recent_activity.description') }}</p>
            <div class="space-y-3">
                @forelse($recentActivity as $activity)
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium">{{ $activity->organization?->name ?? __('shared.dashboard.recent_activity.system') }}</div>
                        <div class="text-sm text-slate-600">{{ $activity->action }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-600">{{ __('shared.dashboard.recent_activity.occurred') }}</div>
                        <div class="text-sm font-medium">{{ $activity->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-center py-4">{{ __('shared.dashboard.recent_activity.no_activity') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Overview tables mapped to widget logic --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card id="subscriptions-overview">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('shared.dashboard.overview.subscriptions.title') }}</h2>
                    <p class="text-slate-500 text-sm">{{ __('shared.dashboard.overview.subscriptions.description') }}</p>
                </div>
                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('shared.dashboard.overview.subscriptions.open') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.organization') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.plan') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.expires') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.subscriptions.headers.manage') }}</th>
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
                                    <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.subscriptions.headers.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500">{{ __('shared.dashboard.overview.subscriptions.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card id="organizations-overview">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ __('shared.dashboard.overview.organizations.title') }}</h2>
                    <p class="text-slate-500 text-sm">{{ __('shared.dashboard.overview.organizations.description') }}</p>
                </div>
                <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('shared.dashboard.overview.organizations.open') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.organization') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.subscription') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.created') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</th>
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
                                        <span class="text-slate-400">{{ __('shared.dashboard.overview.organizations.no_subscription') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($organization->is_active)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('shared.dashboard.overview.organizations.status_active') }}</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('shared.dashboard.overview.organizations.status_inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $organization->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a href="{{ route('superadmin.organizations.show', $organization) }}" class="text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500">{{ __('shared.dashboard.overview.organizations.empty') }}</td>
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
                <h2 class="text-xl font-semibold">{{ __('shared.dashboard.overview.resources.title') }}</h2>
                <p class="text-slate-500 text-sm">{{ __('shared.dashboard.overview.resources.description') }}</p>
            </div>
            <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">{{ __('shared.dashboard.overview.resources.manage_orgs') }}</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div id="resource-properties" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.properties.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.properties.open_owners') }}</a>
                </div>
                @forelse($latestProperties as $property)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $property->address }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.properties.building') }}: {{ $property->building?->display_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.properties.organization') }}: {{ ($organizationLookup[$property->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$property->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$property->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.properties.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-buildings" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.buildings.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.buildings.open_owners') }}</a>
                </div>
                @forelse($latestBuildings as $building)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $building->display_name }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.buildings.address') }}: {{ $building->address }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.buildings.organization') }}: {{ ($organizationLookup[$building->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$building->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$building->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.organizations.headers.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.buildings.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-tenants" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.tenants.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.tenants.open_owners') }}</a>
                </div>
                @forelse($latestTenants as $tenant)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $tenant->name }}</div>
                            <div class="text-xs text-slate-500">{{ $tenant->email }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.tenants.property') }}: {{ $tenant->property?->address ?? __('shared.dashboard.overview.resources.tenants.not_assigned') }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.tenants.organization') }}: {{ ($organizationLookup[$tenant->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tenant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $tenant->is_active ? __('shared.dashboard.overview.resources.tenants.status_active') : __('shared.dashboard.overview.resources.tenants.status_inactive') }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.tenants.empty') }}</p>
                @endforelse
            </div>

            <div id="resource-invoices" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ __('shared.dashboard.overview.resources.invoices.title') }}</h3>
                    <a href="{{ route('superadmin.organizations.index') }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.invoices.open_owners') }}</a>
                </div>
                @forelse($latestInvoices as $invoice)
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900">{{ $invoice->tenant?->name ?? __('tenants.labels.name') }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.amount') }}: {{ number_format($invoice->total_amount, 2) }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.status') }}: {{ enum_label($invoice->status, \App\Enums\InvoiceStatus::class) }}</div>
                            <div class="text-xs text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.organization') }}: {{ ($organizationLookup[$invoice->tenant_id] ?? null)?->name ?? __('shared.dashboard.overview.resources.properties.unknown_org') }}</div>
                        </div>
                        @if($organizationLookup[$invoice->tenant_id] ?? null)
                            <a href="{{ route('superadmin.organizations.show', ($organizationLookup[$invoice->tenant_id] ?? null)->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('shared.dashboard.overview.resources.invoices.manage') }}</a>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('shared.dashboard.overview.resources.invoices.empty') }}</p>
                @endforelse
            </div>
        </div>
    </x-card>

    {{-- Analytics --}}
    <x-card class="mb-8" id="analytics">
        <h2 class="text-xl font-semibold mb-2">{{ __('shared.dashboard.analytics.title') }}</h2>
        <p class="text-sm text-slate-500 mb-2">{{ __('shared.dashboard.analytics.description') }}</p>
        <p class="text-slate-500">{{ __('shared.dashboard.analytics.empty') }}</p>
    </x-card>

</x-backoffice.page>
@endsection
@endswitch
