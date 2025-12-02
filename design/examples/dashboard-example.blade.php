{{-- 
    Dashboard Example using daisyUI Components
    This demonstrates a complete dashboard layout with stats, cards, tables, and charts
--}}

@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold">{{ __('Dashboard') }}</h1>
            <p class="text-base-content/70 mt-1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</p>
        </div>
        
        <div class="flex gap-2">
            <button class="btn btn-ghost btn-circle">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>
            
            <button class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('New Reading') }}
            </button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="stats stats-vertical lg:stats-horizontal shadow w-full">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <div class="stat-title">{{ __('Total Properties') }}</div>
            <div class="stat-value text-primary">{{ $stats['properties'] ?? 0 }}</div>
            <div class="stat-desc">{{ __('Across all buildings') }}</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-secondary">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="stat-title">{{ __('Active Meters') }}</div>
            <div class="stat-value text-secondary">{{ $stats['meters'] ?? 0 }}</div>
            <div class="stat-desc">{{ __('Monitoring consumption') }}</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-success">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="stat-title">{{ __('Pending Invoices') }}</div>
            <div class="stat-value text-success">{{ $stats['invoices'] ?? 0 }}</div>
            <div class="stat-desc">{{ __('Awaiting payment') }}</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-warning">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">{{ __('Total Revenue') }}</div>
            <div class="stat-value text-warning">€{{ number_format($stats['revenue'] ?? 0, 2) }}</div>
            <div class="stat-desc">{{ __('This month') }}</div>
        </div>
    </div>

    {{-- Quick Actions & Recent Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Quick Actions --}}
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">{{ __('Quick Actions') }}</h2>
                
                <div class="space-y-2 mt-4">
                    <a href="{{ route('manager.meter-readings.create') }}" class="btn btn-outline btn-primary w-full justify-start">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Add Meter Reading') }}
                    </a>
                    
                    <a href="{{ route('manager.invoices.create') }}" class="btn btn-outline btn-secondary w-full justify-start">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Generate Invoice') }}
                    </a>
                    
                    <a href="{{ route('manager.reports.index') }}" class="btn btn-outline btn-accent w-full justify-start">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        {{ __('View Reports') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="card bg-base-100 shadow-xl lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">{{ __('Recent Activity') }}</h2>
                
                <div class="overflow-x-auto mt-4">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>{{ __('Activity') }}</th>
                                <th>{{ __('Property') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities ?? [] as $activity)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder">
                                            <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                <span class="text-xs">{{ substr($activity->type, 0, 2) }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold">{{ $activity->description }}</div>
                                            <div class="text-sm opacity-50">{{ $activity->user->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $activity->property->address ?? 'N/A' }}</td>
                                <td>{{ $activity->created_at->diffForHumans() }}</td>
                                <td>
                                    <span class="badge badge-{{ $activity->status_color }}">
                                        {{ $activity->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-base-content/50">
                                    {{ __('No recent activity') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts & Notifications --}}
    @if($alerts ?? false)
    <div class="space-y-2">
        @foreach($alerts as $alert)
        <div class="alert alert-{{ $alert->type }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                @if($alert->type === 'warning')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                @elseif($alert->type === 'error')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                @endif
            </svg>
            <span>{{ $alert->message }}</span>
            @if($alert->action_url)
            <div>
                <a href="{{ $alert->action_url }}" class="btn btn-sm btn-ghost">
                    {{ $alert->action_text }}
                </a>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
