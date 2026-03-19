@extends('layouts.public')

@section('title', config('app.name', 'Tenanto'))

@section('content')
    <div class="px-6 pb-20 pt-6 sm:px-8 lg:px-10">
        <div class="mx-auto max-w-7xl">
            <header class="mb-10 rounded-[2rem] border border-white/14 bg-white/8 px-6 py-5 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-14 items-center justify-center rounded-2xl border border-white/18 bg-white/10 text-xl font-semibold text-white shadow-lg shadow-slate-950/25">
                            T
                        </div>

                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.26em] text-brand-warm">
                                {{ $page['brand']['kicker'] }}
                            </p>
                            <div>
                                <h1 class="font-display text-2xl tracking-tight text-white sm:text-3xl">
                                    {{ $page['brand']['name'] }}
                                </h1>
                                <p class="text-sm text-white/66">{{ $page['brand']['tagline'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between lg:justify-end">
                        <x-shared.language-switcher />

                        <div class="flex items-center gap-3">
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center justify-center rounded-full border border-white/18 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/10"
                            >
                                {{ $page['cta']['login'] }}
                            </a>
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center justify-center rounded-full bg-brand-warm px-5 py-3 text-sm font-semibold text-brand-ink shadow-lg shadow-brand-warm/25 transition hover:translate-y-[-1px] hover:shadow-xl hover:shadow-brand-warm/30"
                            >
                                {{ $page['cta']['register'] }}
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <section class="grid gap-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(20rem,0.95fr)]">
                <div class="rounded-[2.5rem] border border-white/14 bg-white/8 p-8 shadow-[0_30px_110px_rgba(15,23,42,0.22)] backdrop-blur sm:p-10">
                    <div class="mb-8 flex flex-wrap gap-3">
                        @forelse ($page['hero']['chips'] as $chip)
                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/78">
                                {{ $chip }}
                            </span>
                        @empty
                        @endforelse
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_16rem]">
                        <div class="space-y-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-brand-warm">
                                {{ $page['hero']['eyebrow'] }}
                            </p>

                            <div class="space-y-5">
                                <h2 class="max-w-4xl font-display text-4xl leading-[0.98] tracking-tight text-white sm:text-5xl lg:text-6xl">
                                    {{ $page['hero']['title'] }}
                                </h2>

                                <p class="max-w-3xl text-base leading-8 text-white/74 sm:text-lg">
                                    {{ $page['hero']['description'] }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-brand-warm/24 bg-brand-warm/12 p-5 text-brand-cream shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-warm">
                                {{ $page['tester']['heading'] }}
                            </p>
                            <p class="mt-4 text-sm leading-7 text-white/76">
                                {{ $page['tester']['description'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <aside class="grid gap-6">
                    <div class="rounded-[2rem] bg-brand-cream p-6 text-brand-ink shadow-[0_28px_60px_rgba(15,23,42,0.12)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-warm">
                            {{ $page['roles']['heading'] }}
                        </p>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            {{ $page['roles']['description'] }}
                        </p>
                    </div>

                    <div class="rounded-[2rem] border border-white/12 bg-[#10253b] p-6 shadow-[0_24px_60px_rgba(15,23,42,0.2)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-mint">
                            {{ $page['roadmap']['heading'] }}
                        </p>
                        <p class="mt-3 text-sm leading-7 text-white/70">
                            {{ $page['roadmap']['description'] }}
                        </p>
                    </div>
                </aside>
            </section>

            <section class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @forelse ($page['roles']['items'] as $role)
                    <article class="rounded-[2rem] border border-slate-200/85 bg-white/92 p-6 text-slate-900 shadow-[0_22px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ $role['name'] }}</p>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $role['description'] }}</p>
                    </article>
                @empty
                @endforelse
            </section>

            <section class="mt-10 grid gap-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                <article class="rounded-[2.25rem] border border-slate-200/80 bg-white/92 p-7 text-slate-900 shadow-[0_24px_55px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-warm">
                                {{ $page['tester']['heading'] }}
                            </p>
                            <p class="mt-3 max-w-xl text-sm leading-7 text-slate-600">
                                {{ $page['tester']['description'] }}
                            </p>
                        </div>
                        <div class="hidden size-16 items-center justify-center rounded-2xl bg-brand-mint/20 text-brand-ink sm:flex">
                            QA
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3">
                        @forelse ($page['tester']['items'] as $item)
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 px-4 py-4">
                                <span class="mt-1 size-2.5 rounded-full bg-brand-warm"></span>
                                <p class="text-sm leading-7 text-slate-700">{{ $item }}</p>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </article>

                <article class="rounded-[2.25rem] border border-white/12 bg-[#13263f] p-7 shadow-[0_30px_80px_rgba(15,23,42,0.2)]">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-mint">
                            {{ $page['roadmap']['heading'] }}
                        </p>
                        <h3 class="mt-3 font-display text-3xl tracking-tight text-white">
                            {{ $page['roadmap']['lead'] }}
                        </h3>
                        <p class="mt-4 text-sm leading-7 text-white/72">
                            {{ $page['roadmap']['description'] }}
                        </p>
                    </div>

                    <div class="mt-8 grid gap-4">
                        @forelse ($page['roadmap']['items'] as $item)
                            <div class="rounded-[1.75rem] border border-white/10 bg-white/8 p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <h4 class="font-display text-xl tracking-tight text-white">{{ $item['title'] }}</h4>
                                    <span class="inline-flex rounded-full border border-brand-warm/30 bg-brand-warm/12 px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-brand-warm">
                                        {{ $page['roadmap']['status'] }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm leading-7 text-white/72">{{ $item['description'] }}</p>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="mt-10 rounded-[2.5rem] border border-white/14 bg-white/10 px-8 py-10 shadow-[0_32px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:px-10">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.2fr)_auto] lg:items-center">
                    <div class="space-y-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-warm">
                            {{ $page['cta']['heading'] }}
                        </p>
                        <h3 class="font-display text-3xl tracking-tight text-white sm:text-4xl">
                            {{ $page['cta']['description'] }}
                        </h3>
                        <p class="max-w-3xl text-sm leading-7 text-white/72">
                            {{ $page['cta']['note'] }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center justify-center rounded-full border border-white/18 px-6 py-3 text-sm font-semibold text-white transition hover:border-white/28 hover:bg-white/10"
                        >
                            {{ $page['cta']['login'] }}
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex items-center justify-center rounded-full bg-brand-warm px-6 py-3 text-sm font-semibold text-brand-ink shadow-lg shadow-brand-warm/25 transition hover:translate-y-[-1px] hover:shadow-xl hover:shadow-brand-warm/30"
                        >
                            {{ $page['cta']['register'] }}
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
