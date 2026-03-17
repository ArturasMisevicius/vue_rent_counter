<?php

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\TenantPropertyPresenter;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PropertyPage extends Component
{
    public function render(): View
    {
        return view('tenant.property.show', [
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
        $tenant = auth()->user();

        return app(TenantPropertyPresenter::class)->for($tenant);
    }
}
