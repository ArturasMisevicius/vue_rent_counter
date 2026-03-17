<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant greeting, outstanding balance, and recent readings', function () {
    $tenant = TenantPortalFactory::new()
        ->withUserName('Taylor Tenant')
        ->withUnpaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('Taylor')
        ->assertSeeText('Outstanding Balance')
        ->assertSeeText('This Month')
        ->assertSeeText('Recent Readings')
        ->assertSeeText('Submit New Reading');
});

it('shows all paid up copy when no unpaid invoices exist', function () {
    $tenant = TenantPortalFactory::new()
        ->withPaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('All paid up');
});

it('shows the combined unpaid invoice count copy', function () {
    $tenant = TenantPortalFactory::new()
        ->withUnpaidInvoices(2)
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('Across 2 invoices');
});

it('shows no reading this month when a meter is missing a current-month reading', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('No reading this month');
});

it('shows the my property link on the tenant home screen', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('My Property')
        ->assertSee(route('tenant.property.show'), false);
});
