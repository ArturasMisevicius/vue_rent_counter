<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('allows a tenant to submit a reading for an assigned meter', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '245.125')
        ->set('readingDate', now()->toDateString())
        ->set('notes', 'Submitted from the tenant portal.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSeeText('Reading Submitted!')
        ->assertSeeText($meter->identifier)
        ->assertSeeText($meter->unit)
        ->assertSeeText('245.125');

    $reading = MeterReading::query()
        ->where('meter_id', $meter->id)
        ->where('submitted_by_user_id', $tenant->user->id)
        ->latest('id')
        ->first();

    expect($reading)
        ->not->toBeNull()
        ->submission_method->toBe(MeterReadingSubmissionMethod::TENANT_PORTAL)
        ->reading_value->toBe('245.125');
});

it('shows a live consumption preview for the selected meter', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '150.750')
        ->assertSeeText('Consumption Preview')
        ->assertSeeText('5.250');
});

it('shows a live warning when the entered reading is lower than the previous value', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '120.000')
        ->assertSeeText('will be rejected on submission');
});

it('shows a validation error when the submitted reading decreases', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '120.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readingValue']);
});

it('rejects future-dated tenant readings through the shared validation rules', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '245.125')
        ->set('readingDate', now()->addDay()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readingDate']);
});

it('preselects and locks the meter picker for single-meter tenant accounts', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->assertSet('meterId', (string) $meter->id)
        ->assertSeeText($meter->name)
        ->assertSeeHtml('disabled');
});

it('does not expose outside meters and rejects submissions for them', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $outsideMeter = Meter::factory()->create([
        'name' => 'Outside Meter',
    ]);

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->assertDontSeeText('Outside Meter')
        ->set('meterId', (string) $outsideMeter->id)
        ->set('readingValue', '10.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['meterId']);

    expect(
        MeterReading::query()
            ->where('meter_id', $outsideMeter->id)
            ->where('submitted_by_user_id', $tenant->user->id)
            ->exists()
    )->toBeFalse();
});

it('refreshes translated submit reading copy when the shell locale changes', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $component = Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->assertSeeText(__('tenant.pages.readings.title', [], 'en'));

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($tenant->user->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('tenant.pages.readings.title', [], 'lt'))
        ->assertSeeText(__('tenant.pages.readings.preview_heading', [], 'lt'));
});
