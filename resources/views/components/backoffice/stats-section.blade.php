@props([
    'title' => null,
    'description' => null,
    'columns' => 4,
])

<section {{ $attributes->class('space-y-4') }}>
    @if($title || $description)
        <header class="space-y-1">
            @if($title)
                <h2 class="text-lg font-medium text-slate-900">{{ $title }}</h2>
            @endif

            @if($description)
                <p class="text-sm text-slate-500">{{ $description }}</p>
            @endif
        </header>
    @endif

    <div @class([
        'grid grid-cols-1 gap-5',
        'sm:grid-cols-3' => (int) $columns === 3,
        'sm:grid-cols-2' => (int) $columns === 2,
        'sm:grid-cols-2 lg:grid-cols-4' => ! in_array((int) $columns, [2, 3], true),
    ])>
        {{ $slot }}
    </div>
</section>
