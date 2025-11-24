@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $organization->organization_name }}</h1>
            <p class="text-slate-600 mt-2">Organization Details and Activity</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.organizations.edit', $organization) }}" class="px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                Edit
            </a>
            @if($organization->is_active)
            <form method="POST" action="{{ route('superadmin.organizations.deactivate', $organization) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to deactivate this organization?')">
                    Deactivate
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('superadmin.organizations.reactivate', $organization) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Reactivate
                </button>
            </form>
            @endif
            <a href="{{ route('superadmin.organizations.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">
                Back
            </a>
        </div>
    </div>

    {{-- Organization Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-xl font-semibold mb-4">Organization Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-slate-500">Organization Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->organization_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Contact Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Email</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Tenant ID</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-mono">{{ $organization->tenant_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd class="mt-1">
                        @if($organization->is_active)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Active
                        </span>
                        @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                            Inactive
                        </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Created</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-xl font-semibold mb-4">Subscription Details</h2>
            @if($organization->subscription)
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Plan Type</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ enum_label($organization->subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</dd>
                    </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd class="mt-1">
                        <x-status-badge :status="$organization->subscription->status" />
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Start Date</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->subscription->starts_at->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Expiry Date</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $organization->subscription->expires_at->format('M d, Y') }}
                        <span class="text-slate-500">({{ $organization->subscription->expires_at->diffForHumans() }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Limits</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $organization->subscription->max_properties }} properties, {{ $organization->subscription->max_tenants }} tenants
                    </dd>
                </div>
            </dl>
            <div class="mt-4">
                <a href="{{ route('superadmin.subscriptions.show', $organization->subscription) }}" class="text-blue-600 hover:text-blue-800">
                    Manage Subscription →
                </a>
            </div>
            @else
            <p class="text-slate-500">No subscription found</p>
            @endif
        </x-card>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <x-stat-card 
            title="Properties" 
            :value="$stats['total_properties']" 
            icon="🏢"
        />
        <x-stat-card 
            title="Buildings" 
            :value="$stats['total_buildings']" 
            icon="🏗️"
        />
        <x-stat-card 
            title="Tenants" 
            :value="$stats['total_tenants']" 
            icon="👥"
        />
        <x-stat-card 
            title="Active Tenants" 
            :value="$stats['active_tenants']" 
            icon="✅"
            color="green"
        />
        <x-stat-card 
            title="Invoices" 
            :value="$stats['total_invoices']" 
            icon="📄"
        />
    </div>

    {{-- Recent Tenants --}}
    <x-card>
        <h2 class="text-xl font-semibold mb-4">Recent Tenants</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($recentTenants as $tenant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $tenant->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($tenant->property)
                            {{ $tenant->property->address }}
                            @else
                            <span class="text-slate-400">Not assigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($tenant->is_active)
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
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">
                            No tenants yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
