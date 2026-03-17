@extends('layouts.app')

@section('title', __('dashboard.tenant_title') . ' · ' . config('app.name', 'Tenanto'))

@section('content')
    <main class="mx-auto flex min-h-[calc(100vh-11rem)] max-w-5xl items-center py-8">
        <div class="w-full rounded-[2rem] border border-white/70 bg-white/88 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.14)] backdrop-blur xl:p-10">
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
@endsection
