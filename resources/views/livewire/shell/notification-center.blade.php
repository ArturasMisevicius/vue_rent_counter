<div
    x-data="{ open: false }"
    wire:poll.{{ $pollSeconds }}s="refreshNotifications"
    class="relative"
    data-shell-notifications="center"
>
    <button
        type="button"
        x-on:click="open = ! open"
        class="relative inline-flex size-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
    >
        <span class="sr-only">{{ __('shell.notifications.actions.toggle') }}</span>
        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 2.5a4 4 0 0 0-4 4v1.257c0 .468-.124.928-.36 1.332L4.632 10.8A1.75 1.75 0 0 0 6.145 13.5h7.71a1.75 1.75 0 0 0 1.513-2.7l-1.01-1.71A2.625 2.625 0 0 1 14 7.757V6.5a4 4 0 0 0-4-4Z" />
            <path d="M7.5 15.25a2.5 2.5 0 0 0 5 0h-5Z" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-warm px-1.5 py-0.5 text-[0.65rem] font-semibold text-brand-ink">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-on:click.outside="open = false"
        class="absolute right-0 top-full z-20 mt-2 w-80 rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-[0_20px_70px_rgba(15,23,42,0.16)]"
    >
        <div class="flex items-center justify-between gap-3 px-1 pb-3">
            <div>
                <p class="text-sm font-semibold text-slate-950">{{ __('shell.notifications.heading') }}</p>
                <p class="text-xs text-slate-500">{{ trans_choice('shell.notifications.unread_count', $unreadCount, ['count' => $unreadCount]) }}</p>
            </div>

            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-semibold text-brand-ink transition hover:text-brand-warm"
                >
                    {{ __('shell.notifications.actions.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="space-y-2">
            @forelse ($notifications as $notification)
                <button
                    type="button"
                    wire:key="notification-{{ $notification['id'] }}"
                    wire:click="openNotification('{{ $notification['id'] }}')"
                    x-on:click="open = false"
                    @class([
                        'block w-full rounded-[1.25rem] border px-4 py-3 text-left transition',
                        'border-brand-warm/40 bg-brand-warm/10' => $notification['unread'],
                        'border-slate-200 bg-slate-50/70 hover:border-slate-300 hover:bg-slate-50' => ! $notification['unread'],
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-950">{{ $notification['title'] }}</p>
                        <span class="shrink-0 text-[0.7rem] text-slate-400">{{ $notification['relative_time'] }}</span>
                    </div>
                    <p class="mt-2 text-sm leading-5 text-slate-600">{{ $notification['preview'] }}</p>
                </button>
            @empty
                <div class="rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50/70 px-4 py-6 text-center">
                    <p class="text-sm font-semibold text-slate-700">{{ __('shell.notifications.empty.heading') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('shell.notifications.empty.body') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
