<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Pages;

use App\Filament\Resources\InvoiceReminderLogs\InvoiceReminderLogResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceReminderLog extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceReminderLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
