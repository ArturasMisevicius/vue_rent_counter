<div
    data-framework-alert
    {{ $attributes->class([
        'rounded-[1.5rem] border p-5 shadow-sm transition',
        'border-framework-300 bg-white/90 text-slate-700' => $type === 'info',
        'border-emerald-200 bg-emerald-50/90 text-emerald-900' => $type === 'success',
        'border-amber-200 bg-amber-50/90 text-amber-950' => $type === 'warning',
        'border-slate-200 bg-slate-50 text-slate-700' => ! in_array($type, ['info', 'success', 'warning'], true),
    ]) }}
>
    <div class="flex items-start gap-4">
        <span @class([
            'inline-flex rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em]',
            'bg-framework-500 text-white' => $type === 'info',
            'bg-emerald-600 text-white' => $type === 'success',
            'bg-amber-500 text-slate-950' => $type === 'warning',
            'bg-slate-900 text-white' => ! in_array($type, ['info', 'success', 'warning'], true),
        ])>
            {{ strtoupper($type) }}
        </span>

        <div class="min-w-0 flex-1 space-y-2">
            @if ($slots->has('title'))
                <h3 class="text-sm font-semibold text-slate-950">{{ $slots['title'] }}</h3>
            @endif

            <div class="text-sm leading-6">
                {{ $slot }}
            </div>
        </div>
    </div>

    <style>
        [data-framework-alert] a {
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }
    </style>
</div>
