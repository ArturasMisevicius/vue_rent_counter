@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.users.index')">Users</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ $user->name }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">User Details</h1>
            <p class="mt-2 text-sm text-gray-700">View user information and activity</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-3">
            @can('update', $user)
            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit User
            </a>
            @endcan
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- User Information -->
        <div class="lg:col-span-2">
            <x-card title="User Information">
                <dl class="divide-y divide-gray-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->email }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Role</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <x-status-badge :status="$user->role->value">
                                {{ ucfirst($user->role->value) }}
                            </x-status-badge>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->tenant->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->updated_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            @if($user->meterReadings->isNotEmpty())
            <div class="mt-6">
                <x-card title="Activity History">
                    <div class="text-sm text-gray-700">
                        <p class="mb-2"><strong>Meter Readings Entered:</strong> {{ $user->meterReadings->count() }}</p>
                        <p class="text-xs text-gray-500">This user has entered {{ $user->meterReadings->count() }} meter readings in the system.</p>
                    </div>
                </x-card>
            </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <x-card title="Quick Actions">
                <div class="space-y-3">
                    @can('update', $user)
                    <a href="{{ route('admin.users.edit', $user) }}" class="block w-full rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Edit User
                    </a>
                    @endcan
                    
                    @can('delete', $user)
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            Delete User
                        </button>
                    </form>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
