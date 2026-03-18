<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Events\InvoiceFinalized;
use App\Events\MeterReadingSubmitted;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Livewire\Pages\Dashboard\AdminDashboard;
use App\Livewire\Pages\Dashboard\TenantDashboard;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('refreshes the admin dashboard when an invoice finalized event is received', function () {
    $admin = seedRealtimeAdminDashboardUser();

    $component = Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertDontSeeText('INV-REFRESH-001');

    $assignment = PropertyAssignment::query()
        ->select(['id', 'organization_id', 'property_id', 'tenant_user_id'])
        ->where('organization_id', $admin->organization_id)
        ->firstOrFail();

    Invoice::factory()
        ->for($assignment->organization_id !== null
            ? Organization::query()->findOrFail($assignment->organization_id)
            : Organization::factory()->create())
        ->for(Property::query()->findOrFail($assignment->property_id))
        ->for(User::query()->findOrFail($assignment->tenant_user_id), 'tenant')
        ->create([
            'invoice_number' => 'INV-REFRESH-001',
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => 88.40,
            'amount_paid' => 0,
        ]);

    app(DashboardCacheService::class)->touchOrganization($admin->organization_id);

    $component
        ->dispatch('invoice.finalized')
        ->assertSeeText('INV-REFRESH-001');
});

it('dispatches the invoice finalized broadcast event when a draft invoice is finalized', function () {
    Event::fake([InvoiceFinalized::class]);

    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-DRAFT-RT-001',
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
            'items' => [
                ['description' => 'Water usage', 'amount' => 42.15],
            ],
            'total_amount' => 42.15,
        ]);

    app(FinalizeInvoiceAction::class)->handle($invoice, [
        'items' => [
            ['description' => 'Water usage', 'amount' => 42.15],
        ],
        'total_amount' => 42.15,
    ]);

    Event::assertDispatched(InvoiceFinalized::class, function (InvoiceFinalized $event) use ($organization, $invoice, $tenant): bool {
        return $event->organizationId === $organization->id
            && $event->invoiceId === $invoice->id
            && $event->tenantUserId === $tenant->id;
    });
});

it('refreshes the tenant dashboard when a reading submitted event is received', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $fixture->meters->firstOrFail();

    $component = Livewire::actingAs($fixture->user)
        ->test(TenantDashboard::class)
        ->assertDontSeeText('188.500');

    MeterReading::factory()
        ->for($fixture->organization)
        ->for($fixture->property)
        ->for($meter)
        ->create([
            'submitted_by_user_id' => $fixture->user->id,
            'reading_value' => 188.500,
            'reading_date' => now()->toDateString(),
            'validation_status' => MeterReadingValidationStatus::VALID,
            'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
        ]);

    app(DashboardCacheService::class)->touchOrganization($fixture->organization->id);

    $component
        ->dispatch('reading.submitted')
        ->assertSeeText('188.500')
        ->assertSeeText($meter->unit);
});

it('dispatches the reading submitted broadcast event when a tenant saves a reading', function () {
    Event::fake([MeterReadingSubmitted::class]);

    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $fixture->meters->firstOrFail();

    Livewire::actingAs($fixture->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors();

    Event::assertDispatched(MeterReadingSubmitted::class, function (MeterReadingSubmitted $event) use ($fixture, $meter): bool {
        return $event->organizationId === $fixture->organization->id
            && $event->meterId === $meter->id
            && $event->propertyId === $fixture->property->id
            && $event->tenantUserId === $fixture->user->id;
    });
});

function seedRealtimeAdminDashboardUser(): User
{
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    return $admin;
}
