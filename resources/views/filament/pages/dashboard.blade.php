<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ $context['eyebrow'] }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $context['heading'] }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $context['description'] }}</p>

            @if ($context['actions'] !== [])
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($context['actions'] as $action)
                        <a
                            href="{{ $action['url'] }}"
                            wire:navigate
                            class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($context['showTenantSummary'])
            @island(lazy: true)
                @placeholder
                    <section
                        class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]"
                        data-dashboard-tenant-summary-placeholder="true"
                    >
                        <div class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
                            <div class="space-y-3 animate-pulse">
                                <div class="h-3 w-32 rounded-full bg-slate-200"></div>
                                <div class="h-10 w-2/3 rounded-full bg-slate-200"></div>
                                <div class="h-4 w-full rounded-full bg-slate-100"></div>
                                <div class="h-4 w-5/6 rounded-full bg-slate-100"></div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="h-40 rounded-[1.75rem] bg-slate-100 animate-pulse"></div>
                                <div class="h-40 rounded-[1.75rem] bg-slate-100 animate-pulse"></div>
                            </div>

                            <div class="h-64 rounded-[1.75rem] bg-slate-100 animate-pulse"></div>
                        </div>

                        <div class="h-[28rem] rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur animate-pulse"></div>
                    </section>
                @endplaceholder

                <livewire:tenant.home-summary />
            @endisland
        @endif
    </div>
</x-filament-panels::page>
