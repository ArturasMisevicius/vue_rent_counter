@props([])

<section
    {{ $attributes
        ->merge(['data-tenant-panel' => 'main'])
        ->class('flex min-w-0 flex-1 flex-col gap-5 rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:gap-6 sm:p-6') }}
>
    {{ $slot }}
</section>
