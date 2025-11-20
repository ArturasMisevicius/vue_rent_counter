@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :active="true">Dashboard</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-sm text-gray-700">System overview and statistics</p>
        </div>
    </div>

    <!-- Primary Stats Grid -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total Users" :value="$stats['total_users']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Total Properties" :value="$stats['total_properties']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Active Meters" :value="$stats['active_meters']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Total Meter Readings" :value="$stats['total_meter_readings']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Secondary Stats Grid -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Buildings" :value="$stats['total_buildings']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Utility Providers" :value="$stats['total_providers']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Active Tariffs" :value="$stats['active_tariffs']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Readings (Last 7 Days)" :value="$stats['recent_readings_count']">
            <x-slot:icon>
                <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- User Role Breakdown -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">User Breakdown</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-stat-card label="Administrators" :value="$stats['admin_count']" />
            <x-stat-card label="Managers" :value="$stats['manager_count']" />
            <x-stat-card label="Tenants" :value="$stats['tenant_count']" />
        </div>
    </div>

    <!-- Invoice Status Breakdown -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Invoice Status</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-stat-card label="Draft Invoices" :value="$stats['draft_invoices']" />
            <x-stat-card label="Finalized Invoices" :value="$stats['finalized_invoices']" />
            <x-stat-card label="Paid Invoices" :value="$stats['paid_invoices']" />
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @can('viewAny', App\Models\User::class)
            <a href="{{ route('admin.users.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Manage Users</h3>
                        <p class="text-sm text-gray-500">User accounts and roles</p>
                    </div>
                </div>
            </a>
            @endcan

            @can('viewAny', App\Models\Provider::class)
            <a href="{{ route('admin.providers.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Manage Providers</h3>
                        <p class="text-sm text-gray-500">Utility service providers</p>
                    </div>
                </div>
            </a>
            @endcan

            @can('viewAny', App\Models\Tariff::class)
            <a href="{{ route('admin.tariffs.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Manage Tariffs</h3>
                        <p class="text-sm text-gray-500">Configure utility pricing</p>
                    </div>
                </div>
            </a>
            @endcan

            <a href="{{ route('admin.audit.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">View Audit Log</h3>
                        <p class="text-sm text-gray-500">System activity history</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.settings.index') }}" class="relative block rounded-lg border border-gray-300 bg-white px-6 py-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">System Settings</h3>
                        <p class="text-sm text-gray-500">Configure system options</p>
                    </div>
                </div>
            </a>

            @can('create', App\Models\User::class)
            <a href="{{ route('admin.users.create') }}" class="relative block rounded-lg border-2 border-dashed border-gray-300 bg-white px-6 py-4 hover:border-indigo-400 hover:bg-indigo-50 transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Create New User</h3>
                        <p class="text-sm text-gray-500">Add a new system user</p>
                    </div>
                </div>
            </a>
            @endcan
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Recent System Activity</h2>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Recent Users -->
            <x-card title="Recently Created Users">
                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200">
                        @forelse($recentActivity['recent_users'] as $user)
                        <li class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $user->name }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        {{ $user->email }}
                                    </p>
                                </div>
                                <div>
                                    <x-status-badge :status="$user->role->value">
                                        {{ ucfirst($user->role->value) }}
                                    </x-status-badge>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="py-4 text-sm text-gray-500">No recent users</li>
                        @endforelse
                    </ul>
                </div>
            </x-card>

            <!-- Recent Invoices -->
            <x-card title="Recently Created Invoices">
                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200">
                        @forelse($recentActivity['recent_invoices'] as $invoice)
                        <li class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        Invoice #{{ $invoice->id }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        {{ $invoice->tenant->name ?? 'N/A' }} - €{{ number_format($invoice->total_amount, 2) }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                                    </p>
                                </div>
                                <div>
                                    <x-status-badge :status="$invoice->status->value">
                                        {{ ucfirst($invoice->status->value) }}
                                    </x-status-badge>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="py-4 text-sm text-gray-500">No recent invoices</li>
                        @endforelse
                    </ul>
                </div>
            </x-card>

            <!-- Recent Meter Readings -->
            <x-card title="Recent Meter Readings">
                <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200">
                        @forelse($recentActivity['recent_readings'] as $reading)
                        <li class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ ucfirst(str_replace('_', ' ', $reading->meter->type->value)) }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        {{ $reading->meter->property->address ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $reading->reading_date->format('M d, Y') }} - {{ number_format($reading->value, 2) }}
                                    </p>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="py-4 text-sm text-gray-500">No recent readings</li>
                        @endforelse
                    </ul>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
