<?php

namespace App\Filament\Resources\InvoicePayments\Pages;

use App\Filament\Resources\InvoicePayments\InvoicePaymentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoicePayment extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoicePaymentResource::class;
}
