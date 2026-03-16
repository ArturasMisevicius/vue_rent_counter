<?php

declare(strict_types=1);

namespace App\Filament\Resources\MeterResource\Pages;

use App\Filament\Resources\MeterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * View page for Meter resource.
 *
 * Displays comprehensive meter information using the default view layout.
 * Meter readings are accessible via the ReadingsRelationManager.
 */
class ViewMeter extends ViewRecord
{
    protected static string $resource = MeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
