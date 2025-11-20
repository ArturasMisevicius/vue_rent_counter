@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :href="route('admin.users.index')">Users</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Create</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Create User</h1>
            <p class="mt-2 text-sm text-gray-700">Add a new user to the system</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input 
                        name="name" 
                        label="Name" 
                        :value="old('name')" 
                        required 
                    />

                    <x-form-input 
                        name="email" 
                        label="Email" 
                        type="email" 
                        :value="old('email')" 
                        required 
                    />

                    <x-form-select 
                        name="tenant_id" 
                        label="Tenant" 
                        :options="$tenants->pluck('name', 'id')" 
                        :selected="old('tenant_id')" 
                        required 
                    />

                    <x-form-select 
                        name="role" 
                        label="Role" 
                        :options="['admin' => 'Administrator', 'manager' => 'Manager', 'tenant' => 'Tenant']" 
                        :selected="old('role')" 
                        required 
                    />

                    <x-form-input 
                        name="password" 
                        label="Password" 
                        type="password" 
                        required 
                    />

                    <x-form-input 
                        name="password_confirmation" 
                        label="Confirm Password" 
                        type="password" 
                        required 
                    />
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-3">
                    <a href="{{ route('admin.users.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create User
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
