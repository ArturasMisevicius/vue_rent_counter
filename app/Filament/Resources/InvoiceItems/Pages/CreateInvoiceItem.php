<?php

namespace App\Filament\Resources\InvoiceItems\Pages;

use App\Filament\Resources\InvoiceItems\InvoiceItemResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceItem extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoiceItemResource::class;
}
