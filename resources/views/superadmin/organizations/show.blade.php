@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $organization->organization_name }}</h1>
            <p class="text-slate-600 mt-2">{{ __('superadmin.dashboard.organization_show.subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.organizations.edit', $organization) }}" class="px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                {{ __('superadmin.dashboard.organization_show.actions.edit') }}
            </a>
            @if($organization->is_active)
            <form method="POST" action="{{ route('superadmin.organizations.deactivate', $organization) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('{{ __('superadmin.dashboard.organization_show.confirm_deactivate') }}')">
                    {{ __('superadmin.dashboard.organization_show.actions.deactivate') }}
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('superadmin.organizations.reactivate', $organization) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    {{ __('superadmin.dashboard.organization_show.actions.reactivate') }}
                </button>
            </form>
            @endif
            <a href="{{ route('superadmin.organizations.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">
                {{ __('superadmin.dashboard.organization_show.actions.back') }}
            </a>
        </div>
    </div>

    {{-- Organization Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organization_show.organization_info') }}</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.organization_name') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->organization_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.contact_name') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.email') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.tenant_id') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-mono">{{ $organization->tenant_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.status') }}</dt>
                    <dd class="mt-1">
                        @if($organization->is_active)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ __('superadmin.dashboard.organizations_list.status_active') }}
                        </span>
                        @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                            {{ __('superadmin.dashboard.organizations_list.status_inactive') }}
                        </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.created') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organization_show.subscription_details') }}</h2>
            @if($organization->subscription)
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.plan_type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ enum_label($organization->subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</dd>
                    </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.status') }}</dt>
                    <dd class="mt-1">
                        <x-status-badge :status="$organization->subscription->status" />
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.start_date') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $organization->subscription->starts_at->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.expiry_date') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $organization->subscription->expires_at->format('M d, Y') }}
                        <span class="text-slate-500">({{ $organization->subscription->expires_at->diffForHumans() }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('superadmin.dashboard.organization_show.limits') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ __('superadmin.dashboard.organization_show.limit_values', ['properties' => $organization->subscription->max_properties, 'tenants' => $organization->subscription->max_tenants]) }}
                    </dd>
                </div>
            </dl>
            <div class="mt-4">
                <a href="{{ route('superadmin.subscriptions.show', $organization->subscription) }}" class="text-blue-600 hover:text-blue-800">
                    {{ __('superadmin.dashboard.organization_show.manage_subscription') }}
                </a>
            </div>
            @else
            <p class="text-slate-500">{{ __('superadmin.dashboard.organization_show.no_subscription') }}</p>
            @endif
        </x-card>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.properties')" 
            :value="$stats['total_properties']" 
            icon="🏢"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.buildings')" 
            :value="$stats['total_buildings']" 
            icon="🏗️"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.tenants')" 
            :value="$stats['total_tenants']" 
            icon="👥"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.active_tenants')" 
            :value="$stats['active_tenants']" 
            icon="✅"
            color="green"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.invoices')" 
            :value="$stats['total_invoices']" 
            icon="📄"
        />
    </div>

    {{-- Tenants --}}
    <x-card>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">{{ __('superadmin.dashboard.organization_show.tenants_title', ['count' => $stats['total_tenants']]) }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.id') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.email') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.property') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.created') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $tenant->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($tenant->property)
                            {{ $tenant->property->address }}
                            @else
                            <span class="text-slate-400">{{ __('superadmin.dashboard.overview.resources.tenants.not_assigned') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($tenant->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                {{ __('superadmin.dashboard.overview.resources.tenants.status_active') }}
                            </span>
                            @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                {{ __('tenants.statuses.inactive') }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500">
                            {{ __('superadmin.dashboard.overview.resources.tenants.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
