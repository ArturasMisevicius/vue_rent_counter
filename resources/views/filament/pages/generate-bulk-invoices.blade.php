<x-filament-panels::page>
    @php($previewSummary = $this->previewSummary)

    <div class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(22rem,1fr)]">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.sections.billing_period') }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ __('admin.invoices.bulk.description') }}</p>
                    </div>

                    <div wire:loading.flex wire:target="form.billing_period_start,form.billing_period_end,form.due_date" class="items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">
                        <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-slate-950"></span>
                        {{ __('admin.invoices.bulk.preview_refreshing') }}
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="text-sm font-medium text-slate-700">
                        <x-shared.calendar-modal
                            id="billing_period_start"
                            :label="__('admin.invoices.fields.billing_period_start')"
                            :value="$form['billing_period_start'] ?? ''"
                            wire:model.live="form.billing_period_start"
                        />
                        @error('billing_period_start')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="text-sm font-medium text-slate-700">
                        <x-shared.calendar-modal
                            id="billing_period_end"
                            :label="__('admin.invoices.fields.billing_period_end')"
                            :value="$form['billing_period_end'] ?? ''"
                            wire:model.live="form.billing_period_end"
                        />
                        @error('billing_period_end')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="text-sm font-medium text-slate-700">
                        <x-shared.calendar-modal
                            id="due_date"
                            :label="__('admin.invoices.fields.due_date')"
                            :value="$form['due_date'] ?? ''"
                            wire:model.live="form.due_date"
                        />
                        @error('due_date')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.preview.title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('admin.invoices.bulk.preview.description') }}</p>

                <div class="mt-6 grid gap-3">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.invoices.bulk.preview.invoice_count') }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $previewSummary['selected_count'] }}</p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.invoices.bulk.preview.estimated_combined_total') }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $previewSummary['estimated_total'] }}</p>
                    </article>
                </div>

                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                            <x-heroicon-m-exclamation-triangle class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-amber-950">{{ __('admin.invoices.bulk.preview.missing_readings_title') }}</p>
                            <p class="text-sm text-amber-800">{{ __('admin.invoices.bulk.preview.missing_readings_help') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        @forelse ($previewSummary['missing_readings'] as $warning)
                            <div class="rounded-2xl bg-white/80 px-3 py-3 text-sm text-amber-950">
                                <span class="font-semibold">{{ $warning['tenant_name'] }}</span>
                                <span class="text-amber-800">· {{ $warning['property_name'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-amber-900">{{ __('admin.invoices.bulk.preview.no_missing_readings') }}</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.sections.select_tenants') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('admin.invoices.bulk.selection_help') }}</p>
                </div>

                <label class="w-full lg:max-w-sm">
                    <span class="sr-only">{{ __('admin.invoices.bulk.search') }}</span>
                    <input
                        type="search"
                        wire:model.live.debounce.200ms="tenantSearch"
                        placeholder="{{ __('admin.invoices.bulk.search_placeholder') }}"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900"
                    >
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" wire:click="toggleSelectAll" @checked($this->allSelectableCandidatesSelected) class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-950">
                    <span>{{ __('admin.invoices.bulk.actions.select_all') }}</span>
                </label>

                <p class="text-sm text-slate-500">{{ __('admin.invoices.bulk.preview.selected_summary', ['count' => $previewSummary['selected_count']]) }}</p>
            </div>

            <div class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto pr-1">
                @forelse ($this->previewCandidates as $candidate)
                    <label @class([
                        'flex items-start gap-4 rounded-3xl border px-5 py-4 transition',
                        'border-amber-300 bg-amber-50/70' => ($candidate['status_tone'] ?? null) === 'amber',
                        'border-rose-200 bg-rose-50/70' => ($candidate['status_tone'] ?? null) === 'rose',
                        'border-slate-200 bg-white' => ! $candidate['disabled'],
                    ])>
                        <input
                            type="checkbox"
                            wire:model.live="selectedAssignments"
                            value="{{ $candidate['assignment_key'] ?? '' }}"
                            @disabled($candidate['disabled'])
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-950"
                        >

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-950">{{ $candidate['tenant_name'] ?? __('admin.invoices.empty.tenant') }}</p>

                                        @if (filled($candidate['status_label'] ?? null))
                                            <span @class([
                                                'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                                'bg-amber-100 text-amber-900' => ($candidate['status_tone'] ?? null) === 'amber',
                                                'bg-rose-100 text-rose-900' => ($candidate['status_tone'] ?? null) === 'rose',
                                            ])>
                                                {{ $candidate['status_label'] }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-sm text-slate-500">{{ $candidate['property_name'] ?? __('admin.invoices.empty.property') }}</p>
                                    <p class="mt-2 text-sm text-slate-600">{{ __('admin.tenants.columns.unit_area') }}: {{ $candidate['unit_area'] ?? '-' }}</p>
                                </div>

                                <div class="text-left lg:text-right">
                                    @if (filled($candidate['estimated_total'] ?? null))
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin.invoices.bulk.estimated_total') }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ $candidate['estimated_total'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                        {{ __('admin.invoices.bulk.empty_candidates') }}
                    </div>
                @endforelse
            </div>

            @error('selected_assignments')
                <p class="mt-4 text-sm text-rose-600">{{ $message }}</p>
            @enderror

            <div wire:loading.flex wire:target="generateInvoices" class="mt-4 items-center gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-slate-950"></span>
                {{ __('admin.invoices.bulk.processing') }}
            </div>
        </section>

        @if ($summary)
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.summary') }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ __('admin.invoices.bulk.summary_help') }}</p>
                    </div>

                    @if (filled($summary['view_url']))
                        <a href="{{ $summary['view_url'] }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            {{ __('admin.invoices.bulk.actions.view_created_invoices') }}
                        </a>
                    @endif
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

                @if ($summary['errors'] !== [])
                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                        <p class="text-sm font-semibold text-amber-950">{{ __('admin.invoices.bulk.summary_issues') }}</p>
                        <div class="mt-3 space-y-2 text-sm text-amber-900">
                            @foreach ($summary['errors'] as $issue)
                                <p>{{ $issue }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
