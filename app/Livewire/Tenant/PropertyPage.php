<?php

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenant\Portal\TenantPropertyPresenter;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PropertyPage extends Component
{
    public function render(TenantPropertyPresenter $presenter): View
    {
        return view('tenant.property.show', [
            'summary' => $presenter->for(auth()->user()),
        ]);
    }
}
