<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\MeterReadings\ImportMeterReadingsAction;
use App\Filament\Actions\Admin\MeterReadings\UpdateMeterReadingAction;
use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('blocks a reading lower than the previous reading', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $date = CarbonImmutable::parse('2026-01-01');

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 1234,
        'reading_date' => $date->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
        'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ]);

    $result = app(ValidateReadingValue::class)->validate(
        $meter,
        1200,
        $date->addDays(30)->toDateString(),
    );

    expect($result->isBlocking())->toBeTrue()
        ->and($result->message)->toContain('higher than the previous reading');
});

it('rejects readings dated in the future', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    $result = app(ValidateReadingValue::class)->validate(
        $meter,
        245.2,
        now()->addDay()->toDateString(),
    );

    expect($result->isBlocking())->toBeTrue()
        ->and($result->message)->toContain('before or equal');
});

it('flags a reading when consumption exceeds three times the average monthly usage', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $baseDate = CarbonImmutable::parse('2025-10-01');

    foreach ([[100, 0], [150, 30], [200, 60]] as [$value, $offset]) {
        MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
            'reading_value' => $value,
            'reading_date' => $baseDate->addDays($offset)->toDateString(),
            'validation_status' => MeterReadingValidationStatus::VALID,
            'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
        ]);
    }

    $result = app(ValidateReadingValue::class)->validate(
        $meter,
        400,
        $baseDate->addDays(90)->toDateString(),
    );

    expect($result->isBlocking())->toBeFalse()
        ->and($result->isAnomalous())->toBeTrue()
        ->and($result->status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($result->consumptionDelta)->toBe(200.0)
        ->and($result->averageMonthlyUsage)->toBe(50.0)
        ->and($result->notesAsText())->toContain('anomalous');
});

it('adds a gap note when more than sixty days have passed since the previous reading', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $baseDate = CarbonImmutable::parse('2025-12-01');

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 100,
        'reading_date' => $baseDate->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
        'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
    ]);

    $result = app(ValidateReadingValue::class)->validate(
        $meter,
        130,
        $baseDate->addDays(70)->toDateString(),
    );

    expect($result->isBlocking())->toBeFalse()
        ->and($result->hasGapNote())->toBeTrue()
        ->and($result->status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($result->notesAsText())->toContain('60-day gap');
});

it('applies the same flagged outcome to admin updates, tenant submissions, and bulk imports', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $fixture->organization->id,
    ]);

    /** @var Meter $meter */
    $meter = $fixture->meters->firstOrFail();
    $baseDate = CarbonImmutable::parse('2025-10-01');

    foreach ([[100, 0], [150, 30], [200, 60]] as [$value, $offset]) {
        MeterReading::factory()
            ->for($fixture->organization)
            ->for($fixture->property)
            ->for($meter)
            ->create([
                'submitted_by_user_id' => $fixture->user->id,
                'reading_value' => $value,
                'reading_date' => $baseDate->addDays($offset)->toDateString(),
                'validation_status' => MeterReadingValidationStatus::VALID,
                'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
            ]);
    }

    $draftReading = MeterReading::factory()
        ->for($fixture->organization)
        ->for($fixture->property)
        ->for($meter)
        ->create([
            'submitted_by_user_id' => $admin->id,
            'reading_value' => 210,
            'reading_date' => $baseDate->addDays(90)->toDateString(),
            'validation_status' => MeterReadingValidationStatus::VALID,
            'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
        ]);

    $this->actingAs($admin);

    $updatedReading = app(UpdateMeterReadingAction::class)->handle($draftReading, [
        'reading_value' => 400,
        'reading_date' => $baseDate->addDays(90)->toDateString(),
        'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
        'notes' => 'Admin reviewed the spike.',
    ]);

    $preview = app(ImportMeterReadingsAction::class)->handle($meter, [[
        'reading_value' => 750,
        'reading_date' => $baseDate->addDays(120)->toDateString(),
        'submission_method' => MeterReadingSubmissionMethod::IMPORT->value,
    ]]);

    $tenantReading = app(SubmitTenantReadingAction::class)->handle(
        $fixture->user,
        $meter->id,
        750,
        $baseDate->addDays(120)->toDateString(),
        'Tenant submitted the same spike.',
    );

    expect($updatedReading->validation_status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($updatedReading->notes)->toContain('anomalous')
        ->and($tenantReading->validation_status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($tenantReading->notes)->toContain('anomalous')
        ->and($preview['valid'])->toHaveCount(1)
        ->and($preview['valid'][0]['status'])->toBe(MeterReadingValidationStatus::FLAGGED->value)
        ->and($preview['valid'][0]['notes'])->toContain('anomalous');
});
