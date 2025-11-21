@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Organization</h1>
            <p class="text-gray-600 mt-2">Update organization information</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('superadmin.organizations.update', $organization) }}">
                @csrf
                @method('PUT')

                {{-- Organization Information --}}
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Organization Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="organization_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Organization Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="organization_name" 
                                id="organization_name" 
                                value="{{ old('organization_name', $organization->organization_name) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('organization_name') border-red-500 @enderror"
                                required
                            >
                            @error('organization_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tenant ID</label>
                            <input 
                                type="text" 
                                value="{{ $organization->tenant_id }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 font-mono"
                                disabled
                            >
                            <p class="mt-1 text-xs text-gray-500">Tenant ID cannot be changed</p>
                        </div>
                    </div>
                </div>

                {{-- Admin Contact Information --}}
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Contact Information</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Contact Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                value="{{ old('name', $organization->name) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                                required
                            >
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                value="{{ old('email', $organization->email) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                                required
                            >
                            @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Status</h2>
                    
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            id="is_active" 
                            value="1"
                            {{ old('is_active', $organization->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Organization is active
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('superadmin.organizations.show', $organization) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Update Organization
                    </button>
                </div>
            </form>
        </x-card>

        {{-- Subscription Management --}}
        @if($organization->subscription)
        <x-card class="mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Management</h2>
            <p class="text-sm text-gray-600 mb-4">
                To manage subscription details, please use the 
                <a href="{{ route('superadmin.subscriptions.show', $organization->subscription) }}" class="text-blue-600 hover:text-blue-800">
                    subscription management page
                </a>.
            </p>
        </x-card>
        @endif
    </div>
</div>
@endsection
