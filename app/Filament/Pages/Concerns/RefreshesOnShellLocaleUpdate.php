<?php

declare(strict_types=1);

namespace App\Filament\Pages\Concerns;

use App\Livewire\Concerns\AppliesShellLocale;
use Livewire\Attributes\On;

trait RefreshesOnShellLocaleUpdate
{
    use AppliesShellLocale;

    #[On('shell-locale-updated')]
    public function refreshShellLocale(): void
    {
        $this->applyShellLocale();
    }
}
