@extends('layouts.app')

@section('title', __('manager.profile.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-manager.page :title="__('manager.profile.title')" :description="__('manager.profile.description')">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm shadow-emerald-100/80">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-manager.section-card :title="__('manager.profile.account_information')" :description="__('manager.profile.description')" class="lg:col-span-2">
                <form method="POST" action="{{ route('manager.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form-input name="name" :label="__('manager.profile.labels.name')" :value="$user->name" required />
                        <x-form-input name="email" type="email" :label="__('manager.profile.labels.email')" :value="$user->email" required />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form-input name="password" type="password" :label="__('manager.profile.password.label')" autocomplete="new-password" />
                        <x-form-input name="password_confirmation" type="password" :label="__('manager.profile.password.confirmation')" autocomplete="new-password" />
                    </div>
                    <p class="text-xs text-slate-500">{{ __('manager.profile.password.hint') }}</p>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <x-button type="submit">
                            {{ __('manager.profile.update_profile') }}
                        </x-button>
                        <x-button href="{{ route('manager.dashboard') }}" variant="secondary">
                            {{ __('app.nav.dashboard') }}
                        </x-button>
                    </div>
                </form>
            </x-manager.section-card>

            <x-manager.section-card :title="__('manager.profile.language_preference')" :description="__('manager.profile.language_description')">
                <form method="POST" action="{{ route('locale.set') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="locale" class="block text-sm font-semibold text-slate-800">{{ __('manager.profile.labels.language') }}</label>
                        <select name="locale" id="locale" onchange="this.form.submit()" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($languages as $language)
                                <option value="{{ $language->code }}" {{ $language->code === app()->getLocale() ? 'selected' : '' }}>
                                    {{ $language->native_name ?? $language->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-sm text-slate-600">{{ __('manager.profile.language_hint') }}</p>
                    </div>
                </form>
            </x-manager.section-card>

            <x-manager.section-card title="Portfolio pulse" description="High-level health of your managed units.">
                <div class="space-y-3">
                    <x-manager.stat-card :label="__('dashboard.manager.stats.total_properties')" :value="$portfolioStats['properties']" tone="indigo" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M3 9.75 12 3l9 6.75M4.5 10.5V21h5.25v-4.5A1.5 1.5 0 0 1 11.25 15h1.5A1.5 1.5 0 0 1 14.25 16.5V21H19.5V10.5' />
</svg>
SVG"/>
                    <x-manager.stat-card :label="__('dashboard.manager.stats.active_tenants')" :value="$portfolioStats['tenants']" tone="emerald" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.75 19.5a4.5 4.5 0 0 1 10.5 0' />
</svg>
SVG"/>
                    <x-manager.stat-card :label="__('dashboard.manager.stats.active_meters')" :value="$portfolioStats['meters']" tone="slate" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 3v6m0 0 3-3m-3 3-3-3m6 6v6m0 0 3-3m-3 3-3-3M6 5.25h-.75A1.5 1.5 0 0 0 3.75 6.75v10.5a1.5 1.5 0 0 0 1.5 1.5H6M18 5.25h.75a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H18' />
</svg>
SVG"/>
                    <x-manager.stat-card :label="__('dashboard.manager.stats.draft_invoices')" :value="$portfolioStats['drafts']" tone="amber" :icon="<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M9 8.25h6m-6 3h3.75M7.5 21h9A2.25 2.25 0 0 0 18.75 18.75V5.25A2.25 2.25 0 0 0 16.5 3h-9A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21z' />
</svg>
SVG"/>
                </div>
            </x-manager.section-card>
        </div>
    </x-manager.page>
</div>
@endsection
