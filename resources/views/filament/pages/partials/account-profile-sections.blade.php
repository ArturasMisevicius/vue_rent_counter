@php($profileNameValue = htmlspecialchars($profileForm['name'], ENT_COMPAT, 'UTF-8', false))
@php($profileEmailValue = htmlspecialchars($profileForm['email'], ENT_COMPAT, 'UTF-8', false))
@php($profilePhoneValue = htmlspecialchars((string) ($profileForm['phone'] ?? ''), ENT_COMPAT, 'UTF-8', false))
@php($kycValue = static fn (string $key): string => htmlspecialchars((string) ($kycForm[$key] ?? ''), ENT_COMPAT, 'UTF-8', false))
@php($kycChecked = static fn (string $key): bool => (bool) ($kycForm[$key] ?? false))
@php($canManageRiskFields = auth()->user()?->isAdminLike() ?? false)
@php($avatarInitials = collect(explode(' ', trim((string) auth()->user()?->name)))->filter()->take(2)->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode(''))

@if ($this->canManageProfileAvatar())
    <form wire:submit="saveProfileAvatar" class="mb-5">
        <section
            class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6"
            data-avatar-cropper
            data-ready-message="{{ __('shell.profile.avatar.messages.ready') }}"
            data-cropped-message="{{ __('shell.profile.avatar.messages.cropped') }}"
            data-invalid-message="{{ __('shell.profile.avatar.messages.invalid_file') }}"
        >
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                        <x-heroicon-m-camera class="size-5" />
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.avatar.heading') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.avatar.description') }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-3">
                    <span class="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-brand-ink text-lg font-semibold text-white">
                        <img
                            src="{{ $currentAvatarUrl ?? '' }}"
                            alt=""
                            @class([
                                'size-full object-cover',
                                'hidden' => blank($currentAvatarUrl),
                            ])
                            data-avatar-preview-image
                        >
                        <span @class(['hidden' => filled($currentAvatarUrl)]) data-avatar-preview-fallback>{{ $avatarInitials }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('shell.profile.avatar.current_label') }}</p>
                        <p class="truncate text-sm font-semibold text-slate-950">{{ auth()->user()?->name }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-5 xl:flex-row xl:items-start">
                <div class="flex flex-col gap-4 xl:min-w-[20rem] xl:flex-1">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.profile.avatar.upload_label') }}</span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                            data-avatar-file
                        >
                    </label>

                    <input type="hidden" wire:model="avatarForm.avatar" data-avatar-cropped-input>
                    @error('avatarForm.avatar') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror

                    <div class="hidden flex-col gap-4 rounded-3xl border border-slate-200 bg-slate-50 p-4" data-avatar-controls>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>{{ __('shell.profile.avatar.zoom_label') }}</span>
                            <input
                                type="range"
                                min="1"
                                max="3"
                                step="0.05"
                                value="1"
                                class="w-full accent-slate-950"
                                data-avatar-zoom
                            >
                        </label>

                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                            data-avatar-apply
                            disabled
                        >
                            <x-heroicon-m-scissors class="size-4" />
                            {{ __('shell.profile.avatar.actions.crop') }}
                        </button>
                    </div>

                    <p class="min-h-5 text-sm text-slate-500" data-avatar-status>{{ __('shell.profile.avatar.messages.empty') }}</p>
                </div>

                <div class="hidden flex-col items-start gap-3" data-avatar-editor>
                    <canvas
                        width="512"
                        height="512"
                        class="aspect-square w-full max-w-[20rem] cursor-move rounded-3xl border border-slate-200 bg-slate-50 shadow-inner touch-none"
                        aria-label="{{ __('shell.profile.avatar.preview_label') }}"
                        data-avatar-canvas
                    ></canvas>

                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto"
                        data-avatar-save
                        disabled
                    >
                        <x-heroicon-m-check class="size-4" />
                        {{ __('shell.profile.avatar.actions.save') }}
                    </button>
                </div>
            </div>
        </section>
    </form>
@endif

<form wire:submit="saveChanges" class="flex flex-col gap-5">
    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-user-circle class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.personal_information.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.personal_information.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.fields.name') }}</span>
                <input
                    type="text"
                    wire:model="profileForm.name"
                    value="{!! $profileNameValue !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('profileForm.name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.fields.email') }}</span>
                <input
                    type="email"
                    wire:model="profileForm.email"
                    value="{!! $profileEmailValue !!}"
                    autocomplete="email"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('profileForm.email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.fields.phone') }}</span>
                <input
                    type="text"
                    wire:model="profileForm.phone"
                    value="{!! $profilePhoneValue !!}"
                    autocomplete="tel"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('profileForm.phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.fields.locale') }}</span>
                <select wire:model.live="profileForm.locale" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30">
                    @foreach ($profileLocaleOptions as $locale => $label)
                        <option value="{{ $locale }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('profileForm.locale') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-key class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.password.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.password.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <input
                type="email"
                value="{!! $profileEmailValue !!}"
                autocomplete="username"
                tabindex="-1"
                aria-hidden="true"
                class="sr-only"
                readonly
            >

            <label class="space-y-2 text-sm font-medium text-slate-700 md:basis-full">
                <span>{{ __('shell.profile.fields.current_password') }}</span>
                <input
                    type="password"
                    wire:model="passwordForm.current_password"
                    autocomplete="current-password"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('passwordForm.current_password') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.fields.password') }}</span>
                <input
                    type="password"
                    wire:model="passwordForm.password"
                    autocomplete="new-password"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('passwordForm.password') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.fields.password_confirmation') }}</span>
                <input
                    type="password"
                    wire:model="passwordForm.password_confirmation"
                    autocomplete="new-password"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('passwordForm.password_confirmation') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <p class="text-sm text-slate-500 md:basis-full">{{ __('shell.profile.password.note') }}</p>
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-identification class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.identity_verification.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.identity_verification.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.full_legal_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.full_legal_name"
                    value="{!! $kycValue('full_legal_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.full_legal_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.birth_date') }}</span>
                <input
                    type="date"
                    wire:model="kycForm.birth_date"
                    value="{!! $kycValue('birth_date') !!}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.birth_date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.nationality') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.nationality"
                    value="{!! $kycValue('nationality') !!}"
                    autocomplete="country-name"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.nationality') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.gender') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.gender"
                    value="{!! $kycValue('gender') !!}"
                    autocomplete="sex"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.gender') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.marital_status') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.marital_status"
                    value="{!! $kycValue('marital_status') !!}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.marital_status') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.tax_id_number') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tax_id_number"
                    value="{!! $kycValue('tax_id_number') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.tax_id_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:basis-full">
                <span>{{ __('shell.profile.kyc.fields.social_security_number') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.social_security_number"
                    value="{!! $kycValue('social_security_number') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.social_security_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-phone class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.emergency_contacts.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.emergency_contacts.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.secondary_contact_name"
                    value="{!! $kycValue('secondary_contact_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.secondary_contact_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_relationship') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.secondary_contact_relationship"
                    value="{!! $kycValue('secondary_contact_relationship') !!}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.secondary_contact_relationship') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_phone') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.secondary_contact_phone"
                    value="{!! $kycValue('secondary_contact_phone') !!}"
                    autocomplete="tel"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.secondary_contact_phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_email') }}</span>
                <input
                    type="email"
                    wire:model="kycForm.secondary_contact_email"
                    value="{!! $kycValue('secondary_contact_email') !!}"
                    autocomplete="email"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.secondary_contact_email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tertiary_contact_name"
                    value="{!! $kycValue('tertiary_contact_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.tertiary_contact_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_relationship') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tertiary_contact_relationship"
                    value="{!! $kycValue('tertiary_contact_relationship') !!}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.tertiary_contact_relationship') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_phone') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tertiary_contact_phone"
                    value="{!! $kycValue('tertiary_contact_phone') !!}"
                    autocomplete="tel"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.tertiary_contact_phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_email') }}</span>
                <input
                    type="email"
                    wire:model="kycForm.tertiary_contact_email"
                    value="{!! $kycValue('tertiary_contact_email') !!}"
                    autocomplete="email"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.tertiary_contact_email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-briefcase class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.professional_information.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.professional_information.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.employer_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.employer_name"
                    value="{!! $kycValue('employer_name') !!}"
                    autocomplete="organization"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.employer_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.employment_position') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.employment_position"
                    value="{!! $kycValue('employment_position') !!}"
                    autocomplete="organization-title"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.employment_position') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.employment_contract_type') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.employment_contract_type"
                    value="{!! $kycValue('employment_contract_type') !!}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.employment_contract_type') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.monthly_income_range') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.monthly_income_range"
                    value="{!! $kycValue('monthly_income_range') !!}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.monthly_income_range') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-banknotes class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.banking_details.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.banking_details.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.iban') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.iban"
                    value="{!! $kycValue('iban') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.iban') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.swift_bic') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.swift_bic"
                    value="{!! $kycValue('swift_bic') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.swift_bic') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.bank_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.bank_name"
                    value="{!! $kycValue('bank_name') !!}"
                    autocomplete="organization"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.bank_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.bank_account_holder_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.bank_account_holder_name"
                    value="{!! $kycValue('bank_account_holder_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.bank_account_holder_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-shield-check class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.consent_and_risk.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.consent_and_risk.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:basis-full">
                <span>{{ __('shell.profile.kyc.fields.facial_recognition_consent') }}</span>
                <span class="flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    <input
                        type="checkbox"
                        wire:model="kycForm.facial_recognition_consent"
                        @checked($kycChecked('facial_recognition_consent'))
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950"
                    >
                    <span>{{ __('shell.profile.kyc.messages.facial_recognition_consent') }}</span>
                </span>
                @error('kycForm.facial_recognition_consent') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            @if ($canManageRiskFields)
                <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                    <span>{{ __('shell.profile.kyc.fields.payment_history_score') }}</span>
                    <input
                        type="number"
                        wire:model="kycForm.payment_history_score"
                        value="{!! $kycValue('payment_history_score') !!}"
                        inputmode="numeric"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    >
                    @error('kycForm.payment_history_score') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                    <span>{{ __('shell.profile.kyc.fields.external_credit_bureau_reference') }}</span>
                    <input
                        type="text"
                        wire:model="kycForm.external_credit_bureau_reference"
                        value="{!! $kycValue('external_credit_bureau_reference') !!}"
                        autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    >
                    @error('kycForm.external_credit_bureau_reference') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                    <span>{{ __('shell.profile.kyc.fields.internal_credit_score') }}</span>
                    <input
                        type="number"
                        wire:model="kycForm.internal_credit_score"
                        value="{!! $kycValue('internal_credit_score') !!}"
                        inputmode="numeric"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                    >
                    @error('kycForm.internal_credit_score') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700 md:basis-full">
                    <span>{{ __('shell.profile.kyc.fields.blacklist_status') }}</span>
                    <span class="flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        <input
                            type="checkbox"
                            wire:model="kycForm.blacklist_status"
                            @checked($kycChecked('blacklist_status'))
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950"
                        >
                        <span>{{ __('shell.profile.kyc.messages.blacklist_status') }}</span>
                    </span>
                    @error('kycForm.blacklist_status') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>
            @endif
        </div>
    </section>

    <section class="rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-cloud-arrow-up class="size-5" />
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.document_uploads.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.document_uploads.description') }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-4 md:flex-row md:flex-wrap">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.profile_photo') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.profile_photo"
                    accept="image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.profile_photo') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.passport_scan') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.passport_scan"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.passport_scan') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.national_id_front') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.national_id_front"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.national_id_front') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.national_id_back') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.national_id_back"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.national_id_back') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.drivers_license') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.drivers_license"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.drivers_license') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:min-w-[20rem] md:flex-1">
                <span>{{ __('shell.profile.kyc.fields.employment_verification_letter') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.employment_verification_letter"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.employment_verification_letter') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:basis-full">
                <span>{{ __('shell.profile.kyc.fields.direct_debit_mandate') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.direct_debit_mandate"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                >
                @error('kycForm.direct_debit_mandate') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <div class="flex justify-end">
        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
        >
            <x-heroicon-m-check class="size-4" />
            {{ __('shell.profile.actions.save') }}
        </button>
    </div>
</form>
