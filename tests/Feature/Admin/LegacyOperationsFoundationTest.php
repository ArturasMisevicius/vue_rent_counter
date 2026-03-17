<?php

use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the legacy operations foundation tables and additive columns', function () {
    expect(Schema::hasTable('invoice_items'))->toBeTrue()
        ->and(Schema::hasTable('invoice_generation_audits'))->toBeTrue()
        ->and(Schema::hasTable('billing_records'))->toBeTrue()
        ->and(Schema::hasTable('meter_reading_audits'))->toBeTrue()
        ->and(Schema::hasTable('leases'))->toBeTrue()
        ->and(Schema::hasTable('subscription_renewals'))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'last_login_at',
            'currency',
            'suspended_at',
            'suspension_reason',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('invoices', [
            'due_date',
            'payment_reference',
            'paid_amount',
            'snapshot_data',
            'snapshot_created_at',
            'generated_at',
            'generated_by',
            'approval_status',
            'automation_level',
            'approval_deadline',
            'approval_metadata',
            'approved_by',
            'approved_at',
        ]))->toBeTrue()
        ->and(class_exists(InvoiceItem::class))->toBeTrue()
        ->and(class_exists(InvoiceGenerationAudit::class))->toBeTrue()
        ->and(class_exists(BillingRecord::class))->toBeTrue()
        ->and(class_exists(MeterReadingAudit::class))->toBeTrue()
        ->and(class_exists(Lease::class))->toBeTrue()
        ->and(class_exists(SubscriptionRenewal::class))->toBeTrue();
});

it('links legacy operations support through current invoice, reading, subscription, and user models', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create();

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
        ->create();

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create();

    $reading = MeterReading::factory()
        ->for($organization)
        ->for($property)
        ->for($meter)
        ->for($admin, 'submittedBy')
        ->create();

    $subscription = Subscription::factory()
        ->for($organization)
        ->active()
        ->create();

    $utilityService = UtilityService::factory()
        ->for($organization)
        ->create();

    $invoiceItem = InvoiceItem::factory()
        ->for($invoice)
        ->create();

    $generationAudit = InvoiceGenerationAudit::factory()
        ->for($invoice)
        ->for($organization)
        ->for($tenant, 'tenant')
        ->for($admin)
        ->create();

    $billingRecord = BillingRecord::factory()
        ->for($organization)
        ->for($property)
        ->for($invoice)
        ->for($tenant, 'tenant')
        ->for($utilityService)
        ->create();

    $readingAudit = MeterReadingAudit::factory()
        ->for($reading)
        ->for($admin, 'changedBy')
        ->create();

    $lease = Lease::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $renewal = SubscriptionRenewal::factory()
        ->for($subscription)
        ->for($admin)
        ->create();

    expect($invoice->fresh()->invoiceItems->contains($invoiceItem))->toBeTrue()
        ->and($invoice->fresh()->generationAudits->contains($generationAudit))->toBeTrue()
        ->and($invoice->fresh()->billingRecords->contains($billingRecord))->toBeTrue()
        ->and($reading->fresh()->audits->contains($readingAudit))->toBeTrue()
        ->and($subscription->fresh()->renewals->contains($renewal))->toBeTrue()
        ->and($tenant->fresh()->leases->contains($lease))->toBeTrue()
        ->and($billingRecord->fresh()->utilityService->is($utilityService))->toBeTrue()
        ->and($generationAudit->fresh()->organization->is($organization))->toBeTrue();
});
