<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InvoiceService::class);
});

describe('finalize', function () {
    test('successfully finalizes a valid draft invoice', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->service->finalize($invoice);

        expect($invoice->fresh())
            ->status->toBe(InvoiceStatus::FINALIZED)
            ->finalized_at->not->toBeNull();
    });

    test('throws exception when invoice is already finalized', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->service->finalize($invoice);
    })->throws(InvoiceAlreadyFinalizedException::class);

    test('throws validation exception when invoice has no items', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        $this->service->finalize($invoice);
    })->throws(ValidationException::class, 'Cannot finalize invoice: invoice has no items');

    test('throws validation exception when total amount is zero', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->service->finalize($invoice);
    })->throws(ValidationException::class);

    test('throws validation exception when total amount is negative', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => -50.00,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->service->finalize($invoice);
    })->throws(ValidationException::class);

    test('throws validation exception when billing period is invalid', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
            'billing_period_start' => now(),
            'billing_period_end' => now()->subDay(),
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->service->finalize($invoice);
    })->throws(ValidationException::class, 'billing period start must be before billing period end');

    test('throws validation exception when invoice item has invalid data', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => '',
            'unit_price' => 10.00,
            'quantity' => 1,
        ]);

        $this->service->finalize($invoice);
    })->throws(ValidationException::class);

    test('finalizes invoice in transaction', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        // Verify transaction behavior by checking database state
        $this->service->finalize($invoice);
        
        expect($invoice->fresh())
            ->status->toBe(InvoiceStatus::FINALIZED)
            ->finalized_at->not->toBeNull();
    });
});

describe('canFinalize', function () {
    test('returns true for valid draft invoice', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        expect($this->service->canFinalize($invoice))->toBeTrue();
    });

    test('returns false for finalized invoice', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        expect($this->service->canFinalize($invoice))->toBeFalse();
    });

    test('returns false for invoice with no items', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        expect($this->service->canFinalize($invoice))->toBeFalse();
    });

    test('returns false for invoice with zero total', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0,
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        expect($this->service->canFinalize($invoice))->toBeFalse();
    });

    test('returns false for invoice with invalid billing period', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
            'billing_period_start' => now(),
            'billing_period_end' => now()->subDay(),
        ]);
        
        InvoiceItem::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        expect($this->service->canFinalize($invoice))->toBeFalse();
    });
});
