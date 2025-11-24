@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Organizations</h1>
            <p class="text-slate-600 mt-2">Manage all admin accounts and their subscriptions</p>
        </div>
        <a href="{{ route('superadmin.organizations.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <span class="mr-2">➕</span>
            Create Organization
        </a>
    </div>

    @php
        $subscriptionStatuses = \App\Enums\SubscriptionStatus::cases();
    @endphp

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('superadmin.organizations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Organization name or email" class="w-full px-3 py-2 border border-slate-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Subscription Status</label>
                <select name="subscription_status" class="w-full px-3 py-2 border border-slate-300 rounded">
                    <option value="">All</option>
                    @foreach($subscriptionStatuses as $status)
                        <option value="{{ $status->value }}" {{ request('subscription_status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                    Filter
                </button>
            </div>
        </form>
    </x-card>

    {{-- Organizations Table --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Organization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($organizations as $org)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-slate-900">{{ $org->organization_name }}</div>
                            <div class="text-sm text-slate-500">Tenant ID: {{ $org->tenant_id }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900">{{ $org->name }}</div>
                            <div class="text-sm text-slate-500">{{ $org->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($org->subscription)
                            <div class="text-sm">
                                <span class="font-medium">{{ enum_label($org->subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</span>
                                <x-status-badge :status="$org->subscription->status" />
                            </div>
                            <div class="text-xs text-slate-500">
                                Expires: {{ $org->subscription->expires_at->format('M d, Y') }}
                            </div>
                            @else
                            <span class="text-sm text-slate-500">No subscription</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($org->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                            @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $org->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('superadmin.organizations.show', $org) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                            <a href="{{ route('superadmin.organizations.edit', $org) }}" class="text-slate-600 hover:text-slate-900">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500">
                            No organizations found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($organizations->hasPages())
        <div class="mt-4">
            {{ $organizations->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
