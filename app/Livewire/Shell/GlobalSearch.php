<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\GlobalSearchRegistry;
use App\Http\Requests\Shell\SearchQueryRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class GlobalSearch extends Component
{
    use AppliesShellLocale;

    public bool $open = false;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    public function mount(): void
    {
        $this->synchronizeQueryState($this->query);
    }

    public function updatedQuery(): void
    {
        $this->synchronizeQueryState($this->query);
    }

    public function openOverlay(): void
    {
        $this->open = filled($this->query);
    }

    #[On('shell-search-dismissed')]
    public function dismissSearch(): void
    {
        $this->query = '';
        $this->open = false;
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset($this->groupLabels);
        unset($this->results);
        unset($this->visibleResults);
        unset($this->hasResults);
    }

    public function render(): View
    {
        return view('livewire.shell.global-search', [
            'groupLabels' => $this->groupLabels,
            'hasResults' => $this->hasResults,
            'results' => $this->visibleResults,
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function groupLabels(): array
    {
        return app(GlobalSearchRegistry::class)->groupLabelsFor(auth()->user());
    }

    /**
     * @return array<string, array<int, array{group: string, title: string, subtitle: ?string, url: ?string}>>
     */
    #[Computed]
    public function results(): array
    {
        return $this->serializeResults(
            app(GlobalSearchRegistry::class)->search(auth()->user(), $this->query),
        );
    }

    /**
     * @return array<string, array<int, array{group: string, title: string, subtitle: ?string, url: ?string}>>
     */
    #[Computed]
    public function visibleResults(): array
    {
        return collect($this->results)
            ->filter(fn (array $groupResults): bool => $groupResults !== [])
            ->all();
    }

    #[Computed]
    public function hasResults(): bool
    {
        return $this->visibleResults !== [];
    }

    /**
     * @param  array<string, array<int, GlobalSearchResultData>>  $results
     * @return array<string, array<int, array{group: string, title: string, subtitle: ?string, url: ?string}>>
     */
    protected function serializeResults(array $results): array
    {
        return collect($results)
            ->map(fn (array $groupResults): array => array_map(
                fn ($result): array => $result->toArray(),
                $groupResults,
            ))
            ->all();
    }

    protected function synchronizeQueryState(string $query): void
    {
        /** @var SearchQueryRequest $request */
        $request = new SearchQueryRequest;
        $validated = $request->validatePayload([
            'query' => $query,
        ]);

        $this->query = (string) ($validated['query'] ?? '');
        $this->open = filled($this->query);
    }
}
