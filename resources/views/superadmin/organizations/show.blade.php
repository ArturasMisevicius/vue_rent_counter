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
            icon="PR"
            href="#properties-section"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.buildings')" 
            :value="$stats['total_buildings']" 
            icon="BLD"
            href="#buildings-section"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.tenants')" 
            :value="$stats['total_tenants']" 
            icon="TEN"
            href="#tenants-section"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.active_tenants')" 
            :value="$stats['active_tenants']" 
            icon="ACT"
            color="green"
            href="#tenants-section"
        />
        <x-stat-card 
            :title="__('superadmin.dashboard.organization_show.stats.invoices')" 
            :value="$stats['total_invoices']" 
            icon="INV"
            href="#invoices-section"
        />
    </div>

    {{-- Relationship insights --}}
    <x-card class="mb-8">
        <h2 class="text-xl font-semibold mb-4">{{ __('superadmin.dashboard.organization_show.relationship_insights.title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <a href="#properties-section" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-indigo-300 hover:shadow-sm transition">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('superadmin.dashboard.organization_show.relationship_insights.occupied') }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $relationshipMetrics['occupied_properties'] }}</p>
            </a>
            <a href="#properties-section" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-indigo-300 hover:shadow-sm transition">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('superadmin.dashboard.organization_show.relationship_insights.vacant') }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $relationshipMetrics['vacant_properties'] }}</p>
            </a>
            <a href="#properties-section" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-indigo-300 hover:shadow-sm transition">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('superadmin.dashboard.organization_show.relationship_insights.metered') }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $relationshipMetrics['metered_properties'] }}</p>
            </a>
            <a href="#invoices-section" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-indigo-300 hover:shadow-sm transition">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('superadmin.dashboard.organization_show.relationship_insights.draft_invoices') }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $relationshipMetrics['draft_invoices'] }}</p>
            </a>
            <a href="#invoices-section" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-indigo-300 hover:shadow-sm transition">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('superadmin.dashboard.organization_show.relationship_insights.finalized_invoices') }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $relationshipMetrics['finalized_invoices'] }}</p>
            </a>
            <a href="#invoices-section" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 hover:border-indigo-300 hover:shadow-sm transition">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ __('superadmin.dashboard.organization_show.relationship_insights.paid_invoices') }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $relationshipMetrics['paid_invoices'] }}</p>
            </a>
        </div>
    </x-card>
    {{-- Buildings --}}
    <x-card id="buildings-section" class="mb-8">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-semibold">
                    {{ __('superadmin.dashboard.organization_show.resources.buildings.title') }}
                    <span class="text-sm text-slate-500">({{ $stats['total_buildings'] }})</span>
                </h2>
                <p class="text-sm text-slate-600">{{ __('superadmin.dashboard.organization_show.resources.buildings.subtitle') }}</p>
            </div>
            <a href="{{ route('filament.admin.resources.buildings.index') }}" class="text-blue-600 hover:text-blue-800">
                {{ __('superadmin.dashboard.organization_show.resources.properties.open_admin') }}
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.address') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.properties') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.occupied') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.vacant') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.sample_properties') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.buildings.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($buildings as $building)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('filament.admin.resources.buildings.edit', $building) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $building->display_name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $building->address ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $building->properties_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $building->occupied_units_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ max($building->properties_count - $building->occupied_units_count, 0) }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            <div class="flex flex-wrap gap-2">
                                @forelse($building->properties as $property)
                                    <a href="{{ route('filament.admin.resources.properties.edit', $property) }}" class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-indigo-700 hover:text-indigo-900 hover:bg-slate-200">
                                        {{ $property->address }}
                                        <span class="ml-1 text-slate-500">{{ $property->tenants_count }}</span>
                                    </a>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('filament.admin.resources.buildings.edit', $building) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') }}
                                </a>
                                <form action="{{ route('filament.admin.resources.buildings.destroy', $building) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-slate-500">
                            {{ __('superadmin.dashboard.organization_show.resources.buildings.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($buildings->hasPages())
        <div class="mt-4">
            {{ $buildings->links() }}
        </div>
        @endif
    </x-card>

    {{-- Properties --}}
    <x-card id="properties-section" class="mb-8">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-semibold">
                    {{ __('superadmin.dashboard.organization_show.resources.properties.title') }}
                    <span class="text-sm text-slate-500">({{ $stats['total_properties'] }})</span>
                </h2>
                <p class="text-sm text-slate-600">{{ __('superadmin.dashboard.organization_show.resources.properties.subtitle') }}</p>
            </div>
            <a href="{{ route('filament.admin.resources.properties.index') }}" class="text-blue-600 hover:text-blue-800">
                {{ __('superadmin.dashboard.organization_show.resources.properties.open_admin') }}
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.address') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.building') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.area') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.tenants') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.meters') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.invoices') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.latest_invoice') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.properties.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($properties as $property)
                        @php
                            $latestInvoice = $latestInvoicesByProperty[$property->id] ?? null;
                            $invoiceCount = $invoiceCountsByProperty[$property->id] ?? 0;
                        @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('filament.admin.resources.properties.edit', $property) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $property->address }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($property->building)
                                <a href="{{ route('filament.admin.resources.buildings.edit', $property->building) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $property->building->display_name ?? $property->building->address }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                {{ enum_label($property->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ number_format((float) $property->area_sqm, 2) }} sqm</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($property->tenants->isNotEmpty())
                                @foreach($property->tenants as $tenant)
                                    <a href="{{ route('filament.admin.resources.tenants.edit', $tenant) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $tenant->name }}
                                    </a>{{ !$loop->last ? ',' : '' }}
                                @endforeach
                            @else
                                <span class="text-slate-400">{{ __('superadmin.dashboard.organization_show.resources.properties.vacant') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $property->meters_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $invoiceCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($latestInvoice)
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('filament.admin.resources.invoices.view', $latestInvoice->id) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        #{{ $latestInvoice->invoice_number ?? $latestInvoice->id }}
                                    </a>
                                    <x-status-badge :status="$latestInvoice->status" />
                                    @if($latestInvoice->billing_period_end)
                                        <span class="text-xs text-slate-500">{{ $latestInvoice->billing_period_end->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('filament.admin.resources.properties.edit', $property) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('superadmin.dashboard.organization_show.resources.action_view') }}
                                </a>
                                <form action="{{ route('filament.admin.resources.properties.destroy', $property) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') ?? 'Are you sure?' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') ?? 'Delete' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-slate-500">
                            {{ __('superadmin.dashboard.organization_show.resources.properties.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($properties->hasPages())
        <div class="mt-4">
            {{ $properties->links() }}
        </div>
        @endif
    </x-card>

    {{-- Invoices --}}
    <x-card id="invoices-section" class="mb-8">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-semibold">
                    {{ __('superadmin.dashboard.organization_show.resources.invoices.title') }}
                    <span class="text-sm text-slate-500">({{ $stats['total_invoices'] }})</span>
                </h2>
                <p class="text-sm text-slate-600">{{ __('superadmin.dashboard.organization_show.resources.invoices.subtitle') }}</p>
            </div>
            <a href="{{ route('filament.admin.resources.invoices.index') }}" class="text-blue-600 hover:text-blue-800">
                {{ __('superadmin.dashboard.organization_show.resources.properties.open_admin') }}
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.invoice') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.tenant') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.property') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.period') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.total') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.due') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.resources.invoices.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('filament.admin.resources.invoices.view', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">
                                #{{ $invoice->invoice_number ?? $invoice->id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($invoice->tenant)
                                <a href="{{ route('filament.admin.resources.tenants.edit', $invoice->tenant) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $invoice->tenant->name }}
                                </a>
                                <div class="text-xs text-slate-400">{{ $invoice->tenant->email }}</div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($invoice->tenant?->property)
                                <a href="{{ route('filament.admin.resources.properties.edit', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $invoice->tenant->property->address }}
                                </a>
                                @if($invoice->tenant->property->building)
                                    <div class="text-xs text-slate-400">
                                        <a href="{{ route('filament.admin.resources.buildings.edit', $invoice->tenant->property->building) }}" class="text-indigo-500 hover:text-indigo-700">
                                            {{ $invoice->tenant->property->building->display_name ?? $invoice->tenant->property->building->address }}
                                        </a>
                                    </div>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <x-status-badge :status="$invoice->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($invoice->due_date)
                                {{ $invoice->due_date->format('M d, Y') }}
                                @if($invoice->paid_at)
                                    <div class="text-xs text-green-600">{{ $invoice->paid_at->format('M d, Y') }}</div>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('filament.admin.resources.invoices.view', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">
                                    {{ __('common.view') ?? 'View' }}
                                </a>
                                <a href="{{ route('filament.admin.resources.invoices.edit', $invoice) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') ?? 'Edit' }}
                                </a>
                                <form action="{{ route('filament.admin.resources.invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') ?? 'Are you sure?' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') ?? 'Delete' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-slate-500">
                            {{ __('superadmin.dashboard.organization_show.resources.invoices.empty') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
        @endif
    </x-card>

    {{-- Tenants --}}
    <x-card id="tenants-section">
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
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('superadmin.dashboard.organization_show.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('filament.admin.resources.tenants.edit', $tenant) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($tenant->property)
                            <a href="{{ route('filament.admin.resources.properties.edit', $tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->property->address }}
                            </a>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('filament.admin.resources.tenants.edit', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') ?? 'Edit' }}
                                </a>
                                <form action="{{ route('filament.admin.resources.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') ?? 'Are you sure?' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') ?? 'Delete' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-slate-500">
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
