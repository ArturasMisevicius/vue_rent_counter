<x-filament-panels::page>
    @php($page = $this->pageData())
    @php($presentation = $page['presentation'])

    <div class="space-y-6">
        @if (filled($page['draft_notice'] ?? null))
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
                {{ $page['draft_notice'] }}
            </div>
        @endif

        @if (filled($page['overdue_notice'] ?? null))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950">
                {{ $page['overdue_notice'] }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach (['left', 'right'] as $column)
                    <dl class="space-y-4">
                        @foreach ($page['summary'][$column] as $entry)
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                                <dt class="text-sm font-medium text-slate-500">{{ $entry['label'] }}</dt>
                                <dd class="text-right text-sm text-slate-900">
                                    @if ($entry['badge'])
                                        <x-filament::badge :color="$entry['color'] ?? 'gray'">
                                            {{ $entry['value'] }}
                                        </x-filament::badge>
                                    @else
                                        {{ $entry['value'] }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ __('admin.invoices.fields.amount_paid') }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $presentation['paid_amount_display'] ?? '—' }}</p>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ __('admin.invoices.status_summaries.outstanding') }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $presentation['outstanding_amount_display'] ?? '—' }}</p>
            </section>
        </div>

        @php($calculationPreview = $page['calculation_preview'] ?? ['items' => [], 'blocking_errors' => [], 'warnings' => []])

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.sections.calculation_preview') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('admin.invoices.preview.description') }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if (! empty($calculationPreview['blocking_errors']))
                        <x-filament::badge color="danger">
                            {{ __('admin.invoices.preview.blocking_count', ['count' => count($calculationPreview['blocking_errors'])]) }}
                        </x-filament::badge>
                    @endif

                    @if (! empty($calculationPreview['warnings']))
                        <x-filament::badge color="warning">
                            {{ __('admin.invoices.preview.warning_count', ['count' => count($calculationPreview['warnings'])]) }}
                        </x-filament::badge>
                    @endif
                </div>
            </div>

            @if (! empty($calculationPreview['blocking_errors']))
                <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    <p class="font-semibold">{{ __('admin.invoices.preview.blocking_errors') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($calculationPreview['blocking_errors'] as $issue)
                            <li>{{ $issue['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($calculationPreview['warnings']))
                <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    <p class="font-semibold">{{ __('admin.invoices.preview.warnings') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($calculationPreview['warnings'] as $issue)
                            <li>{{ $issue['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('admin.invoices.preview.columns.status') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.preview.columns.item') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.preview.columns.source') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.preview.columns.formula') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.quantity') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.unit_price') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.subtotal') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.tax') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.invoices.fields.total') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.tenant_visible') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($calculationPreview['items'] as $item)
                            <tr>
                                <td class="px-4 py-4">
                                    <x-filament::badge :color="$item['status'] === 'blocked' ? 'danger' : ($item['status'] === 'warning' ? 'warning' : 'success')">
                                        {{ $item['status_label'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-slate-900">{{ $item['title'] }}</p>
                                    @if (filled($item['description_for_tenant']))
                                        <p class="mt-1 text-xs text-slate-500">{{ $item['description_for_tenant'] }}</p>
                                    @endif
                                    @if (! empty($item['blocking_errors']) || ! empty($item['warnings']))
                                        <div class="mt-2 space-y-1 text-xs">
                                            @foreach ($item['blocking_errors'] as $message)
                                                <p class="text-red-700">{{ $message }}</p>
                                            @endforeach
                                            @foreach ($item['warnings'] as $message)
                                                <p class="text-amber-700">{{ $message }}</p>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    @if (filled($item['source_url'] ?? null))
                                        <a href="{{ $item['source_url'] }}" class="font-medium text-primary-600 hover:text-primary-500">
                                            {{ $item['source'] }}
                                        </a>
                                    @else
                                        {{ $item['source'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['formula'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['quantity'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['unit_price'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['subtotal'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['tax'] }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-950">{{ $item['total'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['tenant_visibility_label'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-slate-500">
                                    {{ __('admin.invoices.pdf.empty_items') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('admin.invoices.sections.charges') }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.description') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.period') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.quantity') }}</th>
                            <th class="px-4 py-3">{{ __('admin.invoices.fields.rate') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.invoices.fields.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($page['charge_rows'] as $row)
                            <tr @class([
                                'bg-amber-50/60' => $row['is_adjustment'],
                            ])>
                                <td class="px-4 py-4 text-slate-900">{{ $row['description'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $row['period'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $row['quantity'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $row['rate'] }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-slate-950">{{ $row['total'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    {{ __('admin.invoices.pdf.empty_items') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50/60 text-sm">
                        <tr>
                            <th colspan="4" class="px-4 py-3 text-right font-medium text-slate-600">{{ __('admin.invoices.fields.subtotal') }}</th>
                            <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ $page['subtotal_display'] }}</td>
                        </tr>
                        @if (filled($page['adjustments_display'] ?? null))
                            <tr>
                                <th colspan="4" class="px-4 py-3 text-right font-medium text-slate-600">{{ __('admin.invoices.fields.adjustments') }}</th>
                                <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ $page['adjustments_display'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th colspan="4" class="px-4 py-3 text-right text-base font-semibold text-slate-950">{{ __('admin.invoices.fields.total_amount') }}</th>
                            <td class="px-4 py-3 text-right text-base font-semibold text-slate-950">{{ $page['total_display'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-950">{{ __('admin.invoices.sections.payment_history') }}</h2>

                @forelse ($page['payment_history'] as $payment)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 not-first:mt-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="font-medium text-slate-900">{{ $payment['date'] }}</p>
                                <p class="text-sm text-slate-500">{{ __('admin.invoices.fields.payment_reference') }}: {{ $payment['reference'] }}</p>
                            </div>
                            <p class="font-semibold text-slate-950">{{ $payment['amount'] }}</p>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">{{ $page['payment_history_empty'] }}</p>
                @endforelse
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-950">{{ __('admin.invoices.sections.email_history') }}</h2>

                @forelse ($page['email_history'] as $email)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 not-first:mt-3">
                        <p class="font-medium text-slate-900">{{ $email['date'] }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $email['recipient_email'] }}</p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">{{ $page['email_history_empty'] }}</p>
                @endforelse
            </section>
        </div>
    </div>
</x-filament-panels::page>
