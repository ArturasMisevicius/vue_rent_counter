<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php($cspNonce = \Illuminate\Support\Facades\Vite::cspNonce())
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name', 'Tenanto'))</title>
        <link rel="icon" href="{{ route('favicon') }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ route('favicon') }}" type="image/x-icon">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />

        @livewireStyles(['nonce' => $cspNonce])

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
                    <div class="mb-6 flex justify-end">
                        <x-shared.language-switcher />
                    </div>

                    <div class="mb-8 flex justify-center">
                        <a href="{{ route('home') }}" class="transition hover:opacity-90">
                            <x-shell.brand light />
                        </a>
                    </div>

                    <section class="rounded-[2rem] border border-white/60 bg-white/90 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur xl:p-10">
                        @yield('content')
                    </section>
                </div>
            </main>
        </div>

        @livewireScripts(['nonce' => $cspNonce])
    </body>
</html>
