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
