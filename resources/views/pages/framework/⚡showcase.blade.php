<?php

declare(strict_types=1);

use App\Services\FrameworkShowcaseMetricsService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Livewire 4 + Tailwind 4 Showcase')] class extends Component
{
    #[Validate('required|string|min:3|max:80')]
    public string $headline = 'Framework Studio Notes';

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    #[Session]
    public string $search = '';

    public bool $saved = false;

    public int $saveCount = 0;

    #[Locked]
    public string $profileRouteName = 'profile.edit';

    #[Locked]
    public string $studioRouteName = 'filament.admin.pages.framework-studio';

    public int $iterations = 1 {
        set {
            if ($value < 1) {
                throw new InvalidArgumentException('Iterations must be at least one.');
            }

            $this->iterations = $value;
        }
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperadmin() ?? false, 403);
    }

    public function openPalette(): void
    {
        $this->dispatch('open-palette')->to(ref: 'palette');
    }

    public function openPreviewModal(): void
    {
        $this->dispatch('open-preview-modal')->to(ref: 'previewModal');
    }

    public function save(): void
    {
        $this->validate();

        usleep(250000);

        $this->saved = true;
        $this->saveCount++;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    #[Computed]
    public function stats(): array
    {
        return app(FrameworkShowcaseMetricsService::class)->statCards();
    }

    /**
     * @return array<int, array{title: string, body: string}>
     */
    #[Computed]
    public function filteredFeatureCards(): array
    {
        return collect([
            [
                'title' => 'Single-file components',
                'body' => 'This page itself is a full-page Livewire single-file component routed through Route::livewire().',
            ],
            [
                'title' => 'Scoped assets',
                'body' => 'Both the alert and this page include component-scoped style/script blocks.',
            ],
            [
                'title' => 'Targeted refs',
                'body' => 'The command palette opens through a targeted dispatch to a wire:ref child.',
            ],
            [
                'title' => 'Tailwind v4 utilities',
                'body' => 'The layout uses CSS-first tokens, custom utilities, variants, gradients, and 3D transforms.',
            ],
        ])
            ->filter(function (array $card): bool {
                if ($this->search === '') {
                    return true;
                }

                return Str::of($card['title'].' '.$card['body'])
                    ->lower()
                    ->contains(Str::lower($this->search));
            })
            ->values()
            ->all();
    }
};
?>

