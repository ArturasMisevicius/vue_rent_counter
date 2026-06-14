<x-filament-panels::page>
    <div class="space-y-6">
        <section class="grid gap-3 md:grid-cols-4">
            @foreach ([
                ['label' => __('admin.leads.reports.follow_ups_due'), 'value' => $followUpsDue],
                ['label' => __('admin.leads.reports.duplicates'), 'value' => $duplicates],
                ['label' => __('admin.leads.reports.do_not_contact'), 'value' => $doNotContact],
                ['label' => __('admin.leads.reports.converted'), 'value' => $converted],
            ] as $metric)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('admin.leads.reports.by_source') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('admin.leads.fields.lead_source') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.reports.imported') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.reports.contacted') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.reports.interested') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.reports.converted') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($bySource as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-950">{{ $row['source'] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['imported'] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['contacted'] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['interested'] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['converted'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('admin.leads.reports.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('admin.leads.reports.by_status') }}</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($byStatus as $row)
                        <div class="flex items-center justify-between px-5 py-4 text-sm">
                            <span class="font-medium text-slate-800">{{ $row['label'] }}</span>
                            <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $row['count'] }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-sm text-slate-500">{{ __('admin.leads.reports.empty') }}</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
