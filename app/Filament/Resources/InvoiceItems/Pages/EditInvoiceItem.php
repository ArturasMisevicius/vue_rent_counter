<?php

namespace App\Filament\Resources\InvoiceItems\Pages;

use App\Filament\Resources\InvoiceItems\InvoiceItemResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceItem extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
