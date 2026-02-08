@extends('layouts.tenant')

@section('title', __('shared.property.meters_title'))

@section('tenant-content')
<x-tenant.page :title="__('shared.property.meters_title')" :description="__('shared.property.meters_description')">
    @if($meters->count() > 0)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($meters as $meter)
                <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('shared.meters.labels.type') }}</p>
                            <p class="text-base font-semibold text-slate-900">{{ $meter->getServiceDisplayName() }}</p>
                        </div>
                        <x-status-badge status="active">{{ __('shared.property.meter_status') }}</x-status-badge>
                    </div>

                    <p class="mt-2 text-sm text-slate-700">
                        <span class="font-semibold text-slate-800">{{ __('shared.property.labels.serial') }}</span> {{ $meter->serial_number }}
                    </p>

                    <div class="mt-4">
                        <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('shared.property.view_details') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-600">{{ __('shared.property.no_meters') }}</p>
    @endif
</x-tenant.page>
@endsection
