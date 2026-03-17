<?php

namespace App\Livewire\Tenant;

use App\Support\Tenant\Portal\TenantHomePresenter;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomeSummary extends Component
{
    public function render(): View
    {
        return view('livewire.tenant.home-summary', [
            'summary' => app(TenantHomePresenter::class)->for(auth()->user()),
        ]);
    }
}
