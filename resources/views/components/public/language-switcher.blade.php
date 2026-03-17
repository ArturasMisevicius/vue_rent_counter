@php
    $supportedLocales = app(\App\Filament\Support\Preferences\SupportedLocaleOptions::class)->labels();
    $currentLocale = app()->getLocale();
@endphp

<div {{ $attributes->class(['flex items-center justify-end']) }}>
    <form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/10 p-1 backdrop-blur">
        @csrf

        @forelse ($supportedLocales as $locale => $label)
            @php($isActive = $currentLocale === $locale)

            <button
                type="submit"
                name="locale"
                value="{{ $locale }}"
                aria-current="{{ $isActive ? 'true' : 'false' }}"
                class="@class([
                    'rounded-full px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.22em] transition',
                    'bg-white text-brand-ink shadow-sm' => $isActive,
                    'text-white/72 hover:bg-white/12 hover:text-white' => ! $isActive,
                ])"
            >
                {{ $label }}
            </button>
        @empty
        @endforelse
    </form>
</div>
