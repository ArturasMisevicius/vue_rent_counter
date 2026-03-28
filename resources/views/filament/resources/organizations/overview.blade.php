@php
    $toneClasses = [
        'default' => 'bg-slate-900',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-600',
        'info' => 'bg-sky-500',
        'success' => 'bg-emerald-500',
    ];
@endphp

<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('superadmin.organizations.overview.details_heading') }}</h2>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($overview['details'] as $item)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</dt>
                        <dd class="mt-2 text-sm text-slate-900">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('superadmin.organizations.overview.subscription_heading') }}</h2>

            <dl class="mt-6 space-y-4">
                @foreach ($overview['subscription'] as $item)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</dt>
                        <dd class="mt-2 text-sm text-slate-900">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-950">{{ __('superadmin.organizations.overview.health_heading') }}</h2>

        <div class="mt-6 grid gap-4 lg:grid-cols-4">
            @foreach ($overview['health'] as $item)
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $item['value'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($overview['usage'] as $usage)
                <article class="rounded-2xl border border-slate-200 bg-white px-4 py-5">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="font-semibold text-slate-900">{{ $usage['label'] }}</span>
                        <span class="text-slate-500">
                            {{ __('superadmin.organizations.overview.usage_summary', ['current' => $usage['current'], 'limit' => $usage['limit']]) }}
                        </span>
                    </div>

                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full {{ $toneClasses[$usage['tone']] ?? $toneClasses['default'] }}"
                            style="width: {{ $usage['percentage'] }}%;"
                        ></div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">{{ __('superadmin.organizations.overview.activity_feed_heading') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('superadmin.organizations.overview.activity_feed_description') }}</p>
            </div>

            <a
                href="{{ $auditTimelineUrl }}"
                class="inline-flex items-center rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
            >
                {{ __('superadmin.organizations.overview.view_full_audit_timeline') }}
            </a>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($activityFeed as $item)
                <a
                    href="{{ $item['deep_link'] }}"
                    class="block rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 transition hover:border-slate-300 hover:bg-white"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $toneClasses[$item['tone']] ?? $toneClasses['default'] }}"></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <p class="text-sm font-semibold text-slate-950">{{ $item['what'] }}</p>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $item['record'] }}</p>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                                <span>{{ $item['actor'] }}</span>
                                <span aria-hidden="true">•</span>
                                <span>{{ $item['occurred_at'] }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                    {{ __('superadmin.organizations.overview.activity_feed_empty') }}
                </div>
            @endforelse
        </div>
    </section>
</div>
