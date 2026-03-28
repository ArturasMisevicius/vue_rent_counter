<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:invoices,edit';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SaveInvoiceDraftAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return InvoiceResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
