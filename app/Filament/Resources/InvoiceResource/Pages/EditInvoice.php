<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Http\Requests\FinalizeInvoiceRequest;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Validator;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('finalize')
                ->label(__('invoices.actions.finalize'))
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('invoices.admin.modals.finalize_heading'))
                ->modalDescription(__('invoices.admin.modals.finalize_description'))
                ->modalSubmitActionLabel(__('invoices.admin.modals.finalize_submit'))
                ->visible(fn (Invoice $record): bool => $record->isDraft())
                ->action(function (Invoice $record) {
                    // Create a FinalizeInvoiceRequest instance for validation
                    $request = new FinalizeInvoiceRequest();
                    $request->setRouteResolver(function () use ($record) {
                        return new class($record) {
                            public function __construct(private Invoice $invoice) {}
                            public function parameter($key) {
                                return $key === 'invoice' ? $this->invoice : null;
                            }
                        };
                    });
                    
                    // Create validator
                    $validator = Validator::make([], $request->rules());
                    
                    // Run custom validation
                    $request->withValidator($validator);
                    
                    // Check if validation fails
                    if ($validator->fails()) {
                        Notification::make()
                            ->title(__('invoices.notifications.cannot_finalize'))
                            ->body($validator->errors()->first())
                            ->danger()
                            ->send();
                        
                        return;
                    }
                    
                    // Finalize the invoice
                    $record->finalize();
                    
                    Notification::make()
                        ->title(__('invoices.notifications.finalized_title'))
                        ->body(__('invoices.notifications.finalized_body'))
                        ->success()
                        ->send();
                    
                    // Refresh the page to reflect changes
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }),
            
            Actions\DeleteAction::make()
                ->visible(fn (Invoice $record): bool => $record->isDraft()),
        ];
    }
    
    /**
     * Disable form editing for finalized invoices
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }
}
