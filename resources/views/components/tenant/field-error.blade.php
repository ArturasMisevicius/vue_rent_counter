@props([
    'message',
])

<p {{ $attributes->class('flex items-start gap-2 text-sm font-medium text-rose-700') }}>
    <x-heroicon-m-exclamation-circle class="mt-0.5 size-4 shrink-0" />
    <span>{{ $message }}</span>
</p>
