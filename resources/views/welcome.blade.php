@section('title', config('app.name', 'Tenanto'))

<div class="min-h-screen bg-[#f6f0e6] text-[#182131]">
    <div class="border-b border-[#d8c9b5] bg-[#f8f3eb]/94">
        <header class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-lg bg-[#182131] font-display text-lg font-bold text-[#f6c15a] shadow-[0_8px_24px_rgba(24,33,49,0.18)]">
                    T
                </div>

                <div>
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-[#b87819]">
                        {{ $page['brand']['kicker'] }}
                    </p>
                    <h1 class="font-display text-2xl font-bold tracking-tight text-[#182131]">
                        {{ $page['brand']['name'] }}
                    </h1>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:justify-end">
                <x-shared.language-switcher variant="light" />

                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-[#c8b89f] px-4 text-sm font-semibold text-[#182131] transition hover:border-[#182131] hover:bg-white"
                    >
                        {{ $page['cta']['login'] }}
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-[#f6b13d] px-4 text-sm font-bold text-[#182131] shadow-[0_10px_24px_rgba(182,116,22,0.24)] transition hover:bg-[#f8c15f]"
                    >
                        {{ $page['cta']['register'] }}
                    </a>
                </div>
            </div>
        </header>
    </div>

    <main>
        <section class="relative overflow-hidden border-b border-[#d8c9b5] bg-[#ece4d7]">
            <div class="absolute inset-0 opacity-[0.34] [background-image:linear-gradient(to_right,#a99578_1px,transparent_1px),linear-gradient(to_bottom,#a99578_1px,transparent_1px)] [background-size:28px_28px]"></div>
            <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-[#f6f0e6] to-transparent"></div>

            <div class="relative mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,0.92fr)_minmax(32rem,1.08fr)] lg:items-center lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b87819]">
                        {{ $page['hero']['eyebrow'] }}
                    </p>

                    <h2 class="mt-5 font-display text-5xl font-bold leading-[0.96] tracking-tight text-[#172132] sm:text-6xl lg:text-7xl">
                        {{ $page['hero']['title'] }}
                    </h2>

                    <p class="mt-6 max-w-2xl text-base leading-8 text-[#495566] sm:text-lg">
                        {{ $page['hero']['description'] }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex min-h-12 items-center justify-center rounded-lg bg-[#182131] px-6 text-sm font-bold text-white shadow-[0_18px_34px_rgba(24,33,49,0.2)] transition hover:bg-[#263247]"
                        >
                            {{ $page['cta']['register'] }}
                        </a>
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex min-h-12 items-center justify-center rounded-lg border border-[#bba98f] bg-[#f8f3eb]/80 px-6 text-sm font-bold text-[#182131] transition hover:border-[#182131] hover:bg-white"
                        >
                            {{ $page['cta']['login'] }}
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -left-6 -top-6 h-24 w-32 rounded-lg bg-[#3ec5ad]/20"></div>
                    <div class="absolute -bottom-5 right-6 h-20 w-36 rounded-lg bg-[#f6b13d]/30"></div>

                    <div class="relative overflow-hidden rounded-lg border border-[#172132] bg-[#172132] shadow-[22px_22px_0_#d7c8b4]">
                        <div class="flex items-center justify-between border-b border-white/12 bg-[#111827] px-4 py-3">
                            <div>
                                <p class="text-[0.64rem] font-bold uppercase tracking-[0.24em] text-[#f6b13d]">
                                    {{ $page['brand']['tagline'] }}
                                </p>
                                <p class="mt-1 text-sm font-semibold text-white">Operations workspace</p>
                            </div>
                            <div class="grid grid-cols-3 gap-1">
                                <span class="size-2 rounded-full bg-[#3ec5ad]"></span>
                                <span class="size-2 rounded-full bg-[#f6b13d]"></span>
                                <span class="size-2 rounded-full bg-white/55"></span>
                            </div>
                        </div>

                        <div class="grid min-h-[31rem] lg:grid-cols-[12rem_minmax(0,1fr)]">
                            <nav class="border-b border-white/10 bg-white/[0.04] p-4 lg:border-b-0 lg:border-r">
                                <div class="space-y-2">
                                    @forelse ($page['hero']['chips'] as $chip)
                                        <div class="rounded-lg border border-white/10 px-3 py-3 text-xs font-semibold leading-5 text-white/78">
                                            {{ $chip }}
                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                            </nav>

                            <div class="bg-[#f8f3eb] p-4 text-[#182131]">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    @forelse ($page['roles']['items'] as $role)
                                        @if ($loop->index < 3)
                                            <div class="rounded-lg border border-[#d6c6ae] bg-white px-3 py-3">
                                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-[#b87819]">{{ $role['name'] }}</p>
                                                <p class="mt-2 text-xs leading-5 text-[#5a6675]">{{ $role['description'] }}</p>
                                            </div>
                                        @endif
                                    @empty
                                    @endforelse
                                </div>

                                <div class="mt-4 rounded-lg border border-[#d6c6ae] bg-white">
                                    <div class="grid grid-cols-[1.2fr_0.7fr_0.7fr] border-b border-[#e5d8c8] px-4 py-3 text-[0.66rem] font-bold uppercase tracking-[0.16em] text-[#7b8796]">
                                        <span>Workflow</span>
                                        <span>Status</span>
                                        <span>Owner</span>
                                    </div>
                                    @forelse ($page['roadmap']['items'] as $item)
                                        <div class="grid grid-cols-[1.2fr_0.7fr_0.7fr] gap-3 border-b border-[#eee4d7] px-4 py-3 text-sm last:border-b-0">
                                            <span class="font-semibold text-[#182131]">{{ $item['title'] }}</span>
                                            <span class="text-[#b87819]">{{ $page['roadmap']['status'] }}</span>
                                            <span class="text-[#5a6675]">{{ $page['roles']['items'][$loop->index % count($page['roles']['items'])]['name'] }}</span>
                                        </div>
                                    @empty
                                    @endforelse
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-lg bg-[#182131] p-4 text-white">
                                        <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-[#3ec5ad]">{{ $page['roadmap']['heading'] }}</p>
                                        <p class="mt-3 text-lg font-bold leading-6">{{ $page['roadmap']['lead'] }}</p>
                                    </div>
                                    <div class="rounded-lg border border-[#d6c6ae] bg-[#fffaf2] p-4">
                                        <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-[#b87819]">{{ $page['tester']['heading'] }}</p>
                                        <p class="mt-3 text-sm leading-6 text-[#5a6675]">{{ $page['tester']['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-8 px-5 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)] lg:px-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b87819]">
                    {{ $page['roles']['heading'] }}
                </p>
                <h3 class="mt-3 max-w-xl font-display text-3xl font-bold tracking-tight text-[#182131] sm:text-4xl">
                    {{ $page['roles']['description'] }}
                </h3>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @forelse ($page['roles']['items'] as $role)
                    <article class="rounded-lg border border-[#d8c9b5] bg-white p-5 shadow-[0_12px_28px_rgba(24,33,49,0.07)]">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#b87819]">{{ $role['name'] }}</p>
                        <p class="mt-3 text-sm leading-7 text-[#5a6675]">{{ $role['description'] }}</p>
                    </article>
                @empty
                @endforelse
            </div>
        </section>

        <section class="border-y border-[#d8c9b5] bg-[#182131] text-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#3ec5ad]">
                        {{ $page['tester']['heading'] }}
                    </p>
                    <p class="mt-4 max-w-xl text-base leading-8 text-white/72">
                        {{ $page['tester']['description'] }}
                    </p>
                </div>

                <div class="divide-y divide-white/10 rounded-lg border border-white/12 bg-white/[0.04]">
                    @forelse ($page['tester']['items'] as $item)
                        <div class="flex gap-4 px-5 py-4">
                            <span class="mt-2 size-2 rounded-full bg-[#f6b13d]"></span>
                            <p class="text-sm leading-7 text-white/78">{{ $item }}</p>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 rounded-lg border border-[#d8c9b5] bg-white p-6 shadow-[0_16px_40px_rgba(24,33,49,0.08)] lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:p-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#b87819]">
                        {{ $page['cta']['heading'] }}
                    </p>
                    <h3 class="mt-3 max-w-3xl font-display text-3xl font-bold tracking-tight text-[#182131] sm:text-4xl">
                        {{ $page['cta']['description'] }}
                    </h3>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-[#5a6675]">
                        {{ $page['cta']['note'] }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex min-h-12 items-center justify-center rounded-lg border border-[#c8b89f] px-6 text-sm font-bold text-[#182131] transition hover:border-[#182131]"
                    >
                        {{ $page['cta']['login'] }}
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex min-h-12 items-center justify-center rounded-lg bg-[#f6b13d] px-6 text-sm font-bold text-[#182131] shadow-[0_10px_24px_rgba(182,116,22,0.22)] transition hover:bg-[#f8c15f]"
                    >
                        {{ $page['cta']['register'] }}
                    </a>
                </div>
            </div>
        </section>
    </main>
</div>
