@props([
    'variant' => 'dark',
])

@php
    $supportedLocales = app(\App\Filament\Support\Preferences\SupportedLocaleOptions::class)->labels();
    $currentLocale = app()->getLocale();
    $isLight = $variant === 'light';
@endphp

<div {{ $attributes->class(['flex items-center justify-end']) }}>
    <form
        method="POST"
        action="{{ route('locale.update') }}"
        @class([
            'flex max-w-full flex-wrap items-center justify-center gap-1 rounded-lg border p-1 sm:inline-flex sm:flex-nowrap',
            'border-white/15 bg-white/10 backdrop-blur' => ! $isLight,
            'border-[#c8b89f] bg-white shadow-sm' => $isLight,
        ])
    >
        @csrf

        @forelse ($supportedLocales as $locale => $label)
            @php($isActive = $currentLocale === $locale)

            <button
                type="submit"
                name="locale"
                value="{{ $locale }}"
                aria-current="{{ $isActive ? 'true' : 'false' }}"
                @class([
                    'rounded-md px-2.5 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.16em] transition sm:px-3 sm:text-[0.72rem] sm:tracking-[0.22em]',
                    'bg-white text-brand-ink shadow-sm' => $isActive && ! $isLight,
                    'text-white/72 hover:bg-white/12 hover:text-white' => ! $isActive && ! $isLight,
                    'bg-[#182131] text-white shadow-sm' => $isActive && $isLight,
                    'text-[#5a6675] hover:bg-[#f6f0e6] hover:text-[#182131]' => ! $isActive && $isLight,
                ])
            >
                {{ $label }}
            </button>
        @empty
        @endforelse
    </form>
</div>
