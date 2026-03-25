@php
    $user = auth()->user();
    $dashboardUrl = app(\App\Filament\Support\Shell\DashboardUrlResolver::class)->for($user, preferTenantDashboard: true);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', __('shell.errors.eyebrow', ['status' => ''])).' · '.config('app.name', 'Tenanto')</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-brand-ink text-slate-950 antialiased">
        <div class="relative isolate min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(62,197,173,0.18),transparent_30%),linear-gradient(160deg,#13263f_0%,#10253b_38%,#f6eddc_38%,#f8f4ea_100%)]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(248,205,116,0.2),transparent_26%)]"></div>

            <main class="relative mx-auto flex min-h-screen max-w-4xl items-center px-6 py-12">
                <section class="w-full rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur xl:p-10">
                    <div class="space-y-4">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">@yield('code')</p>
                        <h1 class="font-display text-4xl tracking-tight text-slate-950">@yield('heading')</h1>
                        <p class="max-w-2xl text-sm leading-6 text-slate-600">@yield('message')</p>
                        <a
                            href="{{ $dashboardUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
                        >
                            {{ __('shell.actions.back_to_dashboard') }}
                        </a>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
