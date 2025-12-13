@extends('layouts.tenant')

@section('title', __('tenant.meters.show_title', ['serial' => $meter->serial_number]))

@section('tenant-content')
<x-tenant.page
    :title="__('tenant.meters.show_title', ['serial' => $meter->serial_number])"
    :description="__('tenant.meters.show_description', ['type' => ($meter->serviceConfiguration?->utilityService?->name ?? enum_label($meter->type)), 'property' => $meter->property->address ?? __('tenant.property.title')])"
>
    <x-slot name="actions">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tenant.meters.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                &larr; {{ __('tenant.meters.back') }}
            </a>
            <a href="{{ route('tenant.meter-readings.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                {{ __('tenant.meters.view_all_readings') }}
            </a>
        </div>
    </x-slot>

    <x-tenant.meter-details :meter="$meter" />
</x-tenant.page>
@endsection
