<?php

use App\Enums\InvoiceStatus;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15 09:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('shows only permitted attention widgets to managers', function (): void {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
    ]);
    $period = BillingPeriod::factory()->for($organization)->create([
        'name' => 'March 2026',
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-31',
    ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-300001',
            'billing_period_id' => $period->id,
            'billing_period_start' => '2026-03-01',
            'billing_period_end' => '2026-03-31',
            'status' => InvoiceStatus::DRAFT,
            'automation_level' => 'reading_request',
            'approval_status' => 'pending',
        ]);

    ManagerPermission::syncForManager($manager, $organization, [
        'billing' => ['can_create' => false, 'can_edit' => true, 'can_delete' => false],
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Admin Attention Dashboard')
        ->assertSeeText('Billing progress')
        ->assertSeeText('Waiting for readings')
        ->assertDontSeeText('Tenant onboarding')
        ->assertDontSeeText('Contract attention')
        ->assertDontSeeText('Service configuration health');
});
