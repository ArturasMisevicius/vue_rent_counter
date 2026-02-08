@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('superadmin')
@extends('layouts.app')

@section('title', __('profile.shared.title'))

@section('content')
<x-profile.shell
    :title="__('profile.shared.heading')"
    :description="__('profile.shared.description')"
>
    <x-profile.messages :error-title="__('profile.shared.alerts.errors')" />

    <x-card :title="__('profile.shared.profile_form.title')" class="divide-y divide-slate-100">
        <p class="mb-4 text-sm text-slate-600">{{ __('profile.shared.profile_form.description') }}</p>
        <form method="POST" action="{{ route('superadmin.profile.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.name') }}</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.email') }}</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div>
                <label for="currency" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.currency') }}</label>
                <select
                    id="currency"
                    name="currency"
                    class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    @foreach($currencyOptions as $currencyCode => $currencyLabel)
                        <option value="{{ $currencyCode }}" {{ old('currency', $user->currency ?? 'EUR') === $currencyCode ? 'selected' : '' }}>
                            {{ $currencyLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.password') }}</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p class="mt-1 text-xs text-slate-500">{{ __('profile.shared.profile_form.password_hint') }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.password_confirmation') }}</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit">
                    {{ __('profile.shared.actions.update_profile') }}
                </x-button>
                <a href="{{ route('superadmin.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm font-semibold tracking-tight text-slate-800 shadow-sm transition duration-150 hover:bg-white">
                    {{ __('app.nav.dashboard') }}
                </a>
            </div>
        </form>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('profile.shared.language_form.title')"
        :description="__('profile.shared.language_form.description')"
    />
</x-profile.shell>
@endsection
@break

@case('admin')
@extends('layouts.app')

@section('title', __('profile.shared.title'))

@section('content')
<x-profile.shell
    :title="$user->role->value === 'admin' ? __('profile.shared.org_title') : __('profile.shared.profile_title')"
    :description="$user->role->value === 'admin' ? __('profile.shared.org_description') : __('profile.shared.profile_description')"
