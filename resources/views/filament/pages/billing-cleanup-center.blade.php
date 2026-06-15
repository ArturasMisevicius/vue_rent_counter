<x-filament-panels::page>

    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm text-slate-600">{{ __('admin.billing_cleanup.description') }}</p>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-md border border-slate-200 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('admin.billing_cleanup.summary.total') }}</p>
                        <p class="mt-1 text-xl font-semibold text-slate-950">{{ $this->integrity['summary']['total'] }}</p>
                    </div>
                    <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-rose-700">{{ __('admin.billing_cleanup.summary.blocking') }}</p>
                        <p class="mt-1 text-xl font-semibold text-rose-800">{{ $this->integrity['summary']['blocking'] }}</p>
                    </div>
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-amber-800">{{ __('admin.billing_cleanup.summary.warning') }}</p>
                        <p class="mt-1 text-xl font-semibold text-amber-900">{{ $this->integrity['summary']['warning'] }}</p>
                    </div>
                </div>
            </div>
        </section>

        @foreach ([
            'duplicates' => __('admin.billing_cleanup.sections.duplicates'),
            'orphans' => __('admin.billing_cleanup.sections.orphans'),
        ] as $section => $title)
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('admin.billing_cleanup.columns.type') }}</th>
                                <th class="px-4 py-3">{{ __('admin.billing_cleanup.columns.problem') }}</th>
                                <th class="px-4 py-3">{{ __('admin.billing_cleanup.columns.count') }}</th>
                                <th class="px-4 py-3">{{ __('admin.billing_cleanup.columns.severity') }}</th>
                                <th class="px-4 py-3">{{ __('admin.billing_cleanup.columns.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($this->integrity[$section] as $issue)
                                <tr wire:key="billing-cleanup-{{ $section }}-{{ $issue['problem_type'] }}-{{ implode('-', $issue['entity_ids']) }}">
                                    <td class="px-4 py-4 font-medium text-slate-950">{{ __('admin.billing_cleanup.entity_types.'.$issue['entity_type']) }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $issue['label'] }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-950">{{ $issue['count'] }}</td>
                                    <td class="px-4 py-4">
                                        <span @class([
                                            'rounded-md px-2 py-1 text-xs font-semibold',
                                            'bg-rose-100 text-rose-700' => $issue['severity'] === 'blocking',
                                            'bg-amber-100 text-amber-800' => $issue['severity'] !== 'blocking',
                                        ])>
                                            {{ $issue['severity_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $issue['recommendation_label'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                        {{ __('admin.billing_cleanup.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
