<?php

namespace App\Filament\Resources\InvoiceItems\Pages;

use App\Filament\Resources\InvoiceItems\InvoiceItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceItem extends ViewRecord
{
    protected static string $resource = InvoiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
