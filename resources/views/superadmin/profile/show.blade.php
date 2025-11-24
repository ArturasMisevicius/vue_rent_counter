@extends('layouts.app')

@section('title', __('profile.superadmin.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="space-y-2">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('profile.superadmin.heading') }}</h1>
            <p class="text-sm text-slate-600">{{ __('profile.superadmin.description') }}</p>
        </div>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm shadow-emerald-100/80">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 shadow-sm shadow-rose-100/80">
                <p class="text-sm font-semibold text-rose-800">{{ __('profile.superadmin.alerts.errors') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card :title="__('profile.superadmin.profile_form.title')" class="divide-y divide-slate-100">
            <form method="POST" action="{{ route('superadmin.profile.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-800">{{ __('profile.superadmin.profile_form.name') }}</label>
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
                        <label for="email" class="block text-sm font-medium text-slate-800">{{ __('profile.superadmin.profile_form.email') }}</label>
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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-800">{{ __('profile.superadmin.profile_form.password') }}</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p class="mt-1 text-xs text-slate-500">{{ __('profile.superadmin.profile_form.password_hint') }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-800">{{ __('profile.superadmin.profile_form.password_confirmation') }}</label>
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
                        {{ __('profile.superadmin.actions.update_profile') }}
                    </x-button>
                    <x-button href="{{ route('superadmin.dashboard') }}" variant="secondary">
                        {{ __('app.nav.dashboard') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