>
    <x-profile.messages :error-title="__('profile.shared.alerts.errors')" />

    @if($user->role->value === 'admin' && isset($subscription))
        @if($subscriptionStatus === 'expired')
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 shadow-sm shadow-rose-100/80">
                <p class="font-semibold">{{ __('profile.shared.alerts.expired_title') }}</p>
                <p class="mt-1">{{ __('profile.shared.alerts.expired_body', ['date' => $subscription->expires_at->format('M d, Y')]) }}</p>
            </div>
        @elseif($subscriptionStatus === 'expiring_soon')
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm shadow-amber-100/80">
                <p class="font-semibold">{{ __('profile.shared.alerts.expiring_title') }}</p>
                <p class="mt-1">{{ __('profile.shared.alerts.expiring_body', ['days' => trans_choice('profile.admin.days', $daysUntilExpiry, ['count' => $daysUntilExpiry]), 'date' => $subscription->expires_at->format('M d, Y')]) }}</p>
            </div>
        @endif

        <x-card :title="__('profile.shared.subscription.card_title')">
            <p class="mb-4 text-sm text-slate-600">{{ __('profile.shared.subscription.description') }}</p>
            <dl class="divide-y divide-slate-200">
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.shared.subscription.plan_type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.shared.subscription.status') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        @if($subscription->isActive())
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ enum_label(\App\Enums\SubscriptionStatus::ACTIVE->value, \App\Enums\SubscriptionStatus::class) }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700">{{ enum_label(\App\Enums\SubscriptionStatus::EXPIRED->value, \App\Enums\SubscriptionStatus::class) }}</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.shared.subscription.start_date') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $subscription->starts_at->format('M d, Y') }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('profile.shared.subscription.expiry_date') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        {{ $subscription->expires_at->format('M d, Y') }}
                        @if($daysUntilExpiry > 0)
                            <span class="text-slate-500">{{ __('profile.shared.subscription.days_remaining', ['days' => trans_choice('profile.admin.days', $daysUntilExpiry, ['count' => $daysUntilExpiry])]) }}</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if(isset($usageStats))
                <div class="mt-6 space-y-4">
                    <h3 class="text-sm font-medium text-slate-900">{{ __('profile.shared.subscription.usage_limits') }}</h3>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ __('profile.shared.subscription.properties') }}</span>
                            <span class="text-sm text-slate-500">{{ $usageStats['properties_used'] }} / {{ $usageStats['properties_max'] }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ min($usageStats['properties_percentage'], 100) }}%"></div>
                        </div>
                        @if($usageStats['properties_percentage'] >= 90)
                            <p class="mt-1 text-xs text-amber-600">{{ __('profile.shared.subscription.approaching_limit') }}</p>
                        @endif
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ __('profile.shared.subscription.tenants') }}</span>
                            <span class="text-sm text-slate-500">{{ $usageStats['tenants_used'] }} / {{ $usageStats['tenants_max'] }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ min($usageStats['tenants_percentage'], 100) }}%"></div>
                        </div>
                        @if($usageStats['tenants_percentage'] >= 90)
                            <p class="mt-1 text-xs text-amber-600">{{ __('profile.shared.subscription.approaching_limit') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </x-card>
    @endif

    <x-card :title="__('profile.shared.profile_form.title')">
        <p class="mb-4 text-sm text-slate-600">{{ __('profile.shared.profile_form.description') }}</p>
        <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            @if($user->role->value === 'admin')
                <div>
                    <label for="organization_name" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.organization') }}</label>
                    <input type="text" name="organization_name" id="organization_name" value="{{ old('organization_name', $user->organization_name) }}" class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            @endif

            <div>
                <label for="currency" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.currency') }}</label>
                <select name="currency" id="currency" class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($currencyOptions as $currencyCode => $currencyLabel)
                        <option value="{{ $currencyCode }}" {{ old('currency', $user->currency ?? 'EUR') === $currencyCode ? 'selected' : '' }}>
                            {{ $currencyLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">{{ __('profile.shared.profile_form.submit') }}</x-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('profile.shared.password_form.title')">
        <p class="mb-4 text-sm text-slate-600">{{ __('profile.shared.password_form.description') }}</p>
        <form action="{{ route('admin.profile.update-password') }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.password_form.current') }}</label>
                <input type="password" name="current_password" id="current_password" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.password_form.new') }}</label>
                    <input type="password" name="password" id="password" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.password_form.confirm') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">{{ __('profile.shared.password_form.submit') }}</x-button>
            </div>
        </form>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('profile.shared.language_form.title')"
        :description="__('profile.shared.language_form.description')"
    />
</x-profile.shell>
@endsection
@break

@case('manager')
@extends('layouts.app')

@section('title', __('shared.profile.title'))

@section('content')
<x-profile.shell
    :title="__('shared.profile.title')"
    :description="__('shared.profile.description')"
>
    <x-profile.messages :error-title="__('shared.profile.alerts.errors')" />

    <x-card :title="__('shared.profile.account_information')">
        <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.account_information_description') }}</p>
        <form method="POST" action="{{ route('manager.profile.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-input name="name" :label="__('shared.profile.labels.name')" :value="$user->name" required />
                <x-form-input name="email" type="email" :label="__('shared.profile.labels.email')" :value="$user->email" required />
            </div>

            <x-form-select
                name="currency"
                :label="__('shared.profile.labels.currency')"
                :options="$currencyOptions"
                :value="$user->currency ?? 'EUR'"
                required
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form-input name="password" type="password" :label="__('shared.profile.password.label')" autocomplete="new-password" />
                <x-form-input name="password_confirmation" type="password" :label="__('shared.profile.password.confirmation')" autocomplete="new-password" />
            </div>
            <p class="text-xs text-slate-500">{{ __('shared.profile.password.hint') }}</p>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit">
                    {{ __('shared.profile.update_profile') }}
                </x-button>
                <a href="{{ route('manager.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm font-semibold tracking-tight text-slate-800 shadow-sm transition duration-150 hover:bg-white">
                    {{ __('app.nav.dashboard') }}
                </a>
            </div>
        </form>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('shared.profile.language_preference')"
        :description="__('shared.profile.language_description')"
    />

    <x-card :title="__('shared.profile.portfolio.title')">
        <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.portfolio.description') }}</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card :label="__('dashboard.shared.stats.total_properties')" :value="$portfolioStats['properties']" icon="🏢" />
            <x-stat-card :label="__('dashboard.shared.stats.active_tenants')" :value="$portfolioStats['tenants']" icon="👥" color="green" />
            <x-stat-card :label="__('dashboard.shared.stats.active_meters')" :value="$portfolioStats['meters']" icon="📟" />
            <x-stat-card :label="__('dashboard.shared.stats.draft_invoices')" :value="$portfolioStats['drafts']" icon="🧾" color="yellow" />
        </div>
    </x-card>
</x-profile.shell>
@endsection
@break

@case('tenant')
@extends('layouts.tenant')

@section('title', __('shared.profile.title'))

@section('tenant-content')
<x-profile.shell
    :title="__('shared.profile.title')"
    :description="__('shared.profile.description')"
>
    <x-profile.messages :error-title="__('shared.profile.alerts.errors')" />

    <x-card :title="__('shared.profile.account_information')">
        <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.account_information_description') }}</p>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.name') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.email') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.role') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ enum_label($user->role) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.created') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->created_at->format('Y-m-d') }}</dd>
            </div>
        </dl>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('shared.profile.language_preference')"
        :description="__('shared.profile.language.description')"
    />

    @if($user->property)
        <x-card :title="__('shared.profile.assigned_property')">
            <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.assigned_property_description') }}</p>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->property->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($user->property->type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.area') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->property->area_sqm }} m²</dd>
                </div>
                @if($user->property->building)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.building') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $user->property->building->display_name }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.property.labels.building_address') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $user->property->building->address }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>
    @endif

    @if($user->parentUser)
        <x-card :title="__('shared.profile.manager_contact.title')">
            <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.manager_contact.description') }}</p>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                @if($user->parentUser->organization_name)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.organization') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $user->parentUser->organization_name }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.contact_name') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->parentUser->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('shared.profile.labels.email') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        <a href="mailto:{{ $user->parentUser->email }}" class="text-indigo-700 font-semibold hover:text-indigo-800">
                            {{ $user->parentUser->email }}
                        </a>
                    </dd>
                </div>
            </dl>
        </x-card>
    @endif

    <x-card :title="__('shared.profile.update_profile')">
        <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.update_description') }}</p>
        <form method="POST" action="{{ route('tenant.profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800">{{ __('shared.profile.labels.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-rose-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800">{{ __('shared.profile.labels.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-rose-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="mb-2 text-sm font-semibold text-slate-900">{{ __('shared.profile.change_password') }}</h4>
                <p class="mb-4 text-sm text-slate-600">{{ __('shared.profile.password_note') }}</p>

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-slate-800">{{ __('shared.profile.labels.current_password') }}</label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('current_password') border-rose-500 @enderror">
                        @error('current_password')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-800">{{ __('shared.profile.labels.new_password') }}</label>
                        <input type="password" name="password" id="password" autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-rose-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-800">{{ __('shared.profile.labels.confirm_password') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">
                    {{ __('shared.profile.save_changes') }}
                </x-button>
            </div>
        </form>
    </x-card>
</x-profile.shell>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('profile.shared.title'))

@section('content')
<x-profile.shell
    :title="__('profile.shared.heading')"
    :description="__('profile.shared.description')"
>
    <x-profile.messages :error-title="__('profile.shared.alerts.errors')" />

    <x-card :title="__('profile.shared.profile_form.title')" class="divide-y divide-slate-100">
        <p class="mb-4 text-sm text-slate-600">{{ __('profile.shared.profile_form.description') }}</p>
        <form method="POST" action="{{ route('superadmin.profile.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.name') }}</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.email') }}</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div>
                <label for="currency" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.currency') }}</label>
                <select
                    id="currency"
                    name="currency"
                    class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    @foreach($currencyOptions as $currencyCode => $currencyLabel)
                        <option value="{{ $currencyCode }}" {{ old('currency', $user->currency ?? 'EUR') === $currencyCode ? 'selected' : '' }}>
                            {{ $currencyLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.password') }}</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p class="mt-1 text-xs text-slate-500">{{ __('profile.shared.profile_form.password_hint') }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-800">{{ __('profile.shared.profile_form.password_confirmation') }}</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit">
                    {{ __('profile.shared.actions.update_profile') }}
                </x-button>
                <a href="{{ route('superadmin.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm font-semibold tracking-tight text-slate-800 shadow-sm transition duration-150 hover:bg-white">
                    {{ __('app.nav.dashboard') }}
                </a>
            </div>
        </form>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('profile.shared.language_form.title')"
        :description="__('profile.shared.language_form.description')"
    />
</x-profile.shell>
@endsection
@endswitch
