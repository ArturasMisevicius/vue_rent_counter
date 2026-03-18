<?php

use App\Enums\PropertyType;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Http\Requests\Admin\Properties\StorePropertyRequest;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('returns a validation error when an admin at the property limit tries to create another property', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 1,
        'tenant_limit_snapshot' => 5,
    ]);

    Property::factory()->for($organization)->for($building)->create();

    $this->actingAs($admin);

    $request = (new StorePropertyRequest)->forOrganization($organization->id);

    try {
        $request->validatePayload([
            'building_id' => $building->id,
            'name' => 'Overflow Unit',
            'unit_number' => '99',
            'type' => PropertyType::APARTMENT,
            'floor_area_sqm' => '60.00',
        ], $admin);

        $this->fail('Expected the subscription limit validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())
            ->toHaveKey('subscription_limit')
            ->and($exception->errors()['subscription_limit'])
            ->toContain(__('subscriptions.property_limit_reached'));
    }
});

it('allows an admin below the property limit to create a property', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 2,
        'tenant_limit_snapshot' => 5,
    ]);

    Property::factory()->for($organization)->for($building)->create();

    $this->actingAs($admin);

    $property = app(CreatePropertyAction::class)->handle($organization, [
        'building_id' => $building->id,
        'name' => 'Fresh Unit',
        'unit_number' => 'B-22',
        'type' => PropertyType::APARTMENT,
        'floor_area_sqm' => 57.25,
    ]);

    expect($property)
        ->name->toBe('Fresh Unit')
        ->organization_id->toBe($organization->id);
});

it('halts the filament property create page when the property limit is reached', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 1,
        'tenant_limit_snapshot' => 5,
    ]);

    Property::factory()->for($organization)->for($building)->create([
        'name' => 'Existing Unit',
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateProperty::class)
        ->set('data.building_id', $building->id)
        ->set('data.name', 'Blocked Unit')
        ->set('data.unit_number', 'B-33')
        ->set('data.type', PropertyType::APARTMENT->value)
        ->set('data.floor_area_sqm', '45.00')
        ->call('create')
        ->assertNotified();

    expect(Property::query()
        ->where('organization_id', $organization->id)
        ->where('name', 'Blocked Unit')
        ->exists())->toBeFalse();
});

it('allows invoice reads but blocks invoice generation during the grace period', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(3),
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful();

    expect(fn () => app(GenerateBulkInvoicesAction::class)->handle(
        $organization,
        now()->startOfMonth()->toDateString(),
        now()->endOfMonth()->toDateString(),
        $admin,
    ))->toThrow(ValidationException::class);
});

it('allows only profile and settings pages after the subscription grace period', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(10),
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    $settingsUrl = route('filament.admin.pages.settings').'#subscription';

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertRedirect($settingsUrl);
});

it('shows a suspension message when the subscription is suspended', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::SUSPENDED,
        'expires_at' => now()->addMonth(),
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertStatus(423)
        ->assertSeeText(__('behavior.subscription.suspended.title'));
});

it('uses the cache and performs a single subscription lookup for repeated checks', function () {
    Cache::flush();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 10,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    $checker = app(SubscriptionChecker::class);

    DB::connection()->flushQueryLog();
    DB::connection()->enableQueryLog();

    expect($checker->canCreateProperty($admin))->toBeTrue()
        ->and($checker->getRemainingProperties($admin))->toBe(10)
        ->and($checker->canCreateTenant($admin))->toBeTrue()
        ->and($checker->getRemainingTenants($admin))->toBe(10);

    expect(DB::connection()->getQueryLog())->toHaveCount(1);
});
