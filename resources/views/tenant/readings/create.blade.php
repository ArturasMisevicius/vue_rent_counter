<x-layouts.tenant
    :title="__('tenant.pages.readings.title').' · '.config('app.name', 'Tenanto')"
    :breadcrumbs="$breadcrumbs"
>
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="space-y-3">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.navigation.readings') }}</p>
                <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('tenant.pages.readings.heading') }}</h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.pages.readings.description') }}</p>
            </div>
        </section>

        @livewire(\App\Livewire\Tenant\SubmitReadingPage::class)
    </div>
</x-layouts.tenant>
