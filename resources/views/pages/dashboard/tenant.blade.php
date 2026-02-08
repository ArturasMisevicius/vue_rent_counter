@extends('layouts.tenant')

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
