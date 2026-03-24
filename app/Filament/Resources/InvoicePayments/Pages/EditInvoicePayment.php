<?php

namespace App\Filament\Resources\InvoicePayments\Pages;

use App\Filament\Resources\InvoicePayments\InvoicePaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoicePayment extends EditRecord
{
    protected static string $resource = InvoicePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
