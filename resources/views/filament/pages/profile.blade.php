<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ __('shell.profile.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('shell.profile.title') }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('shell.profile.description') }}</p>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.personal_information.heading') }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.personal_information.description') }}</p>

            <form wire:submit="saveProfile" class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.fields.name') }}</span>
                    <input
                        type="text"
                        wire:model="profileForm.name"
                        value="{{ $profileForm['name'] }}"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.fields.email') }}</span>
                    <input
                        type="email"
                        wire:model="profileForm.email"
                        value="{{ $profileForm['email'] }}"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                    <span>{{ __('shell.profile.fields.locale') }}</span>
                    <select wire:model="profileForm.locale" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        @foreach (config('tenanto.locales', []) as $locale => $label)
                            <option value="{{ $locale }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                    >
                        {{ __('shell.profile.actions.save') }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.password.heading') }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.password.description') }}</p>

            <form wire:submit="updatePassword" class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                    <span>{{ __('shell.profile.fields.current_password') }}</span>
                    <input
                        type="password"
                        wire:model="passwordForm.current_password"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.fields.password') }}</span>
                    <input
                        type="password"
                        wire:model="passwordForm.password"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.fields.password_confirmation') }}</span>
                    <input
                        type="password"
                        wire:model="passwordForm.password_confirmation"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                </label>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                    >
                        {{ __('shell.profile.actions.update_password') }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
