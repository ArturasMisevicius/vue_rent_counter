<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Pages;

use App\Filament\Resources\InvoiceEmailLogs\InvoiceEmailLogResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceEmailLog extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
