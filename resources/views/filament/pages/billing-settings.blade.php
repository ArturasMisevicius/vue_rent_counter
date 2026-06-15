<x-filament-panels::page>
    <form wire:submit="saveBillingSettings" class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.billing.heading') }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.billing.description') }}</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 md:col-span-2">
                    <span>{{ __('shell.settings.billing.fields.auto_generation_enabled') }}</span>
                    <input
                        type="checkbox"
                        wire:model="billingForm.auto_generation_enabled"
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950"
                    >
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.settings.billing.fields.billing_frequency') }}</span>
                    <select wire:model="billingForm.billing_frequency" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        @foreach ($this->getFrequencyOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('billingForm.billing_frequency') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.settings.billing.fields.timezone') }}</span>
                    <select wire:model="billingForm.timezone" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        @foreach ($this->getTimezoneOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('billingForm.timezone') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.settings.billing.fields.invoice_generation_day') }}</span>
                    <input type="number" min="1" max="28" wire:model="billingForm.invoice_generation_day" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    @error('billingForm.invoice_generation_day') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.settings.billing.fields.reading_deadline_day') }}</span>
                    <input type="number" min="1" max="28" wire:model="billingForm.reading_deadline_day" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    @error('billingForm.reading_deadline_day') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.settings.billing.fields.payment_due_days') }}</span>
                    <input type="number" min="0" max="90" wire:model="billingForm.payment_due_days" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    @error('billingForm.payment_due_days') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('shell.settings.billing.fields.default_currency') }}</span>
                    <input type="text" maxlength="3" wire:model="billingForm.default_currency" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-900">
                    @error('billingForm.default_currency') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                    <span>{{ __('shell.settings.billing.fields.reminder_days_before_deadline') }}</span>
                    <input type="text" wire:model="reminderDays" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                    @error('billingForm.reminder_days_before_deadline') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </label>

                @foreach (['send_created_notification', 'send_reminders'] as $field)
                    <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.billing.fields.'.$field) }}</span>
                        <input
                            type="checkbox"
                            wire:model="billingForm.{{ $field }}"
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950"
                        >
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
            >
                {{ __('shell.settings.billing.actions.save') }}
            </button>
        </div>
    </form>
</x-filament-panels::page>
