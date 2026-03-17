@extends('layouts.guest')

@section('title', __('auth.login_title'))

@section('content')
    @php($loginError = $errors->first('email'))

    <div class="space-y-8">
        <div class="space-y-3 text-center">
            <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('auth.login_title') }}</h1>
            <p class="text-sm text-slate-600">{{ __('auth.login_subtitle') }}</p>
        </div>

        @if (filled($loginError))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ $loginError }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-auth-form novalidate>
            @csrf

            <div class="space-y-2">
                <label for="email" class="text-sm font-semibold text-slate-800">{{ __('auth.email_label') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-warm focus:ring-2 focus:ring-brand-warm/20"
                />
                @if ($errors->has('email') && $loginError !== __('auth.invalid_credentials') && $loginError !== __('auth.account_suspended'))
                    <p class="text-sm text-rose-600">{{ $loginError }}</p>
                @endif
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-4">
                    <label for="password" class="text-sm font-semibold text-slate-800">{{ __('auth.password_label') }}</label>
                    <a href="{{ url('/forgot-password') }}" class="text-sm font-medium text-brand-ink transition hover:text-brand-warm">
                        {{ __('auth.forgot_password_link') }}
                    </a>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-warm focus:ring-2 focus:ring-brand-warm/20"
                />
                @error('password')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                data-submit-button
                data-loading-text="{{ __('auth.login_button_loading') }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-ink px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-ink/20 transition hover:bg-brand-ink/95 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span data-submit-label>{{ __('auth.login_button') }}</span>
                <span data-submit-spinner class="hidden items-center gap-2">
                    <span class="size-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                    <span>{{ __('auth.login_button_loading') }}</span>
                </span>
            </button>

            <p class="text-center text-sm text-slate-500">
                {{ __('auth.no_account_prompt') }}
                <a href="{{ route('register') }}" class="font-semibold text-brand-ink transition hover:text-brand-warm">
                    {{ __('auth.register_link') }}
                </a>
            </p>
        </form>
    </div>
@endsection
