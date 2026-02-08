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
