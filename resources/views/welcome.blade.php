<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vilnius Utilities · Rent Counter</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Manrope"', 'system-ui', 'sans-serif'],
                        display: ['"Space Grotesk"', '"Manrope"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        midnight: '#0f172a',
                        skyline: '#38bdf8',
                        indigoInk: '#6366f1',
                    },
                    boxShadow: {
                        glow: '0 18px 50px rgba(99, 102, 241, 0.2)',
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-50 antialiased">
@php
    $brand = config('landing.brand', []);
    $features = config('landing.features', []);
    $publishedFaqs = \App\Models\Faq::query()->published()->orderBy('display_order')->orderBy('id')->get();
    $faqItems = $publishedFaqs->isNotEmpty()
        ? $publishedFaqs->map(fn ($faq) => [
            'question' => $faq->question,
            'answer' => $faq->answer,
            'category' => $faq->category,
        ])
        : collect(config('landing.faq', []));
    $canLogin = Route::has('login');
    $canRegister = Route::has('register');
    $languages = \App\Models\Language::query()->where('is_active', true)->orderBy('display_order')->get();
    $currentLocale = app()->getLocale();
    $canSwitchLocale = Route::has('locale.set');
@endphp

<div class="relative overflow-hidden min-h-screen">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 -top-32 h-80 w-80 rounded-full bg-indigo-500/30 blur-[120px]"></div>
        <div class="absolute right-0 top-10 h-72 w-72 rounded-full bg-sky-400/25 blur-[110px]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-950 to-slate-950"></div>
    </div>

    <header class="relative max-w-6xl mx-auto px-6 pt-10 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white font-display text-xl shadow-glow">V</span>
            <div class="leading-tight">
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300">{{ __('app.brand.name') }}</p>
                <p class="font-display text-lg text-white">{{ __('app.brand.product') }}</p>
            </div>
        </div>

        @if($canLogin)
            <div class="flex items-center gap-3">
                @if($canSwitchLocale)
                    <form method="POST" action="{{ route('locale.set') }}" class="hidden sm:block">
                        @csrf
                        <select name="locale" onchange="this.form.submit()" class="bg-white/10 border border-white/20 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-white/40">
                            @foreach($languages as $language)
                                <option value="{{ $language->code }}" {{ $language->code === $currentLocale ? 'selected' : '' }}>
                                    {{ $language->native_name ?? $language->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-200 hover:text-white">{{ __('app.cta.login') }}</a>
                @if($canRegister)
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-white text-slate-900 px-4 py-2 text-sm font-semibold shadow-lg shadow-white/20 transition hover:-translate-y-0.5">
                        {{ __('app.cta.register') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" />
                        </svg>
                    </a>
                @endif
            </div>
        @endif
    </header>

    <main class="relative max-w-6xl mx-auto px-6 pb-16">
        <section class="grid lg:grid-cols-2 gap-12 pt-16">
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-sky-200 ring-1 ring-white/10">
                    {{ __('landing.hero.badge') }}
                </p>
                <h1 class="font-display text-4xl sm:text-5xl font-bold text-white leading-tight">
                    {{ __('landing.hero.title') }}
                </h1>
                <p class="text-lg text-slate-300 leading-relaxed">
                    {{ __('landing.hero.tagline') }}
                </p>

                <div class="flex flex-wrap gap-3">
                    @if($canLogin)
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-sky-500 px-5 py-3 text-sm font-semibold text-white shadow-glow transition hover:-translate-y-0.5">
                            {{ __('app.cta.go_to_app') }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @endif
                    @if($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-semibold text-white/90 backdrop-blur transition hover:border-white/40 hover:-translate-y-0.5">
                            {{ __('app.cta.create_account') }}
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm text-slate-300">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-display text-white">5 min</p>
                        <p class="text-slate-400">{{ __('landing.metrics.cache') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-display text-white">Zero</p>
                        <p class="text-slate-400">{{ __('landing.metrics.readings') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-display text-white">100%</p>
                        <p class="text-slate-400">{{ __('landing.metrics.isolation') }}</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 via-sky-400/15 to-transparent blur-3xl"></div>
                <div class="relative rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Live overview</p>
                            <p class="mt-2 text-xl font-display text-white">Portfolio health</p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-200 ring-1 ring-emerald-500/30">
                            Healthy
                        </span>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm text-slate-300">Draft invoices</p>
                            <p class="mt-1 text-3xl font-display text-white">42</p>
                            <p class="text-xs text-slate-400">Ready to finalize</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm text-slate-300">Meters validated</p>
                            <p class="mt-1 text-3xl font-display text-white">98%</p>
                            <p class="text-xs text-slate-400">Across all zones</p>
                        </div>
                        <div class="col-span-2 rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm text-slate-300 mb-2">Recent readings</p>
                            <div class="grid grid-cols-3 gap-3 text-xs text-slate-300">
                                <div class="rounded-xl bg-white/5 px-3 py-2 border border-white/5">
                                    <p class="font-semibold text-white">Water</p>
                                    <p class="text-slate-400">Monotonic ✓</p>
                                </div>
                                <div class="rounded-xl bg-white/5 px-3 py-2 border border-white/5">
                                    <p class="font-semibold text-white">Electricity</p>
                                    <p class="text-slate-400">Anomaly scan ✓</p>
                                </div>
                                <div class="rounded-xl bg-white/5 px-3 py-2 border border-white/5">
                                    <p class="font-semibold text-white">Heating</p>
                                    <p class="text-slate-400">Zone split ✓</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="mt-20 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">{{ __('landing.features_subtitle') }}</p>
                    <h2 class="mt-2 text-3xl font-display font-bold text-white">{{ __('landing.features_title') }}</h2>
                </div>
                @if($canRegister)
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold text-white/90 hover:border-white/30">
                        Start now
                    </a>
                @endif
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($features as $feature)
                    <div class="group relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur transition hover:-translate-y-1 hover:border-white/20">
                        <div class="flex items-center justify-between">
                            <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white inline-flex items-center justify-center shadow-glow">
                                {!! svgIcon($feature['icon'] ?? 'sparkles') !!}
                            </div>
                            <span class="text-xs font-semibold text-slate-400">Trusted</span>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-white">{{ __($feature['title']) }}</h3>
                        <p class="mt-3 text-sm text-slate-300 leading-relaxed">{{ __($feature['description']) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="faq" class="mt-20 grid lg:grid-cols-2 gap-10">
            <div class="space-y-3">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Answers you need</p>
                <h2 class="text-3xl font-display font-bold text-white">FAQ</h2>
                <p class="text-slate-300">
                    {{ __('landing.faq_intro') }}
                </p>
                @if($canLogin)
                    <div class="flex gap-3 pt-3">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow">
                            {{ __('app.cta.login') }}
                        </a>
                        @if($canRegister)
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 px-4 py-2.5 text-sm font-semibold text-white/90 hover:border-white/30">
                            {{ __('app.cta.register') }}
                        </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                @foreach($faqItems as $faq)
                    <details class="group rounded-2xl border border-white/10 bg-white/5 px-5 py-4 backdrop-blur">
                        <summary class="flex cursor-pointer items-center justify-between text-left text-base font-semibold text-white">
                            <span>{{ __($faq['question']) }}</span>
                            <span class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-slate-200 transition group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-sm text-slate-300 leading-relaxed">{{ __($faq['answer']) }}</p>
                        @if(!empty($faq['category']))
                            <p class="mt-2 text-xs text-slate-400">Category: {{ $faq['category'] }}</p>
                        @endif
                    </details>
                @endforeach
            </div>
        </section>

        <section class="mt-16">
            <div class="rounded-3xl border border-white/10 bg-gradient-to-r from-indigo-600/80 via-sky-500/70 to-indigo-600/80 px-6 py-8 shadow-glow">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-white/80">{{ __('landing.cta_bar.eyebrow') }}</p>
                        <h3 class="text-2xl font-display font-bold text-white mt-1">{{ __('landing.cta_bar.title') }}</h3>
                    </div>
                    <div class="flex gap-3">
                        @if($canLogin)
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-white text-slate-900 px-5 py-3 text-sm font-semibold shadow-lg shadow-slate-900/20">
                                {{ __('app.cta.login') }}
                            </a>
                        @endif
                        @if($canRegister)
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/60 px-5 py-3 text-sm font-semibold text-white">
                                {{ __('app.cta.register') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

@php
    /**
     * Render minimal icons mapped by key.
     */
    if (! function_exists('svgIcon')) {
        function svgIcon(string $key): string {
            return match($key) {
                'meter' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3a9 9 0 0 0-9 9v4a2 2 0 0 0 2 2h2l1 3h8l1-3h2a2 2 0 0 0 2-2v-4a9 9 0 0 0-9-9Zm-5 9h.01M9 12h.01m2 0h.01m2 0h.01m2 0h.01"/></svg>',
                'invoice' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h8m-8 4h5m-5 4h3M6 21h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H9.5a2 2 0 0 0-1.414.586L6.586 4.086A2 2 0 0 1 5.172 4H6a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>',
                'shield' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3 4.5 6v6c0 4.03 3.02 7.47 7.5 9 4.48-1.53 7.5-4.97 7.5-9V6L12 3Z"/></svg>',
                'chart' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M7 16V9m5 7V5m5 11v-4"/></svg>',
                'rocket' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 15h6m-3 0v3m0-3a9 9 0 0 0 9-9c-3.75 0-7.5 1.5-9 6-1.5-4.5-5.25-6-9-6a9 9 0 0 0 9 9Z"/></svg>',
                'users' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 14a4 4 0 1 0-8 0m8 0a4 4 0 0 1 4 4v1m-4-5a4 4 0 0 1-4 4m0 0a4 4 0 0 1-4-4m4 4v2m-4-2a4 4 0 0 0-4 4v1m16-5v2"/></svg>',
                default => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 12 2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
            };
        }
    }
@endphp
</body>
</html>
