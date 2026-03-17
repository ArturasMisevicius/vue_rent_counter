@props([
    'icon',
    'title',
    'description' => null,
])

<div class="rounded-[2rem] border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]">
    <div class="mx-auto flex max-w-md flex-col items-center gap-4">
        <div class="flex size-14 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-[0_18px_36px_rgba(15,23,42,0.16)]">
            <x-dynamic-component :component="$icon" class="size-7" />
        </div>

        <div class="space-y-2">
            <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ $title }}</h3>

            @if (filled($description))
                <p class="text-sm leading-7 text-slate-600">{{ $description }}</p>
            @endif
        </div>

        @if (trim((string) $slot) !== '')
            <div class="flex flex-wrap justify-center gap-3">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
