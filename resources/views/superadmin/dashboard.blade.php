@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('superadmin.dashboard.title') }}</h1>
        <p class="text-gray-600 mt-2">{{ __('superadmin.dashboard.subtitle') }}</p>
    </div>

    {{-- Subscription Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_subscriptions')" 
            :value="$totalSubscriptions" 
            icon="📊"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.active_subscriptions')" 
            :value="$activeSubscriptions" 
            icon="✅"
            color="green"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.expired_subscriptions')" 
            :value="$expiredSubscriptions" 
            icon="⏰"
            color="red"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.suspended_subscriptions')" 
            :value="$suspendedSubscriptions" 
            icon="⏸️"
            color="yellow"
        />
    </div>

    {{-- Organization Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organizations.title') }}</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ __('superadmin.dashboard.organizations.total') }}</span>
                    <span class="text-2xl font-bold">{{ $totalOrganizations }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ __('superadmin.dashboard.organizations.active') }}</span>
                    <span class="text-2xl font-bold text-green-600">{{ $activeOrganizations }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ __('superadmin.dashboard.organizations.inactive') }}</span>
                    <span class="text-2xl font-bold text-red-600">{{ $totalOrganizations - $activeOrganizations }}</span>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800">
                    {{ __('superadmin.dashboard.organizations.view_all') }}
                </a>
            </div>
        </x-card>

        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.subscription_plans.title') }}</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ __('superadmin.dashboard.subscription_plans.basic') }}</span>
                    <span class="text-2xl font-bold">{{ $subscriptionsByPlan['basic'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ __('superadmin.dashboard.subscription_plans.professional') }}</span>
                    <span class="text-2xl font-bold">{{ $subscriptionsByPlan['professional'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ __('superadmin.dashboard.subscription_plans.enterprise') }}</span>
                    <span class="text-2xl font-bold">{{ $subscriptionsByPlan['enterprise'] ?? 0 }}</span>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">
                    {{ __('superadmin.dashboard.subscription_plans.view_all') }}
                </a>
            </div>
        </x-card>
    </div>

    {{-- System-wide Usage Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_properties')" 
            :value="$totalProperties" 
            icon="🏢"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_buildings')" 
            :value="$totalBuildings" 
            icon="🏗️"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_tenants')" 
            :value="$totalTenants" 
            icon="👥"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.stats.total_invoices')" 
            :value="$totalInvoices" 
            icon="📄"
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
                            <span class="text-sm text-gray-600 ml-2">({{ $subscription->user->email }})</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-600">{{ __('superadmin.dashboard.expiring_subscriptions.expires') }}</span>
                            <span class="font-medium text-yellow-700">{{ $subscription->expires_at->format('M d, Y') }}</span>
                            <span class="text-sm text-gray-600 ml-2">({{ $subscription->expires_at->diffForHumans() }})</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-card>
    @endif

    {{-- Top Organizations --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organizations.top_by_properties') }}</h2>
            <div class="space-y-3">
                @forelse($topOrganizations as $org)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium">{{ $org->organization_name }}</div>
                        <div class="text-sm text-gray-600">{{ $org->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ $org->properties_count }}</div>
                        <div class="text-xs text-gray-600">{{ __('superadmin.dashboard.organizations.properties_count') }}</div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">{{ __('superadmin.dashboard.organizations.no_organizations') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.recent_activity.title') }}</h2>
            <div class="space-y-3">
                @forelse($recentActivity as $admin)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium">{{ $admin->organization_name }}</div>
                        <div class="text-sm text-gray-600">{{ $admin->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">{{ __('superadmin.dashboard.recent_activity.last_activity') }}</div>
                        <div class="text-sm font-medium">{{ $admin->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">{{ __('superadmin.dashboard.recent_activity.no_activity') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Quick Actions --}}
    <x-card>
        <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.quick_actions.title') }}</h2>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('superadmin.organizations.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <span class="mr-2">➕</span>
                {{ __('superadmin.dashboard.quick_actions.create_organization') }}
            </a>
            <a href="{{ route('superadmin.organizations.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                <span class="mr-2">🏢</span>
                {{ __('superadmin.dashboard.quick_actions.manage_organizations') }}
            </a>
            <a href="{{ route('superadmin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                <span class="mr-2">📊</span>
                {{ __('superadmin.dashboard.quick_actions.manage_subscriptions') }}
            </a>
        </div>
    </x-card>
</div>
@endsection
