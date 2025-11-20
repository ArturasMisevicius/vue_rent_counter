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
                ->label('Finalize Invoice')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Finalize Invoice')
                ->modalDescription('Are you sure you want to finalize this invoice? Once finalized, the invoice cannot be edited.')
                ->modalSubmitActionLabel('Finalize')
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
                            ->title('Cannot finalize invoice')
                            ->body($validator->errors()->first())
                            ->danger()
                            ->send();
                        
                        return;
                    }
                    
                    // Finalize the invoice
                    $record->finalize();
                    
                    Notification::make()
                        ->title('Invoice finalized')
                        ->body('The invoice has been successfully finalized.')
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
