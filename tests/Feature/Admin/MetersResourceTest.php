<?php

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Filament\Actions\Admin\Meters\CreateMeterAction;
use App\Filament\Actions\Admin\Meters\DeleteMeterAction;
use App\Filament\Actions\Admin\Meters\ToggleMeterStatusAction;
use App\Filament\Actions\Admin\Meters\UpdateMeterAction;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('shows organization-scoped meter resource pages with history details', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);

    $meter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Main Water Meter',
        'identifier' => 'MTR-1001',
        'type' => MeterType::WATER,
        'unit' => MeterType::WATER->defaultUnit(),
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 125.500,
        'reading_date' => now()->subDay()->toDateString(),
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherMeter = Meter::factory()->for($otherOrganization)->for($otherProperty)->create([
        'name' => 'Hidden Meter',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText('Meters')
        ->assertSeeText($meter->name)
        ->assertSeeText($meter->identifier)
        ->assertSeeText($property->name)
        ->assertDontSeeText($otherMeter->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.create'))
        ->assertSuccessful()
        ->assertSeeText('Property')
        ->assertSeeText('Meter Type')
        ->assertSeeText('Measurement Unit');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.view', $meter))
        ->assertSuccessful()
        ->assertSeeText('Meter Details')
        ->assertSeeText('Reading History');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText($meter->name);
});

it('creates meters with default units, updates and toggles status, and blocks deletion when readings exist', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $created = app(CreateMeterAction::class)->handle($organization, [
        'property_id' => $property->id,
        'name' => 'Basement Meter',
        'identifier' => 'MTR-BASEMENT',
        'type' => MeterType::ELECTRICITY,
        'unit' => null,
        'status' => MeterStatus::ACTIVE,
        'installed_at' => now()->subMonth()->toDateString(),
    ]);

    expect($created)
        ->organization_id->toBe($organization->id)
        ->unit->toBe('kWh')
        ->status->toBe(MeterStatus::ACTIVE);

    $updated = app(UpdateMeterAction::class)->handle($created, [
        'property_id' => $property->id,
        'name' => 'Basement Meter Updated',
        'identifier' => 'MTR-UPDATED',
        'type' => MeterType::WATER,
        'unit' => null,
        'status' => MeterStatus::ACTIVE,
        'installed_at' => now()->subWeeks(2)->toDateString(),
    ]);

    expect($updated)
        ->name->toBe('Basement Meter Updated')
        ->identifier->toBe('MTR-UPDATED')
        ->unit->toBe('m3');

    $inactive = app(ToggleMeterStatusAction::class)->handle($updated);
    $reactivated = app(ToggleMeterStatusAction::class)->handle($inactive->fresh());

    expect($inactive->status)->toBe(MeterStatus::INACTIVE)
        ->and($reactivated->status)->toBe(MeterStatus::ACTIVE);

    MeterReading::factory()->for($organization)->for($property)->for($reactivated)->create();

    expect(fn () => app(DeleteMeterAction::class)->handle($reactivated))
        ->toThrow(ValidationException::class);

    expect(Meter::query()->whereKey($reactivated->id)->exists())->toBeTrue();

    $deletableMeter = Meter::factory()->for($organization)->for($property)->create();

    app(DeleteMeterAction::class)->handle($deletableMeter);

    expect(Meter::query()->whereKey($deletableMeter->id)->exists())->toBeFalse();
});

it('reactivates actionable meter statuses but leaves retired meters unchanged', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $faultyMeter = Meter::factory()->for($organization)->for($property)->create([
        'status' => MeterStatus::FAULTY,
    ]);

    $retiredMeter = Meter::factory()->for($organization)->for($property)->create([
        'status' => MeterStatus::RETIRED,
    ]);

    $reactivated = app(ToggleMeterStatusAction::class)->handle($faultyMeter);
    $unchanged = app(ToggleMeterStatusAction::class)->handle($retiredMeter);

    expect($reactivated->status)->toBe(MeterStatus::ACTIVE)
        ->and($unchanged->status)->toBe(MeterStatus::RETIRED);
});
