<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4">
        <h3 class="text-base font-semibold text-slate-950">Recently Created Organizations</h3>
        <p class="text-sm text-slate-500">New customer workspaces created across the platform.</p>
    </div>

    <div class="space-y-3">
        @forelse ($organizations as $organization)
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="font-semibold text-slate-950">{{ $organization->name }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $organization->slug }} • {{ $organization->created_at?->toFormattedDateString() }}</p>
            </article>
        @empty
            <p class="text-sm text-slate-500">No organizations have been created yet.</p>
        @endforelse
    </div>
</div>
