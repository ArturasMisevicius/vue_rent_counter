@extends('layouts.guest')

@section('title', __('auth.login_title'))

@section('content')
    @php($loginError = $errors->first('email'))

    <div class="space-y-8">
        <div class="space-y-3 text-center">
            <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('auth.login_title') }}</h1>
            <p class="text-sm text-slate-600">{{ __('auth.login_subtitle') }}</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session()->has('auth.session_expired'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                {{ session('auth.session_expired') }}
            </div>
        @endif

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

        @if (($demoAccounts ?? []) !== [])
            <section class="rounded-[1.75rem] border border-slate-200 bg-slate-50/80 p-5">
                <div class="mb-4 space-y-1">
                    <h2 class="font-display text-2xl tracking-tight text-slate-950">Demo Accounts</h2>
                    <p class="text-sm text-slate-600">Use these seeded credentials for role-based system testing. Click a username to autofill the login form.</p>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Username</th>
                                <th class="px-4 py-3 font-semibold">Password</th>
                                <th class="px-4 py-3 font-semibold">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            @forelse ($demoAccounts as $account)
                                <tr>
                                    <td class="px-4 py-3">
                                        <button
                                            type="button"
                                            data-demo-account
                                            data-demo-email="{{ $account['email'] }}"
                                            data-demo-password="{{ $account['password'] }}"
                                            data-demo-account-trigger
                                            data-demo-account-email="{{ $account['email'] }}"
                                            data-demo-account-password="{{ $account['password'] }}"
                                            class="rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs text-slate-900 transition hover:bg-brand-warm/15 hover:text-slate-950"
                                        >
                                            {{ $account['email'] }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $account['password'] }}</td>
                                    <td class="px-4 py-3">{{ $account['role'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-sm text-slate-500">No demo accounts are available yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
