@if (filled($href))
    <a href="{{ $href }}" @if ($wireNavigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        @if (filled($icon))
            <x-dynamic-component :component="$icon" class="size-4 shrink-0" />
        @endif

        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if (filled($icon))
            <x-dynamic-component :component="$icon" class="size-4 shrink-0" />
        @endif

        {{ $slot }}
    </button>
@endif
