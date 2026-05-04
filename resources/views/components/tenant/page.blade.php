@props([])

<div
    {{ $attributes
        ->merge(['data-tenant-layout' => 'standard'])
        ->class('mx-auto flex w-full max-w-[112rem] flex-col gap-5 sm:gap-6') }}
>
    {{ $slot }}
</div>