<div class="space-y-8">
    <nav class="framework-panel flex flex-wrap items-center gap-3 px-4 py-3">
        <a
            href="{{ route('framework.livewire.showcase') }}"
            wire:navigate
            wire:current="border-slate-950 bg-slate-950 text-white"
            class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
        >
            Showcase
        </a>
        <a
            href="{{ route($this->profileRouteName) }}"
            wire:navigate
            wire:current="border-slate-950 bg-slate-950 text-white"
            class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
        >
            Profile
        </a>
    </nav>

    <section class="framework-panel p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl space-y-4">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-framework-500">Framework Studio</p>
                <h1 class="font-display text-5xl tracking-tight text-slate-950">Livewire 4 + Tailwind 4 Showcase</h1>
                <p class="text-sm leading-7 text-slate-600">
                    A contained framework lab inside Tenanto that uses supported Livewire 4, Tailwind 4, and Filament 5 features without disturbing the billing domain.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route($this->studioRouteName) }}"
                    class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    Open Framework Studio
                </a>
                <button
                    type="button"
                    wire:click="openPalette"
                    class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition not-hover:opacity-75 hover:bg-slate-800"
                >
                    Open command palette
                </button>
                <button
                    type="button"
                    wire:click="openPreviewModal"
                    class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    Open preview modal
                </button>
            </div>
        </div>
    </section>

    <livewire:framework.command-palette wire:ref="palette" />
    <livewire:framework.preview-modal
        wire:ref="previewModal"
        title="Framework showcase preview"
    />

    <livewire:framework.alert type="info" class="framework-panel">
        <livewire:slot name="title">Route + namespace demo</livewire:slot>
        This page is served through <code>Route::livewire('/framework/livewire-showcase', 'pages::framework.showcase')</code> and is restricted to superadmins.
    </livewire:framework.alert>

    <section class="@container grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <div class="framework-panel framework-grid overflow-hidden p-8">
            <div class="flex flex-col gap-8 @max-xl:flex-col xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-2xl space-y-4">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-framework-500">Single-file page component</p>
                    <h2 class="font-display text-5xl tracking-tight text-slate-950">High-voltage Livewire with CSS-first Tailwind tokens</h2>
                    <p class="text-sm leading-7 text-slate-600">
                        This surface combines scoped assets, islands, targeted refs, dynamic utility values, and Tailwind v4’s newer transforms and gradients.
                    </p>
                </div>

                <div class="perspective-distant">
                    <div class="transform-3d rounded-[1.75rem] border border-white/70 bg-radial-[at_25%_25%] from-framework-300 via-white to-brand-cream p-6 shadow-2xl rotate-y-12 rotate-x-6 translate-z-4 inset-ring-1 inset-ring-white/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Parallel-ready</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $saveCount }}</p>
                        <p class="mt-2 text-sm text-slate-600">successful showcase save cycles</p>
                    </div>
                </div>
            </div>
        </div>

        @island(name: 'showcase-stats', lazy: true)
            @placeholder
                <aside class="framework-panel animate-pulse p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Loading isolated metrics...</p>
                    <div class="mt-4 space-y-3">
                        <div class="h-18 rounded-2xl bg-slate-100"></div>
                        <div class="h-18 rounded-2xl bg-slate-100"></div>
                        <div class="h-18 rounded-2xl bg-slate-100"></div>
                    </div>
                </aside>
            @endplaceholder
            <aside class="framework-panel p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Isolated stats island</p>
                <div class="mt-4 space-y-3">
                    @foreach ($this->stats as $stat)
                        <div class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 nth-2:bg-slate-100">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>
        @endisland
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <article class="framework-panel p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Interactive controls</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Loading state + property hooks</h3>
                </div>
                <span class="rounded-full bg-linear-45 from-framework-500 to-brand-mint px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-white">
                    v4
                </span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-slate-700">Headline</span>
                    <input
                        type="text"
                        wire:model.live.blur="headline"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-framework-400 focus:ring-2 focus:ring-framework-300/30"
                    />
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-medium text-slate-700">Iterations</span>
                    <input
                        type="number"
                        min="1"
                        wire:model.live="iterations"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-framework-400 focus:ring-2 focus:ring-framework-300/30"
                    />
                </label>
            </div>

            <label class="mt-4 block space-y-2">
                <span class="text-sm font-medium text-slate-700">Notes</span>
                <textarea
                    wire:model.live.blur="notes"
                    rows="3"
                    class="field-sizing-content min-h-32 w-full rounded-[1.5rem] border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-framework-400 focus:ring-2 focus:ring-framework-300/30"
                    placeholder="Type here to test blur syncing, scoped JS, and auto-growing textarea behavior."
                ></textarea>
            </label>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="save"
                    data-framework-save
                    class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Save showcase notes
                </button>
                <div data-live="{{ $saved ? 'true' : 'false' }}" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 transition in-data-live:text-emerald-600">
                    {{ $saved ? 'Saved' : 'Idle' }}
                </div>
            </div>

            @if ($saved)
                <div wire:transition class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    Changes synced with validated showcase state.
                </div>
            @endif
        </article>

        <article class="framework-panel p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Searchable features</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Computed cards with modern variants</h3>
                </div>
                <input
                    type="search"
                    wire:model.live="search"
                    placeholder="Search features..."
                    class="w-full max-w-xs rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-framework-400 focus:ring-2 focus:ring-framework-300/30"
                />
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2" wire:replace>
                @foreach ($this->filteredFeatureCards as $card)
                    <div class="rounded-[1.5rem] border border-slate-200 bg-conic-180 from-white via-framework-300/20 to-white p-5 inset-shadow-xs inset-ring-1 inset-ring-white/70">
                        <p class="text-sm font-semibold text-slate-950">{{ $card['title'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $card['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <details class="framework-panel open:shadow-2xl p-6" open>
        <summary class="cursor-pointer list-none text-lg font-semibold text-slate-950">Tailwind v4 patterns in use</summary>
        <ul class="mt-4 grid gap-3 text-sm text-slate-700 *:font-bold">
            <li>Dynamic utilities like <code>z-40</code> and 3D transforms like <code>translate-z-4</code>.</li>
            <li>CSS-first directives with <code>@theme</code>, <code>@utility</code>, <code>@variant</code>, and <code>@property</code>.</li>
            <li>State styling via <code>open:</code>, <code>not-hover:</code>, descendant selectors, and nth-child variants.</li>
        </ul>
    </details>

    <style>
        [data-framework-save][data-loading] {
            opacity: 0.55;
            cursor: wait;
            transform: translateY(1px);
        }

        [data-live='true'] {
            border-color: color-mix(in oklab, var(--color-framework-500) 45%, white 55%);
            background: color-mix(in oklab, var(--color-framework-500) 8%, white 92%);
        }

        @starting-style {
            .framework-panel {
                opacity: 0;
                transform: translateY(1rem) scale(0.98);
            }
        }
    </style>

    <script>
        this.$watch('notes', (value) => {
            if (value.length > 180) {
                this.$set('headline', 'Detailed framework notes');
            }
        });
    </script>
</div>
