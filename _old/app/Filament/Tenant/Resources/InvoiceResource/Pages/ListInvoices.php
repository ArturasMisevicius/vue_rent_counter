<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\InvoiceResource\Pages;

use App\Filament\Tenant\Resources\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

final class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}