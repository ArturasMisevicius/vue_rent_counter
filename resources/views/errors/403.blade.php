<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('shell.error_403_title') }} · {{ config('app.name', 'Tenanto') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-brand-ink text-slate-950 antialiased">
        <div class="relative isolate min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(248,205,116,0.24),transparent_32%),linear-gradient(180deg,#fff8eb_0%,#f8f4ea_50%,#eef7f5_100%)]">
            <main class="relative flex min-h-screen items-center justify-center px-6 py-12">
                <div class="w-full max-w-2xl space-y-8">
                    <div class="flex justify-center">
                        <x-shell.brand :href="app(\App\Support\Shell\DashboardUrlResolver::class)->for(auth()->user())" />
                    </div>

                    <section class="rounded-[2rem] border border-white/70 bg-white/92 p-8 text-center shadow-[0_28px_90px_rgba(15,23,42,0.14)] backdrop-blur xl:p-10">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('shell.error_label') }}</p>
                        <h1 class="mt-4 font-display text-4xl tracking-tight text-slate-950">{{ __('shell.error_403_title') }}</h1>
                        <p class="mx-auto mt-4 max-w-xl text-sm leading-6 text-slate-600">{{ __('shell.error_403_message') }}</p>

                        <div class="mt-8">
                            <a
                                href="{{ app(\App\Support\Shell\DashboardUrlResolver::class)->for(auth()->user()) }}"
                                class="inline-flex items-center justify-center rounded-full bg-brand-ink px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                            >
                                {{ __('shell.back_to_dashboard') }}
                            </a>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
