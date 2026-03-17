<?php

use App\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('locks finalized invoice commercial fields while keeping payment updates available', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
            'amount_paid' => 0,
            'items' => [
                ['description' => 'Water usage', 'amount' => 100.00],
            ],
            'finalized_at' => null,
            'payment_reference' => null,
        ]);

    $finalized = app(FinalizeInvoiceAction::class)->handle($invoice, [
        'total_amount' => 125.00,
        'items' => [
            ['description' => 'Final water usage', 'amount' => 125.00],
        ],
    ]);

    expect($finalized->status)->toBe(InvoiceStatus::FINALIZED)
        ->and($finalized->finalized_at)->not->toBeNull()
        ->and((float) $finalized->total_amount)->toBe(125.0)
        ->and($finalized->items)->toBe([
            ['description' => 'Final water usage', 'amount' => 125.00],
        ]);

    expect(fn () => app(SaveInvoiceDraftAction::class)->handle($finalized->fresh(), [
        'total_amount' => 200.00,
        'items' => [
            ['description' => 'Changed after finalization', 'amount' => 200.00],
        ],
    ]))->toThrow(ValidationException::class);

    expect((float) $finalized->fresh()->total_amount)->toBe(125.0)
        ->and($finalized->fresh()->items)->toBe([
            ['description' => 'Final water usage', 'amount' => 125.00],
        ]);

    $paid = app(RecordInvoicePaymentAction::class)->handle($finalized->fresh(), [
        'amount_paid' => 125.00,
        'payment_reference' => 'BANK-001',
        'paid_at' => now(),
    ]);

    expect($paid->status)->toBe(InvoiceStatus::PAID)
        ->and((float) $paid->amount_paid)->toBe(125.0)
        ->and($paid->payment_reference)->toBe('BANK-001')
        ->and((float) $paid->total_amount)->toBe(125.0)
        ->and($paid->items)->toBe([
            ['description' => 'Final water usage', 'amount' => 125.00],
        ]);

    Livewire::actingAs($admin)
        ->test(EditInvoice::class, ['record' => $paid->getRouteKey()])
        ->assertFormFieldVisible('amount_paid')
        ->assertFormFieldVisible('payment_reference')
        ->assertFormFieldHidden('total_amount')
        ->assertFormFieldHidden('items');
});
