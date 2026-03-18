<x-filament-panels::page>
    @if ($context['dashboardComponent'])
        @livewire($context['dashboardComponent'], [], key('dashboard-'.$context['dashboardKey']))
    @else
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.title') }}</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('dashboard.not_available') }}</p>
        </section>
    @endif
</x-filament-panels::page>
