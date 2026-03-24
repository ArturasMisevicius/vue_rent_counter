<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Pages;

use App\Filament\Resources\InvoiceEmailLogs\InvoiceEmailLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceEmailLog extends CreateRecord
{
    protected static string $resource = InvoiceEmailLogResource::class;
}
