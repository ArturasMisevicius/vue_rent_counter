<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4">
        <h3 class="text-base font-semibold text-slate-950">Recent Security Violations</h3>
        <p class="text-sm text-slate-500">The latest platform risk signals and suspicious activity.</p>
    </div>

    <div class="space-y-3">
        @forelse ($violations as $violation)
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="font-semibold text-slate-950">{{ $violation->summary }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $violation->organization?->name }} • {{ $violation->severity?->label() }} • {{ $violation->ip_address }}</p>
            </article>
        @empty
            <p class="text-sm text-slate-500">No recent security violations were detected.</p>
        @endforelse
    </div>
</div>
