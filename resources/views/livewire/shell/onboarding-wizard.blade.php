<div
    @if ($isEligible)
        x-data
        x-on:tenanto-open-onboarding-tour.window="$wire.open()"
        data-onboarding-wizard-root
    @endif
>
    @if ($isEligible && $isOpen)
        <section
            role="dialog"
            aria-modal="true"
            aria-labelledby="onboarding-wizard-title"
            class="fixed inset-0 z-[980] flex items-center justify-center bg-slate-950/62 px-3 py-4 backdrop-blur-sm sm:px-6"
            data-onboarding-wizard-panel
        >
            <div class="flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_34px_90px_rgba(15,23,42,0.32)]">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-brand-mint/15 px-3 py-1 text-xs font-semibold uppercase tracking-normal text-brand-ink">
                                    {{ __('onboarding.tour.badge') }}
                                </span>

                                @if ($roleLabel)
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-normal text-slate-500 ring-1 ring-slate-200">
                                        {{ $roleLabel }}
                                    </span>
                                @endif
                            </div>

                            <div>
                                <h2 id="onboarding-wizard-title" class="font-display text-xl tracking-tight text-slate-950 sm:text-2xl">
                                    {{ __('onboarding.tour.title') }}
                                </h2>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    {{ __('onboarding.tour.subtitle') }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="dismiss"
                            aria-label="{{ __('onboarding.tour.actions.close') }}"
                            class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-800"
                        >
                            <x-heroicon-m-x-mark class="size-5" />
                        </button>
                    </div>

                    <div class="mt-4 flex items-center gap-2" aria-label="{{ __('onboarding.tour.progress_label') }}">
                        @foreach ($steps as $index => $step)
                            <span
                                wire:key="onboarding-progress-{{ $step['key'] }}"
                                @class([
                                    'h-2 min-w-0 flex-1 rounded-full transition',
                                    'bg-brand-ink' => $index <= $stepIndex,
                                    'bg-slate-200' => $index > $stepIndex,
                                ])
                            ></span>
                        @endforeach
                    </div>
                </div>

                <div class="min-h-0 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-ink text-white shadow-[0_16px_34px_rgba(19,38,63,0.22)]">
                            <x-dynamic-component :component="$currentStep['icon']" class="size-7" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-4">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-warm">
                                    {{ __('onboarding.tour.step_count', ['current' => $stepIndex + 1, 'total' => $totalSteps]) }}
                                </p>

                                <h3 class="font-display text-2xl tracking-tight text-slate-950">
                                    {{ $currentStep['title'] }}
                                </h3>

                                <p class="text-base leading-7 text-slate-700">
                                    {{ $currentStep['body'] }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                                {{ $currentStep['detail'] }}
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($steps as $index => $step)
                                    <button
                                        type="button"
                                        wire:key="onboarding-step-jump-{{ $step['key'] }}"
                                        wire:click="goTo({{ $index }})"
                                        @class([
                                            'inline-flex min-h-10 items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition',
                                            'bg-slate-950 text-white' => $index === $stepIndex,
                                            'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' => $index !== $stepIndex,
                                        ])
                                    >
                                        <span>{{ $index + 1 }}</span>
                                        <span class="hidden sm:inline">{{ $step['title'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-white px-5 py-4 sm:px-6">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            type="button"
                            wire:click="dismiss"
                            class="inline-flex min-h-11 items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        >
                            {{ __('onboarding.tour.actions.later') }}
                        </button>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="previous"
                                @disabled($isFirstStep)
                                class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:flex-none"
                            >
                                <x-heroicon-m-chevron-left class="size-4" />
                                {{ __('onboarding.tour.actions.back') }}
                            </button>

                            <button
                                type="button"
                                wire:click="{{ $isLastStep ? 'finish' : 'next' }}"
                                class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-ink px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-ink/18 transition hover:bg-brand-ink/95 sm:flex-none"
                            >
                                {{ $isLastStep ? __('onboarding.tour.actions.finish') : __('onboarding.tour.actions.next') }}
                                <x-heroicon-m-chevron-right class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
