<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Pages;

use App\Filament\Resources\InvoiceReminderLogs\InvoiceReminderLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceReminderLogs extends ListRecords
{
    protected static string $resource = InvoiceReminderLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
