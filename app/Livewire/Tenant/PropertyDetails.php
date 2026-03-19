<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\TenantPropertyPresenter;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PropertyDetails extends Component
{
    use ResolvesTenantWorkspace;

    public function mount(): void
    {
        $this->tenantWorkspace();
    }

    public function render(): View
    {
        abort_if(($this->summary['has_assignment'] ?? false) === false, 404);

        return view('livewire.tenant.property-details', [
            'summary' => $this->summary,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        /** @var User $tenant */
        $tenant = $this->currentTenant();

        return app(TenantPropertyPresenter::class)->for($tenant);
    }
}
