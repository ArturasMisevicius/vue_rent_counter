<div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ __('dashboard.organization_eyebrow') }}</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('admin.reports.title') }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('admin.reports.description') }}</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap gap-3">
            @foreach ($this->tabs as $tab => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    @class([
                        'rounded-full px-4 py-2 text-sm font-semibold transition',
                        'bg-slate-950 text-white shadow-sm' => $activeTab === $tab,
                        'bg-slate-100 text-slate-700 hover:bg-slate-200' => $activeTab !== $tab,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('admin.reports.filters.start_date') }}</span>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                />
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('admin.reports.filters.end_date') }}</span>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                />
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('admin.reports.filters.building') }}</span>
                <select
                    wire:model.live="buildingId"
                    class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                >
                    <option value="">{{ __('admin.reports.filters.all') }}</option>
                    @foreach ($this->buildingOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('admin.reports.filters.property') }}</span>
                <select
                    wire:model.live="propertyId"
                    class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                >
                    <option value="">{{ __('admin.reports.filters.all') }}</option>
                    @foreach ($this->propertyOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('admin.reports.filters.tenant') }}</span>
                <select
                    wire:model.live="tenantId"
                    class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                >
                    <option value="">{{ __('admin.reports.filters.all') }}</option>
                    @foreach ($this->tenantOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            @if (in_array($activeTab, ['consumption', 'meter_compliance'], true))
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.reports.filters.meter_type') }}</span>
                    <select
                        wire:model.live="meterType"
                        class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    >
                        <option value="">{{ __('admin.reports.filters.all') }}</option>
                        @foreach ($this->meterTypeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>{{ __('admin.reports.filters.status_filter') }}</span>
                <select
                    wire:model.live="statusFilter"
                    class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                >
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        @if ($hasOrganizationContext)
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="exportCsv"
                    class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                >
                    {{ __('admin.reports.actions.export_csv') }}
                </button>

                <button
                    type="button"
                    wire:click="exportPdf"
                    class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-200"
                >
                    {{ __('admin.reports.actions.export_pdf') }}
                </button>
            </div>
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-end md:justify-between">
            <div>
                <h3 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $report['title'] }}</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $report['description'] }}</p>
            </div>
        </div>

        @if ($report['summary'] !== [])
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($report['summary'] as $item)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $item['label'] }}</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if (($report['rows'] ?? []) === [])
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-600">
                {{ $report['empty_state'] }}
            </div>
        @else
            <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach ($report['columns'] as $column)
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach

                            @if ($activeTab === 'outstanding_balances')
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ __('admin.reports.columns.actions') }}
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($rows as $index => $row)
                            <tr wire:key="reports-row-{{ $activeTab }}-{{ $row['invoice_id'] ?? $row['meter_id'] ?? $row['tenant_id'] ?? $index }}">
                                @foreach ($report['columns'] as $column)
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $row[$column['key']] ?? '—' }}
                                    </td>
                                @endforeach

                                @if ($activeTab === 'outstanding_balances')
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        @if (filled($row['tenant_email'] ?? null) && filled($row['invoice_id'] ?? null))
                                            <button
                                                type="button"
                                                wire:click="sendReminder({{ $row['invoice_id'] }})"
                                                class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-900 transition hover:bg-amber-200"
                                            >
                                                {{ __('admin.reports.actions.send_reminder') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400">{{ __('dashboard.not_available') }}</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $rows->links() }}
            </div>
        @endif
    </section>
</div>
