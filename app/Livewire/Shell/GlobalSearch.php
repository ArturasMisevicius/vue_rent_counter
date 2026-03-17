<?php

namespace App\Livewire\Shell;

use App\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Support\Shell\Search\GlobalSearchRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalSearch extends Component
{
    public bool $open = false;

    public string $query = '';

    /**
     * @var array<string, array<int, array{group: string, title: string, subtitle: ?string, url: ?string}>>
     */
    public array $results = [];

    public function mount(GlobalSearchRegistry $globalSearchRegistry): void
    {
        $this->results = $this->serializeResults(
            $globalSearchRegistry->search(auth()->user(), $this->query),
        );
    }

    public function updatedQuery(): void
    {
        $this->open = filled(trim($this->query));
        $this->results = $this->serializeResults(
            app(GlobalSearchRegistry::class)->search(auth()->user(), $this->query),
        );
    }

    public function openOverlay(): void
    {
        $this->open = true;
    }

    #[On('shell-search-dismissed')]
    public function dismissSearch(): void
    {
        $this->query = '';
        $this->open = false;
        $this->results = $this->serializeResults(
            app(GlobalSearchRegistry::class)->search(auth()->user(), ''),
        );
    }

    public function render(GlobalSearchRegistry $globalSearchRegistry): View
    {
        return view('livewire.shell.global-search', [
            'groupLabels' => $globalSearchRegistry->groupLabelsFor(auth()->user()),
        ]);
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
}
