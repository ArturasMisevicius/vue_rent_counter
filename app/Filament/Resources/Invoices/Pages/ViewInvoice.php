<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            InvoiceResource::getUrl('index') => InvoiceResource::getPluralModelLabel(),
            $this->record->invoice_number,
        ];
    }

    public function getTitle(): string
    {
        return __('admin.invoices.view_title');
    }
}
