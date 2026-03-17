@props([
    'heading' => null,
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white/80 px-5 py-6 text-sm text-slate-600" data-empty-state="true">
    @if (filled($heading))
        <p class="font-semibold text-slate-950">{{ $heading }}</p>
    @endif

    @if (filled($description))
        <p @class(['mt-2' => filled($heading)])>{{ $description }}</p>
    @endif

    @if (filled($actionLabel) && filled($actionUrl))
        <a
            href="{{ $actionUrl }}"
            class="mt-4 inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
        >
            {{ $actionLabel }}
        </a>
    @endif
</div>
