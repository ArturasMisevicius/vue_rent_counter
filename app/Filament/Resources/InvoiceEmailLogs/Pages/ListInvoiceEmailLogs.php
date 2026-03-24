<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Pages;

use App\Filament\Resources\InvoiceEmailLogs\InvoiceEmailLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceEmailLogs extends ListRecords
{
    protected static string $resource = InvoiceEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
