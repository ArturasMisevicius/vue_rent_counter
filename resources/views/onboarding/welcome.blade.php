@section('title', __('onboarding.title'))
    <div class="space-y-8">
        <div class="space-y-4 text-center">
            <span class="inline-flex items-center rounded-full border border-brand-mint/30 bg-brand-mint/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-brand-ink">
                {{ __('onboarding.trial_badge') }}
            </span>

            <div class="space-y-3">
                <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('onboarding.title') }}</h1>
                <p class="text-sm text-slate-600">{{ __('onboarding.subtitle') }}</p>
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/80 p-5">
            <p class="text-sm leading-6 text-slate-700">{{ __('onboarding.trial_message') }}</p>
        </div>

        <form method="POST" action="{{ route('welcome.store') }}" class="space-y-6" data-auth-form novalidate>
            @csrf

            <div class="space-y-2">
                <label for="name" class="text-sm font-semibold text-slate-800">{{ __('onboarding.organization_name_label') }}</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    autocomplete="organization"
                    required
                    data-slug-source
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-warm focus:ring-2 focus:ring-brand-warm/20"
                />
                @error('name')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="slug" class="text-sm font-semibold text-slate-800">{{ __('onboarding.organization_slug_label') }}</label>
                <input
                    id="slug"
                    name="slug"
                    type="text"
                    value="{{ old('slug') }}"
                    autocapitalize="none"
                    spellcheck="false"
                    required
                    data-slug-target
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-warm focus:ring-2 focus:ring-brand-warm/20"
                />
                <p class="text-sm text-slate-500">{{ __('onboarding.organization_slug_help') }}</p>
                @error('slug')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                data-submit-button
                data-loading-text="{{ __('onboarding.submit_button_loading') }}"
                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-brand-ink px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-ink/20 transition hover:bg-brand-ink/95 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span data-submit-label>{{ __('onboarding.submit_button') }}</span>
                <span data-submit-spinner class="hidden items-center gap-2">
                    <span class="size-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                    <span>{{ __('onboarding.submit_button_loading') }}</span>
                </span>
            </button>
        </form>
    </div>
