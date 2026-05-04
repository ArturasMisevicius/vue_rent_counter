@props([
    'groups' => [],
])

@php
    $groups = collect($groups)->filter(fn ($group): bool => ! empty($group['readings'] ?? []));
@endphp

<x-tenant.card class="px-4 py-4 sm:px-5 sm:py-5" data-tenant-recent-readings>
    <x-tenant.section-heading
        icon="heroicon-m-clipboard-document-list"
        icon-tone="white"
        :title="__('tenant.pages.home.recent_readings')"
    />

    <div class="mt-4 space-y-4">
        @forelse ($groups as $group)
            <section
                wire:key="recent-reading-group-{{ $group['id'] ?? $loop->index }}"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                data-tenant-recent-readings-group
            >
                <header class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm">
                            <x-heroicon-m-home-modern class="size-5" />
                        </span>

                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-950">{{ $group['property_name'] ?? __('tenant.shell.assigned_property') }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                                @if (filled($group['building_name'] ?? null))
                                    <span class="inline-flex min-w-0 items-center gap-1.5">
                                        <x-heroicon-m-building-office-2 class="size-4 shrink-0" />
                                        <span class="truncate">{{ $group['building_name'] }}</span>
                                    </span>
                                @endif

                                @if (filled($group['unit_number'] ?? null))
                                    <span>{{ __('tenant.pages.property.property_unit_label', [
                                        'type' => $group['property_type'] ?? __('tenant.shell.assigned_property'),
                                        'unit' => $group['unit_number'],
                                    ]) }}</span>
                                @endif

                                <span>{{ trans_choice('tenant.pages.home.recent_readings_count', (int) ($group['reading_count'] ?? count($group['readings'] ?? [])), [
                                    'count' => (int) ($group['reading_count'] ?? count($group['readings'] ?? [])),
                                ]) }}</span>
                            </div>
                        </div>
                    </div>

                    @if (filled($group['url'] ?? null))
                        <a
                            href="{{ $group['url'] }}"
                            wire:navigate
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-brand-mint/35"
                        >
                            <span>{{ __('tenant.navigation.property') }}</span>
                            <x-heroicon-m-arrow-up-right class="size-4" />
                        </a>
                    @endif
                </header>

                <div class="divide-y divide-slate-100">
                    @foreach ($group['readings'] as $reading)
                        <article id="tenant-reading-{{ $reading['id'] }}" wire:key="reading-{{ $reading['id'] }}" class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-mint/15 text-brand-ink">
                                    <x-heroicon-m-bolt class="size-4" />
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-950">{{ $reading['meter_name'] ?? $reading['meter_identifier'] }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                                        @if (filled($reading['meter_identifier'] ?? null))
                                            <span class="rounded-lg bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600">{{ $reading['meter_identifier'] }}</span>
                                        @endif

                                        @if (filled($reading['meter_type'] ?? null))
                                            <span>{{ $reading['meter_type'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                <span class="inline-flex min-h-9 items-center rounded-xl bg-slate-100 px-3 text-sm font-semibold text-slate-950">
                                    {{ $reading['value'] }} {{ $reading['unit'] }}
                                </span>
                                <span class="inline-flex min-h-9 items-center gap-1.5 rounded-xl bg-white px-3 text-sm text-slate-500 ring-1 ring-slate-200">
                                    <x-heroicon-m-calendar-days class="size-4" />
                                    {{ $reading['date'] }}
                                </span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <x-shared.empty-state
                icon="heroicon-m-beaker"
                :title="__('tenant.pages.home.recent_readings')"
                :description="__('tenant.messages.no_readings_yet')"
            />
        @endforelse
    </div>
</x-tenant.card>