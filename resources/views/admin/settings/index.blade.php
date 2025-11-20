@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Settings</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">System Settings</h1>
            <p class="mt-2 text-sm text-gray-700">Configure system options and maintenance tasks</p>
        </div>
    </div>

    <!-- System Statistics -->
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </x-slot>
            <x-slot name="label">Total Users</x-slot>
            <x-slot name="value">{{ $stats['total_users'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
            </x-slot>
            <x-slot name="label">Total Properties</x-slot>
            <x-slot name="value">{{ $stats['total_properties'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                </svg>
            </x-slot>
            <x-slot name="label">Total Meters</x-slot>
            <x-slot name="value">{{ $stats['total_meters'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </x-slot>
            <x-slot name="label">Total Invoices</x-slot>
            <x-slot name="value">{{ $stats['total_invoices'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </x-slot>
            <x-slot name="label">Database Size</x-slot>
            <x-slot name="value">{{ $stats['database_size'] }}</x-slot>
        </x-stat-card>

        <x-stat-card>
            <x-slot name="icon">
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </x-slot>
            <x-slot name="label">Cache Size</x-slot>
            <x-slot name="value">{{ $stats['cache_size'] }}</x-slot>
        </x-stat-card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- System Information -->
        <x-card title="System Information">
            <dl class="divide-y divide-gray-200">
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Laravel Version</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ app()->version() }}</dd>
                </div>
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ PHP_VERSION }}</dd>
                </div>
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Database</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">SQLite (WAL Mode)</dd>
                </div>
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Environment</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        <x-status-badge :status="app()->environment()">
                            {{ ucfirst(app()->environment()) }}
                        </x-status-badge>
                    </dd>
                </div>
                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Timezone</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ config('app.timezone') }}</dd>
                </div>
            </dl>
        </x-card>

        <!-- Maintenance Tasks -->
        <x-card title="Maintenance Tasks">
            <div class="space-y-4">
                @can('clearCache')
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Clear Cache</h4>
                        <p class="text-sm text-gray-500">Clear application, config, route, and view caches</p>
                    </div>
                    <form action="{{ route('admin.settings.clear-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Clear Cache
                        </button>
                    </form>
                </div>
                @endcan

                @can('runBackup')
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Run Backup</h4>
                        <p class="text-sm text-gray-500">Create a backup of the database</p>
                    </div>
                    <form action="{{ route('admin.settings.run-backup') }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Run Backup
                        </button>
                    </form>
                </div>
                @endcan
            </div>
        </x-card>
    </div>

    <!-- Application Settings -->
    @can('updateSettings')
    <div class="mt-6">
        <x-card title="Application Settings">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700">Application Name</label>
                        <input 
                            type="text" 
                            name="app_name" 
                            id="app_name" 
                            value="{{ old('app_name', config('app.name')) }}" 
                            @class([
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 sm:text-sm',
                                'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('app_name'),
                                'border-gray-300 focus:border-indigo-500' => !$errors->has('app_name'),
                            ])
                        >
                        @error('app_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">The name of your application</p>
                    </div>

                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                        <select 
                            name="timezone" 
                            id="timezone" 
                            @class([
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-indigo-500 sm:text-sm',
                                'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500' => $errors->has('timezone'),
                                'border-gray-300 focus:border-indigo-500' => !$errors->has('timezone'),
                            ])
                        >
                            <option value="Europe/Vilnius" {{ old('timezone', config('app.timezone')) === 'Europe/Vilnius' ? 'selected' : '' }}>Europe/Vilnius</option>
                            <option value="UTC" {{ old('timezone', config('app.timezone')) === 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">The default timezone for the application</p>
                    </div>

                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Note</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>Settings changes require updating the .env file and may require application restart.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end">
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Save Settings
                    </button>
                </div>
            </form>
        </x-card>
    </div>
    @endcan
</div>
@endsection
