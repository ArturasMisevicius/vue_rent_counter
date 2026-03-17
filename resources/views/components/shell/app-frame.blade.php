<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'Tenanto') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        @livewireStyles

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-brand-ink text-slate-950 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(62,197,173,0.18),transparent_28%),linear-gradient(160deg,#13263f_0%,#10253b_34%,#f6eddc_34%,#f8f4ea_100%)]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(248,205,116,0.18),transparent_24%)]"></div>

            <div class="relative mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 pb-8 pt-6 sm:px-6 sm:pt-8">
                @livewire(\App\Livewire\Shell\Topbar::class, ['context' => 'page', 'eyebrow' => $eyebrow, 'heading' => $heading])

                <main @class(['flex-1', 'pb-20' => $showTenantNavigation])>
                    @if ($breadcrumbs !== [])
                        <x-shell.breadcrumbs :items="$breadcrumbs" />
                    @endif

                    {{ $slot }}
                </main>
            </div>

            @if ($showTenantNavigation)
                @livewire(\App\Livewire\Shell\TenantBottomNavigation::class)
            @endif
        </div>

        @livewireScripts
    </body>
</html>
