<?php

use App\Filament\Support\Tenant\Portal\TenantPropertyPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('returns the assigned tenant name, email, and phone for the tenant information output', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $fixture->user->forceFill([
        'phone' => '+37065556666',
    ])->save();

    $summary = app(TenantPropertyPresenter::class)->for($fixture->user->fresh());

    expect($summary['tenant_name'])->toBe($fixture->user->name)
        ->and($summary['tenant_email'])->toBe($fixture->user->email)
        ->and($summary['tenant_phone'])->toBe('+37065556666');
});
