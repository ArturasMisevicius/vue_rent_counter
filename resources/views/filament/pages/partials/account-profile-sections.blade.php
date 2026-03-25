@php($profileNameValue = htmlspecialchars($profileForm['name'], ENT_COMPAT, 'UTF-8', false))
@php($profileEmailValue = htmlspecialchars($profileForm['email'], ENT_COMPAT, 'UTF-8', false))
@php($profilePhoneValue = htmlspecialchars((string) ($profileForm['phone'] ?? ''), ENT_COMPAT, 'UTF-8', false))
@php($kycValue = static fn (string $key): string => htmlspecialchars((string) ($kycForm[$key] ?? ''), ENT_COMPAT, 'UTF-8', false))
@php($kycChecked = static fn (string $key): bool => (bool) ($kycForm[$key] ?? false))
@php($canManageRiskFields = auth()->user()?->isAdminLike() ?? false)

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

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.identity_verification.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.identity_verification.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.full_legal_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.full_legal_name"
                    value="{!! $kycValue('full_legal_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.full_legal_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.birth_date') }}</span>
                <input
                    type="date"
                    wire:model="kycForm.birth_date"
                    value="{!! $kycValue('birth_date') !!}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.birth_date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.nationality') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.nationality"
                    value="{!! $kycValue('nationality') !!}"
                    autocomplete="country-name"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.nationality') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.gender') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.gender"
                    value="{!! $kycValue('gender') !!}"
                    autocomplete="sex"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.gender') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.marital_status') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.marital_status"
                    value="{!! $kycValue('marital_status') !!}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.marital_status') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.tax_id_number') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tax_id_number"
                    value="{!! $kycValue('tax_id_number') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.tax_id_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                <span>{{ __('shell.profile.kyc.fields.social_security_number') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.social_security_number"
                    value="{!! $kycValue('social_security_number') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.social_security_number') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.emergency_contacts.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.emergency_contacts.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.secondary_contact_name"
                    value="{!! $kycValue('secondary_contact_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.secondary_contact_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_relationship') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.secondary_contact_relationship"
                    value="{!! $kycValue('secondary_contact_relationship') !!}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.secondary_contact_relationship') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_phone') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.secondary_contact_phone"
                    value="{!! $kycValue('secondary_contact_phone') !!}"
                    autocomplete="tel"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.secondary_contact_phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.secondary_contact_email') }}</span>
                <input
                    type="email"
                    wire:model="kycForm.secondary_contact_email"
                    value="{!! $kycValue('secondary_contact_email') !!}"
                    autocomplete="email"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.secondary_contact_email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tertiary_contact_name"
                    value="{!! $kycValue('tertiary_contact_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.tertiary_contact_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_relationship') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tertiary_contact_relationship"
                    value="{!! $kycValue('tertiary_contact_relationship') !!}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.tertiary_contact_relationship') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_phone') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.tertiary_contact_phone"
                    value="{!! $kycValue('tertiary_contact_phone') !!}"
                    autocomplete="tel"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.tertiary_contact_phone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.tertiary_contact_email') }}</span>
                <input
                    type="email"
                    wire:model="kycForm.tertiary_contact_email"
                    value="{!! $kycValue('tertiary_contact_email') !!}"
                    autocomplete="email"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.tertiary_contact_email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.professional_information.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.professional_information.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.employer_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.employer_name"
                    value="{!! $kycValue('employer_name') !!}"
                    autocomplete="organization"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.employer_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.employment_position') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.employment_position"
                    value="{!! $kycValue('employment_position') !!}"
                    autocomplete="organization-title"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.employment_position') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.employment_contract_type') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.employment_contract_type"
                    value="{!! $kycValue('employment_contract_type') !!}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.employment_contract_type') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.monthly_income_range') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.monthly_income_range"
                    value="{!! $kycValue('monthly_income_range') !!}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.monthly_income_range') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.banking_details.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.banking_details.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.iban') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.iban"
                    value="{!! $kycValue('iban') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.iban') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.swift_bic') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.swift_bic"
                    value="{!! $kycValue('swift_bic') !!}"
                    autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.swift_bic') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.bank_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.bank_name"
                    value="{!! $kycValue('bank_name') !!}"
                    autocomplete="organization"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.bank_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.bank_account_holder_name') }}</span>
                <input
                    type="text"
                    wire:model="kycForm.bank_account_holder_name"
                    value="{!! $kycValue('bank_account_holder_name') !!}"
                    autocomplete="name"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.bank_account_holder_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.consent_and_risk.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.consent_and_risk.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
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
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.kyc.fields.payment_history_score') }}</span>
                    <input
                        type="number"
                        wire:model="kycForm.payment_history_score"
                        value="{!! $kycValue('payment_history_score') !!}"
                        inputmode="numeric"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                    @error('kycForm.payment_history_score') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.kyc.fields.external_credit_bureau_reference') }}</span>
                    <input
                        type="text"
                        wire:model="kycForm.external_credit_bureau_reference"
                        value="{!! $kycValue('external_credit_bureau_reference') !!}"
                        autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                    @error('kycForm.external_credit_bureau_reference') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.profile.kyc.fields.internal_credit_score') }}</span>
                    <input
                        type="number"
                        wire:model="kycForm.internal_credit_score"
                        value="{!! $kycValue('internal_credit_score') !!}"
                        inputmode="numeric"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                    @error('kycForm.internal_credit_score') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
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

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.profile.kyc.sections.document_uploads.heading') }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.profile.kyc.sections.document_uploads.description') }}</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.profile_photo') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.profile_photo"
                    accept="image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.profile_photo') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.passport_scan') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.passport_scan"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.passport_scan') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.national_id_front') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.national_id_front"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.national_id_front') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.national_id_back') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.national_id_back"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.national_id_back') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.drivers_license') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.drivers_license"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.drivers_license') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('shell.profile.kyc.fields.employment_verification_letter') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.employment_verification_letter"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.employment_verification_letter') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                <span>{{ __('shell.profile.kyc.fields.direct_debit_mandate') }}</span>
                <input
                    type="file"
                    wire:model="kycForm.direct_debit_mandate"
                    accept="application/pdf,image/*"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                >
                @error('kycForm.direct_debit_mandate') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </label>
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
