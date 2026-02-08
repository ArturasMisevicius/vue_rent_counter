<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * Mutate the form data before creating the record.
     * Automatically assigns tenant_id from authenticated user.
     *
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set tenant_id from authenticated user
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        return $data;
    }
}
