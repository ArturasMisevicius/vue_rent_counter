@props([
    'status',
    'title',
    'description',
    'actionUrl',
])

@php
    $user = auth()->user();

    if ($user && filled($user->locale)) {
        app()->setLocale($user->locale);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · {{ config('app.name', 'Tenanto') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sora:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-brand-ink text-slate-950 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(62,197,173,0.18),transparent_28%),linear-gradient(160deg,#13263f_0%,#10253b_34%,#f6eddc_34%,#f8f4ea_100%)]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(248,205,116,0.18),transparent_24%)]"></div>

            <div class="relative mx-auto flex min-h-screen w-full max-w-5xl flex-col justify-center px-4 py-10 sm:px-6">
                <div class="mx-auto w-full max-w-3xl rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-10">
                    <div class="space-y-8">
                        <div class="flex justify-start">
                            <a href="{{ $actionUrl }}">
                                <x-shell.brand />
                            </a>
                        </div>

                        <div class="space-y-4">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('shell.errors.eyebrow', ['status' => $status]) }}</p>
                            <div class="space-y-3">
                                <h1 class="font-display text-4xl tracking-tight text-slate-950 sm:text-5xl">{{ $title }}</h1>
                                <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ $description }}</p>
                            </div>
                        </div>

                        <div>
                            <a
                                href="{{ $actionUrl }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                {{ __('shell.actions.back_to_dashboard') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
