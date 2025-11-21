@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Create New Organization</h1>
            <p class="text-gray-600 mt-2">Create a new admin account with subscription</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('superadmin.organizations.store') }}">
                @csrf

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
                                value="{{ old('organization_name') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('organization_name') border-red-500 @enderror"
                                required
                            >
                            @error('organization_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
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
                                value="{{ old('name') }}"
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
                                value="{{ old('email') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                                required
                            >
                            @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror"
                                required
                                minlength="8"
                            >
                            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                            @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Subscription Details --}}
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="plan_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Plan Type <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="plan_type" 
                                id="plan_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('plan_type') border-red-500 @enderror"
                                required
                            >
                                <option value="">Select a plan</option>
                                <option value="basic" {{ old('plan_type') === 'basic' ? 'selected' : '' }}>Basic (10 properties, 50 tenants)</option>
                                <option value="professional" {{ old('plan_type') === 'professional' ? 'selected' : '' }}>Professional (50 properties, 200 tenants)</option>
                                <option value="enterprise" {{ old('plan_type') === 'enterprise' ? 'selected' : '' }}>Enterprise (Unlimited)</option>
                            </select>
                            @error('plan_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Expiry Date <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="expires_at" 
                                id="expires_at" 
                                value="{{ old('expires_at', now()->addYear()->format('Y-m-d')) }}"
                                min="{{ now()->addDay()->format('Y-m-d') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('expires_at') border-red-500 @enderror"
                                required
                            >
                            @error('expires_at')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('superadmin.organizations.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Create Organization
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
