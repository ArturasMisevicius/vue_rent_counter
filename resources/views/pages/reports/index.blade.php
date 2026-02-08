@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('manager')
@extends('layouts.app')

@section('title', __('reports.shared.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('reports.shared.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('reports.shared.index.description') }}</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.properties') }}</x-slot>
            <x-slot name="value">{{ $stats['total_properties'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.meters') }}</x-slot>
            <x-slot name="value">{{ $stats['total_meters'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.readings') }}</x-slot>
            <x-slot name="value">{{ $stats['readings_this_month'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.invoices') }}</x-slot>
            <x-slot name="value">{{ $stats['invoices_this_month'] }}</x-slot>
        </x-stat-card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('manager.reports.consumption') }}" class="group relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white p-3 text-indigo-700 ring-1 ring-indigo-100 shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25m-4.5-13.5h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('reports.shared.index.cards.consumption.title') }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('reports.shared.index.cards.consumption.description') }}</p>
                </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-indigo-700">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                {{ __('reports.shared.index.cards.consumption.cta') }}
            </div>
        </a>

        <a href="{{ route('manager.reports.revenue') }}" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white p-3 text-slate-900 ring-1 ring-slate-100 shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('reports.shared.index.cards.revenue.title') }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('reports.shared.index.cards.revenue.description') }}</p>
                </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                {{ __('reports.shared.index.cards.revenue.cta') }}
            </div>
        </a>

        <a href="{{ route('manager.reports.meter-reading-compliance') }}" class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white p-3 text-emerald-700 ring-1 ring-emerald-100 shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('reports.shared.index.cards.compliance.title') }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('reports.shared.index.cards.compliance.description') }}</p>
                </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ __('reports.shared.index.cards.compliance.cta') }}
            </div>
        </a>
    </div>

    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.index.guide.title') }}</x-slot>
            
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p><strong class="text-slate-900">{{ __('reports.shared.index.guide.items.consumption.title') }}:</strong> {{ __('reports.shared.index.guide.items.consumption.body') }}</p>
                <p><strong class="text-slate-900">{{ __('reports.shared.index.guide.items.revenue.title') }}:</strong> {{ __('reports.shared.index.guide.items.revenue.body') }}</p>
                <p><strong class="text-slate-900">{{ __('reports.shared.index.guide.items.compliance.title') }}:</strong> {{ __('reports.shared.index.guide.items.compliance.body') }}</p>
            </div>
        </x-card>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('reports.shared.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('reports.shared.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('reports.shared.index.description') }}</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.properties') }}</x-slot>
            <x-slot name="value">{{ $stats['total_properties'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.meters') }}</x-slot>
            <x-slot name="value">{{ $stats['total_meters'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.readings') }}</x-slot>
            <x-slot name="value">{{ $stats['readings_this_month'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">{{ __('reports.shared.index.stats.invoices') }}</x-slot>
            <x-slot name="value">{{ $stats['invoices_this_month'] }}</x-slot>
        </x-stat-card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('manager.reports.consumption') }}" class="group relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white p-3 text-indigo-700 ring-1 ring-indigo-100 shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25m-4.5-13.5h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('reports.shared.index.cards.consumption.title') }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('reports.shared.index.cards.consumption.description') }}</p>
                </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-indigo-700">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                {{ __('reports.shared.index.cards.consumption.cta') }}
            </div>
        </a>

        <a href="{{ route('manager.reports.revenue') }}" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white p-3 text-slate-900 ring-1 ring-slate-100 shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('reports.shared.index.cards.revenue.title') }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('reports.shared.index.cards.revenue.description') }}</p>
                </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                {{ __('reports.shared.index.cards.revenue.cta') }}
            </div>
        </a>

        <a href="{{ route('manager.reports.meter-reading-compliance') }}" class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white p-3 text-emerald-700 ring-1 ring-emerald-100 shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('reports.shared.index.cards.compliance.title') }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ __('reports.shared.index.cards.compliance.description') }}</p>
                </div>
            </div>
            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ __('reports.shared.index.cards.compliance.cta') }}
            </div>
        </a>
    </div>

    <div class="mt-8">
        <x-card>
            <x-slot name="title">{{ __('reports.shared.index.guide.title') }}</x-slot>
            
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p><strong class="text-slate-900">{{ __('reports.shared.index.guide.items.consumption.title') }}:</strong> {{ __('reports.shared.index.guide.items.consumption.body') }}</p>
                <p><strong class="text-slate-900">{{ __('reports.shared.index.guide.items.revenue.title') }}:</strong> {{ __('reports.shared.index.guide.items.revenue.body') }}</p>
                <p><strong class="text-slate-900">{{ __('reports.shared.index.guide.items.compliance.title') }}:</strong> {{ __('reports.shared.index.guide.items.compliance.body') }}</p>
            </div>
        </x-card>
    </div>
</div>
@endsection
@endswitch
