@props([
    'title',
    'description' => null,
])

<div class="ds-shell">
    <div class="ds-shell__inner">
        <div class="ds-shell__header">
            <h1 class="ds-shell__title">{{ $title }}</h1>
            @if($description)
                <p class="ds-shell__description">{{ $description }}</p>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>
