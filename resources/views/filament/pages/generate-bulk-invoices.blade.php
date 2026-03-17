<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">{{ __('admin.invoices.bulk.title') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('admin.invoices.bulk.description') }}</p>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.invoices.fields.billing_period_start') }}</span>
                    <input type="date" wire:model="form.billing_period_start" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                </label>
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.invoices.fields.billing_period_end') }}</span>
                    <input type="date" wire:model="form.billing_period_end" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                </label>
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.invoices.fields.due_date') }}</span>
                    <input type="date" wire:model="form.due_date" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                </label>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" wire:click="previewInvoices" class="rounded-2xl border px-4 py-2 text-sm font-semibold">
                    {{ __('admin.invoices.bulk.actions.preview') }}
                </button>
                <button type="button" wire:click="generateInvoices" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                    {{ __('admin.invoices.bulk.actions.generate') }}
                </button>
            </div>
        </section>

        @if ($preview)
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.bulk.summary') }}</h3>
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('admin.invoices.bulk.created') }}: {{ count($preview['created'] ?? $preview['valid'] ?? []) }}
                    · {{ __('admin.invoices.bulk.skipped') }}: {{ count($preview['skipped'] ?? []) }}
                </p>
            </section>
        @endif
    </div>
</x-filament-panels::page>
