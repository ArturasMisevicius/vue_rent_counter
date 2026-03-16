@props([
    'title',
    'description' => null,
    'eyebrow' => null,
    'variant' => 'surface',
])

<div {{ $attributes->class('ds-shell space-y-6') }}>
    <section @class([
        'relative overflow-hidden rounded-3xl',
        'border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-200/60 backdrop-blur-sm' => $variant !== 'hero',
        'border border-slate-200 bg-gradient-to-r from-indigo-600 via-sky-500 to-indigo-500 text-white shadow-2xl shadow-indigo-500/20' => $variant === 'hero',
    ])>
        <div class="pointer-events-none absolute inset-0 opacity-60">
            <div @class([
                'absolute -left-14 -top-20 h-56 w-56 rounded-full blur-3xl',
                'bg-indigo-500/10' => $variant !== 'hero',
                'bg-white/10' => $variant === 'hero',
            ])></div>
            <div @class([
                'absolute right-0 top-0 h-44 w-44 rounded-full blur-3xl',
                'bg-sky-400/10' => $variant !== 'hero',
                'bg-white/10' => $variant === 'hero',
            ])></div>
        </div>

        <div class="relative px-5 py-6 sm:px-8 sm:py-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="ds-shell__header">
                    @if($eyebrow || $variant === 'hero')
                        <p @class([
                            'text-xs font-semibold uppercase tracking-[0.22em]',
                            'text-indigo-500' => $variant !== 'hero',
                            'text-indigo-100/90' => $variant === 'hero',
                        ])>
                            {{ $eyebrow ?? __('dashboard.shared.workspace_label') }}
                        </p>
                    @endif

                    <h1 @class([
                        'ds-shell__title mt-1 text-2xl font-semibold sm:text-3xl',
                        'text-slate-900' => $variant !== 'hero',
                        'text-white' => $variant === 'hero',
                    ])>{{ $title }}</h1>

                    @if($description)
                        <p @class([
                            'ds-shell__description mt-2 max-w-3xl text-sm sm:text-base',
                            'text-slate-600' => $variant !== 'hero',
                            'text-indigo-50/90' => $variant === 'hero',
                        ])>{{ $description }}</p>
                    @endif

                    @isset($meta)
                        <div @class([
                            'mt-3 flex flex-wrap gap-2 text-[12px] font-semibold',
                            'text-slate-500' => $variant !== 'hero',
                            'text-indigo-50/80' => $variant === 'hero',
                        ])>
                            {{ $meta }}
                        </div>
                    @endisset
                </div>

                @isset($actions)
                    <div class="flex flex-wrap gap-3">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        </div>
    </section>

    <div class="space-y-6">
        {{ $slot }}
    </div>
</div>
