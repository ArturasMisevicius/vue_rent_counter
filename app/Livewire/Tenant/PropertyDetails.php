<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\TenantPropertyPresenter;
use App\Http\Requests\Tenant\PropertyHistoryFilterRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class PropertyDetails extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;

    #[Url(as: 'year')]
    public string $selectedYear = 'all';

    #[Url(as: 'month')]
    public string $selectedMonth = 'all';

    public function mount(): void
    {
        $this->tenantWorkspace();

        $filtersRequest = new PropertyHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedYear' => $this->selectedYear,
            'selectedMonth' => $this->selectedMonth,
        ]);

        $this->selectedYear = (string) $validated['selectedYear'];
        $this->selectedMonth = (string) $validated['selectedMonth'];
    }

    public function render(): View
    {
        abort_if(($this->summary['has_assignment'] ?? false) === false, 404);

        return view('livewire.tenant.property-details', [
            'summary' => $this->summary,
        ]);
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();
        unset($this->summary);
    }

    public function updatedSelectedYear(string $selectedYear): void
    {
        $filtersRequest = new PropertyHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedYear' => $selectedYear,
            'selectedMonth' => $this->selectedMonth,
        ]);

        $this->selectedYear = (string) $validated['selectedYear'];
        $this->selectedMonth = (string) $validated['selectedMonth'];

        unset($this->summary);
    }

    public function updatedSelectedMonth(string $selectedMonth): void
    {
        $filtersRequest = new PropertyHistoryFilterRequest;
        $validated = $filtersRequest->validatePayload([
            'selectedYear' => $this->selectedYear,
            'selectedMonth' => $selectedMonth,
        ]);

        $this->selectedYear = (string) $validated['selectedYear'];
        $this->selectedMonth = (string) $validated['selectedMonth'];

        unset($this->summary);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        /** @var User $tenant */
        $tenant = $this->currentTenant();

        return app(TenantPropertyPresenter::class)->for($tenant, $this->selectedYear, $this->selectedMonth);
    }
}
