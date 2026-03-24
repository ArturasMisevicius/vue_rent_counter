<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Pages;

use App\Filament\Resources\InvoiceReminderLogs\InvoiceReminderLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceReminderLog extends EditRecord
{
    protected static string $resource = InvoiceReminderLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
