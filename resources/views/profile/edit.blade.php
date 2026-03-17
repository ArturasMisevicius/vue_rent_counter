<x-layouts.app
    :title="__('shell.profile.title').' · '.config('app.name', 'Tenanto')"
    :heading="__('shell.profile.heading')"
    :subtitle="__('shell.profile.description')"
>
    <x-slot:actions>
        <a
            href="{{ app(\App\Filament\Support\Shell\DashboardUrlResolver::class)->for(auth()->user()) }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
        >
            {{ __('shell.actions.back_to_dashboard') }}
        </a>
    </x-slot:actions>

    <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <x-shared.form-section :title="__('shell.navigation.items.profile')" :description="__('shell.profile.description')" />
    </section>
</x-layouts.app>
