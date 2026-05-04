<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterType;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Meter;
use App\Models\MeterReading;
use Carbon\CarbonImmutable;
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

it('allows a tenant to submit readings for multiple meters from one form', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(2)
        ->create();

    /** @var Meter $firstMeter */
    $firstMeter = $tenant->meters->values()->get(0);
    /** @var Meter $secondMeter */
    $secondMeter = $tenant->meters->values()->get(1);

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set("readings.{$firstMeter->id}.value", '245.125')
        ->set("readings.{$firstMeter->id}.notes", 'Kitchen meter.')
        ->set("readings.{$secondMeter->id}.value", '310.500')
        ->set("readings.{$secondMeter->id}.notes", 'Bathroom meter.')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSeeText('2 Readings Submitted!')
        ->assertSeeText($firstMeter->identifier)
        ->assertSeeText($secondMeter->identifier)
        ->assertSeeText('245.125')
        ->assertSeeText('310.500');

    $readings = MeterReading::query()
        ->whereIn('meter_id', [$firstMeter->id, $secondMeter->id])
        ->where('submitted_by_user_id', $tenant->user->id)
        ->get();

    expect($readings)->toHaveCount(2);

    $readings->each(function (MeterReading $reading): void {
        expect($reading->submission_method)->toBe(MeterReadingSubmissionMethod::TENANT_PORTAL);
    });
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

it('shows full lithuanian month names in previous reading dates', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    MeterReading::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($meter)
        ->create([
            'submitted_by_user_id' => $tenant->user->id,
            'reading_value' => 145.500,
            'reading_date' => CarbonImmutable::parse('2026-03-02')->toDateString(),
        ]);

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    Livewire::actingAs($tenant->user->fresh())
        ->test(SubmitReadingPage::class)
        ->assertSeeText('2026 m. kovo 2 d.')
        ->assertDontSeeText('kov 2, 2026');
});

it('localizes seeded operations demo meter names on the tenant reading page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $meter->forceFill([
        'name' => 'Operations Demo Meter',
        'type' => MeterType::ELECTRICITY,
    ])->save();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    Livewire::actingAs($tenant->user->fresh())
        ->test(SubmitReadingPage::class)
        ->assertSeeText('Operacijų demonstracinis skaitiklis: Elektra')
        ->assertDontSeeText('Operations Demo Meter')
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSeeText('Operacijų demonstracinis skaitiklis: Elektra')
        ->assertDontSeeText('Operations Demo Meter');
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
        ->assertSee('data-tenant-reading-batch-form', false)
        ->assertSee('data-tenant-reading-row', false)
        ->assertDontSee('id="meterId"', false);
});

it('shows the tenant phone on the submit reading page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $tenant->user->forceFill([
        'phone' => '+37066667777',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText('+37066667777');
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
