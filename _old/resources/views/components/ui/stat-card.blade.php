@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'slate',
    'valueColor' => 'text-slate-900',
    'badge' => null,
])

<div @class([
    'relative overflow-hidden rounded-2xl border bg-white p-4 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-md',
    'border-indigo-100' => $tone === 'indigo',
    'border-emerald-100' => $tone === 'emerald',
    'border-amber-100' => $tone === 'amber',
    'border-slate-100' => ! in_array($tone, ['indigo', 'emerald', 'amber'], true),
])>
    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-500/20 via-sky-500/20 to-emerald-400/20"></div>

    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            @if($icon)
                <div @class([
                    'flex h-11 w-11 items-center justify-center rounded-xl ring-1 ring-inset',
                    'bg-indigo-50 text-indigo-700 ring-indigo-100' => $tone === 'indigo',
                    'bg-emerald-50 text-emerald-700 ring-emerald-100' => $tone === 'emerald',
                    'bg-amber-50 text-amber-700 ring-amber-100' => $tone === 'amber',
                    'bg-slate-50 text-slate-700 ring-slate-100' => ! in_array($tone, ['indigo', 'emerald', 'amber'], true),
                ])>
                    {!! $icon !!}
                </div>
            @endif

            <div>
                <p class="truncate text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold {{ $valueColor }}">{{ $value }}</p>

                @if($hint)
                    <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
                @endif
            </div>
        </div>

        @if($badge)
            <div class="rounded-full bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">{{ $badge }}</div>
        @endif
    </div>
</div>
