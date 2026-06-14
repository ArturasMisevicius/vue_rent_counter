<section class="rounded-lg border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="text-base font-semibold text-slate-950">{{ __('admin.leads.sections.outreach_timeline') }}</h2>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse ($activities as $activity)
            <article class="px-5 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">
                            {{ $activity->direction?->label() ?? $activity->direction }}
                            · {{ $activity->channel?->label() ?? $activity->channel }}
                        </p>
                        @if (filled($activity->subject))
                            <p class="mt-1 text-sm text-slate-700">{{ $activity->subject }}</p>
                        @endif
                    </div>
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                        {{ $activity->status?->label() ?? $activity->status }}
                    </span>
                </div>

                <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $activity->message_summary }}</p>

                <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                    <span>{{ $activity->user?->name ?? __('admin.leads.labels.unknown_user') }}</span>
                    <span>{{ $activity->created_at?->toDayDateTimeString() }}</span>
                    @if ($activity->next_follow_up_at)
                        <span>{{ __('admin.leads.fields.next_follow_up_at') }}: {{ $activity->next_follow_up_at->toDayDateTimeString() }}</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="px-5 py-8 text-sm text-slate-500">
                {{ __('admin.leads.empty.outreach_timeline') }}
            </div>
        @endforelse
    </div>
</section>
