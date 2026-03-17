@extends('layouts.guest')

@section('title', __('auth.reset_password_title'))

@section('content')
    <div class="space-y-8">
        <div class="space-y-3 text-center">
            <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('auth.reset_password_title') }}</h1>
        </div>

        <form
            method="POST"
            action="{{ route('password.update') }}"
            class="space-y-5"
            data-auth-form
            data-password-mismatch="{{ __('auth.password_confirmation_mismatch') }}"
            novalidate
        >
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">

            @error('email')
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    {{ $message }}
                </div>
            @enderror

            <div class="space-y-2">
                <label for="password" class="text-sm font-semibold text-slate-800">{{ __('auth.new_password_label') }}</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    data-password-field
                    required
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-warm focus:ring-2 focus:ring-brand-warm/20"
                />
                @error('password')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-semibold text-slate-800">{{ __('auth.confirm_password_label') }}</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    data-password-confirmation-field
                    required
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-warm focus:ring-2 focus:ring-brand-warm/20"
                />
                <p data-password-confirmation-error class="hidden text-sm text-rose-600"></p>
                @error('password_confirmation')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                data-submit-button
                data-loading-text="{{ __('auth.reset_password_button_loading') }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-ink px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-ink/20 transition hover:bg-brand-ink/95 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span data-submit-label>{{ __('auth.reset_password_button') }}</span>
                <span data-submit-spinner class="hidden items-center gap-2">
                    <span class="size-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                    <span>{{ __('auth.reset_password_button_loading') }}</span>
                </span>
            </button>
        </form>
    </div>
@endsection
