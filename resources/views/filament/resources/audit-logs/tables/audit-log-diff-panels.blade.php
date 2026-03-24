<div class="grid gap-4 md:grid-cols-2">
    @foreach (['before' => __('superadmin.audit_logs.diff.before'), 'after' => __('superadmin.audit_logs.diff.after')] as $side => $heading)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <header class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900">
                {{ $heading }}
            </header>

            <div class="divide-y divide-slate-200">
                @forelse ($rows as $row)
                    <div
                        @class([
                            'audit-log-diff-row px-4 py-3',
                            'audit-log-diff-row--changed bg-amber-50' => $row['changed'],
                        ])
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $row['label'] }}</p>
                        <p class="mt-1 whitespace-pre-wrap break-words font-mono text-sm text-slate-700">{{ $row[$side] }}</p>
                    </div>
                @empty
                    <div class="px-4 py-3 text-sm text-slate-500">
                        {{ __('superadmin.audit_logs.diff.empty') }}
                    </div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
