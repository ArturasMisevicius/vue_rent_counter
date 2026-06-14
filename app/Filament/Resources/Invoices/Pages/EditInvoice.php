<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Actions\Admin\Invoices\PrepareReadingRequestInvoiceAction;
use App\Filament\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
            Action::make('prepareFromReadings')
                ->label(__('admin.invoices.actions.prepare_from_readings'))
                ->icon('heroicon-m-calculator')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => __('admin.invoices.actions.prepare_from_readings_heading', [
                    'number' => $this->record instanceof Invoice ? $this->record->invoice_number : '',
                ]))
                ->modalDescription(__('admin.invoices.messages.prepare_from_readings_confirmation'))
                ->modalSubmitActionLabel(__('admin.invoices.actions.prepare_invoice'))
                ->visible(fn (): bool => $this->record instanceof Invoice
                    && $this->record->canPrepareReadingRequestFromAdminWorkspace())
                ->authorize(fn (): bool => $this->record instanceof Invoice
                    && InvoiceResource::canEdit($this->record))
                ->action(function (PrepareReadingRequestInvoiceAction $prepareReadingRequestInvoiceAction): void {
                    if (! $this->record instanceof Invoice) {
                        return;
                    }

                    $user = Auth::user();
                    $this->record = $prepareReadingRequestInvoiceAction->handle(
                        $this->record,
                        $user instanceof User ? $user : null,
                    );
                    $this->fillForm();

                    Notification::make()
                        ->title(__('admin.invoices.messages.prepared_from_readings', [
                            'number' => $this->record->invoice_number,
                        ]))
                        ->success()
                        ->send();
                }),
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
