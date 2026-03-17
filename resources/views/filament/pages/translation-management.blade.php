<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">Translation Management</h2>
            <p class="mt-2 text-sm text-slate-600">Review the current catalog across available locales.</p>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-3">
                @forelse ($rows as $row)
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="font-medium text-slate-950">{{ $row->group }}.{{ $row->key }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No translations found.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
