@props(['title' => null])

<div {{ $attributes->merge(['class' => 'ds-card']) }}>
    @if($title)
        <h3 class="ds-card__title">
            <span class="ds-card__title-dot"></span>
            {{ $title }}
        </h3>
    @endif

    {{ $slot }}
</div>
