@props([
    'label',
    'value',
    'icon' => null,
    'description' => null,
    'tone' => 'muted',
])

<x-tenant.card :tone="$tone" {{ $attributes }}>
    <div class="flex items-start gap-3">
        @if (filled($icon))
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm">
                <x-dynamic-component :component="$icon" class="size-5" />
            </span>
        @endif

        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ $label }}</p>
            <p class="mt-2 break-words font-semibold text-slate-950">{{ $value }}</p>

            @if (filled($description))
                <p class="mt-1 break-words text-sm leading-6 text-slate-600">{{ $description }}</p>
            @endif
        </div>
    </div>
</x-tenant.card>
