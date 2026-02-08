@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

@php
    $buttonClasses = 'ds-btn ' . match($variant) {
        'secondary' => 'ds-btn--secondary',
        'danger' => 'ds-btn--danger',
        default => 'ds-btn--primary',
    };
@endphp

@if($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $buttonClasses]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $buttonClasses]) }}
    >
        {{ $slot }}
    </button>
@endif
