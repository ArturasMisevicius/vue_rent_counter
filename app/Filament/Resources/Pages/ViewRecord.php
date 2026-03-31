<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use Filament\Support\Enums\Width;

abstract class ViewRecord extends \Filament\Resources\Pages\ViewRecord
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
