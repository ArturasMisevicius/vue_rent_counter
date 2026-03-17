<?php

namespace App\Livewire\Tenant;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ReadingCreatePage extends Component
{
    public function render(): View
    {
        return view('tenant.readings.create');
    }
}
