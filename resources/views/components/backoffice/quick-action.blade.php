@props([
    'href',
    'title',
    'description' => null,
    'variant' => 'solid',
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'group relative block rounded-2xl px-5 py-4 transition',
        'border border-slate-300 bg-white shadow-sm hover:-translate-y-0.5 hover:border-indigo-400 hover:shadow-md' => $variant === 'solid',
        'border-2 border-dashed border-slate-300 bg-white hover:border-indigo-400 hover:bg-indigo-50' => $variant === 'dashed',
    ]) }}
>
    <div class="flex items-center gap-4">
        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100">
            @isset($icon)
                {{ $icon }}
            @else
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            @endisset
        </div>
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            @if($description)
                <p class="text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>
    </div>
</a>
