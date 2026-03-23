<x-filament-panels::page>
    <div class="space-y-6">
        <section class="framework-panel framework-grid overflow-hidden p-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <p class="text-sm font-semibold uppercase tracking-[0.26em] text-framework-500">Framework Lab</p>
                    <h2 class="font-display text-4xl tracking-tight text-slate-950">Livewire 4, Filament 5, and Tailwind 4 in one contained studio</h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        This page is intentionally isolated from Tenanto’s billing workflows. It demonstrates supported modern framework features,
                        links to the full-page Livewire showcase, and provides a safe Filament CRUD surface for experimentation.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <a
                        href="{{ route('framework.livewire.showcase') }}"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Open showcase
                    </a>
                    <a
                        href="{{ route('filament.admin.resources.framework-showcases.index') }}"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Browse demo resource
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <article class="framework-panel p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">What is implemented here</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                    <li>Single-file Livewire page components with scoped CSS and JS.</li>
                    <li>Class-based Livewire child components plus <code>wire:ref</code> targeting.</li>
                    <li>Tailwind v4 CSS-first tokens, utilities, variants, gradients, and 3D transforms.</li>
                    <li>Filament widgets, slide-over actions, notifications, and a demo CRUD resource.</li>
                </ul>
            </article>

            <article class="framework-panel p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Suggested next steps</p>
                <ol class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                    <li>Review the full-page showcase route to validate the Livewire 4 behavior and scoped assets.</li>
                    <li>Create a few Framework Showcase records to test polling, exports, and notifications.</li>
                    <li>Promote any patterns you like into the real dashboard and tenant-facing flows incrementally.</li>
                </ol>
            </article>
        </section>
    </div>
</x-filament-panels::page>
