<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('finalize')
                ->label(__('admin.invoices.actions.finalize'))
                ->visible(fn (): bool => $this->getRecord()->status->value === 'draft')
                ->action(function (): void {
                    app(FinalizeInvoiceAction::class)->handle($this->getRecord(), auth()->user());
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Invoice $record */
        /** @var Organization $organization */
        $organization = auth()->user()->organization;

        $data['invoice_id'] = $record->id;

        return app(SaveInvoiceDraftAction::class)->handle($organization, $data, auth()->user());
    }
}
