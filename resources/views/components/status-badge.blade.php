@props(['status'])

@php
$translations = array_merge(
    \App\Enums\InvoiceStatus::labels(),
    \App\Enums\ServiceType::labels(),
    \App\Enums\UserRole::labels(),
    \App\Enums\MeterType::labels(),
    \App\Enums\PropertyType::labels(),
);

$statusString = (string) $status;
$label = $translations[$statusString] ?? null;

if (!$label) {
    $slotLabel = trim((string) $slot);
    $label = $slotLabel !== '' ? $slotLabel : ucwords(str_replace('_', ' ', $statusString));
}

$classes = match($status) {
    'draft' => 'bg-yellow-100 text-yellow-800',
    'finalized' => 'bg-blue-100 text-blue-800',
    'paid' => 'bg-green-100 text-green-800',
    'active' => 'bg-green-100 text-green-800',
    'inactive' => 'bg-gray-100 text-gray-800',
    default => 'bg-gray-100 text-gray-800',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
