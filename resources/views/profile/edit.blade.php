<x-layouts.authenticated
    :title="__('shell.profile.title').' · '.config('app.name', 'Tenanto')"
    :eyebrow="__('shell.profile.eyebrow')"
    :heading="__('shell.profile.heading')"
>
    <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-4">
            <div class="space-y-3">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('shell.navigation.items.profile') }}</p>
                <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('shell.profile.heading') }}</h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('shell.profile.description') }}</p>
            </div>

            <a
                href="{{ app(\App\Support\Shell\DashboardUrlResolver::class)->for(auth()->user()) }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            >
                {{ __('shell.actions.back_to_dashboard') }}
            </a>
        </div>
    </section>
</x-layouts.authenticated>
