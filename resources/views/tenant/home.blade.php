<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('dashboard.tenant_title') }} · {{ config('app.name', 'Tenanto') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-brand-ink text-slate-950 antialiased">
        <div class="relative isolate min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(62,197,173,0.18),transparent_30%),linear-gradient(160deg,#13263f_0%,#10253b_38%,#f6eddc_38%,#f8f4ea_100%)]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(248,205,116,0.2),transparent_26%)]"></div>

            <main class="relative mx-auto flex min-h-screen max-w-5xl items-center px-6 py-12">
                <div class="w-full rounded-[2rem] border border-white/60 bg-white/90 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur xl:p-10">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-3">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('dashboard.tenant_eyebrow') }}</p>
                            <div class="space-y-2">
                                <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('dashboard.tenant_heading') }}</h1>
                                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('dashboard.tenant_body') }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                {{ __('dashboard.logout_button') }}
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
