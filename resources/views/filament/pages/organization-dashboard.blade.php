<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ __('dashboard.organization_eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.organization_heading') }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('dashboard.organization_body') }}</p>

            <div class="mt-6">
                <a
                    href="{{ route('filament.admin.pages.reports') }}"
                    class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Reports
                </a>
            </div>
        </section>
    </div>
</x-filament-panels::page>
