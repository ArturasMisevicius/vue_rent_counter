@extends('layouts.app')

@section('title', 'Organization Profile')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item href="{{ route('admin.dashboard') }}">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Profile</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">
                @if($user->role->value === 'admin')
                    Organization Profile
                @else
                    Profile
                @endif
            </h1>
            <p class="mt-2 text-sm text-slate-700">
                @if($user->role->value === 'admin')
                    Manage your organization profile and subscription
                @else
                    Manage your profile information
                @endif
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc space-y-1 pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($user->role->value === 'admin' && isset($subscription))
        <!-- Subscription Status Banner -->
        @if($subscriptionStatus === 'expired')
            <div class="mt-6 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800">Subscription Expired</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>Your subscription expired on {{ $subscription->expires_at->format('M d, Y') }}. Please contact support to renew your subscription.</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($subscriptionStatus === 'expiring_soon')
            <div class="mt-6 rounded-md bg-yellow-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-yellow-800">Subscription Expiring Soon</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Your subscription will expire in {{ $daysUntilExpiry }} {{ $daysUntilExpiry === 1 ? 'day' : 'days' }} on {{ $subscription->expires_at->format('M d, Y') }}. Contact support to renew.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Subscription Details -->
        <div class="mt-8">
            <x-card title="Subscription Details">
                <dl class="divide-y divide-slate-200">
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Plan Type</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            {{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}
                        </dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Status</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            @if($subscription->isActive())
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                    {{ enum_label(\App\Enums\SubscriptionStatus::ACTIVE->value, \App\Enums\SubscriptionStatus::class) }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                    {{ enum_label(\App\Enums\SubscriptionStatus::EXPIRED->value, \App\Enums\SubscriptionStatus::class) }}
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Start Date</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            {{ $subscription->starts_at->format('M d, Y') }}
                        </dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">Expiry Date</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            {{ $subscription->expires_at->format('M d, Y') }}
                            @if($daysUntilExpiry > 0)
                                <span class="text-slate-500">({{ $daysUntilExpiry }} days remaining)</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                @if(isset($usageStats))
                    <div class="mt-6 space-y-4">
                        <h3 class="text-sm font-medium text-slate-900">Usage Limits</h3>
                        
                        <!-- Properties Usage -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-slate-700">Properties</span>
                                <span class="text-sm text-slate-500">{{ $usageStats['properties_used'] }} / {{ $usageStats['properties_max'] }}</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usageStats['properties_percentage'], 100) }}%"></div>
                            </div>
                            @if($usageStats['properties_percentage'] >= 90)
                                <p class="mt-1 text-xs text-yellow-600">Approaching limit - consider upgrading your plan</p>
                            @endif
                        </div>

                        <!-- Tenants Usage -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-slate-700">Tenants</span>
                                <span class="text-sm text-slate-500">{{ $usageStats['tenants_used'] }} / {{ $usageStats['tenants_max'] }}</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usageStats['tenants_percentage'], 100) }}%"></div>
                            </div>
                            @if($usageStats['tenants_percentage'] >= 90)
                                <p class="mt-1 text-xs text-yellow-600">Approaching limit - consider upgrading your plan</p>
                            @endif
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    @endif

    <!-- Profile Information -->
    <div class="mt-8">
        <form action="{{ route('admin.profile.update') }}" method="POST">
            @csrf
            @method('PATCH')

            <x-card title="Profile Information">
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium leading-6 text-slate-900">Full Name</label>
                        <div class="mt-2">
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium leading-6 text-slate-900">Email Address</label>
                        <div class="mt-2">
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    @if($user->role->value === 'admin')
                    <div>
                        <label for="organization_name" class="block text-sm font-medium leading-6 text-slate-900">Organization Name</label>
                        <div class="mt-2">
                            <input type="text" name="organization_name" id="organization_name" value="{{ old('organization_name', $user->organization_name) }}"
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center justify-end">
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Update Profile
                        </button>
                    </div>
                </div>
            </x-card>
        </form>
    </div>

    <!-- Change Password -->
    <div class="mt-8">
        <form action="{{ route('admin.profile.update-password') }}" method="POST">
            @csrf
            @method('PATCH')

            <x-card title="Change Password">
                <div class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium leading-6 text-slate-900">Current Password</label>
                        <div class="mt-2">
                            <input type="password" name="current_password" id="current_password" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium leading-6 text-slate-900">New Password</label>
                        <div class="mt-2">
                            <input type="password" name="password" id="password" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium leading-6 text-slate-900">Confirm New Password</label>
                        <div class="mt-2">
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Update Password
                        </button>
                    </div>
                </div>
            </x-card>
        </form>
    </div>
</div>
@endsection
