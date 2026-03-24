<?php

namespace App\Filament\Resources\InvoicePayments\Pages;

use App\Filament\Resources\InvoicePayments\InvoicePaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoicePayment extends CreateRecord
{
    protected static string $resource = InvoicePaymentResource::class;
}
