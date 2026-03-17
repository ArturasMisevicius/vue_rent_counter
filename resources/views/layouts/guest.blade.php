<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name', 'Tenanto'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-brand-ink text-slate-950 antialiased">
        <div class="relative isolate min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(248,205,116,0.28),transparent_32%),linear-gradient(160deg,#13263f_0%,#10253b_42%,#f6eddc_42%,#f8f4ea_100%)]">
            <div class="absolute inset-x-0 top-0 h-80 bg-[radial-gradient(circle_at_top_left,rgba(62,197,173,0.22),transparent_42%)]"></div>
            <div class="absolute -left-20 top-28 size-56 rounded-full bg-brand-warm/20 blur-3xl"></div>
            <div class="absolute -right-12 top-16 size-48 rounded-full bg-brand-mint/20 blur-3xl"></div>

            <main class="relative flex min-h-screen items-center justify-center px-6 py-12">
                <div class="w-full max-w-xl">
                    <div class="mb-8 flex justify-center">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-4 text-white transition hover:opacity-90">
                            <span class="flex size-14 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-xl font-semibold shadow-lg shadow-slate-950/20 backdrop-blur">
                                T
                            </span>
                            <span class="flex flex-col">
                                <span class="font-display text-2xl tracking-tight">Tenanto</span>
                                <span class="text-xs uppercase tracking-[0.28em] text-white/65">{{ __('auth.brand_tagline') }}</span>
                            </span>
                        </a>
                    </div>

                    <section class="rounded-[2rem] border border-white/60 bg-white/90 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur xl:p-10">
                        @yield('content')
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
