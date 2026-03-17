@extends('layouts.app')

@section('title', __('shell.my_profile') . ' · ' . config('app.name', 'Tenanto'))

@section('content')
    <main class="mx-auto flex min-h-[calc(100vh-11rem)] max-w-4xl items-start py-8">
        <section class="w-full rounded-[2rem] border border-white/70 bg-white/88 p-8 shadow-[0_24px_70px_rgba(15,23,42,0.12)] backdrop-blur">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('shell.sections.account') }}</p>
                <h1 class="font-display text-4xl tracking-tight text-slate-950">{{ __('shell.my_profile') }}</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    {{ __('shell.profile_placeholder') }}
                </p>
            </div>

            <dl class="mt-8 grid gap-6 rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('auth.full_name_label') }}</dt>
                    <dd class="text-sm font-semibold text-slate-950">{{ $user->name }}</dd>
                </div>

                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('auth.email_label') }}</dt>
                    <dd class="text-sm font-semibold text-slate-950">{{ $user->email }}</dd>
                </div>

                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('shell.role') }}</dt>
                    <dd class="text-sm font-semibold text-slate-950">{{ $user->role->label() }}</dd>
                </div>

                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('shell.language') }}</dt>
                    <dd class="text-sm font-semibold text-slate-950">{{ data_get(config('tenanto.locales.' . $user->locale), 'native_name', $user->locale) }}</dd>
                </div>
            </dl>
        </section>
    </main>
@endsection
