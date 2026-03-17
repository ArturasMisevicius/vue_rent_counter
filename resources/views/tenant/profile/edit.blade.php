<x-layouts.tenant
    :title="__('tenant.pages.profile.title').' · '.config('app.name', 'Tenanto')"
    :heading="__('tenant.pages.profile.heading')"
    :breadcrumbs="[
        ['label' => __('tenant.navigation.home'), 'url' => route('tenant.home')],
        ['label' => __('tenant.pages.profile.heading')],
    ]"
>
    <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="space-y-3">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.navigation.profile') }}</p>
                <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('tenant.pages.profile.page_heading') }}</h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.pages.profile.description') }}</p>
            </div>

            @if (session('status') === 'tenant-profile-updated')
                <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                    {{ __('tenant.pages.profile.success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="name" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.profile.name') }}</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $tenant->name) }}"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    />
                    @error('name')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="email" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.profile.email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $tenant->email) }}"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    />
                    @error('email')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="locale" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.profile.locale') }}</label>
                    <select
                        id="locale"
                        name="locale"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    >
                        @foreach ($supportedLocales as $locale => $label)
                            <option value="{{ $locale }}" @selected(old('locale', $tenant->locale) === $locale)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('locale')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900"
                >
                    {{ __('tenant.actions.save_profile') }}
                </button>
            </form>
        </section>

        <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.profile.security_eyebrow') }}</p>
                <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.profile.password_heading') }}</h3>
                <p class="text-sm leading-6 text-slate-600">{{ __('tenant.pages.profile.password_description') }}</p>
            </div>

            @if (session('status') === 'tenant-password-updated')
                <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                    {{ __('tenant.pages.profile.password_success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.profile.password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="current_password" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.profile.current_password') }}</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    />
                    @error('current_password')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.profile.new_password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    />
                    @error('password')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.profile.confirm_password') }}</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    />
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    {{ __('tenant.actions.update_password') }}
                </button>
            </form>
        </section>
    </div>
</x-layouts.tenant>
