<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\TenantDocumentIndexQuery;
use App\Filament\Support\Tenant\Portal\TenantDocumentPresenter;
use App\Http\Requests\Tenant\TenantDocumentFilterRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Documents extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;

    #[Url(as: 'category')]
    public string $selectedCategory = 'all';

    public function mount(): void
    {
        $this->tenantWorkspace();
        $this->selectedCategory = $this->validatedCategory($this->selectedCategory);
    }

    public function render(TenantDocumentPresenter $presenter): View
    {
        $documents = $this->documents;

        return view('livewire.tenant.documents', [
            'tenant' => $this->tenant,
            'documents' => $presenter->presentMany($documents),
            'filters' => $presenter->filters($this->categoryCounts),
            'selectedCategory' => $this->selectedCategory,
        ]);
    }

    public function updatedSelectedCategory(string $category): void
    {
        $this->selectedCategory = $this->validatedCategory($category);

        unset($this->documents);
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset($this->tenant, $this->documents, $this->categoryCounts);
    }

    #[Computed]
    public function tenant(): User
    {
        return $this->currentTenant();
    }

    #[Computed]
    public function documents(): \Illuminate\Database\Eloquent\Collection
    {
        return app(TenantDocumentIndexQuery::class)->for(
            $this->tenant,
            $this->selectedCategory === 'all' ? null : $this->selectedCategory,
        );
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function categoryCounts(): array
    {
        return app(TenantDocumentIndexQuery::class)->countsFor($this->tenant);
    }

    private function validatedCategory(string $category): string
    {
        $validated = (new TenantDocumentFilterRequest)->validatePayload([
            'selectedCategory' => $category,
        ]);

        return (string) $validated['selectedCategory'];
    }
}
