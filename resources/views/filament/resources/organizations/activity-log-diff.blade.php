<div class="space-y-4">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <p>
            <span class="font-semibold text-slate-900">{{ __('superadmin.organizations.activity_log.record') }}:</span>
            {{ $resourceLabel ?? __('superadmin.organizations.activity_log.organization_fallback') }}
        </p>
        <p class="mt-1">
            <span class="font-semibold text-slate-900">{{ __('superadmin.organizations.activity_log.ip_address') }}:</span>
            {{ $activityLog->ip_address ?? __('superadmin.organizations.activity_log.unknown_ip') }}
        </p>
    </div>

    @if (blank($rows ?? []))
        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">{{ __('superadmin.organizations.activity_log.empty') }}</p>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('superadmin.organizations.activity_log.columns.field') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('superadmin.organizations.activity_log.columns.before') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('superadmin.organizations.activity_log.columns.after') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach (($rows ?? []) as $row)
                        <tr @class(['bg-amber-50' => (bool) data_get($row, 'changed')])>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ data_get($row, 'label') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ data_get($row, 'before') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ data_get($row, 'after') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
