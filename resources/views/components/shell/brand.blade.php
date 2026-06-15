<span {{ $attributes->class('inline-flex items-center gap-4') }}>
    <span class="flex size-12 items-center justify-center rounded-2xl border text-lg font-semibold {{ $badgeClass }}">
        T
    </span>

    <span class="flex flex-col">
        <span class="font-display text-xl tracking-tight {{ $titleClass }}">Tenanto</span>
        <span class="text-xs uppercase tracking-[0.24em] {{ $taglineClass }}">{{ __('auth.brand_tagline') }}</span>
    </span>
</span>
