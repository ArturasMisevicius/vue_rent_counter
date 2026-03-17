@php
    $currentLocaleConfig = data_get($this->locales, $currentLocale, data_get($this->locales, 'en'));
@endphp

<details class="relative">
    <summary class="list-none cursor-pointer">
        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 shadow-sm">
            {{ data_get($currentLocaleConfig, 'abbreviation', 'EN') }}
        </span>
    </summary>

    <div class="absolute right-0 top-[calc(100%+0.75rem)] z-50 w-44 rounded-3xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-950/10">
        @foreach ($this->locales as $localeCode => $locale)
            <button
                type="button"
                wire:key="shell-locale-{{ $localeCode }}"
                wire:click="switchLocale('{{ $localeCode }}')"
                class="flex w-full items-center rounded-2xl px-3 py-2 text-left text-sm font-medium transition hover:bg-slate-50"
                @disabled($currentLocale === $localeCode)
            >
                {{ $locale['native_name'] }}
            </button>
        @endforeach
    </div>
</details>
