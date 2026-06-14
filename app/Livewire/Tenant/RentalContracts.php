<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\TenantRentalContractPresenter;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class RentalContracts extends Component
{
    public function render(TenantRentalContractPresenter $presenter): View
    {
        $tenant = $this->tenant();

        return view('livewire.tenant.rental-contracts', [
            'tenant' => $tenant,
            'contracts' => $presenter->for($tenant),
        ]);
    }

    private function tenant(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isTenant(), 403);

        return $user;
    }
}
