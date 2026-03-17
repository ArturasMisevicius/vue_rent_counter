<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Organization;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Organization $organization */
        $organization = auth()->user()->organization;

        return app(SaveInvoiceDraftAction::class)->handle($organization, $data, auth()->user());
    }
}
