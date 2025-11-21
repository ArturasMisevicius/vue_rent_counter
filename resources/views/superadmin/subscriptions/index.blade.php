@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Subscriptions</h1>
        <p class="text-gray-600 mt-2">Manage all organization subscriptions</p>
    </div>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('superadmin.subscriptions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Organization name" class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plan Type</label>
                <select name="plan_type" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="">All</option>
                    <option value="basic" {{ request('plan_type') === 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="professional" {{ request('plan_type') === 'professional' ? 'selected' : '' }}>Professional</option>
                    <option value="enterprise" {{ request('plan_type') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiring Soon</label>
                <select name="expiring_soon" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="">All</option>
                    <option value="1" {{ request('expiring_soon') === '1' ? 'selected' : '' }}>Within 14 days</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    Filter
                </button>
            </div>
        </form>
    </x-card>

    {{-- Subscriptions Table --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ $subscription->user->organization_name }}</div>
                            <div class="text-sm text-gray-500">{{ $subscription->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium">{{ ucfirst($subscription->plan_type) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-status-badge :status="$subscription->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $subscription->max_properties }} properties</div>
                            <div>{{ $subscription->max_tenants }} tenants</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $subscription->expires_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $subscription->expires_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-900">Manage</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No subscriptions found
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
