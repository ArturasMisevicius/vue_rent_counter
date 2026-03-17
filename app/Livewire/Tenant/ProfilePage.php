<?php

namespace App\Livewire\Tenant;

use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProfilePage extends Component
{
    public function render(): View
    {
        return view('tenant.profile.edit', [
            'tenant' => auth()->user(),
            'supportedLocales' => $this->supportedLocales,
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function supportedLocales(): array
    {
        return app(SupportedLocaleOptions::class)->labels();
    }
}
