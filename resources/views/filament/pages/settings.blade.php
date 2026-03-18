<x-filament-panels::page>
    <div class="space-y-6">
        @include('filament.pages.partials.account-profile-sections')

        @if ($this->canManageOrganizationSettings())
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.organization.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.organization.description') }}</p>

                <form wire:submit="saveOrganizationSettings" class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.organization.fields.billing_contact_name') }}</span>
                        <input type="text" wire:model="organizationForm.billing_contact_name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.organization.fields.billing_contact_email') }}</span>
                        <input type="email" wire:model="organizationForm.billing_contact_email" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.organization.fields.billing_contact_phone') }}</span>
                        <input type="text" wire:model="organizationForm.billing_contact_phone" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>{{ __('shell.settings.organization.fields.payment_instructions') }}</span>
                        <textarea wire:model="organizationForm.payment_instructions" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"></textarea>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>{{ __('shell.settings.organization.fields.invoice_footer') }}</span>
                        <textarea wire:model="organizationForm.invoice_footer" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"></textarea>
                    </label>

                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                            {{ __('shell.settings.organization.actions.save') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.notifications.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.notifications.description') }}</p>

                <form wire:submit="saveNotificationPreferences" class="mt-5 space-y-4">
                    <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                        <span class="space-y-1">
                            <span class="block">{{ __('shell.settings.notifications.fields.new_invoice_generated') }}</span>
                            <span class="block text-xs font-normal text-slate-500">{{ __('shell.settings.notifications.help.new_invoice_generated') }}</span>
                        </span>
                        <input type="checkbox" wire:model="notificationForm.new_invoice_generated" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950">
                    </label>

                    <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                        <span class="space-y-1">
                            <span class="block">{{ __('shell.settings.notifications.fields.invoice_overdue') }}</span>
                            <span class="block text-xs font-normal text-slate-500">{{ __('shell.settings.notifications.help.invoice_overdue') }}</span>
                        </span>
                        <input type="checkbox" wire:model="notificationForm.invoice_overdue" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950">
                    </label>

                    <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                        <span class="space-y-1">
                            <span class="block">{{ __('shell.settings.notifications.fields.tenant_submits_reading') }}</span>
                            <span class="block text-xs font-normal text-slate-500">{{ __('shell.settings.notifications.help.tenant_submits_reading') }}</span>
                        </span>
                        <input type="checkbox" wire:model="notificationForm.tenant_submits_reading" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950">
                    </label>

                    <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                        <span class="space-y-1">
                            <span class="block">{{ __('shell.settings.notifications.fields.subscription_expiring') }}</span>
                            <span class="block text-xs font-normal text-slate-500">{{ __('shell.settings.notifications.help.subscription_expiring') }}</span>
                        </span>
                        <input type="checkbox" wire:model="notificationForm.subscription_expiring" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950">
                    </label>

                    <div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                            {{ __('shell.settings.notifications.actions.save') }}
                        </button>
                    </div>
                </form>
            </section>

            <section id="subscription" class="scroll-mt-24 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.subscription.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.subscription.description') }}</p>

                <form wire:submit="renewSubscription" class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.subscription.fields.plan') }}</span>
                        <select wire:model="renewalForm.plan" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                            @foreach ($this->getPlanOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.subscription.fields.duration') }}</span>
                        <select wire:model="renewalForm.duration" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                            @foreach ($this->getDurationOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                            {{ __('shell.settings.subscription.actions.renew') }}
                        </button>
                    </div>
                </form>
            </section>
        @endif
    </div>
</x-filament-panels::page>
