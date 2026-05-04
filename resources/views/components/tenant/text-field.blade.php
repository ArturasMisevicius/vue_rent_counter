@props([
    'id',
    'label',
    'as' => 'input',
    'type' => 'text',
    'errors' => [],
])

<div class="space-y-2">
    <label for="{{ $id }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>

    @if ($as === 'textarea')
        <textarea
            id="{{ $id }}"
            {{ $attributes->class('block min-h-24 w-full resize-y rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30') }}
        >{{ $slot }}</textarea>
    @else
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            {{ $attributes->class('block min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30') }}
        />
    @endif

    @foreach ($errors as $message)
        <x-tenant.field-error :message="$message" />
    @endforeach
</div>
