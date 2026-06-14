<div class="space-y-5">
    @forelse ($articles as $article)
            <article class="rounded-lg border border-slate-200 bg-white p-5" wire:key="context-help-{{ $pageKey }}-{{ $article['slug'] }}">
                <h3 class="text-base font-semibold text-slate-950">{{ $article['title'] }}</h3>
                <div class="prose prose-sm mt-3 max-w-none text-slate-600">
                    {!! nl2br(e($article['body'])) !!}
                </div>
            </article>
    @empty
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <p class="text-sm font-semibold text-slate-950">{{ __('help.context.empty.heading') }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('help.context.empty.description') }}</p>
        </div>
    @endforelse

    @if ($helpCenterUrl !== null)
        <a
            href="{{ $helpCenterUrl }}"
            wire:navigate
            class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900"
        >
            <x-heroicon-m-book-open class="size-4" />
            {{ __('help.actions.open_help_center') }}
        </a>
    @endif
</div>
