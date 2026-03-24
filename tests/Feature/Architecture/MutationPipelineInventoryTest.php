<?php

declare(strict_types=1);

use Illuminate\Support\Str;

it('keeps representative mutation entrypoints delegated to shared action and service seams', function (): void {
    $mutationEntryPoints = [
        'app/Filament/Actions/Admin/MeterReadings/CreateMeterReadingAction.php' => [
            'ValidateReadingValue',
            'MeterReadingService',
            'meterReadingService->create(',
        ],
        'app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php' => [
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
