<?php

namespace App\Filament\Resources\InvoiceItems\Pages;

use App\Filament\Resources\InvoiceItems\InvoiceItemResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewInvoiceItem extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
