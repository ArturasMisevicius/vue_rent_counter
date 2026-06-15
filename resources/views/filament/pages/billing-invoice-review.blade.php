<x-filament-panels::page>

    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">{{ $this->review['billing_period'] }}</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-950">{{ $this->review['invoice_number'] }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $this->review['invoice_status_label'] }} · {{ $this->review['approval_status'] ?? __('dashboard.not_available') }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="recalculateInvoice" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('admin.billing_review.actions.recalculate') }}
                    </button>
                    @if ($this->review['can_approve'])
                        <div class="flex flex-col gap-2">
                            @if ($this->review['warnings'] !== [])
                                <label class="inline-flex items-center gap-2 text-xs font-medium text-amber-700">
                                    <input type="checkbox" wire:model="acceptInvoiceWarnings" class="rounded border-amber-300 text-slate-950">
                                    {{ __('admin.billing_review.actions.accept_invoice_warnings') }}
                                </label>
                            @endif

                            <button type="button" wire:click="approveInvoice" class="rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                {{ __('admin.billing_review.actions.approve_invoice') }}
                            </button>
                        </div>
                    @endif
                    @if ($this->review['missing_readings'] !== [])
                        <button type="button" wire:click="sendReminder" class="rounded-md border border-amber-200 px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">
                            {{ __('admin.billing_review.actions.send_reminder') }}
                        </button>
                    @endif
                    @if ($this->review['can_send'])
                        <button type="button" wire:click="sendInvoice" class="rounded-md border border-emerald-200 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                            {{ __('admin.billing_review.actions.send_invoice') }}
                        </button>
                    @endif
                </div>
            </div>

            <dl class="mt-5 grid gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('admin.billing_review.invoice_review.tenant_property') }}</dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-950">{{ $this->review['tenant_name'] }}</dd>
                    <dd class="text-sm text-slate-600">{{ $this->review['property_name'] }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('admin.billing_review.invoice_review.readings') }}</dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-950">{{ $this->review['readings_progress'] }}</dd>
                    <dd class="text-sm text-slate-600">{{ __('admin.billing_review.invoice_review.submitted_count', ['count' => $this->review['submitted_readings_count']]) }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('admin.billing_review.invoice_review.preview_total') }}</dt>
                    <dd class="mt-2 text-lg font-semibold text-slate-950">{{ $this->review['preview_total'] }} {{ $this->review['currency'] }}</dd>
                </div>
            </dl>
        </section>

        @if ($this->review['blocking_errors'] !== [])
            <section class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                <h3 class="text-sm font-semibold text-rose-950">{{ __('admin.billing_review.invoice_review.blocking_errors') }}</h3>
                <div class="mt-3 space-y-2 text-sm text-rose-900">
                    @foreach ($this->review['blocking_errors'] as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($this->review['warnings'] !== [])
            <section class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                <h3 class="text-sm font-semibold text-amber-950">{{ __('admin.billing_review.invoice_review.warnings') }}</h3>
                <div class="mt-3 space-y-2 text-sm text-amber-900">
                    @foreach ($this->review['warnings'] as $warning)
                        <p>{{ $warning }}</p>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-950">{{ __('admin.billing_review.invoice_review.submitted_readings') }}</h3>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($this->review['submitted_readings'] as $reading)
                    <div wire:key="invoice-review-reading-{{ $reading['reading_id'] ?? $reading['meter_id'] }}" class="grid gap-4 px-5 py-4 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,1.2fr)]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-950">{{ $reading['meter_name'] }}</p>
                                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $reading['status_label'] }}</span>
                                @foreach ($reading['issue_labels'] as $label)
                                    <span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ $label }}</span>
                                @endforeach
                            </div>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $reading['reading_value'] }} {{ $reading['meter_unit'] }} · {{ $reading['reading_date'] }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ __('admin.billing_review.invoice_review.previous') }}: {{ $reading['previous_reading_value'] ?? '-' }}
                                · {{ __('admin.billing_review.invoice_review.consumption') }}: {{ $reading['consumption'] ?? '-' }}
                            </p>
                        </div>

                        @if ($reading['reading_id'])
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="approveReading({{ $reading['reading_id'] }})" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                        {{ __('admin.billing_review.actions.approve_reading') }}
                                    </button>
                                    <button type="button" wire:click="voidReading({{ $reading['reading_id'] }})" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        {{ __('admin.billing_review.actions.void_reading') }}
                                    </button>
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input type="checkbox" wire:model="confirmNegativeConsumption.{{ $reading['reading_id'] }}" class="rounded border-slate-300 text-slate-950">
                                        {{ __('admin.billing_review.actions.accept_warning') }}
                                    </label>
                                </div>

                                <div class="space-y-2">
                                    <input type="text" wire:model="rejectionComments.{{ $reading['reading_id'] }}" placeholder="{{ __('admin.billing_review.fields.rejection_comment') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    <button type="button" wire:click="rejectReading({{ $reading['reading_id'] }})" class="rounded-md border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                        {{ __('admin.billing_review.actions.reject_reading') }}
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <input type="text" wire:model="resubmissionComments.{{ $reading['reading_id'] }}" placeholder="{{ __('admin.billing_review.fields.resubmission_comment') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    <button type="button" wire:click="requestResubmission({{ $reading['reading_id'] }})" class="rounded-md border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50">
                                        {{ __('admin.billing_review.actions.request_resubmission') }}
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <input type="text" wire:model="correctionValues.{{ $reading['reading_id'] }}" placeholder="{{ __('admin.billing_review.fields.corrected_value') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    <input type="text" wire:model="correctionReasons.{{ $reading['reading_id'] }}" placeholder="{{ __('admin.billing_review.fields.correction_reason') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    <button type="button" wire:click="correctReading({{ $reading['reading_id'] }})" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        {{ __('admin.billing_review.actions.correct_reading') }}
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <input type="text" wire:model="voidReasons.{{ $reading['reading_id'] }}" placeholder="{{ __('admin.billing_review.fields.void_reason') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">{{ __('admin.billing_review.invoice_review.no_submitted_readings') }}</p>
                @endforelse
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-950">{{ __('admin.billing_review.invoice_review.missing_readings') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($this->review['missing_readings'] as $reading)
                        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $reading['meter_name'] }}</div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('admin.billing_review.invoice_review.no_missing_readings') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-950">{{ __('admin.billing_review.invoice_review.services_extra_charges') }}</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($this->review['services'] as $service)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $service['name'] }}</span>
                            <span class="text-slate-600">{{ $service['preview_total'] }} {{ $this->review['currency'] }}</span>
                        </div>
                    @endforeach
                    @foreach ($this->review['extra_charges'] as $charge)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $charge['description'] }}</span>
                            <span class="text-slate-600">{{ $charge['total'] }} {{ $this->review['currency'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-950">{{ __('admin.billing_review.invoice_review.calculation_preview') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($this->review['calculation_preview'] as $item)
                        <div class="rounded-lg border border-slate-100 px-4 py-3">
                            <div class="flex items-start justify-between gap-4 text-sm">
                                <span class="font-medium text-slate-900">{{ $item['description'] }}</span>
                                <span class="font-semibold text-slate-950">{{ $item['total'] }} {{ $this->review['currency'] }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $item['quantity'] }} {{ $item['unit'] }} · {{ $item['unit_price'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('admin.billing_review.invoice_review.no_calculation_items') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-950">{{ __('admin.billing_review.invoice_review.history') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($this->review['history'] as $event)
                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm">
                            <p class="font-medium text-slate-900">{{ $event['label'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $event['actor'] ?? __('dashboard.not_available') }} · {{ $event['at'] ?? $event['description'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('admin.billing_review.invoice_review.no_history') }}</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
