<?php

namespace App\Livewire\Shell;

use App\Models\User;
use App\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Support\Shell\Search\GlobalSearchRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GlobalSearch extends Component
{
    public bool $isOpen = false;

    public string $query = '';

    public function openSearch(): void
    {
        $this->isOpen = true;
    }

    public function closeSearch(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    /**
     * @return array<string, string>
     */
    public function getGroupLabelsProperty(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return app(GlobalSearchRegistry::class)->labelsFor($user);
    }

    /**
     * @return array<string, list<GlobalSearchResultData>>
     */
    public function getGroupedResultsProperty(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return app(GlobalSearchRegistry::class)->search($user, $this->query);
    }

    public function render(): View
    {
        return view('livewire.shell.global-search');
    }
}
