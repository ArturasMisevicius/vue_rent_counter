<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Pages;

use App\Filament\Resources\InvoiceEmailLogs\InvoiceEmailLogResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceEmailLog extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceEmailLogResource::class;
}
