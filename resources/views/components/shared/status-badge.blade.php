@props([
    'status',
    'model' => null,
])

@php
    $statusValue = $status instanceof \BackedEnum ? (string) $status->value : (string) $status;

    $styles = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-300/80',
        'scheduled' => 'bg-sky-50 text-sky-700 ring-sky-300/80',
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
        'finalized' => 'bg-brand-ink/10 text-brand-ink ring-brand-ink/15',
        'partially_paid' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
        'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
        'active' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
        'valid' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
        'overdue' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
        'failed' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
        'rejected' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
        'suspended' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
        'flagged' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
        'degraded' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
        'void' => 'bg-slate-200 text-slate-700 ring-slate-300/80',
        'inactive' => 'bg-slate-100 text-slate-700 ring-slate-300/80',
        'info' => 'bg-sky-50 text-sky-800 ring-sky-300/80',
        'sent' => 'bg-sky-50 text-sky-800 ring-sky-300/80',
        'healthy' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
        'low' => 'bg-slate-100 text-slate-700 ring-slate-300/80',
        'medium' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
        'high' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
        'critical' => 'bg-rose-100 text-rose-900 ring-rose-400/80',
    ];

    $resolvedClasses = $styles[$statusValue] ?? 'bg-slate-100 text-slate-700 ring-slate-300/80';

    $translationKey = null;

    if ($status instanceof \BackedEnum && method_exists($status, 'translationKey')) {
        $translationKey = $status->translationKey();
    }

    if ($translationKey === null && $model instanceof \Illuminate\Database\Eloquent\Model) {
        $statusCast = $model->getCasts()['status'] ?? null;

        if (is_string($statusCast) && enum_exists($statusCast) && method_exists($statusCast, 'translationKeyPrefix')) {
            $translationKey = $statusCast::translationKeyPrefix().'.'.$statusValue;
        } else {
            $translationKey = 'enums.'.\Illuminate\Support\Str::snake(class_basename($model)).'_status.'.$statusValue;
        }
    }

    $translationKey ??= 'enums.status.'.$statusValue;
@endphp

<span class="{{ implode(' ', [
    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] ring-1 ring-inset',
    $resolvedClasses,
]) }}">
    {{ __($translationKey) }}
</span>
