@props([
    'id',
    'label',
])

<label for="{{ $id }}" {{ $attributes->whereDoesntStartWith('wire:model')->class('block rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-medium text-slate-700') }}>
    <span class="font-semibold">{{ $label }}</span>
    <select
        id="{{ $id }}"
        {{ $attributes->whereStartsWith('wire:model')->class('mt-2 block min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30') }}
    >
        {{ $slot }}
    </select>
</label>
