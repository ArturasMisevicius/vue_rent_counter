@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-manager.page title="Reports" description="Analytics and insights for your managed units.">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('manager.reports.consumption') }}" class="group relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-white p-3 text-indigo-700 ring-1 ring-indigo-100 shadow-sm">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25m-4.5-13.5h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Consumption</p>
                        <p class="mt-1 text-xs text-slate-600">Track usage trends by property, meter type, or date range.</p>
                    </div>
                </div>
                <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-indigo-700">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    Ready to explore
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
                        <p class="text-sm font-semibold text-slate-900">Revenue</p>
                        <p class="mt-1 text-xs text-slate-600">See invoiced, paid, and outstanding amounts over time.</p>
                    </div>
                </div>
                <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Billing health
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
                        <p class="text-sm font-semibold text-slate-900">Compliance</p>
                        <p class="mt-1 text-xs text-slate-600">Spot properties missing meter readings for the current cycle.</p>
                    </div>
                </div>
                <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Stay on schedule
                </div>
            </a>
        </div>

        <x-manager.section-card title="How to use these reports" description="Make billing reviews predictable and confident." class="mt-6">
            <div class="space-y-3 text-sm text-slate-600">
                <p><strong>Consumption:</strong> Compare usage month-over-month, filter by property, and export trends for provider reviews.</p>
                <p><strong>Revenue:</strong> Validate invoicing progress before closing the period and ensure overdue balances are visible.</p>
                <p><strong>Compliance:</strong> Identify meters without current readings and redirect your team to the right properties.</p>
            </div>
        </x-manager.section-card>
    </x-manager.page>
</div>
@endsection
