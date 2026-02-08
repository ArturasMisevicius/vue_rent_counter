@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'slate',
])

@php
$toneStyles = [
    'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-100'],
    'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-100'],
    'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-100'],
    'slate' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-100'],
];

$tone = $toneStyles[$tone] ?? $toneStyles['slate'];
@endphp

<div class="relative overflow-hidden rounded-2xl border {{ $tone['border'] }} bg-white p-4 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-500/20 via-sky-500/20 to-emerald-400/20"></div>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            @if($icon)
                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone['bg'] }} {{ $tone['text'] }} ring-1 ring-inset {{ $tone['border'] }}">
                    {!! $icon !!}
                </div>
            @endif
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $value }}</p>
                @if($hint)
                    <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
                @endif
            </div>
        </div>
        <div class="rounded-full bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">Manager</div>
    </div>
</div>
