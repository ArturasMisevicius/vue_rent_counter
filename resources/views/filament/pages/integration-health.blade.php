<x-filament-panels::page>
    <div class="space-y-6" wire:poll.30s>
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">Integration Health</h2>
            <p class="mt-2 text-sm text-slate-600">Platform probes refresh automatically every 30 seconds.</p>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            @forelse ($checks as $check)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $check->label }}</p>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ ucfirst($check->status->value ?? $check->status) }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $check->summary }}</p>
                </article>
            @empty
                <p class="text-sm text-slate-500">No checks available.</p>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
