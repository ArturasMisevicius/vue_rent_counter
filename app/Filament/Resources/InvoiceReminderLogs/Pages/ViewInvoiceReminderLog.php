<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Pages;

use App\Filament\Resources\InvoiceReminderLogs\InvoiceReminderLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceReminderLog extends ViewRecord
{
    protected static string $resource = InvoiceReminderLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
