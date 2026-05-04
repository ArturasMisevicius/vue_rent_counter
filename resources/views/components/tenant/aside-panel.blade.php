@props([])

<aside
    {{ $attributes
        ->merge(['data-tenant-panel' => 'aside'])
        ->class('flex w-full flex-col gap-5 rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_22px_64px_rgba(15,23,42,0.12)] backdrop-blur sm:p-6 xl:w-[24rem] 2xl:w-[28rem]') }}
>
    {{ $slot }}
</aside>
