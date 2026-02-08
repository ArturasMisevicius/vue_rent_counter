@extends('layouts.app')

@section('title', __('profile.admin.title'))

@section('content')
<x-profile.shell
    :title="$user->role->value === 'admin' ? __('profile.admin.org_title') : __('profile.admin.profile_title')"
    :description="$user->role->value === 'admin' ? __('profile.admin.org_description') : __('profile.admin.profile_description')"
>
    <x-profile.messages :error-title="__('profile.admin.alerts.errors')" />

    @if($user->role->value === 'admin' && isset($subscription))
        @if($subscriptionStatus === 'expired')
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 shadow-sm shadow-rose-100/80">
                <p class="font-semibold">{{ __('profile.admin.alerts.expired_title') }}</p>
                <p class="mt-1">{{ __('profile.admin.alerts.expired_body', ['date' => $subscription->expires_at->format('M d, Y')]) }}</p>
            </div>
        @elseif($subscriptionStatus === 'expiring_soon')
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm shadow-amber-100/80">
                <p class="font-semibold">{{ __('profile.admin.alerts.expiring_title') }}</p>
                <p class="mt-1">{{ __('profile.admin.alerts.expiring_body', ['days' => trans_choice('profile.admin.days', $daysUntilExpiry, ['count' => $daysUntilExpiry]), 'date' => $subscription->expires_at->format('M d, Y')]) }}</p>
            </div>
        @endif

        <x-card :title="__('profile.admin.subscription.card_title')">
            <p class="mb-4 text-sm text-slate-600">{{ __('profile.admin.subscription.description') }}</p>
            <dl class="divide-y divide-slate-200">
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.admin.subscription.plan_type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.admin.subscription.status') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        @if($subscription->isActive())
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ enum_label(\App\Enums\SubscriptionStatus::ACTIVE->value, \App\Enums\SubscriptionStatus::class) }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700">{{ enum_label(\App\Enums\SubscriptionStatus::EXPIRED->value, \App\Enums\SubscriptionStatus::class) }}</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.admin.subscription.start_date') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $subscription->starts_at->format('M d, Y') }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.admin.subscription.expiry_date') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        {{ $subscription->expires_at->format('M d, Y') }}
                        @if($daysUntilExpiry > 0)
                            <span class="text-slate-500">{{ __('profile.admin.subscription.days_remaining', ['days' => trans_choice('profile.admin.days', $daysUntilExpiry, ['count' => $daysUntilExpiry])]) }}</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if(isset($usageStats))
                <div class="mt-6 space-y-4">
                    <h3 class="text-sm font-medium text-slate-900">{{ __('profile.admin.subscription.usage_limits') }}</h3>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ __('profile.admin.subscription.properties') }}</span>
                            <span class="text-sm text-slate-500">{{ $usageStats['properties_used'] }} / {{ $usageStats['properties_max'] }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ min($usageStats['properties_percentage'], 100) }}%"></div>
                        </div>
                        @if($usageStats['properties_percentage'] >= 90)
                            <p class="mt-1 text-xs text-amber-600">{{ __('profile.admin.subscription.approaching_limit') }}</p>
                        @endif
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ __('profile.admin.subscription.tenants') }}</span>
                            <span class="text-sm text-slate-500">{{ $usageStats['tenants_used'] }} / {{ $usageStats['tenants_max'] }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ min($usageStats['tenants_percentage'], 100) }}%"></div>
                        </div>
                        @if($usageStats['tenants_percentage'] >= 90)
                            <p class="mt-1 text-xs text-amber-600">{{ __('profile.admin.subscription.approaching_limit') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </x-card>
    @endif

    <x-card :title="__('profile.admin.profile_form.title')">
        <p class="mb-4 text-sm text-slate-600">{{ __('profile.admin.profile_form.description') }}</p>
        <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.profile_form.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.profile_form.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            @if($user->role->value === 'admin')
                <div>
                    <label for="organization_name" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.profile_form.organization') }}</label>
                    <input type="text" name="organization_name" id="organization_name" value="{{ old('organization_name', $user->organization_name) }}" class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            @endif

            <div>
                <label for="currency" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.profile_form.currency') }}</label>
                <select name="currency" id="currency" class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($currencyOptions as $currencyCode => $currencyLabel)
                        <option value="{{ $currencyCode }}" {{ old('currency', $user->currency ?? 'EUR') === $currencyCode ? 'selected' : '' }}>
                            {{ $currencyLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">{{ __('profile.admin.profile_form.submit') }}</x-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('profile.admin.password_form.title')">
        <p class="mb-4 text-sm text-slate-600">{{ __('profile.admin.password_form.description') }}</p>
        <form action="{{ route('admin.profile.update-password') }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.password_form.current') }}</label>
                <input type="password" name="current_password" id="current_password" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.password_form.new') }}</label>
                    <input type="password" name="password" id="password" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-800">{{ __('profile.admin.password_form.confirm') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">{{ __('profile.admin.password_form.submit') }}</x-button>
            </div>
        </form>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('profile.admin.language_form.title')"
        :description="__('profile.admin.language_form.description')"
    />
</x-profile.shell>
@endsection
