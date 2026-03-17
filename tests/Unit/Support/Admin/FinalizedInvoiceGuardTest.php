<?php

use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;

it('allows payment fields to change on finalized invoices but locks commercial fields', function () {
    $guard = app(FinalizedInvoiceGuard::class);

    expect($guard->canMutateField('paid_at'))->toBeTrue()
        ->and($guard->canMutateField('payment_reference'))->toBeTrue()
        ->and($guard->canMutateField('amount_paid'))->toBeTrue()
        ->and($guard->canMutateField('status'))->toBeTrue()
        ->and($guard->canMutateField('total_amount'))->toBeFalse()
        ->and($guard->canMutateField('items'))->toBeFalse()
        ->and($guard->canMutateField('billing_period_start'))->toBeFalse();
});
