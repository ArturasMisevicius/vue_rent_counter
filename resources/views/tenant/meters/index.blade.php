@extends('layouts.tenant')

@section('title', __('tenant.meters.index_title'))

@section('tenant-content')
<x-tenant.page :title="__('tenant.meters.index_title')" :description="__('tenant.meters.index_description')">
    @if($metersCollection->isEmpty())
        <x-tenant.alert type="info" :title="__('tenant.meters.empty_title')">
            {{ __('tenant.meters.empty_body') }}
        </x-tenant.alert>
    @else
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-4 shadow-sm">
                <div class="absolute -left-4 -top-6 h-24 w-24 rounded-full bg-indigo-500/10 blur-3xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-700">{{ __('tenant.meters.overview.title') }}</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $metersCollection->count() }}</p>
                <p class="text-sm text-slate-600">{{ __('tenant.meters.overview.active') }}</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-white p-4 shadow-sm">
                <div class="absolute -right-5 -top-8 h-24 w-24 rounded-full bg-sky-400/10 blur-3xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700">{{ __('tenant.meters.overview.zones') }}</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $metersCollection->where('supports_zones', true)->count() }}</p>
                <p class="text-sm text-slate-600">{{ __('tenant.meters.overview.zones_hint') }}</p>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white p-4 shadow-sm">
                <div class="absolute -right-4 -bottom-10 h-24 w-24 rounded-full bg-emerald-400/10 blur-3xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">{{ __('tenant.meters.overview.latest_update') }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $latestReadingDate ? $latestReadingDate->format('M d, Y') : __('tenant.meters.overview.no_readings') }}
                </p>
                <p class="text-sm text-slate-600">{{ __('tenant.meters.overview.recency_hint') }}</p>
            </div>
        </div>

        <x-tenant.section-card class="mt-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('tenant.meters.list_title') }}</h2>
                    <p class="text-sm text-slate-600">{{ __('tenant.meters.list_description') }}</p>
                </div>
                <a href="{{ route('tenant.meter-readings.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('tenant.meters.all_readings') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($meters as $meter)
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-md shadow-slate-200/60 transition hover:border-indigo-200">
                        <div class="absolute inset-0 bg-gradient-to-br {{ ($meterStyleMap[$meter->id]['halo'] ?? 'from-indigo-200/70 via-white to-white') }}"></div>
                        <div class="absolute right-4 top-4 h-16 w-16 rounded-full bg-slate-200/40 blur-3xl"></div>
                        <div class="relative flex flex-col gap-4 p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ ($meterStyleMap[$meter->id]['chip'] ?? 'bg-indigo-100 text-indigo-800') }}">
                                        <span class="text-base">&bull;</span>
                                        {{ $meter->getServiceDisplayName() }}
                                    </span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-900/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-700">
                                        {{ $meter->supports_zones ? __('tenant.meters.labels.day_night') : __('tenant.meters.labels.single_zone') }}
                                    </span>
                                </div>
                                <x-status-badge status="active">{{ __('tenant.meters.status_active') }}</x-status-badge>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-white/80 px-4 py-3 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meters.labels.serial') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $meter->serial_number }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meters.labels.latest') }}</p>
                                    <div class="mt-1 flex items-baseline gap-2">
                                        @if($meter->readings->first())
                                            <p class="text-xl font-semibold text-slate-900">
                                                {{ number_format($meter->readings->first()->getEffectiveValue(), 2) }}
                                            </p>
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $meter->getUnitOfMeasurement() }}</p>
                                        @else
                                            <span class="text-sm text-slate-500">{{ __('tenant.meters.labels.not_recorded') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meters.labels.updated') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $meter->readings->first() ? $meter->readings->first()->reading_date->format('Y-m-d') : '—' }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ __('tenant.meters.overview.latest_update') }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('tenant.meters.view_history') }}
                                </a>
                                <a href="{{ route('tenant.meter-readings.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('tenant.meters.all_readings') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($meters instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-6">
                    {{ $meters->links() }}
                </div>
            @endif
        </x-tenant.section-card>
    @endif
</x-tenant.page>
@endsection
