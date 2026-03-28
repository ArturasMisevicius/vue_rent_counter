@php
    $toneClasses = [
        'default' => 'bg-slate-900',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-600',
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
</div>
