<?php

namespace App\Filament\Resources\InvoicePayments\Pages;

use App\Filament\Resources\InvoicePayments\InvoicePaymentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoicePayment extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = InvoicePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
