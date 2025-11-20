@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('manager.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Reports</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
            <p class="mt-2 text-sm text-gray-700">Analytics and insights for property management</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Consumption Report -->
        <a href="{{ route('manager.reports.consumption') }}" class="relative block rounded-lg border-2 border-gray-300 bg-white px-6 py-8 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Consumption Report</h3>
                    <p class="mt-1 text-sm text-gray-500">View utility consumption by property</p>
                </div>
            </div>
        </a>

        <!-- Revenue Report -->
        <a href="{{ route('manager.reports.revenue') }}" class="relative block rounded-lg border-2 border-gray-300 bg-white px-6 py-8 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Revenue Report</h3>
                    <p class="mt-1 text-sm text-gray-500">Analyze revenue by period</p>
                </div>
            </div>
        </a>

        <!-- Meter Reading Compliance -->
        <a href="{{ route('manager.reports.meter-reading-compliance') }}" class="relative block rounded-lg border-2 border-gray-300 bg-white px-6 py-8 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Reading Compliance</h3>
                    <p class="mt-1 text-sm text-gray-500">Track meter reading completion</p>
                </div>
            </div>
        </a>
    </div>

    <div class="mt-8">
        <x-card>
            <x-slot name="title">Report Information</x-slot>
            
            <div class="mt-4 space-y-4 text-sm text-gray-600">
                <p><strong>Consumption Report:</strong> View utility consumption patterns across properties. Filter by date range and property to analyze usage trends.</p>
                <p><strong>Revenue Report:</strong> Track billing revenue over time. See total invoiced amounts, paid amounts, and outstanding balances.</p>
                <p><strong>Meter Reading Compliance:</strong> Monitor which properties have submitted meter readings for the current period. Identify properties that need attention.</p>
            </div>
        </x-card>
    </div>
</div>
@endsection
