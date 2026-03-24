@php($profileNameValue = htmlspecialchars($profileForm['name'], ENT_COMPAT, 'UTF-8', false))
@php($profileEmailValue = htmlspecialchars($profileForm['email'], ENT_COMPAT, 'UTF-8', false))
@php($profilePhoneValue = htmlspecialchars((string) ($profileForm['phone'] ?? ''), ENT_COMPAT, 'UTF-8', false))

<form wire:submit="saveChanges" class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.personal_information.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.personal_information.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.fields.name') }}</span>
                <input
                    type="text"
                    wire:model="profileForm.name"
                    value="{!! $profileNameValue !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('profileForm.name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.fields.email') }}</span>
                <input
                    type="email"
                    wire:model="profileForm.email"
                    value="{!! $profileEmailValue !!}"
                    autocomplete="email"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('profileForm.email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.fields.phone') }}</span>
                <input
                    type="text"
                    wire:model="profileForm.phone"
                    value="{!! $profilePhoneValue !!}"
                    autocomplete="tel"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('profileForm.phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.fields.locale') }}</span>
                <select wire:model.live="profileForm.locale" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    @foreach ($profileLocaleOptions as $locale => $label)
                        <option value="{{ $locale }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('profileForm.locale') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.password.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.password.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <input
                type="email"
                value="{!! $profileEmailValue !!}"
                autocomplete="username"
                tabindex="-1"
                aria-hidden="true"
                class="sr-only"
                readonly
            >

            <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                <span>{{ __('shell.profile.fields.current_password') }}</span>
                <input
                    type="password"
                    wire:model="passwordForm.current_password"
                    autocomplete="current-password"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('passwordForm.current_password') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.fields.password') }}</span>
                <input
                    type="password"
                    wire:model="passwordForm.password"
                    autocomplete="new-password"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('passwordForm.password') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.fields.password_confirmation') }}</span>
                <input
                    type="password"
                    wire:model="passwordForm.password_confirmation"
                    autocomplete="new-password"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('passwordForm.password_confirmation') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <p class="text-sm text-slate-500 md:col-span-2">{{ __('shell.profile.password.note') }}</p>
        </div>
    </section>

    <div class="flex justify-end">
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
        >
            {{ __('shell.profile.actions.save') }}
        </button>
    </div>
</form>
