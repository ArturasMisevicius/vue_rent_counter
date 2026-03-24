<?php

declare(strict_types=1);

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Filament\Actions\Admin\MeterReadings\ImportMeterReadingsAction;
use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('applies the same anomaly outcome to tenant submissions as the shared admin create action', function (): void {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(2)
        ->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $fixture->organization->id,
    ]);

    /** @var Meter $tenantMeter */
    $tenantMeter = $fixture->meters->first();
    /** @var Meter $adminMeter */
    $adminMeter = $fixture->meters->last();

    $baseDate = CarbonImmutable::parse('2025-10-01');

    foreach ([$tenantMeter, $adminMeter] as $meter) {
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
    }

    $readingDate = $baseDate->addDays(120)->toDateString();

    $adminReading = app(CreateMeterReadingAction::class)->handle(
        meter: $adminMeter,
        readingValue: 750,
        readingDate: $readingDate,
        submittedBy: $admin,
        submissionMethod: MeterReadingSubmissionMethod::ADMIN_MANUAL,
        notes: 'Admin reviewed the spike.',
    );

    Livewire::actingAs($fixture->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $tenantMeter->id)
        ->set('readingValue', '750')
        ->set('readingDate', $readingDate)
        ->set('notes', 'Tenant submitted the same spike.')
        ->call('submit')
        ->assertHasNoErrors();

    $tenantReading = MeterReading::query()
        ->where('meter_id', $tenantMeter->id)
        ->where('submitted_by_user_id', $fixture->user->id)
        ->latest('id')
        ->firstOrFail();

    expect($tenantReading->submission_method)->toBe(MeterReadingSubmissionMethod::TENANT_PORTAL)
        ->and($tenantReading->validation_status)->toBe($adminReading->validation_status)
        ->and($tenantReading->validation_status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($tenantReading->notes)->toContain('anomalous')
        ->and($tenantReading->notes)->toContain('60-day gap')
        ->and($adminReading->notes)->toContain('anomalous')
        ->and($adminReading->notes)->toContain('60-day gap');
});

it('applies the same blocking validation to zero-value readings across tenant and operator write paths', function (): void {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $fixture->organization->id,
    ]);

    /** @var Meter $meter */
    $meter = $fixture->meters->firstOrFail();
    $readingDate = now()->toDateString();

    expect(fn (): MeterReading => app(CreateMeterReadingAction::class)->handle(
        meter: $meter,
        readingValue: 0,
        readingDate: $readingDate,
        submittedBy: $admin,
        submissionMethod: MeterReadingSubmissionMethod::ADMIN_MANUAL,
        notes: 'Operator attempted a zero-value reading.',
    ))->toThrow(ValidationException::class);

    $importPreview = app(ImportMeterReadingsAction::class)->handle($meter, [[
        'reading_value' => 0,
        'reading_date' => $readingDate,
        'submission_method' => MeterReadingSubmissionMethod::IMPORT->value,
    ]]);

    expect($importPreview['valid'])->toBeEmpty()
        ->and($importPreview['invalid'])->toHaveCount(1)
        ->and($importPreview['invalid'][0]['errors'])->toHaveKey('reading_value');

    expect(fn (): MeterReading => app(SubmitTenantReadingAction::class)->handle(
        tenant: $fixture->user,
        meterId: $meter->id,
        readingValue: 0,
        readingDate: $readingDate,
        notes: 'Tenant attempted a zero-value reading.',
    ))->toThrow(ValidationException::class);

    Livewire::actingAs($fixture->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '0')
        ->set('readingDate', $readingDate)
        ->call('submit')
        ->assertHasErrors(['readingValue']);
});
