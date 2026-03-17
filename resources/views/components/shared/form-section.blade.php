@props([
    'title',
    'description' => null,
])

<section class="space-y-5">
    <div class="space-y-2 border-b border-slate-200/80 pb-4">
        <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ $title }}</h2>

        @if (filled($description))
            <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
        @endif
    </div>

    <div class="space-y-5">
        {{ $slot }}
    </div>
</section>
