<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ __('dashboard.organization_eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('admin.reports.title') }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('admin.reports.description') }}</p>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap gap-3">
                @foreach ($this->tabs() as $tab => $label)
                    <button
                        type="button"
                        wire:click="switchTab('{{ $tab }}')"
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
                        wire:model.live="filters.start_date"
                        class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    />
                </label>

                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.reports.filters.end_date') }}</span>
                    <input
                        type="date"
                        wire:model.live="filters.end_date"
                        class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    />
                </label>

                @if ($activeTab === 'consumption')
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('admin.reports.filters.meter_type') }}</span>
                        <select
                            wire:model.live="filters.meter_type"
                            class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        >
                            <option value="">{{ __('admin.reports.filters.all') }}</option>
                            @foreach ($this->meterTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @elseif ($activeTab === 'revenue')
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('admin.reports.filters.invoice_status') }}</span>
                        <select
                            wire:model.live="filters.invoice_status"
                            class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        >
                            <option value="">{{ __('admin.reports.filters.all') }}</option>
                            @foreach ($this->invoiceStatusOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @elseif ($activeTab === 'outstanding_balances')
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700 md:col-span-2 xl:col-span-1">
                        <input
                            type="checkbox"
                            wire:model.live="filters.only_overdue"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        <span>{{ __('admin.reports.filters.only_overdue') }}</span>
                    </label>
                @elseif ($activeTab === 'meter_compliance')
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ __('admin.reports.filters.compliance_state') }}</span>
                        <select
                            wire:model.live="filters.compliance_state"
                            class="w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        >
                            <option value="">{{ __('admin.reports.filters.all') }}</option>
                            @foreach ($this->complianceStateOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="loadReport"
                    class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500"
                >
                    {{ __('admin.reports.actions.load') }}
                </button>

                @if ($hasLoadedReport)
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
                @endif
            </div>
        </section>

        @if ($report !== null)
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $report['title'] }}</h3>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $report['description'] }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($report['summary'] as $item)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($report['rows'] === [])
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
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($report['rows'] as $row)
                                    <tr>
                                        @foreach ($report['columns'] as $column)
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $row[$column['key']] ?? '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
