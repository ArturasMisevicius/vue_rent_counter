<nav class="fixed inset-x-0 bottom-0 z-10 border-t border-slate-200/80 bg-white/95 backdrop-blur" data-shell-nav="tenant-bottom">
    <div class="mx-auto grid max-w-5xl grid-cols-4 gap-2 px-3 py-3">
        @foreach ($items as $item)
            <a
                href="{{ $item->url }}"
                wire:key="tenant-nav-{{ $item->routeName }}"
                wire:navigate
                @if ($item->active)
                    aria-current="page"
                    data-shell-current="{{ $item->routeName }}"
                @endif
                @class([
                    'inline-flex min-h-14 items-center justify-center rounded-2xl px-3 text-center text-sm font-semibold transition',
                    'bg-brand-ink text-white shadow-[0_12px_32px_rgba(19,38,63,0.18)]' => $item->active,
                    'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! $item->active,
                ])
            >
                {{ $item->label }}
            </a>
        @endforeach
    </div>
</nav>
