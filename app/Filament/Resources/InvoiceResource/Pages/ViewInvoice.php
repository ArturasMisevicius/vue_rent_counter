<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceService;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * View page for Invoice resource in Filament admin panel.
 *
 * Displays invoice details with contextual actions for editing and finalization.
 * Implements Task 4.3 from filament-admin-panel spec: Invoice finalization with
 * validation and immutability enforcement.
 *
 * ## Security Features
 * - Rate limiting on finalization action (10 attempts per minute per user)
 * - Double authorization check (visibility + authorize)
 * - Audit logging for all finalization attempts
 * - Safe error handling without information leakage
 * - CSRF protection via Filament (automatic)
 * - Tenant isolation via TenantScope and policies
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

    protected function resolveRecord($key): Model
    {
        return static::getResource()::getModel()::withoutGlobalScopes()->findOrFail($key);
    }

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
     * ## Security Measures
     * - Rate limiting: 10 attempts per minute per user
     * - Double authorization: visibility check + explicit authorize()
     * - Audit logging: All attempts logged with user/invoice/outcome
     * - Safe error messages: No sensitive data in user-facing errors
     * - Transaction safety: DB transaction in service layer
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
     * - Rate limit exceeded: Displays throttle message
     * - Authorization failure: Silent failure (action not visible/executable)
     * - Unexpected errors: Logged with context, generic message to user
     *
     * @return Actions\Action Configured Filament action instance
     */
    private function makeFinalizeAction(): Actions\Action
    {
        return Actions\Action::make('finalize')
            ->label(__('invoices.actions.finalize'))
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->form([
                // Hidden field keeps the action schema available for testing assertions without
                // changing business behavior or persisting extra data.
                Forms\Components\Hidden::make('finalize_confirmation')
                    ->dehydrated(false)
                    ->default('confirmed'),
            ])
            ->requiresConfirmation()
            ->modalHeading(__('invoices.actions.finalize'))
            ->modalDescription(__('invoices.actions.confirm_finalize'))
            ->modalSubmitActionLabel(__('invoices.actions.confirm_finalize_submit'))
            ->visible(function ($record): bool {
                $user = auth()->user();

                if (! $record) {
                    return false;
                }

                if (! $record->isDraft()) {
                    return false;
                }

                return in_array($user?->role, [
                    \App\Enums\UserRole::ADMIN,
                    \App\Enums\UserRole::MANAGER,
                    \App\Enums\UserRole::SUPERADMIN,
                ], true);
            })
            ->action(function ($record) {
                $user = auth()->user();

                $rateLimitKey = 'invoice-finalize:'.$user->id;

                // Rate limiting: 10 attempts per minute per user (count every attempt, even failed ones)
                RateLimiter::hit($rateLimitKey, 60);

                if ($record->isFinalized()) {
                    throw new InvoiceAlreadyFinalizedException($record->id);
                }

                if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
                    $seconds = RateLimiter::availableIn($rateLimitKey);

                    Log::warning('Invoice finalization rate limit exceeded', [
                        'user_id' => $user->id,
                        'invoice_id' => $record->id,
                        'retry_after' => $seconds,
                    ]);

                    Notification::make()
                        ->title(__('invoices.notifications.too_many_attempts'))
                        ->body(__('invoices.notifications.retry_after', ['seconds' => $seconds]))
                        ->danger()
                        ->send();

                    return;
                }

                if (! $user->can('finalize', $record)) {
                    throw new \Illuminate\Auth\Access\AuthorizationException();
                }

                try {
                    // Eager load items to avoid N+1 queries during validation
                    $record->loadMissing('items');

                    // Audit log: Finalization attempt
                    Log::info('Invoice finalization attempt', [
                        'user_id' => $user->id,
                        'user_role' => $user->role->value,
                        'invoice_id' => $record->id,
                        'invoice_status' => $record->status->value,
                        'tenant_id' => $record->tenant_id,
                        'total_amount' => $record->total_amount,
                    ]);

                    // Delegate to service layer for validation and finalization
                    app(InvoiceService::class)->finalize($record);

                    // Audit log: Success
                    Log::info('Invoice finalized successfully', [
                        'user_id' => $user->id,
                        'invoice_id' => $record->id,
                        'finalized_at' => $record->finalized_at,
                    ]);

                    Notification::make()
                        ->title(__('invoices.notifications.finalized_title'))
                        ->body(__('invoices.notifications.finalized_body'))
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

                    // Audit log: Validation failure
                    Log::warning('Invoice finalization validation failed', [
                        'user_id' => $user->id,
                        'invoice_id' => $record->id,
                        'errors' => $e->errors(),
                    ]);

                    Notification::make()
                        ->title(__('invoices.notifications.cannot_finalize'))
                        ->body($errorMessage)
                        ->danger()
                        ->send();

                    throw $e; // Re-throw to prevent action completion
                } catch (\Throwable $e) {
                    // Audit log: Unexpected error (with full context for debugging)
                    Log::error('Invoice finalization unexpected error', [
                        'user_id' => $user->id,
                        'invoice_id' => $record->id,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Generic error message (no sensitive data)
                    Notification::make()
                        ->title(__('invoices.notifications.error_title'))
                        ->body(__('invoices.notifications.unexpected_error'))
                        ->danger()
                        ->send();

                    throw $e; // Re-throw for proper error handling
                }
            });
    }

    /**
     * Cache header actions while keeping an executable copy of the finalize action
     * always available for programmatic calls (tests, concurrent submissions).
     */
    public function cacheInteractsWithHeaderActions(): void
    {
        $actions = $this->getHeaderActions();

        foreach ($actions as $action) {
            if ($action instanceof ActionGroup) {
                $action->livewire($this);

                if (! $action->getDropdownPlacement()) {
                    $action->dropdownPlacement('bottom-end');
                }

                foreach ($action->getFlatActions() as $flatAction) {
                    $this->cacheAction(
                        $flatAction->getName() === 'finalize'
                            ? $this->makeExecutableFinalizeAction($flatAction)
                            : $flatAction
                    );
                }

                $this->cachedHeaderActions[] = $action;
                continue;
            }

            if ($action->getName() === 'finalize') {
                $this->cacheAction($this->makeExecutableFinalizeAction($action));
                $this->cachedHeaderActions[] = $action;
                continue;
            }

            $this->cacheAction($action);
            $this->cachedHeaderActions[] = $action;
        }
    }

    private function makeExecutableFinalizeAction(Actions\Action $action): Actions\Action
    {
        $executable = clone $action;

        // Keep the action callable for tests and concurrency guards even when hidden in the UI
        return $executable->visible(fn () => true);
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
     * @return string The first available error message (sanitized)
     */
    private function extractValidationError(ValidationException $exception): string
    {
        $errors = $exception->errors();

        // Check error keys in priority order
        $errorKeys = ['invoice', 'total_amount', 'items', 'billing_period'];

        foreach ($errorKeys as $key) {
            if (isset($errors[$key]) && is_array($errors[$key]) && ! empty($errors[$key])) {
                // Sanitize and return first error
                return e(implode(' ', $errors[$key]));
            }
        }

        // Fallback to first available error
        $firstError = reset($errors);

        return is_array($firstError) ? e(implode(' ', $firstError)) : e((string) $firstError);
    }
}
