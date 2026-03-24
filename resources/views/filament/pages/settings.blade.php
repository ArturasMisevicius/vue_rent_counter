<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="saveSettings" class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.organization.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.organization.description') }}</p>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.organization.fields.organization_name') }}</span>
                        <input
                            type="text"
                            wire:model="organizationForm.organization_name"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                        >
                        @error('organizationForm.organization_name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.organization.fields.billing_contact_email') }}</span>
                        <span class="block text-xs font-normal text-slate-500">{{ __('shell.settings.organization.help.billing_contact_email') }}</span>
                        <input
                            type="email"
                            wire:model="organizationForm.billing_contact_email"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                        >
                        @error('organizationForm.billing_contact_email') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>{{ __('shell.settings.organization.fields.invoice_footer') }}</span>
                        <textarea
                            wire:model="organizationForm.invoice_footer"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                        ></textarea>
                        @error('organizationForm.invoice_footer') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.notifications.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.notifications.description') }}</p>

                <div class="mt-5 space-y-4">
                    @foreach ([
                        'new_invoice_generated',
                        'invoice_overdue',
                        'tenant_submits_reading',
                        'subscription_expiring',
                    ] as $notificationKey)
                        <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700">
                            <span class="space-y-1">
                                <span class="block">{{ __('shell.settings.notifications.fields.'.$notificationKey) }}</span>
                            </span>
                            <input
                                type="checkbox"
                                wire:model.live="notificationForm.{{ $notificationKey }}"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950"
                            >
                        </label>
                    @endforeach
                </div>
            </section>

            <section id="subscription" class="scroll-mt-24 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('shell.settings.subscription.heading') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('shell.settings.subscription.description') }}</p>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('shell.settings.subscription.fields.current_plan') }}</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">
                            {{ $currentPlan ? \App\Enums\SubscriptionPlan::from($currentPlan)->label() : '—' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('shell.settings.subscription.fields.status') }}</p>
                        <div class="mt-2">
                            @if ($currentStatus)
                                <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-sm font-semibold text-white">
                                    {{ \App\Enums\SubscriptionStatus::from($currentStatus)->label() }}
                                </span>
                            @else
                                <span class="text-lg font-semibold text-slate-950">—</span>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('shell.settings.subscription.fields.expiry_date') }}</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $currentExpiry ?: '—' }}</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($subscriptionUsage as $usage)
                        @php($barColor = match ($usage['tone']) {
                            'danger' => 'bg-rose-500',
                            'warning' => 'bg-amber-500',
                            default => 'bg-slate-900',
                        })

                        <div class="rounded-2xl border border-slate-200 px-4 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-sm font-semibold text-slate-950">{{ $usage['label'] }}</p>
                                <p class="text-sm text-slate-600">{{ $usage['summary'] }}</p>
                            </div>

                            <div class="mt-3 h-3 rounded-full bg-slate-100">
                                <div class="{{ $barColor }} h-3 rounded-full transition-all" style="width: {{ $usage['percent'] }}%"></div>
                            </div>

                            @if ($usage['limit_reached'])
                                <div class="mt-3 space-y-3">
                                    <p class="text-sm font-medium text-rose-600">{{ $usage['message'] }}</p>
                                    <button
                                        type="button"
                                        wire:click="openSubscriptionPanel"
                                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                                    >
                                        {{ __('shell.settings.subscription.actions.renew_upgrade') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    <button
                        type="button"
                        wire:click="openSubscriptionPanel"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
                    >
                        {{ __('shell.settings.subscription.actions.renew_upgrade') }}
                    </button>
                </div>
            </section>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                >
                    {{ __('shell.settings.actions.save') }}
                </button>
            </div>
        </form>
    </div>

    @if ($showSubscriptionPanel)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4">
            <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-950">{{ __('shell.settings.subscription.panel.heading') }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ __('shell.settings.subscription.panel.description') }}</p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeSubscriptionPanel"
                        class="rounded-2xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        {{ __('shell.settings.subscription.actions.cancel') }}
                    </button>
                </div>

                <form wire:submit="renewSubscription" class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.subscription.fields.plan') }}</span>
                        <select wire:model="renewalForm.plan" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                            @foreach ($this->getPlanOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('renewalForm.plan') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('shell.settings.subscription.fields.duration') }}</span>
                        <select wire:model="renewalForm.duration" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                            @foreach ($this->getDurationOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('renewalForm.duration') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <div class="md:col-span-2 flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeSubscriptionPanel"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            {{ __('shell.settings.subscription.actions.cancel') }}
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                        >
                            {{ __('shell.settings.subscription.actions.confirm') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
