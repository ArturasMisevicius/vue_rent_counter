<?php

declare(strict_types=1);

use Illuminate\Support\Str;

it('keeps representative mutation entrypoints delegated to shared request, action, and service seams', function (): void {
    $mutationEntryPoints = [
        'app/Filament/Actions/Admin/MeterReadings/CreateMeterReadingAction.php' => [
            'StoreMeterReadingRequest',
            'validatePayload(',
            'ValidateReadingValue',
            'MeterReadingService',
            'meterReadingService->create(',
        ],
        'app/Filament/Actions/Admin/Invoices/SendInvoiceEmailAction.php' => [
            'SendInvoiceEmailRequest',
            'validatePayload(',
        ],
        'app/Filament/Actions/Admin/Invoices/GenerateBulkInvoicesAction.php' => [
            'GenerateBulkInvoicesRequest',
            'validatePayload(',
        ],
        'app/Filament/Actions/Admin/Invoices/CreateInvoiceDraftAction.php' => [
            'CreateInvoiceDraftRequest',
            'validatePayload(',
            'BillingServiceInterface',
            'SubscriptionLimitGuard',
            'billingService->createDraft(',
        ],
        'app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php' => [
            'StoreMeterReadingRequest',
            'validatePayload(',
            'CreateMeterReadingAction',
            'createMeterReadingAction->handle(',
            'WorkspaceResolver',
        ],
        'app/Filament/Actions/Admin/Invoices/FinalizeInvoiceAction.php' => [
            'BillingServiceInterface',
            'SubscriptionLimitGuard',
            'billingService->finalize(',
        ],
        'app/Filament/Actions/Admin/Invoices/RecordInvoicePaymentAction.php' => [
            'BillingServiceInterface',
            'SubscriptionLimitGuard',
            'billingService->applyPayment(',
        ],
        'app/Filament/Actions/Admin/Invoices/SaveInvoiceDraftAction.php' => [
            'BillingServiceInterface',
            'SubscriptionLimitGuard',
            'billingService->saveDraft(',
        ],
        'app/Services/Billing/BillingService.php' => [
            'SaveInvoiceDraftRequest',
            'ProcessPaymentRequest',
            'validatePayload(',
        ],
    ];

    foreach ($mutationEntryPoints as $path => $requiredStrings) {
        $contents = file_get_contents(base_path($path));

        expect($contents)->not->toBeFalse();

        foreach ($requiredStrings as $requiredString) {
            expect(Str::contains((string) $contents, $requiredString))
                ->toBeTrue("Expected {$path} to contain {$requiredString}.");
        }
    }
});
