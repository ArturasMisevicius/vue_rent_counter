@php
    $sections = [
        ['key' => 'billing_cards', 'title' => __('dashboard.attention.sections.billing_progress'), 'icon' => 'heroicon-m-banknotes'],
        ['key' => 'tenant_onboarding_cards', 'title' => __('dashboard.attention.sections.tenant_onboarding'), 'icon' => 'heroicon-m-user-plus'],
        ['key' => 'configuration_health_cards', 'title' => __('dashboard.attention.sections.configuration_health'), 'icon' => 'heroicon-m-wrench-screwdriver'],
        ['key' => 'contract_cards', 'title' => __('dashboard.attention.sections.contracts'), 'icon' => 'heroicon-m-document-text'],
        ['key' => 'document_cards', 'title' => __('dashboard.attention.sections.documents'), 'icon' => 'heroicon-m-paper-clip'],
        ['key' => 'data_integrity_cards', 'title' => __('dashboard.attention.sections.data_integrity'), 'icon' => 'heroicon-m-shield-exclamation'],
    ];
@endphp

<div wire:poll.visible.30s="refreshDashboardOnInterval" class="space-y-8">
    <section class="border-b border-slate-200 pb-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-semibold text-emerald-700">{{ __('dashboard.attention.header.greeting') }}</p>
                <h2 class="text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.attention.header.title') }}</h2>

                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600">
                    <span>{{ __('dashboard.attention.header.organization', ['organization' => $dashboard['summary']['organization_name']]) }}</span>
                    <span>{{ __('dashboard.attention.header.period', ['period' => $dashboard['summary']['billing_period']]) }}</span>
                </div>
            </div>

            <div class="w-full max-w-sm">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">{{ __('dashboard.attention.header.billing_completion') }}</span>
                    <span class="font-semibold text-slate-950">{{ $dashboard['summary']['billing_completion'] }}%</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-slate-200">
                    <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $dashboard['summary']['billing_completion'] }}%"></div>
                </div>
            </div>
        </div>
    </section>

    @if ($dashboard['top_cards'] !== [])
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            @foreach ($dashboard['top_cards'] as $card)
                @php($toneClass = match ($card['tone'] ?? 'default') {
                    'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    'info' => 'border-sky-200 bg-sky-50 text-sky-700',
                    default => 'border-slate-200 bg-white text-slate-700',
                })

                <a
                    href="{{ $card['url'] ?? '#' }}"
                    wire:navigate
                    class="group rounded-lg border {{ $toneClass }} p-4 transition hover:-translate-y-0.5 hover:shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold uppercase text-current">{{ $card['label'] ?? '' }}</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $card['count'] ?? 0 }}</p>
                        </div>
                        <x-dynamic-component :component="$card['icon'] ?? 'heroicon-m-circle-stack'" class="size-5 shrink-0" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-700">{{ $card['action'] ?? '' }}</p>
                </a>
            @endforeach
        </section>
    @endif

    <section class="space-y-4">
        <div class="flex items-center gap-2">
            <x-heroicon-m-exclamation-triangle class="size-5 text-amber-600" />
            <h3 class="text-lg font-semibold text-slate-950">{{ __('dashboard.attention.sections.needs_action') }}</h3>
        </div>

        @if ($dashboard['needs_action_items'] !== [])
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('dashboard.attention.table.priority') }}</th>
                            <th class="px-4 py-3">{{ __('dashboard.attention.table.issue') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('dashboard.attention.table.count') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('dashboard.attention.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($dashboard['needs_action_items'] as $item)
                            @php($priorityClass = match ($item['priority']) {
                                'high' => 'bg-rose-100 text-rose-700',
                                'medium' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            })

                            <tr>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClass }}">
                                        {{ $item['priority_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $item['issue'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ $item['count'] }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ $item['url'] }}" wire:navigate class="font-semibold text-emerald-700 hover:text-emerald-900">
                                        {{ $item['action'] }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-shared.empty-state
                icon="heroicon-m-check-circle"
                :title="$dashboard['summary']['empty_title']"
                :description="$dashboard['summary']['empty_description']"
            />
        @endif
    </section>

    @if (($dashboard['visible_widgets']['billing'] ?? false) && $dashboard['billing_progress']['stages'] !== [])
        <section class="space-y-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-semibold text-slate-950">
                    {{ __('dashboard.attention.sections.billing_for_period', ['period' => $dashboard['billing_progress']['period']]) }}
                </h3>
                <p class="text-sm font-medium text-slate-600">
                    {{ __('dashboard.attention.progress.total_invoices', ['count' => $dashboard['billing_progress']['total_invoices']]) }}
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($dashboard['billing_progress']['stages'] as $stage)
                    @php($stageClass = match ($stage['tone']) {
                        'warning' => 'border-amber-200 bg-amber-50',
                        'success' => 'border-emerald-200 bg-emerald-50',
                        'info' => 'border-sky-200 bg-sky-50',
                        default => 'border-slate-200 bg-white',
                    })

                    <div class="rounded-lg border {{ $stageClass }} p-4">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ $stage['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $stage['count'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ($sections as $section)
        @continue(($dashboard[$section['key']] ?? []) === [])

        <section class="space-y-4">
            <div class="flex items-center gap-2">
                <x-dynamic-component :component="$section['icon']" class="size-5 text-slate-500" />
                <h3 class="text-lg font-semibold text-slate-950">{{ $section['title'] }}</h3>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($dashboard[$section['key']] as $card)
                    @php($cardClass = match ($card['tone'] ?? 'default') {
                        'danger' => 'border-rose-200 hover:border-rose-300',
                        'warning' => 'border-amber-200 hover:border-amber-300',
                        'success' => 'border-emerald-200 hover:border-emerald-300',
                        'info' => 'border-sky-200 hover:border-sky-300',
                        default => 'border-slate-200 hover:border-slate-300',
                    })

                    <a
                        href="{{ $card['url'] ?? '#' }}"
                        wire:navigate
                        class="group rounded-lg border {{ $cardClass }} bg-white p-4 transition hover:bg-slate-50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-700">{{ $card['label'] ?? '' }}</p>
                                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $card['count'] ?? 0 }}</p>
                            </div>
                            <x-dynamic-component :component="$card['icon'] ?? 'heroicon-m-circle-stack'" class="size-5 shrink-0 text-slate-400 transition group-hover:text-slate-700" />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-emerald-700">{{ $card['action'] ?? '' }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="space-y-4">
        <div class="flex items-center gap-2">
            <x-heroicon-m-clock class="size-5 text-slate-500" />
            <h3 class="text-lg font-semibold text-slate-950">{{ __('dashboard.attention.sections.recent_activity') }}</h3>
        </div>

        @if ($dashboard['recent_activity'] !== [])
            <div class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
                @foreach ($dashboard['recent_activity'] as $activity)
                    <a href="{{ $activity['url'] }}" wire:navigate class="flex flex-col gap-1 px-4 py-3 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-sm text-slate-700">
                            <span class="font-semibold text-slate-950">{{ $activity['who'] }}</span>
                            {{ $activity['what'] }}
                        </span>
                        <span class="text-xs font-medium text-slate-500">{{ $activity['when'] }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <x-shared.empty-state
                icon="heroicon-m-clock"
                :title="__('dashboard.attention.empty.no_activity_title')"
                :description="__('dashboard.attention.empty.no_activity_description')"
            />
        @endif
    </section>
</div>
