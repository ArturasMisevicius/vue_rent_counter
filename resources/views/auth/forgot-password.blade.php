@section('title', __('auth.forgot_password_title'))
    <div class="space-y-8">
        <div class="space-y-3 text-center">
            <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('auth.forgot_password_title') }}</h1>
            <p class="text-sm text-slate-600">{{ __('auth.forgot_password_subtitle') }}</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" data-auth-form novalidate>
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
                @error('email')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                data-submit-button
                data-loading-text="{{ __('auth.send_reset_link_button_loading') }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-ink px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-ink/20 transition hover:bg-brand-ink/95 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span data-submit-label>{{ __('auth.send_reset_link_button') }}</span>
                <span data-submit-spinner class="hidden items-center gap-2">
                    <span class="size-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                    <span>{{ __('auth.send_reset_link_button_loading') }}</span>
                </span>
            </button>
        </form>
    </div>
