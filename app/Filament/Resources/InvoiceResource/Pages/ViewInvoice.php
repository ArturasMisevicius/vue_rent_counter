<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

/**
 * View page for Invoice resource in Filament admin panel.
 *
 * Displays invoice details with contextual actions for editing and finalization.
 * Implements Task 4.3 from filament-admin-panel spec: Invoice finalization with
 * validation and immutability enforcement.
 *
 * ## Features
 * - Edit action (visible only for draft invoices with update permission)
 * - Finalize action (visible only for draft invoices with finalize permission)
 * - Automatic validation via InvoiceService before finalization
 * - Real-time UI feedback via Filament notifications
 * - Proper separation of concerns (business logic in service layer)
 *
 * ## Authorization
 * - Edit: Requires 'update' permission via InvoicePolicy
 * - Finalize: Requires 'finalize' permission via InvoicePolicy
 * - Respects tenant scope isolation (admin/manager can only finalize their tenant's invoices)
 *
 * ## Validation Rules (enforced by InvoiceService)
 * - Invoice must have at least one item
 * - Total amount must be greater than zero
 * - All items must have valid description, unit_price >= 0, quantity >= 0
 * - Billing period start must be before billing period end
 * - Invoice must be in DRAFT status
 *
 * @see InvoiceService For finalization business logic
 * @see InvoicePolicy For authorization rules
 * @see Invoice::finalize() For model-level finalization
 */
final class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * Get the header actions for the invoice view page.
     *
     * Returns an array of Filament actions that appear in the page header.
     * Actions are conditionally visible based on invoice status and user permissions.
     *
     * @return array<Actions\Action> Array of Filament action instances
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => auth()->user()->can('update', $record)),

            $this->makeFinalizeAction(),
        ];
    }

    /**
     * Create the finalize invoice action.
     *
     * Implements Task 4.3: Invoice finalization action with validation.
     * Delegates business logic to InvoiceService for proper separation of concerns.
     *
     * ## Action Behavior
     * - Displays confirmation modal before finalization
     * - Validates invoice via InvoiceService (checks items, amounts, billing period)
     * - Sets status to FINALIZED and records finalized_at timestamp
     * - Makes invoice immutable (prevents further edits except status changes)
     * - Refreshes UI to reflect updated status
     *
     * ## Visibility Rules
     * - Only visible for DRAFT invoices
     * - Requires 'finalize' permission (checked via InvoicePolicy)
     * - Hidden for FINALIZED or PAID invoices
     *
     * ## Error Handling
     * - ValidationException: Displays specific validation errors in danger notification
     * - Re-throws exception to prevent action completion
     * - Extracts errors from multiple possible keys (invoice, total_amount, items, billing_period)
     *
     * @return Actions\Action Configured Filament action instance
     */
    private function makeFinalizeAction(): Actions\Action
    {
        return Actions\Action::make('finalize')
            ->label(__('Finalize Invoice'))
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('Finalize Invoice'))
            ->modalDescription(__('Are you sure you want to finalize this invoice? Once finalized, the invoice cannot be modified.'))
            ->modalSubmitActionLabel(__('Yes, finalize it'))
            ->visible(fn ($record) => $record->isDraft() && auth()->user()->can('finalize', $record))
            ->authorize(fn ($record) => auth()->user()->can('finalize', $record))
            ->action(function ($record) {
                try {
                    // Eager load items to avoid N+1 queries during validation
                    $record->loadMissing('items');

                    // Delegate to service layer for validation and finalization
                    app(InvoiceService::class)->finalize($record);

                    Notification::make()
                        ->title(__('Invoice finalized'))
                        ->body(__('The invoice has been successfully finalized.'))
                        ->success()
                        ->send();

                    // Refresh record to show updated status and finalized_at timestamp
                    $this->refreshFormData([
                        'status',
                        'finalized_at',
                    ]);
                } catch (ValidationException $e) {
                    // Extract first available error message from validation exception
                    $errorMessage = $this->extractValidationError($e);

                    Notification::make()
                        ->title(__('Cannot finalize invoice'))
                        ->body($errorMessage)
                        ->danger()
                        ->send();

                    throw $e; // Re-throw to prevent action completion
                }
            });
    }

    /**
     * Extract the first validation error message from a ValidationException.
     *
     * Checks multiple possible error keys in priority order:
     * 1. invoice - General invoice validation errors
     * 2. total_amount - Amount validation errors
     * 3. items - Invoice items validation errors
     * 4. billing_period - Billing period validation errors
     *
     * @param  ValidationException  $exception  The validation exception
     * @return string The first available error message
     */
    private function extractValidationError(ValidationException $exception): string
    {
        $errors = $exception->errors();

        // Check error keys in priority order
        $errorKeys = ['invoice', 'total_amount', 'items', 'billing_period'];

        foreach ($errorKeys as $key) {
            if (isset($errors[$key]) && is_array($errors[$key]) && ! empty($errors[$key])) {
                return implode(' ', $errors[$key]);
            }
        }

        // Fallback to first available error
        $firstError = reset($errors);

        return is_array($firstError) ? implode(' ', $firstError) : (string) $firstError;
    }
}
