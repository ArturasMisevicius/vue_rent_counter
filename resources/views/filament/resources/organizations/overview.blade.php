@php
    $toneClasses = [
        'default' => 'bg-slate-900',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-600',
    ];
@endphp

<div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-950">Organization Details</h2>

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
        <h2 class="text-lg font-semibold text-slate-950">Subscription Summary</h2>

        <dl class="mt-6 space-y-4">
            @foreach ($overview['subscription'] as $item)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</dt>
                    <dd class="mt-2 text-sm text-slate-900">{{ $item['value'] }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-6 space-y-4">
            @foreach ($overview['usage'] as $usage)
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="font-semibold text-slate-900">{{ $usage['label'] }}</span>
                        <span class="text-slate-500">{{ $usage['current'] }} of {{ $usage['limit'] }}</span>
                    </div>

                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full {{ $toneClasses[$usage['tone']] ?? $toneClasses['default'] }}"
                            style="width: {{ $usage['percentage'] }}%;"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
