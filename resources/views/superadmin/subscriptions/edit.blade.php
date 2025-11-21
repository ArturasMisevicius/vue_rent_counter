@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Subscription</h1>
            <p class="text-gray-600 mt-2">{{ $subscription->user->organization_name }}</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('superadmin.subscriptions.update', $subscription) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
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
                            <option value="basic" {{ old('plan_type', $subscription->plan_type) === 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="professional" {{ old('plan_type', $subscription->plan_type) === 'professional' ? 'selected' : '' }}>Professional</option>
                            <option value="enterprise" {{ old('plan_type', $subscription->plan_type) === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                        </select>
                        @error('plan_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="status" 
                            id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('status') border-red-500 @enderror"
                            required
                        >
                            <option value="active" {{ old('status', $subscription->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ old('status', $subscription->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="suspended" {{ old('status', $subscription->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="cancelled" {{ old('status', $subscription->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
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
                            value="{{ old('expires_at', $subscription->expires_at->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('expires_at') border-red-500 @enderror"
                            required
                        >
                        @error('expires_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_properties" class="block text-sm font-medium text-gray-700 mb-1">
                            Maximum Properties <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="max_properties" 
                            id="max_properties" 
                            value="{{ old('max_properties', $subscription->max_properties) }}"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('max_properties') border-red-500 @enderror"
                            required
                        >
                        @error('max_properties')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_tenants" class="block text-sm font-medium text-gray-700 mb-1">
                            Maximum Tenants <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="max_tenants" 
                            id="max_tenants" 
                            value="{{ old('max_tenants', $subscription->max_tenants) }}"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 @error('max_tenants') border-red-500 @enderror"
                            required
                        >
                        @error('max_tenants')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-6 border-t mt-6">
                    <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Update Subscription
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
