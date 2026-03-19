<?php

declare(strict_types=1);

use App\Livewire\Tenant\InvoiceHistory;
use App\Livewire\Tenant\PropertyDetails;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

dataset('tenant-self-service-components', [
    'invoice history' => InvoiceHistory::class,
    'property details' => PropertyDetails::class,
    'submit reading' => SubmitReadingPage::class,
]);

it('forbids non-tenant accounts from rendering tenant self-service livewire components directly', function (string $component) {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test($component)
        ->assertForbidden();
})->with('tenant-self-service-components');

it('treats malformed cross-organization assignments as having no active tenant property boundary', function () {
    $tenantOrganization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $tenantOrganization->id,
    ]);

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    PropertyAssignment::factory()
        ->for($foreignOrganization)
        ->for($foreignProperty)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    Livewire::actingAs($tenant)
        ->test(SubmitReadingPage::class)
        ->assertSeeText(__('tenant.messages.no_meters_assigned'));

    Livewire::actingAs($tenant)
        ->test(PropertyDetails::class)
        ->assertNotFound();
});
