@php
    $before = $activityLog->metadata['before'] ?? [];
    $after = $activityLog->metadata['after'] ?? [];
    $keys = collect(array_keys($before))
        ->merge(array_keys($after))
        ->unique()
        ->values();
@endphp

<div class="space-y-4">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <p>
            <span class="font-semibold text-slate-900">{{ __('superadmin.organizations.activity_log.record') }}:</span>
            {{ $activityLog->resource_type ? class_basename($activityLog->resource_type) : __('superadmin.organizations.activity_log.organization_fallback') }}
            @if ($activityLog->resource_id !== null)#{{ $activityLog->resource_id }}@endif
        </p>
        <p class="mt-1">
            <span class="font-semibold text-slate-900">{{ __('superadmin.organizations.activity_log.ip_address') }}:</span>
            {{ $activityLog->ip_address ?? __('superadmin.organizations.activity_log.unknown_ip') }}
        </p>
    </div>

    @if ($keys->isEmpty())
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
                    @foreach ($keys as $key)
                        @php
                            $beforeValue = $before[$key] ?? null;
                            $afterValue = $after[$key] ?? null;
                            $changed = $beforeValue !== $afterValue;
                        @endphp

                        <tr @class(['bg-amber-50' => $changed])>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ str($key)->headline() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ is_array($beforeValue) ? json_encode($beforeValue) : ($beforeValue ?? '—') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ is_array($afterValue) ? json_encode($afterValue) : ($afterValue ?? '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
