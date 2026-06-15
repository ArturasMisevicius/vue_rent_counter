@if (filled($href))
    <a href="{{ $href }}" @if ($wireNavigate) wire:navigate @endif {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <article {{ $attributes->class($classes) }}>
        {{ $slot }}
    </article>
@endif
