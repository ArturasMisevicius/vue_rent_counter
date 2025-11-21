@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Superadmin Dashboard</h1>
        <p class="text-gray-600 mt-2">System-wide statistics and organization management</p>
    </div>

    {{-- Subscription Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            title="Total Subscriptions" 
            :value="$totalSubscriptions" 
            icon="📊"
        />
        <x-stat-card 
            title="Active Subscriptions" 
            :value="$activeSubscriptions" 
            icon="✅"
            color="green"
        />
        <x-stat-card 
            title="Expired Subscriptions" 
            :value="$expiredSubscriptions" 
            icon="⏰"
            color="red"
        />
        <x-stat-card 
            title="Suspended Subscriptions" 
            :value="$suspendedSubscriptions" 
            icon="⏸️"
            color="yellow"
        />
    </div>

    {{-- Organization Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-xl font-semibold mb-4">Organizations</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Organizations</span>
                    <span class="text-2xl font-bold">{{ $totalOrganizations }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Active Organizations</span>
                    <span class="text-2xl font-bold text-green-600">{{ $activeOrganizations }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Inactive Organizations</span>
                    <span class="text-2xl font-bold text-red-600">{{ $totalOrganizations - $activeOrganizations }}</span>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('superadmin.organizations.index') }}" class="text-blue-600 hover:text-blue-800">
                    View all organizations →
                </a>
            </div>
        </x-card>

        <x-card>
            <h2 class="text-xl font-semibold mb-4">Subscription Plans</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Basic</span>
                    <span class="text-2xl font-bold">{{ $subscriptionsByPlan['basic'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Professional</span>
                    <span class="text-2xl font-bold">{{ $subscriptionsByPlan['professional'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Enterprise</span>
                    <span class="text-2xl font-bold">{{ $subscriptionsByPlan['enterprise'] ?? 0 }}</span>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800">
                    View all subscriptions →
                </a>
            </div>
        </x-card>
    </div>

    {{-- System-wide Usage Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <x-stat-card 
            title="Total Properties" 
            :value="$totalProperties" 
            icon="🏢"
        />
        <x-stat-card 
            title="Total Buildings" 
            :value="$totalBuildings" 
            icon="🏗️"
        />
        <x-stat-card 
            title="Total Tenants" 
            :value="$totalTenants" 
            icon="👥"
        />
        <x-stat-card 
            title="Total Invoices" 
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
                <h3 class="text-lg font-semibold text-yellow-800">Expiring Subscriptions</h3>
                <p class="text-yellow-700 mt-1">{{ $expiringSubscriptions->count() }} subscription(s) expiring within 14 days</p>
                <div class="mt-4 space-y-2">
                    @foreach($expiringSubscriptions as $subscription)
                    <div class="flex justify-between items-center bg-white p-3 rounded">
                        <div>
                            <span class="font-medium">{{ $subscription->user->organization_name }}</span>
                            <span class="text-sm text-gray-600 ml-2">({{ $subscription->user->email }})</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-600">Expires:</span>
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
            <h2 class="text-xl font-semibold mb-4">Top Organizations by Properties</h2>
            <div class="space-y-3">
                @forelse($topOrganizations as $org)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium">{{ $org->organization_name }}</div>
                        <div class="text-sm text-gray-600">{{ $org->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ $org->properties_count }}</div>
                        <div class="text-xs text-gray-600">properties</div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No organizations yet</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h2 class="text-xl font-semibold mb-4">Recent Admin Activity</h2>
            <div class="space-y-3">
                @forelse($recentActivity as $admin)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-medium">{{ $admin->organization_name }}</div>
                        <div class="text-sm text-gray-600">{{ $admin->email }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Last login:</div>
                        <div class="text-sm font-medium">{{ $admin->last_login_at?->diffForHumans() ?? 'Never' }}</div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No activity yet</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Quick Actions --}}
    <x-card>
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('superadmin.organizations.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <span class="mr-2">➕</span>
                Create New Organization
            </a>
            <a href="{{ route('superadmin.organizations.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                <span class="mr-2">🏢</span>
                Manage Organizations
            </a>
            <a href="{{ route('superadmin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                <span class="mr-2">📊</span>
                Manage Subscriptions
            </a>
        </div>
    </x-card>
</div>
@endsection
