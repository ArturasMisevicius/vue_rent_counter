<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ __('shell.profile.eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('shell.profile.title') }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('shell.profile.description') }}</p>
        </section>

        @include('filament.pages.partials.account-profile-sections')
    </div>
</x-filament-panels::page>
