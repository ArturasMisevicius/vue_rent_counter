@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Subscription Details</h1>
                <p class="text-slate-600 mt-2">{{ $subscription->user->organization_name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.subscriptions.edit', $subscription) }}" class="px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                    Edit
                </a>
                <a href="{{ route('superadmin.subscriptions.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">
                    Back
                </a>
            </div>
        </div>

        {{-- Subscription Details --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <x-card>
                <h2 class="text-xl font-semibold mb-4">Subscription Information</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Plan Type</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $subscription->plan_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Status</dt>
                        <dd class="mt-1">
                            <x-status-badge :status="$subscription->status" />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Start Date</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $subscription->starts_at->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Expiry Date</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $subscription->expires_at->format('M d, Y') }}
                            <span class="text-slate-500">({{ $subscription->expires_at->diffForHumans() }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Days Until Expiry</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if($subscription->isExpired())
                            <span class="text-red-600 font-medium">{{ enum_label(\App\Enums\SubscriptionStatus::EXPIRED->value, \App\Enums\SubscriptionStatus::class) }}</span>
                            @else
                            {{ $subscription->daysUntilExpiry() }} days
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card>
                <h2 class="text-xl font-semibold mb-4">Organization</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Organization Name</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $subscription->user->organization_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Contact Name</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $subscription->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Email</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $subscription->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Tenant ID</dt>
                        <dd class="mt-1 text-sm text-slate-900 font-mono">{{ $subscription->user->tenant_id }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <a href="{{ route('superadmin.organizations.show', $subscription->user) }}" class="text-blue-600 hover:text-blue-800">
                        View Organization →
                    </a>
                </div>
            </x-card>
        </div>

        {{-- Usage Statistics --}}
        <x-card class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Usage Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-slate-700">Properties</span>
                        <span class="text-sm text-slate-500">{{ $usage['properties_used'] }} / {{ $usage['properties_limit'] }}</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $usage['properties_limit'] > 0 ? min(($usage['properties_used'] / $usage['properties_limit']) * 100, 100) : 0 }}%"></div>
                    </div>
                    @if($usage['properties_used'] >= $usage['properties_limit'])
                    <p class="text-xs text-red-600 mt-1">Limit reached</p>
                    @endif
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-slate-700">Tenants</span>
                        <span class="text-sm text-slate-500">{{ $usage['tenants_used'] }} / {{ $usage['tenants_limit'] }}</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $usage['tenants_limit'] > 0 ? min(($usage['tenants_used'] / $usage['tenants_limit']) * 100, 100) : 0 }}%"></div>
                    </div>
                    @if($usage['tenants_used'] >= $usage['tenants_limit'])
                    <p class="text-xs text-red-600 mt-1">Limit reached</p>
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Actions --}}
        <x-card>
            <h2 class="text-xl font-semibold mb-4">Actions</h2>
            <div class="space-y-4">
                {{-- Renew Subscription --}}
                @if(
                    $subscription->status === \App\Enums\SubscriptionStatus::ACTIVE ||
                    $subscription->status === \App\Enums\SubscriptionStatus::EXPIRED
                )
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded">
                    <div>
                        <h3 class="font-medium text-slate-900">Renew Subscription</h3>
                        <p class="text-sm text-slate-600">Extend the subscription expiry date</p>
                    </div>
                    <button 
                        onclick="document.getElementById('renewModal').classList.remove('hidden')"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Renew
                    </button>
                </div>
                @endif

                {{-- Suspend Subscription --}}
                @if($subscription->status === \App\Enums\SubscriptionStatus::ACTIVE)
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded">
                    <div>
                        <h3 class="font-medium text-slate-900">Suspend Subscription</h3>
                        <p class="text-sm text-slate-600">Temporarily suspend access</p>
                    </div>
                    <button 
                        onclick="document.getElementById('suspendModal').classList.remove('hidden')"
                        class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                    >
                        Suspend
                    </button>
                </div>
                @endif

                {{-- Cancel Subscription --}}
                @if($subscription->status !== \App\Enums\SubscriptionStatus::CANCELLED->value)
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded">
                    <div>
                        <h3 class="font-medium text-slate-900">Cancel Subscription</h3>
                        <p class="text-sm text-slate-600">Permanently cancel the subscription</p>
                    </div>
                    <form method="POST" action="{{ route('superadmin.subscriptions.cancel', $subscription) }}" class="inline">
                        @csrf
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                            onclick="return confirm('Are you sure you want to cancel this subscription? This action cannot be undone.')"
                        >
                            Cancel
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </x-card>
    </div>
</div>

{{-- Renew Modal --}}
<div id="renewModal" class="hidden fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-slate-900 mb-4">Renew Subscription</h3>
        <form method="POST" action="{{ route('superadmin.subscriptions.renew', $subscription) }}">
            @csrf
            <div class="mb-4">
                <label for="expires_at" class="block text-sm font-medium text-slate-700 mb-1">
                    New Expiry Date
                </label>
                <input 
                    type="date" 
                    name="expires_at" 
                    id="expires_at" 
                    value="{{ now()->addYear()->format('Y-m-d') }}"
                    min="{{ now()->addDay()->format('Y-m-d') }}"
                    class="w-full px-3 py-2 border border-slate-300 rounded"
                    required
                >
            </div>
            <div class="flex justify-end gap-2">
                <button 
                    type="button" 
                    onclick="document.getElementById('renewModal').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400"
                >
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Renew
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Suspend Modal --}}
<div id="suspendModal" class="hidden fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-slate-900 mb-4">Suspend Subscription</h3>
        <form method="POST" action="{{ route('superadmin.subscriptions.suspend', $subscription) }}">
            @csrf
            <div class="mb-4">
                <label for="reason" class="block text-sm font-medium text-slate-700 mb-1">
                    Reason for Suspension
                </label>
                <textarea 
                    name="reason" 
                    id="reason" 
                    rows="3"
                    class="w-full px-3 py-2 border border-slate-300 rounded"
                    required
                ></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button 
                    type="button" 
                    onclick="document.getElementById('suspendModal').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400"
                >
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    Suspend
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
