<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Filament\Actions\Admin\MeterReadings\ImportMeterReadingsAction;
use App\Filament\Actions\Admin\MeterReadings\RejectMeterReadingAction;
use App\Filament\Actions\Admin\MeterReadings\UpdateMeterReadingAction;
use App\Filament\Actions\Admin\MeterReadings\ValidateMeterReadingAction;
use App\Filament\Resources\MeterReadings\Pages\ListMeterReadings;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows organization-scoped meter reading resource pages with validation badges', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);
    $meter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Main Water Meter',
    ]);

    $reading = MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 125.500,
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherMeter = Meter::factory()->for($otherOrganization)->for($otherProperty)->create();
    $otherReading = MeterReading::factory()->for($otherOrganization)->for($otherProperty)->for($otherMeter)->create([
        'reading_value' => 999.999,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meter-readings.index'))
        ->assertSuccessful()
        ->assertSeeText('Meter Readings')
        ->assertSeeText($meter->name)
        ->assertSeeText('Valid')
        ->assertDontSeeText((string) $otherReading->reading_value);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meter-readings.create'))
        ->assertSuccessful()
        ->assertSeeText('Meter')
        ->assertSeeText('Reading Value')
        ->assertSeeText('Reading Date')
        ->assertSeeText('Submission Method');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meter-readings.view', $reading))
        ->assertSuccessful()
        ->assertSeeText('Reading Details')
        ->assertSeeText($meter->name)
        ->assertSeeText('125.5');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meter-readings.index'))
        ->assertSuccessful()
        ->assertSeeText($meter->name);
});

it('creates and updates readings with validation rules, anomaly flags, and gap notes', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 50,
        'reading_date' => now()->subDays(100)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 50,
        'reading_date' => now()->subDays(100)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 100,
        'reading_date' => now()->subDays(70)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    expect(fn () => app(CreateMeterReadingAction::class)->handle(
        $meter,
        120,
        now()->addDay()->toDateString(),
        null,
        MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ))->toThrow(ValidationException::class);

    expect(fn () => app(CreateMeterReadingAction::class)->handle(
        $meter,
        90,
        now()->toDateString(),
        null,
        MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ))->toThrow(ValidationException::class);

    $flagged = app(CreateMeterReadingAction::class)->handle(
        $meter,
        350,
        now()->toDateString(),
        null,
        MeterReadingSubmissionMethod::ADMIN_MANUAL,
    );

    expect($flagged->validation_status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($flagged->notes)->toContain('anomalous spike')
        ->and($flagged->notes)->toContain('60-day gap');

    $updated = app(UpdateMeterReadingAction::class)->handle($flagged, [
        'reading_value' => 110,
        'reading_date' => now()->subDays(69)->toDateString(),
        'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
        'notes' => null,
    ]);

    expect($updated->validation_status)->toBe(MeterReadingValidationStatus::VALID)
        ->and($updated->notes)->toBeNull();
});

it('revalidates pending rows and returns an import preview with invalid rows', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 100,
        'reading_date' => now()->subDays(10)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $pending = MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 120,
        'reading_date' => now()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::PENDING,
    ]);

    $validated = app(ValidateMeterReadingAction::class)->handle($pending);

    expect($validated->validation_status)->toBe(MeterReadingValidationStatus::VALID);

    $rejected = app(RejectMeterReadingAction::class)->handle($pending->fresh(), [
        'reason' => 'Manual review rejected this reading.',
    ]);

    expect($rejected->validation_status)->toBe(MeterReadingValidationStatus::REJECTED)
        ->and($rejected->notes)->toContain('Manual review rejected this reading.')
        ->and(AuditLog::query()
            ->where('subject_type', MeterReading::class)
            ->where('subject_id', $rejected->id)
            ->exists())->toBeTrue();

    $preview = app(ImportMeterReadingsAction::class)->handle($meter, [
        [
            'reading_value' => 125,
            'reading_date' => now()->toDateString(),
            'submission_method' => MeterReadingSubmissionMethod::IMPORT->value,
        ],
        [
            'reading_value' => 95,
            'reading_date' => now()->toDateString(),
            'submission_method' => MeterReadingSubmissionMethod::IMPORT->value,
        ],
    ]);

    expect($preview['valid'])->toHaveCount(1)
        ->and($preview['invalid'])->toHaveCount(1)
        ->and($preview['valid'][0]['status'])->toBe(MeterReadingValidationStatus::VALID->value)
        ->and($preview['invalid'][0]['errors'])->toHaveKey('reading_value');
});

it('shows organization context on the meter readings list for superadmins while keeping admins scoped', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create();
    $buildingB = Building::factory()->for($organizationB)->create();

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
    ]);

    $meterA = Meter::factory()->for($organizationA)->for($propertyA)->create([
        'name' => 'North Meter',
    ]);
    $meterB = Meter::factory()->for($organizationB)->for($propertyB)->create([
        'name' => 'Aurora Meter',
    ]);

    $readingA = MeterReading::factory()->for($organizationA)->for($propertyA)->for($meterA)->create([
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    $readingB = MeterReading::factory()->for($organizationB)->for($propertyB)->for($meterB)->create([
        'validation_status' => MeterReadingValidationStatus::FLAGGED,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meter-readings.index'))
        ->assertSuccessful()
        ->assertSeeText('Meter Readings')
        ->assertSeeText($meterA->name)
        ->assertDontSeeText($meterB->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.meter-readings.index'))
        ->assertSuccessful()
        ->assertSeeText('Meter Readings')
        ->assertSeeText($meterA->name)
        ->assertSeeText($meterB->name)
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListMeterReadings::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $readingA)
        ->assertTableColumnStateSet('organization.name', $organizationB->name, $readingB)
        ->assertTableColumnStateSet('validation_status', MeterReadingValidationStatus::VALID, $readingA)
        ->assertCanSeeTableRecords([$readingA, $readingB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$readingA])
        ->assertCanNotSeeTableRecords([$readingB]);
});
