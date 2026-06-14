<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-950">{{ __('admin.leads.import.preview_title') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('admin.leads.import.preview_description') }}</p>
        </section>

        @if ($preview)
            <section class="grid gap-3 md:grid-cols-5">
                @foreach ([
                    'rows_total',
                    'valid_rows',
                    'invalid_rows',
                    'possible_duplicates',
                    'missing_contact',
                ] as $metric)
                    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __("admin.leads.import.metrics.{$metric}") }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $preview[$metric] ?? 0 }}</p>
                    </article>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('admin.leads.import.errors_title') }}</h2>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @forelse ($preview['errors'] ?? [] as $error)
                        <div class="border-b border-slate-100 px-5 py-3 text-sm text-rose-700">
                            {{ __('admin.leads.import.row_error', ['row' => $error['row'], 'field' => $error['field'], 'message' => $error['message']]) }}
                        </div>
                    @empty
                        <div class="px-5 py-6 text-sm text-slate-500">{{ __('admin.leads.import.no_errors') }}</div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('admin.leads.import.rows_title') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('admin.leads.import.columns.row') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.fields.listing_title') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.fields.property_address') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.fields.owner_phone') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.fields.owner_email') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.import.columns.status') }}</th>
                                <th class="px-4 py-3">{{ __('admin.leads.fields.duplicate_reasons') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse (array_slice($preview['rows'] ?? [], 0, 50) as $row)
                                @php($data = $row['data'] ?? [])
                                <tr>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['row_number'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-900">{{ $data['listing_title'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $data['property_address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $data['owner_phone'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $data['owner_email'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $row['status'] ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ collect($row['duplicates'] ?? [])->pluck('message')->implode(', ') ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('admin.leads.import.no_rows') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                {{ __('admin.leads.import.empty_preview') }}
            </section>
        @endif
    </div>
</x-filament-panels::page>
