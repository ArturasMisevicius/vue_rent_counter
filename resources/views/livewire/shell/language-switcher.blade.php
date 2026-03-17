<div x-data="{ open: false }" class="relative" data-shell-locale="switcher">
    <button
        type="button"
        x-on:click="open = ! open"
        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
    >
        <span>{{ $currentLocaleLabel }}</span>
        <svg class="size-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-on:click.outside="open = false"
        class="absolute right-0 top-full z-20 mt-2 w-48 rounded-[1.5rem] border border-slate-200 bg-white p-2 shadow-[0_20px_70px_rgba(15,23,42,0.16)]"
    >
        @foreach ($locales as $locale => $label)
            @php($isActive = $currentLocale === $locale)

            <button
                type="button"
                wire:click="changeLocale('{{ $locale }}')"
                x-on:click="open = false"
                @class([
                    'flex w-full items-center justify-between rounded-2xl px-3 py-2 text-left text-sm transition',
                    'bg-slate-100 text-slate-950' => $isActive,
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-950' => ! $isActive,
                ])
            >
                <span>{{ $label }}</span>
                <span class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ mb_strtoupper($locale) }}</span>
            </button>
        @endforeach
    </div>
</div>
