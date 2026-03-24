<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ __('shell.notifications.page.eyebrow') }}</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('shell.notifications.page.title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                {{ __('shell.notifications.page.description') }}
            </p>
        </section>

        @if (filled($statusMessage))
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                {{ $statusMessage }}
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('shell.notifications.page.stats.unread') }}</p>
                <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ $unreadCount }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ __('shell.notifications.page.stats.unread_description') }}</p>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('shell.notifications.page.stats.total') }}</p>
                        <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ $totalCount }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ __('shell.notifications.page.stats.total_description') }}</p>
                    </div>

                    @if ($unreadCount > 0)
                        <button
                            type="button"
                            wire:click="markAllAsRead"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            {{ __('shell.notifications.actions.mark_all_read') }}
                        </button>
                    @endif
                </div>
            </article>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            @if (count($notifications) > 0)
                <div class="divide-y divide-slate-100">
                    @foreach ($notifications as $notification)
                        <article wire:key="platform-notification-{{ $notification['id'] }}" class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-base font-semibold text-slate-950">{{ $notification['title'] }}</h2>

                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $notification['unread'],
                                        'bg-slate-100 text-slate-600' => ! $notification['unread'],
                                    ])>
                                        {{ $notification['unread'] ? __('shell.notifications.status.unread') : __('shell.notifications.status.read') }}
                                    </span>
                                </div>

                                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $notification['preview'] }}</p>
                                <p class="mt-3 text-xs font-medium uppercase tracking-[0.18em] text-slate-400">{{ $notification['relative_time'] }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <button
                                    type="button"
                                    wire:click="openNotification('{{ $notification['id'] }}')"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                >
                                    {{ filled($notification['url']) ? __('shell.notifications.page.actions.open') : ($notification['unread'] ? __('shell.notifications.page.actions.mark_read') : __('shell.notifications.page.actions.viewed')) }}
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-slate-700">{{ __('shell.notifications.empty.heading') }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ __('shell.notifications.empty.body') }}</p>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
