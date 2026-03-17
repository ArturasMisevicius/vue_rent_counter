<?php

namespace App\Livewire\Shell;

use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\GlobalSearchRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class GlobalSearch extends Component
{
    public bool $open = false;

    #[Validate('nullable|string|max:120')]
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->query = trim($this->query);
        $this->validateOnly('query');
        $this->open = filled(trim($this->query));
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
    }

    public function render(): View
    {
        return view('livewire.shell.global-search', [
            'groupLabels' => $this->groupLabels,
            'results' => $this->results,
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
