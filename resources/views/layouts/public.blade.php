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
    <body class="min-h-screen bg-brand-ink text-white antialiased">
        <div class="relative isolate overflow-hidden bg-[radial-gradient(circle_at_top,rgba(248,205,116,0.26),transparent_32%),linear-gradient(160deg,#13263f_0%,#10253b_44%,#f6eddc_44%,#fbf7ef_100%)]">
            <div class="absolute inset-x-0 top-0 h-96 bg-[radial-gradient(circle_at_top_left,rgba(62,197,173,0.24),transparent_36%)]"></div>
            <div class="absolute -left-24 top-28 size-72 rounded-full bg-brand-warm/16 blur-3xl"></div>
            <div class="absolute right-0 top-16 size-64 rounded-full bg-brand-mint/16 blur-3xl"></div>

            <main class="relative">
                @yield('content')
            </main>
        </div>

        @livewireScripts(['nonce' => $cspNonce])
    </body>
</html>
