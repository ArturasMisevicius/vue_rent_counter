<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Pages;

use App\Filament\Resources\InvoiceEmailLogs\InvoiceEmailLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceEmailLog extends EditRecord
{
    protected static string $resource = InvoiceEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
