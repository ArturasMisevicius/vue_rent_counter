<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">{{ __('admin.reports.title') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('admin.reports.description') }}</p>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.reports.fields.start_date') }}</span>
                    <input type="date" wire:model="filters.start_date" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                </label>
                <label class="space-y-2 text-sm font-medium text-slate-700">
                    <span>{{ __('admin.reports.fields.end_date') }}</span>
                    <input type="date" wire:model="filters.end_date" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900">
                </label>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" wire:click="loadReport('consumption')" class="rounded-2xl border px-4 py-2 text-sm font-semibold">
                    {{ __('admin.reports.tabs.consumption') }}
                </button>
                <button type="button" wire:click="loadReport('revenue')" class="rounded-2xl border px-4 py-2 text-sm font-semibold">
                    {{ __('admin.reports.tabs.revenue') }}
                </button>
                <button type="button" wire:click="loadReport('outstanding')" class="rounded-2xl border px-4 py-2 text-sm font-semibold">
                    {{ __('admin.reports.tabs.outstanding') }}
                </button>
                <button type="button" wire:click="loadReport('compliance')" class="rounded-2xl border px-4 py-2 text-sm font-semibold">
                    {{ __('admin.reports.tabs.compliance') }}
                </button>
            </div>
        </section>

        @if ($hasLoadedReport)
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-950">{{ __('admin.reports.tabs.'.$activeTab) }}</h3>
                    <div class="flex gap-2">
                        <span class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Export CSV</span>
                        <span class="rounded-2xl border px-4 py-2 text-sm font-semibold text-slate-700">Export PDF</span>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                @foreach (array_keys($rows[0] ?? ['result' => '']) as $column)
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ str($column)->replace('_', ' ')->title() }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($rows as $row)
                                <tr>
                                    @foreach ($row as $value)
                                        <td class="px-4 py-3 text-slate-700">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-slate-500" colspan="6">{{ __('admin.reports.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
