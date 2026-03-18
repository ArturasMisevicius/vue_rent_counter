<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-950">{{ __('admin.invoices.bulk.title') }}</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('admin.invoices.bulk.description') }}</p>
                </div>

                <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                    <button
                        type="button"
                        wire:click="$set('step', 1)"
                        class="@class([
                            'rounded-2xl px-4 py-2 text-sm font-semibold transition',
                            'bg-slate-950 text-white' => $step === 1,
                            'text-slate-600' => $step !== 1,
                        ])"
                    >
                        {{ __('admin.invoices.bulk.steps.period') }}
                    </button>
                    <button
                        type="button"
                        wire:click="$set('step', 2)"
                        @disabled($preview === null)
                        class="@class([
                            'rounded-2xl px-4 py-2 text-sm font-semibold transition',
                            'bg-slate-950 text-white' => $step === 2,
                            'text-slate-600' => $step !== 2,
                            'opacity-50' => $preview === null,
                        ])"
                    >
                        {{ __('admin.invoices.bulk.steps.tenants') }}
                    </button>
                </div>
            </div>

            @if ($step === 1)
                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('admin.invoices.fields.billing_period_start') }}</span>
                        <input type="date" wire:model.live="form.billing_period_start" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        @error('billing_period_start')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('admin.invoices.fields.billing_period_end') }}</span>
                        <input type="date" wire:model.live="form.billing_period_end" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        @error('billing_period_end')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('admin.invoices.fields.due_date') }}</span>
                        <input type="date" wire:model.live="form.due_date" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                        @error('due_date')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" wire:click="previewInvoices" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                        {{ __('admin.invoices.bulk.actions.preview') }}
                    </button>

                    @if ($preview !== null)
                        <button type="button" wire:click="$set('step', 2)" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">
                            {{ __('admin.invoices.bulk.actions.review_tenants') }}
                        </button>
                    @endif
                </div>
            @endif

            @if ($step === 2)
                <div class="mt-8 space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.steps.tenants') }}</h3>
                            <p class="text-sm text-slate-500">{{ __('admin.invoices.bulk.selection_help') }}</p>
                        </div>

                        <label class="w-full md:max-w-sm">
                            <span class="sr-only">{{ __('admin.invoices.bulk.search') }}</span>
                            <input
                                type="search"
                                wire:model.live.debounce.200ms="tenantSearch"
                                placeholder="{{ __('admin.invoices.bulk.search_placeholder') }}"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                            >
                        </label>
                    </div>

                    @if ($preview === null)
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                            {{ __('admin.invoices.bulk.empty_preview') }}
                        </div>
                    @else
                        <div class="grid gap-3">
                            @forelse ($this->previewCandidates as $candidate)
                                <label class="@class([
                                    'flex items-start gap-4 rounded-3xl border px-5 py-4 transition',
                                    'border-amber-300 bg-amber-50/70' => $candidate['disabled'],
                                    'border-slate-200 bg-white' => ! $candidate['disabled'],
                                ])">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedAssignments"
                                        value="{{ $candidate['assignment_key'] ?? '' }}"
                                        @disabled($candidate['disabled'])
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-950"
                                    >

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="font-semibold text-slate-950">{{ $candidate['tenant_name'] ?? __('admin.invoices.empty.tenant') }}</p>
                                                <p class="text-sm text-slate-500">{{ $candidate['property_name'] ?? __('admin.invoices.empty.property') }}</p>
                                            </div>

                                            @if ($candidate['disabled'])
                                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">
                                                    {{ __('admin.invoices.bulk.status.already_billed') }}
                                                </span>
                                            @endif
                                        </div>

                                        @isset($candidate['total'])
                                            <p class="mt-3 text-sm text-slate-600">
                                                {{ __('admin.invoices.bulk.estimated_total') }}:
                                                <span class="font-semibold text-slate-950">EUR {{ number_format((float) $candidate['total'], 2) }}</span>
                                            </p>
                                        @endisset
                                    </div>
                                </label>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                                    {{ __('admin.invoices.bulk.empty_candidates') }}
                                </div>
                            @endforelse
                        </div>

                        @error('selected_assignments')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading.flex wire:target="generateInvoices" class="items-center gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                            <span class="inline-flex h-2 w-2 rounded-full bg-slate-950 animate-pulse"></span>
                            {{ __('admin.invoices.bulk.processing') }}
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button" wire:click="$set('step', 1)" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">
                                {{ __('admin.invoices.bulk.actions.back') }}
                            </button>
                            <button type="button" wire:click="generateInvoices" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                                {{ __('admin.invoices.bulk.actions.generate') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        @if ($summary)
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.summary') }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ __('admin.invoices.bulk.summary_help') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.invoices.bulk.progress') }}</p>
                        <p class="text-2xl font-semibold tracking-tight text-slate-950">{{ $summary['created'] }}/{{ $summary['total'] }}</p>
                    </div>
                </div>

                <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-100">
                    @php
                        $completion = $summary['total'] > 0 ? round(($summary['created'] / $summary['total']) * 100) : 0;
                    @endphp
                    <div class="h-full rounded-full bg-slate-950 transition-all" style="width: {{ $completion }}%"></div>
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">{{ __('admin.invoices.bulk.created') }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-950">{{ $summary['created'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">{{ __('admin.invoices.bulk.failed') }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-amber-950">{{ $summary['failed'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.invoices.bulk.skipped') }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $summary['skipped'] }}</p>
                    </article>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
