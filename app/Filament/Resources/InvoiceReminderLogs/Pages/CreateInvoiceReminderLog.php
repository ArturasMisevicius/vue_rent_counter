<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Pages;

use App\Filament\Resources\InvoiceReminderLogs\InvoiceReminderLogResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceReminderLog extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceReminderLogResource::class;
}
