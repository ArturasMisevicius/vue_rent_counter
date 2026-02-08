@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'slate',
])

<div class="relative overflow-hidden rounded-2xl border {{ match($tone) {
    'indigo' => 'border-indigo-100',
    'emerald' => 'border-emerald-100',
    'amber' => 'border-amber-100',
    default => 'border-slate-100',
} }} bg-white p-4 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-500/20 via-sky-500/20 to-emerald-400/20"></div>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            @if($icon)
                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ match($tone) {
                    'indigo' => 'bg-indigo-50',
                    'emerald' => 'bg-emerald-50',
                    'amber' => 'bg-amber-50',
                    default => 'bg-slate-50',
                } }} {{ match($tone) {
                    'indigo' => 'text-indigo-700',
                    'emerald' => 'text-emerald-700',
                    'amber' => 'text-amber-700',
                    default => 'text-slate-700',
                } }} ring-1 ring-inset {{ match($tone) {
                    'indigo' => 'ring-indigo-100',
                    'emerald' => 'ring-emerald-100',
                    'amber' => 'ring-amber-100',
                    default => 'ring-slate-100',
                } }}">
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
        <div class="rounded-full bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">{{ __('dashboard.manager.badge') }}</div>
    </div>
</div>
