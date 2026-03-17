@php
    $notificationsPollSeconds = (int) config('tenanto.polling.notifications', 30);
@endphp

<div
    class="relative"
    wire:poll.{{ $notificationsPollSeconds }}s="refresh"
>
    <button
        type="button"
        wire:click="togglePanel"
        class="relative inline-flex size-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm"
        aria-label="{{ __('shell.notifications') }}"
    >
        <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 3a4 4 0 0 0-4 4v2.6c0 .5-.19.98-.53 1.33L4 12.4h12l-1.47-1.47A1.88 1.88 0 0 1 14 9.6V7a4 4 0 0 0-4-4Zm-1.7 12a1.75 1.75 0 0 0 3.4 0" />
        </svg>

        @if ($this->unreadCount > 0)
            <span
                data-unread-count="{{ $this->unreadCount }}"
                class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white"
            >
                {{ $this->unreadCount }}
            </span>
        @endif
    </button>

    @if ($isOpen)
        <section class="absolute right-0 top-[calc(100%+0.75rem)] z-50 w-[22rem] rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-950/10">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-950">{{ __('shell.notifications') }}</p>
                    <p class="text-xs text-slate-500">{{ __('shell.notifications_subtitle') }}</p>
                </div>

                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-semibold text-slate-500 transition hover:text-slate-950"
                >
                    {{ __('shell.mark_all_as_read') }}
                </button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($this->notifications as $notification)
                    <button
                        type="button"
                        wire:key="shell-notification-{{ $notification['id'] }}"
                        wire:click="openNotification('{{ $notification['id'] }}')"
                        @class([
                            'block w-full rounded-[1.5rem] border px-4 py-3 text-left transition',
                            'border-brand-mint/40 bg-brand-mint/10' => ! $notification['is_read'],
                            'border-slate-200 bg-slate-50/70 hover:bg-slate-100/80' => $notification['is_read'],
                        ])
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-950">{{ $notification['title'] }}</p>
                                <p class="mt-1 text-sm leading-5 text-slate-600">{{ $notification['preview'] }}</p>
                            </div>

                            <span class="shrink-0 text-[0.7rem] font-medium uppercase tracking-[0.18em] text-slate-400">
                                {{ $notification['relative_time'] }}
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50/70 px-4 py-6 text-center text-sm text-slate-500">
                        {{ __('shell.notifications_empty') }}
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</div>
