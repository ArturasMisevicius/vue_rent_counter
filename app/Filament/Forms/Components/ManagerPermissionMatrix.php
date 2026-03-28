<?php

namespace App\Filament\Forms\Components;

use App\Livewire\Filament\ManagerPermissionMatrixPanel;
use Closure;
use Filament\Schemas\Components\Livewire;

class ManagerPermissionMatrix extends Livewire
{
    public static function make(string|Closure $component = ManagerPermissionMatrixPanel::class, array|Closure $data = []): static
    {
        /** @var static $component */
        $component = parent::make($component, $data);

        return $component->columnSpanFull();
    }
}
