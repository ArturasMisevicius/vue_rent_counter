@props(['status'])

@php
$translations = array_merge(
    \App\Enums\InvoiceStatus::labels(),
    \App\Enums\ServiceType::labels(),
    \App\Enums\UserRole::labels(),
    \App\Enums\MeterType::labels(),
    \App\Enums\PropertyType::labels(),
    \App\Enums\SubscriptionStatus::labels(),
    \App\Enums\SubscriptionPlanType::labels(),
    \App\Enums\UserAssignmentAction::labels(),
);

$statusString = (string) $status;
$label = $translations[$statusString] ?? null;

if (!$label) {
    $slotLabel = trim((string) $slot);
    $label = $slotLabel !== '' ? $slotLabel : ucwords(str_replace('_', ' ', $statusString));
}

$classes = match($statusString) {
    'draft' => 'bg-amber-50 text-amber-700 border-amber-200',
    'finalized' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
    'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'inactive' => 'bg-slate-100 text-slate-700 border-slate-200',
    'expired' => 'bg-rose-50 text-rose-700 border-rose-200',
    'suspended' => 'bg-amber-50 text-amber-700 border-amber-200',
    'cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
    default => 'bg-slate-100 text-slate-700 border-slate-200',
};

$dotClass = match($statusString) {
    'draft' => 'bg-amber-400',
    'finalized' => 'bg-indigo-500',
    'paid' => 'bg-emerald-500',
    'active' => 'bg-emerald-500',
    'inactive' => 'bg-slate-400',
    'expired' => 'bg-rose-400',
    'suspended' => 'bg-amber-400',
    'cancelled' => 'bg-slate-400',
    default => 'bg-slate-400',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border $classes"]) }}>
    <span class="h-2.5 w-2.5 rounded-full {{ $dotClass }}"></span>
    <span>{{ $label }}</span>
</span>
