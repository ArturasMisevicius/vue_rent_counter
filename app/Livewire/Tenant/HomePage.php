<?php

namespace App\Livewire\Tenant;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
    public function render(): View
    {
        return view('tenant.home');
    }
}
