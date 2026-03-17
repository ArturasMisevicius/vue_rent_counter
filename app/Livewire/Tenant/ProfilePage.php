<?php

namespace App\Livewire\Tenant;

use App\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProfilePage extends Component
{
    public function render(SupportedLocaleOptions $supportedLocaleOptions): View
    {
        return view('tenant.profile.edit', [
            'tenant' => auth()->user(),
            'supportedLocales' => $supportedLocaleOptions->labels(),
        ]);
    }
}
