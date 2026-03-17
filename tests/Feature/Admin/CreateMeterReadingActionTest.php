<?php

use App\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a shared meter reading through the validation service', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $meter = Meter::factory()->for($organization)->for($property)->create();

    $reading = app(CreateMeterReadingAction::class)->handle(
        meter: $meter,
        readingValue: '145.500',
        readingDate: now()->toDateString(),
        submittedBy: $tenant,
        submissionMethod: MeterReadingSubmissionMethod::TENANT_PORTAL,
    );

    expect($reading)->toBeInstanceOf(MeterReading::class)
        ->and($reading->organization_id)->toBe($organization->id)
        ->and($reading->property_id)->toBe($property->id)
        ->and($reading->submitted_by_user_id)->toBe($tenant->id)
        ->and($reading->submission_method)->toBe(MeterReadingSubmissionMethod::TENANT_PORTAL)
        ->and($reading->validation_status)->toBe(MeterReadingValidationStatus::VALID);
});

it('rejects future-dated and decreasing shared meter readings', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $meter = Meter::factory()->for($organization)->for($property)->create();

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 300,
        'reading_date' => now()->subDay()->toDateString(),
        'submitted_by_user_id' => $admin->id,
        'validation_status' => MeterReadingValidationStatus::VALID,
        'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ]);

    expect(fn () => app(CreateMeterReadingAction::class)->handle(
        meter: $meter,
        readingValue: '299.000',
        readingDate: now()->toDateString(),
        submittedBy: $admin,
        submissionMethod: MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ))->toThrow(ValidationException::class);

    expect(fn () => app(CreateMeterReadingAction::class)->handle(
        meter: $meter,
        readingValue: '350.000',
        readingDate: now()->addDay()->toDateString(),
        submittedBy: $admin,
        submissionMethod: MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ))->toThrow(ValidationException::class);
});
